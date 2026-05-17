<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\VoidRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\DatabaseMessage;
use Illuminate\Notifications\Notification;

class VoidRequestResolvedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly int $voidRequestId) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): DatabaseMessage
    {
        $voidRequest = VoidRequest::with('transaction')->find($this->voidRequestId);
        $orNumber  = $voidRequest?->transaction?->or_number ?? '—';
        $status    = strtoupper($voidRequest?->status ?? 'UPDATED');
        $approved  = $status === 'APPROVED';

        return new DatabaseMessage([
            'type'        => 'void.resolved',
            'title'       => $approved ? 'Void Request Approved' : 'Void Request Rejected',
            'message'     => "Your void request for OR #{$orNumber} was {$status}.",
            'tone'        => $approved ? 'green' : 'red',
            'icon'        => $approved ? 'check-circle' : 'x-circle',
            'action_url'  => $voidRequest
                ? route('org.transactions.show', $voidRequest->transaction_id)
                : url('/'),
            'entity_type' => 'VOID_REQUEST',
            'entity_id'   => $this->voidRequestId,
        ]);
    }
}
