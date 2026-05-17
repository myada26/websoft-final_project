<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\AuditLog;
use App\Models\Remittance;
use App\Models\User;
use App\Notifications\RemittanceSubmittedNotification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;

class RemittanceObserver
{
    public function created(Remittance $remittance): void
    {
        AuditLog::create([
            'user_id'     => Auth::id(),
            'action'      => 'REMITTANCE_CREATED',
            'entity_type' => 'REMITTANCE',
            'entity_id'   => $remittance->id,
            'details'     => [
                'organization_id'  => $remittance->organization_id,
                'academic_year_id' => $remittance->academic_year_id,
                'total_amount'     => (string) $remittance->total_amount,
            ],
            'ip_address' => request()?->ip(),
            'timestamp'  => now(),
        ]);

        $reviewers = User::where('organization_id', $remittance->organization_id)
            ->where('role', 'AUDITOR')
            ->where('is_active', true)
            ->get();

        if ($reviewers->isNotEmpty()) {
            Notification::send($reviewers, new RemittanceSubmittedNotification($remittance->id));
        }
    }

    public function updated(Remittance $remittance): void
    {
        if (! $remittance->wasChanged('status')) {
            return;
        }

        $action = match ($remittance->status) {
            'VERIFIED' => 'REMITTANCE_VERIFIED',
            'ACCEPTED' => 'REMITTANCE_ACCEPTED',
            default    => 'REMITTANCE_UPDATED',
        };

        AuditLog::create([
            'user_id'     => Auth::id(),
            'action'      => $action,
            'entity_type' => 'REMITTANCE',
            'entity_id'   => $remittance->id,
            'details'     => [
                'from_status' => $remittance->getOriginal('status'),
                'to_status'   => $remittance->status,
            ],
            'ip_address' => request()?->ip(),
            'timestamp'  => now(),
        ]);
    }
}
