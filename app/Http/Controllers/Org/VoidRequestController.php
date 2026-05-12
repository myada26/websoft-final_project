<?php

namespace App\Http\Controllers\Org;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Transaction;
use App\Models\VoidRequest;
use App\Services\FineService;
use Illuminate\Http\Request;

class VoidRequestController extends Controller
{
    public function index()
    {
        $voidRequests = VoidRequest::whereHas('transaction', fn ($q) => $q->where('organization_id', auth()->user()->organization_id))
            ->with(['transaction.student', 'requestedBy', 'approvedBy'])
            ->when(request('status'), fn ($q, $status) => $q->where('status', $status))
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('org.void-requests.index', compact('voidRequests'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'transaction_id' => 'nullable|exists:transactions,id',
            'or_number' => 'nullable|string|max:100',
            'reason' => 'required|string|max:2000',
        ]);

        $transaction = isset($data['transaction_id'])
            ? Transaction::findOrFail($data['transaction_id'])
            : Transaction::where('or_number', $data['or_number'] ?? null)->firstOrFail();

        if ($transaction->organization_id !== auth()->user()->organization_id || $transaction->is_void) {
            abort(403);
        }

        $voidRequest = VoidRequest::firstOrCreate(
            ['transaction_id' => $transaction->id],
            [
                'requested_by_user_id' => auth()->user()->id,
                'reason' => $data['reason'],
                'status' => 'PENDING',
                'created_at' => now(),
            ]
        );

        if ($voidRequest->wasRecentlyCreated) {
            AuditLog::create([
                'user_id'     => auth()->user()->id,
                'action'      => 'VOID_REQUESTED',
                'entity_type' => 'VOID_REQUEST',
                'entity_id'   => $voidRequest->id,
                'details'     => [
                    'or_number' => $transaction->or_number,
                    'reason'    => $data['reason'],
                ],
                'ip_address' => $request->ip(),
                'timestamp'  => now(),
            ]);
        }

        return redirect()->route('org.void-requests.index')->with('success', 'Void request submitted for chairperson review.');
    }

    public function approve(Request $request, VoidRequest $voidRequest)
    {
        $this->authorizeOrgVoid($voidRequest);

        if ($voidRequest->status !== 'PENDING') {
            return back()->with('error', 'Only pending void requests can be approved.');
        }

        // FR-0019: Chairperson cannot void their own transactions
        $voidRequest->loadMissing('transaction');
        if ($voidRequest->transaction->processed_by_user_id === auth()->user()->id) {
            return back()->with('error', 'You cannot void your own transaction.');
        }
        $voidRequest->transaction->update(['is_void' => true]);

        // Revert fine status to UNPAID when its payment transaction is voided (FR-0029)
        if ($voidRequest->transaction->isFine() && $voidRequest->transaction->studentFine) {
            app(FineService::class)->revertPaid($voidRequest->transaction);
        }

        $voidRequest->update([
            'approved_by_user_id' => auth()->user()->id,
            'status' => 'APPROVED',
            'resolved_at' => now(),
        ]);

        AuditLog::create([
            'user_id'     => auth()->user()->id,
            'action'      => 'VOID_APPROVED',
            'entity_type' => 'VOID_REQUEST',
            'entity_id'   => $voidRequest->id,
            'details'     => [
                'or_number'      => $voidRequest->transaction->or_number,
                'transaction_id' => $voidRequest->transaction_id,
            ],
            'ip_address' => $request->ip(),
            'timestamp'  => now(),
        ]);

        return back()->with('success', 'Void request approved.');
    }

    public function reject(Request $request, VoidRequest $voidRequest)
    {
        $this->authorizeOrgVoid($voidRequest);

        if ($voidRequest->status !== 'PENDING') {
            return back()->with('error', 'Only pending void requests can be rejected.');
        }

        $voidRequest->loadMissing('transaction');
        $voidRequest->update([
            'approved_by_user_id' => auth()->user()->id,
            'status' => 'REJECTED',
            'resolved_at' => now(),
        ]);

        AuditLog::create([
            'user_id'     => auth()->user()->id,
            'action'      => 'VOID_REJECTED',
            'entity_type' => 'VOID_REQUEST',
            'entity_id'   => $voidRequest->id,
            'details'     => [
                'or_number'      => $voidRequest->transaction->or_number,
                'transaction_id' => $voidRequest->transaction_id,
            ],
            'ip_address' => $request->ip(),
            'timestamp'  => now(),
        ]);

        return back()->with('success', 'Void request rejected.');
    }

    private function authorizeOrgVoid(VoidRequest $voidRequest): void
    {
        $voidRequest->loadMissing('transaction');

        if ($voidRequest->transaction->organization_id !== auth()->user()->organization_id) {
            abort(403);
        }
    }
}
