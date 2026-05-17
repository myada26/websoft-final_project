<?php

namespace App\Observers;

use App\Models\AuditLog;
use App\Models\VoidRequest;
use Illuminate\Support\Facades\Auth;

class VoidRequestObserver
{
    public function created(VoidRequest $voidRequest): void
    {
        AuditLog::create([
            'user_id'     => Auth::id(),
            'action'      => 'VOID_REQUESTED',
            'entity_type' => 'VOID_REQUEST',
            'entity_id'   => $voidRequest->id,
            'details'     => [
                'transaction_id' => $voidRequest->transaction_id,
                'reason'         => $voidRequest->reason ?? null,
            ],
            'ip_address' => request()?->ip(),
            'timestamp'  => now(),
        ]);
    }

    public function updated(VoidRequest $voidRequest): void
    {
        if (! $voidRequest->wasChanged('status')) {
            return;
        }

        $action = match ($voidRequest->status) {
            'APPROVED' => 'VOID_APPROVED',
            'REJECTED' => 'VOID_REJECTED',
            default    => null,
        };

        if (! $action) {
            return;
        }

        AuditLog::create([
            'user_id'     => Auth::id(),
            'action'      => $action,
            'entity_type' => 'VOID_REQUEST',
            'entity_id'   => $voidRequest->id,
            'details'     => [
                'transaction_id' => $voidRequest->transaction_id,
                'decided_by'     => Auth::id(),
            ],
            'ip_address' => request()?->ip(),
            'timestamp'  => now(),
        ]);
    }
}
