<?php

namespace App\Notifications;

use App\Models\Organization;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;

class DailySummaryReportNotification extends Notification
{
    use Queueable;

    public function __construct(
        private Organization $organization,
        private Collection   $summary,
        private string       $date
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject("[FCATS] Daily Collection Summary — {$this->organization->name} — {$this->date}")
            ->greeting("Hello, {$notifiable->username}!")
            ->line("Daily collection summary for **{$this->organization->name}** on {$this->date}:");

        foreach ($this->summary as $row) {
            $mail->line(
                "• {$row->transaction_type} / {$row->payment_method}: "
                . "₱" . number_format((float) $row->total, 2)
                . " ({$row->count} transactions)"
            );
        }

        return $mail->line('This is an automated daily report from FCATS.');
    }
}
