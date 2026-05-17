<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\VoidRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\DatabaseMessage;
use Illuminate\Notifications\Notification;

class VoidRequestSubmittedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly int $voidRequestId) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): DatabaseMessage
    {
        $voidRequest = VoidRequest::with(['transaction.student', 'requestedBy'])->find($this->voidRequestId);

        $orNumber = $voidRequest?->transaction?->or_number ?? '—';
        $student  = $voidRequest?->transaction?->student?->full_name ?? 'Unknown student';
        $cashier  = $voidRequest?->requestedBy?->username ?? 'Cashier';

        return new DatabaseMessage([
            'type'        => 'void.submitted',
            'title'       => 'New Void Request',
            'message'     => "{$cashier} requested a void on OR #{$orNumber} ({$student}).",
            'tone'        => 'red',
            'icon'        => 'x-circle',
            'action_url'  => $voidRequest
                ? route('org.void-requests.index')
                : url('/'),
            'entity_type' => 'VOID_REQUEST',
            'entity_id'   => $this->voidRequestId,
        ]);
    }
}
