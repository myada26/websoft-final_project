<?php

namespace App\Mail;

use App\Models\Transaction;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Barryvdh\DomPDF\Facade\Pdf;

class TransactionReceipt extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Transaction $transaction)
    {
        $this->transaction->loadMissing([
            'student',
            'organization',
            'academicYear',
            'processedBy',
            'feeProfile',
        ]);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Official Receipt - {$this->transaction->or_number} - {$this->transaction->organization?->name}"
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.receipt',
            with: ['transaction' => $this->transaction]
        );
    }

    public function attachments(): array
    {
        $pdf = Pdf::loadView('pdf.receipt', ['transaction' => $this->transaction])
            ->setPaper([0, 0, 396, 612]);

        return [
            Attachment::fromData(fn () => $pdf->output(), 'receipt.pdf')
                ->withMime('application/pdf'),
        ];
    }
}