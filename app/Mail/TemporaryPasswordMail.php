<?php

namespace App\Mail;

use App\Models\Organization;
use App\Models\Student;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TemporaryPasswordMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Student $student,
        public Organization $organization,
        public string $username,
        public string $temporaryPassword,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'FCATS Temporary Password'
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.temporary-password',
            with: [
                'student' => $this->student,
                'organization' => $this->organization,
                'username' => $this->username,
                'temporaryPassword' => $this->temporaryPassword,
            ],
        );
    }
}
