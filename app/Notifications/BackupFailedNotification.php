<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BackupFailedNotification extends Notification
{
    use Queueable;

    public function __construct(
        private string $errorMessage = ''
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->error()
            ->subject('[FCATS] Automated Backup Failed')
            ->line('The FCATS automated backup failed at ' . now()->format('Y-m-d H:i:s') . '.')
            ->when($this->errorMessage, fn($mail) => $mail->line('Error: ' . $this->errorMessage))
            ->line('Please check the backup configuration and logs immediately.')
            ->action('View Application', url('/admin/dashboard'));
    }
}
