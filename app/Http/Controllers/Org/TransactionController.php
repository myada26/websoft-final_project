<?php

namespace App\Http\Controllers\Org;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\FeeProfile;
use App\Models\Student;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TransactionController extends Controller
{
    public function index()
    {
        $transactions = Transaction::where('organization_id', auth()->user()->organization_id)
            ->with(['student', 'processedBy', 'feeProfile', 'remittance'])
            ->when(request('search'), fn ($q, $s) => $q->where(function ($query) use ($s) {
                $query->where('or_number', 'like', "%{$s}%")
                    ->orWhereHas('student', fn ($student) => $student
                        ->where('student_number', 'like', "%{$s}%")
                        ->orWhere('first_name', 'like', "%{$s}%")
                        ->orWhere('last_name', 'like', "%{$s}%"));
            }))
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('org.transactions.index', compact('transactions'));
    }

    public function create(Request $request)
    {
        $feeProfiles = FeeProfile::where('organization_id', auth()->user()->organization_id)
            ->active()
            ->orderBy('name')
            ->get();

        $searchResults = null;
        $searchQuery = $request->query('student');

        if ($searchQuery) {
            $searchResults = $this->studentSearch($searchQuery);
        }

        return view('org.transactions.create', compact('feeProfiles', 'searchResults', 'searchQuery'));
    }

    public function search(Request $request)
    {
        $request->validate(['q' => 'required|string|max:100']);

        return redirect()->route('org.transactions.create', ['student' => $request->q]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'student_id' => 'required|exists:students,id',
            'payment_method' => 'required|in:CASH,GCASH',
            'gcash_reference' => 'nullable|required_if:payment_method,GCASH|string|max:100',
            'fee_profile_ids' => 'required|array|min:1',
            'fee_profile_ids.*' => 'exists:fee_profiles,id',
        ]);

        $orgId = auth()->user()->organization_id;
        $activeSemester = AcademicYear::where('is_active', true)->firstOrFail();

        $transaction = DB::transaction(function () use ($data, $orgId, $activeSemester) {
            $sequence = \App\Models\OrSequence::lockForUpdate()->firstOrCreate(
                ['organization_id' => $orgId],
                ['last_or_number' => 0]
            );

            $feeProfile = FeeProfile::where('organization_id', $orgId)
                ->whereIn('id', $data['fee_profile_ids'])
                ->active()
                ->firstOrFail();

            $sequence->increment('last_or_number');

            return Transaction::create([
                'or_number' => sprintf('OR-%s-%05d', now()->format('Y'), $sequence->last_or_number),
                'organization_id' => $orgId,
                'academic_year_id' => $activeSemester->id,
                'student_id' => $data['student_id'],
                'processed_by_user_id' => auth()->id(),
                'amount_paid' => $feeProfile->amount,
                'payment_method' => $data['payment_method'],
                'reference_number' => $data['gcash_reference'] ?? null,
                'fee_profile_id' => $feeProfile->id,
                'transaction_type' => 'FEE',
                'is_void' => false,
            ]);
        });

        return redirect()->route('org.transactions.show', $transaction)->with('success', 'Transaction recorded.');
    }

    public function show(Transaction $transaction)
    {
        if ($transaction->organization_id !== auth()->user()->organization_id) {
            abort(403);
        }

        $transaction->load(['student', 'organization', 'academicYear', 'processedBy', 'feeProfile', 'voidRequest']);

        return view('org.transactions.show', compact('transaction'));
    }

    private function studentSearch(string $search)
    {
        return Student::where(function ($query) use ($search) {
                $query->where('student_number', 'like', "%{$search}%")
                    ->orWhere('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%");
            })
            ->with('latestEnrollment.program')
            ->limit(10)
            ->get();
    }
}
