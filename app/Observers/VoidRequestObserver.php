<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\AuditLog;
use App\Models\Transaction;
use App\Models\User;
use App\Models\VoidRequest;
use App\Notifications\VoidRequestResolvedNotification;
use App\Notifications\VoidRequestSubmittedNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;

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

        $approvers = $this->approversForOrg($voidRequest);
        if ($approvers->isNotEmpty()) {
            Notification::send($approvers, new VoidRequestSubmittedNotification($voidRequest->id));
        }
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

        $requester = User::find($voidRequest->requested_by_user_id);
        if ($requester) {
            $requester->notify(new VoidRequestResolvedNotification($voidRequest->id));
        }
    }

    private function approversForOrg(VoidRequest $voidRequest): Collection
    {
        $orgId = Transaction::whereKey($voidRequest->transaction_id)->value('organization_id');

        if (! $orgId) {
            return collect();
        }

        return User::where('organization_id', $orgId)
            ->where('role', 'CHAIRPERSON')
            ->where('is_active', true)
            ->get();
    }
}
