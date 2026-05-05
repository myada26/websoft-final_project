<?php

namespace App\Http\Controllers\Org;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Models\VoidRequest;
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

        VoidRequest::firstOrCreate(
            ['transaction_id' => $transaction->id],
            [
                'requested_by_user_id' => auth()->id(),
                'reason' => $data['reason'],
                'status' => 'PENDING',
                'created_at' => now(),
            ]
        );

        return redirect()->route('org.void-requests.index')->with('success', 'Void request submitted for chairperson review.');
    }

    public function approve(VoidRequest $voidRequest)
    {
        $this->authorizeOrgVoid($voidRequest);

        if ($voidRequest->status !== 'PENDING') {
            return back()->with('error', 'Only pending void requests can be approved.');
        }

        $voidRequest->transaction->update(['is_void' => true]);
        $voidRequest->update([
            'approved_by_user_id' => auth()->id(),
            'status' => 'APPROVED',
            'resolved_at' => now(),
        ]);

        return back()->with('success', 'Void request approved.');
    }

    public function reject(VoidRequest $voidRequest)
    {
        $this->authorizeOrgVoid($voidRequest);

        if ($voidRequest->status !== 'PENDING') {
            return back()->with('error', 'Only pending void requests can be rejected.');
        }

        $voidRequest->update([
            'approved_by_user_id' => auth()->id(),
            'status' => 'REJECTED',
            'resolved_at' => now(),
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
