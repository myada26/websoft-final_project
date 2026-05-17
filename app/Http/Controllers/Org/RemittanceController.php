<?php

namespace App\Http\Controllers\Org;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Remittance;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RemittanceController extends Controller
{
    public function index()
    {
        $orgId = auth()->user()->organization_id;
        $remittances = Remittance::where('organization_id', $orgId)
            ->with(['academicYear', 'transactions:id,remittance_id,amount_paid'])
            ->orderByDesc('created_at')
            ->paginate(15);

        // Single GROUP BY query replaces 6 separate COUNT/SUM queries (NFR-002)
        $stats = Remittance::where('organization_id', $orgId)
            ->whereIn('status', ['PENDING', 'VERIFIED', 'ACCEPTED'])
            ->select('status', DB::raw('COUNT(*) as cnt'))
            ->groupBy('status')
            ->pluck('cnt', 'status');

        $amounts = Transaction::where('transactions.organization_id', $orgId)
            ->where('transactions.is_void', false)
            ->whereNotNull('transactions.remittance_id')
            ->join('remittances', 'transactions.remittance_id', '=', 'remittances.id')
            ->whereIn('remittances.status', ['PENDING', 'VERIFIED', 'ACCEPTED'])
            ->select('remittances.status', DB::raw('SUM(transactions.amount_paid) as total'))
            ->groupBy('remittances.status')
            ->pluck('total', 'status');

        $pendingCount   = $stats->get('PENDING', 0);
        $pendingAmount  = $amounts->get('PENDING', 0);
        $verifiedCount  = $stats->get('VERIFIED', 0);
        $verifiedAmount = $amounts->get('VERIFIED', 0);
        $acceptedCount  = $stats->get('ACCEPTED', 0);
        $acceptedAmount = $amounts->get('ACCEPTED', 0);

        return view('org.remittances.index', compact(
            'remittances', 'pendingCount', 'pendingAmount',
            'verifiedCount', 'verifiedAmount', 'acceptedCount', 'acceptedAmount'
        ));
    }

    public function store(Request $request)
    {
        $orgId = auth()->user()->organization_id;
        $activeSemester = \App\Models\AcademicYear::where('is_active', true)->first();

        if (!$activeSemester) {
            return back()->with('error', 'No active academic year found.');
        }

        // Find unremitted transactions for this org
        $transactions = \App\Models\Transaction::where('organization_id', $orgId)
            ->whereNull('remittance_id')
            ->where('is_void', false)
            ->get();

        if ($transactions->isEmpty()) {
            return back()->with('error', 'No unremitted transactions found.');
        }

        $remittance = \App\Models\Remittance::create([
            'control_number'    => sprintf('REM-%s-%05d', now()->format('Y'), \App\Models\Remittance::where('organization_id', $orgId)->count() + 1),
            'organization_id'  => $orgId,
            'academic_year_id' => $activeSemester->id,
            'total_amount'      => $transactions->sum('amount_paid'),
            'created_by_user_id' => auth()->user()->id,
            'status'           => 'PENDING',
        ]);

        foreach ($transactions as $tx) {
            $tx->update(['remittance_id' => $remittance->id]);
        }

        AuditLog::create([
            'user_id'     => auth()->user()->id,
            'action'      => 'REMITTANCE_CREATED',
            'entity_type' => 'REMITTANCE',
            'entity_id'   => $remittance->id,
            'details'     => [
                'control_number'  => $remittance->control_number,
                'total_amount'    => $remittance->total_amount,
                'tx_count'        => $transactions->count(),
            ],
            'ip_address' => $request->ip(),
            'timestamp'  => now(),
        ]);

        return redirect()->route('org.remittances.index')->with('success', 'Remittance batch created.');
    }

    public function show(\App\Models\Remittance $remittance)
    {
        if ($remittance->organization_id !== auth()->user()->organization_id) {
            abort(403);
        }

        $remittance->load(['academicYear', 'transactions.student']);
        return view('org.remittances.show', compact('remittance'));
    }

    public function verify(Request $request, \App\Models\Remittance $remittance)
    {
        if ($remittance->organization_id !== auth()->user()->organization_id) {
            abort(403);
        }

        if ($remittance->status !== 'PENDING') {
            return back()->with('error', 'Only pending remittances can be verified.');
        }

        $remittance->update([
            'status' => 'VERIFIED',
            'verified_by_user_id' => auth()->user()->id,
            'verified_at' => now(),
        ]);

        AuditLog::create([
            'user_id'     => auth()->user()->id,
            'action'      => 'REMITTANCE_VERIFIED',
            'entity_type' => 'REMITTANCE',
            'entity_id'   => $remittance->id,
            'details'     => ['control_number' => $remittance->control_number],
            'ip_address'  => $request->ip(),
            'timestamp'   => now(),
        ]);

        return back()->with('success', 'Remittance batch verified.');
    }

    public function accept(Request $request, \App\Models\Remittance $remittance)
    {
        if ($remittance->organization_id !== auth()->user()->organization_id) {
            abort(403);
        }

        if ($remittance->status !== 'VERIFIED') {
            return back()->with('error', 'Only verified remittances can be accepted.');
        }

        $remittance->update([
            'status' => 'ACCEPTED',
            'accepted_by_user_id' => auth()->user()->id,
            'accepted_at' => now(),
        ]);

        AuditLog::create([
            'user_id'     => auth()->user()->id,
            'action'      => 'REMITTANCE_ACCEPTED',
            'entity_type' => 'REMITTANCE',
            'entity_id'   => $remittance->id,
            'details'     => ['control_number' => $remittance->control_number],
            'ip_address'  => $request->ip(),
            'timestamp'   => now(),
        ]);

        return back()->with('success', 'Remittance batch accepted.');
    }
}
