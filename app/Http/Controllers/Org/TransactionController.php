<?php

namespace App\Http\Controllers\Org;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\AuditLog;
use App\Models\FeeProfile;
use App\Models\Student;
use App\Models\StudentFine;
use App\Models\Transaction;
use App\Models\User;
use App\Services\FineService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TransactionController extends Controller
{
    public function index(Request $request)
    {
        $orgId = auth()->user()->organization_id;

        $query = Transaction::where('organization_id', $orgId)
            ->with(['student', 'processedBy', 'feeProfile', 'remittance']);

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('or_number', 'like', "%{$s}%")
                    ->orWhereHas('student', fn ($st) => $st
                        ->where('student_number', 'like', "%{$s}%")
                        ->orWhere('first_name', 'like', "%{$s}%")
                        ->orWhere('last_name', 'like', "%{$s}%"));
            });
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        if ($request->filled('academic_year_id')) {
            $query->where('academic_year_id', $request->academic_year_id);
        }

        if ($request->filled('type')) {
            $query->where('transaction_type', $request->type);
        }

        if ($request->filled('payment_method')) {
            $query->where('payment_method', $request->payment_method);
        }

        if ($request->filled('officer_id')) {
            $query->where('processed_by_user_id', $request->officer_id);
        }

        $transactions   = $query->orderByDesc('created_at')->paginate(20);
        $academicYears  = AcademicYear::orderByDesc('id')->get();
        $officers       = User::where('organization_id', $orgId)->orderBy('username')->get();

        return view('org.transactions.index', compact('transactions', 'academicYears', 'officers'));
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

        // Load outstanding fines for the found student (for FINE payment flow)
        $unpaidFines = collect();
        if ($searchResults && $searchResults->count() === 1) {
            $unpaidFines = StudentFine::where('student_id', $searchResults->first()->id)
                ->where('organization_id', auth()->user()->organization_id)
                ->where('status', 'UNPAID')
                ->with('event:id,name,date')
                ->get();
        }

        // Check fine collection window status
        $fineWindowService = app(\App\Services\FineCollectionWindowService::class);
        $fineWindow = $fineWindowService->getWindow(auth()->user()->organization_id);
        $fineWindowOpen = $fineWindowService->canCollectFine(auth()->user()->organization_id);

        return view('org.transactions.create', compact('feeProfiles', 'searchResults', 'searchQuery', 'unpaidFines', 'fineWindowOpen', 'fineWindow'));
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
            'fee_profile_ids' => 'required|array|size:1',
            'fee_profile_ids.*' => 'exists:fee_profiles,id',
        ]);

        $orgId = auth()->user()->organization_id;
        $activeSemester = AcademicYear::where('is_active', true)->firstOrFail();
        $student = Student::findOrFail($data['student_id']);

        abort_unless(
            $student->isMemberOf($orgId, $activeSemester->id),
            403,
            'Student is not enrolled within this organization scope.'
        );

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
                'processed_by_user_id' => auth()->user()->id,
                'amount_paid' => $feeProfile->amount,
                'payment_method' => $data['payment_method'],
                'reference_number' => $data['gcash_reference'] ?? null,
                'fee_profile_id' => $feeProfile->id,
                'transaction_type' => 'FEE',
                'is_void' => false,
            ]);
        });

        $transaction->loadMissing('student');

        AuditLog::create([
            'user_id'     => auth()->user()->id,
            'action'      => 'TRANSACTION_CREATED',
            'entity_type' => 'TRANSACTION',
            'entity_id'   => $transaction->id,
            'details'     => [
                'or_number'      => $transaction->or_number,
                'student_number' => $transaction->student->student_number,
                'amount_paid'    => $transaction->amount_paid,
                'payment_method' => $transaction->payment_method,
            ],
            'ip_address' => $request->ip(),
            'timestamp'  => now(),
        ]);

        // Send email receipt (FR-0031)
        app(\App\Services\ReceiptEmailService::class)->send($transaction);

        return redirect()->route('org.transactions.show', $transaction)->with('success', 'Transaction recorded.');
    }

    public function storeFine(Request $request)
    {
        $data = $request->validate([
            'student_id'      => 'required|exists:students,id',
            'payment_method'  => 'required|in:CASH,GCASH',
            'gcash_reference' => 'nullable|required_if:payment_method,GCASH|string|max:100',
            'amount_paid'     => 'required|numeric|min:0.01',
            'student_fine_id' => 'nullable|exists:student_fines,id',
        ]);

        $orgId          = auth()->user()->organization_id;
        $activeSemester = AcademicYear::where('is_active', true)->firstOrFail();

        // Check if fine collection window is open
        $fineWindowService = app(\App\Services\FineCollectionWindowService::class);
        if (!$fineWindowService->canCollectFine($orgId)) {
            return back()->with('error', 'Fine collection is currently closed. Please contact the Treasurer to open the fine collection window.');
        }

        // Verify full amount only - no partial payments for fines
        if (!empty($data['student_fine_id'])) {
            $studentFine = \App\Models\StudentFine::find($data['student_fine_id']);
            if ($studentFine && $data['amount_paid'] < $studentFine->fine_amount) {
                return back()->with('error', 'Partial payments are not allowed. Pay the full fine amount of ₱' . number_format($studentFine->fine_amount, 2));
            }
        }

        $unpaidFineQuery = StudentFine::where('student_id', $data['student_id'])
            ->where('organization_id', $orgId)
            ->where('academic_year_id', $activeSemester->id)
            ->where('status', 'UNPAID');

        if (!empty($data['student_fine_id']) && !(clone $unpaidFineQuery)->where('id', $data['student_fine_id'])->exists()) {
            return back()->with('error', 'Selected fine is no longer payable.');
        }

        $outstandingFineIds = (clone $unpaidFineQuery)->pluck('id');
        $outstandingTotal = (float) (clone $unpaidFineQuery)->sum('fine_amount');

        if ($outstandingFineIds->isEmpty()) {
            return back()->with('error', 'This student has no outstanding fines for the active semester.');
        }

        if (round((float) $data['amount_paid'], 2) !== round($outstandingTotal, 2)) {
            return back()->with('error', 'Partial fine payments are not allowed. Collect the full outstanding balance of PHP ' . number_format($outstandingTotal, 2) . '.');
        }

        $transaction = DB::transaction(function () use ($data, $orgId, $activeSemester, $outstandingFineIds) {
            $sequence = \App\Models\OrSequence::lockForUpdate()->firstOrCreate(
                ['organization_id' => $orgId],
                ['last_or_number' => 0]
            );
            $sequence->increment('last_or_number');

            return Transaction::create([
                'or_number'            => sprintf('OR-%s-%05d', now()->format('Y'), $sequence->last_or_number),
                'organization_id'      => $orgId,
                'academic_year_id'     => $activeSemester->id,
                'student_id'           => $data['student_id'],
                'processed_by_user_id' => auth()->user()->id,
                'amount_paid'          => $data['amount_paid'],
                'payment_method'       => $data['payment_method'],
                'reference_number'     => $data['gcash_reference'] ?? null,
                'fee_profile_id'       => null,
                'transaction_type'     => 'FINE',
                'student_fine_id'      => $data['student_fine_id'] ?? null,
                'is_void'              => false,
            ]);
        });

        StudentFine::whereIn('id', $outstandingFineIds)->update([
            'status' => 'PAID',
            'transaction_id' => $transaction->id,
            'updated_at' => now(),
        ]);

        // Sync fine status if a specific fine was linked
        if ($data['student_fine_id'] ?? null) {
            app(FineService::class)->markPaid($transaction);
        }

        $transaction->loadMissing('student');

        AuditLog::create([
            'user_id'     => auth()->user()->id,
            'action'      => 'TRANSACTION_CREATED',
            'entity_type' => 'TRANSACTION',
            'entity_id'   => $transaction->id,
            'details'     => [
                'or_number'        => $transaction->or_number,
                'student_number'   => $transaction->student->student_number,
                'amount_paid'      => $transaction->amount_paid,
                'payment_method'   => $transaction->payment_method,
                'transaction_type' => 'FINE',
                'student_fine_id'  => $data['student_fine_id'] ?? null,
            ],
            'ip_address' => $request->ip(),
            'timestamp'  => now(),
        ]);

        // Send email receipt (FR-0031)
        app(\App\Services\ReceiptEmailService::class)->send($transaction);

        return redirect()->route('org.transactions.show', $transaction)->with('success', 'Fine payment recorded.');
    }

    public function show(Transaction $transaction)
    {
        if ($transaction->organization_id !== auth()->user()->organization_id) {
            abort(403);
        }

        $transaction->load(['student', 'organization', 'academicYear', 'processedBy', 'feeProfile', 'voidRequest', 'studentFine.event']);

        return view('org.transactions.show', compact('transaction'));
    }

    public function receipt(Transaction $transaction)
    {
        if ($transaction->organization_id !== auth()->user()->organization_id) {
            abort(403);
        }

        $transaction->load(['student', 'feeProfile', 'processedBy', 'organization', 'academicYear', 'voidRequest']);

        $pdf = Pdf::loadView('pdf.receipt', compact('transaction'))
            ->setPaper([0, 0, 396, 612]); // half-letter portrait

        return $pdf->stream('OR-' . $transaction->or_number . '.pdf');
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
