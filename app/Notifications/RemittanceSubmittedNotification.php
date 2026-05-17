<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Remittance;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\DatabaseMessage;
use Illuminate\Notifications\Notification;

class RemittanceSubmittedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly int $remittanceId) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): DatabaseMessage
    {
        $remittance = Remittance::with(['createdBy', 'organization'])->find($this->remittanceId);

        $control = $remittance?->control_number ?? '—';
        $amount  = number_format((float) ($remittance?->total_amount ?? 0), 2);
        $by      = $remittance?->createdBy?->username ?? 'Treasurer';
        $org     = $remittance?->organization?->name ?? 'an organization';

        return new DatabaseMessage([
            'type'        => 'remittance.submitted',
            'title'       => 'New Remittance Submitted',
            'message'     => "{$by} submitted remittance {$control} for {$org} (₱{$amount}).",
            'tone'        => 'green',
            'icon'        => 'file-text',
            'action_url'  => $remittance
                ? route('org.remittances.show', $remittance->id)
                : url('/'),
            'entity_type' => 'REMITTANCE',
            'entity_id'   => $this->remittanceId,
        ]);
    }
}
