<?php

namespace App\Services;

use App\Models\Transaction;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ReceiptEmailService
{
    public function send(Transaction $transaction): void
    {
        $transaction->loadMissing(['student', 'organization', 'academicYear', 'processedBy', 'feeProfile']);

        $student = $transaction->student;

        if (!$student || !$student->email) {
            Log::info("Receipt email skipped: Student or email missing", [
                'transaction_id' => $transaction->id,
                'student_id' => $student?->id,
            ]);
            return;
        }

        if (app()->environment('testing') && !$this->hasHttpFake()) {
            Log::info('Receipt email skipped during non-email test run.', [
                'transaction_id' => $transaction->id,
                'student_id' => $student->id,
            ]);
            return;
        }

        $pdf = Pdf::loadView('pdf.receipt', ['transaction' => $transaction])
            ->setPaper([0, 0, 396, 612])
            ->output();

        try {
            $response = Http::withToken((string) env('MAILTRAP_TOKEN'))
                ->timeout(10)
                ->post('https://send.api.mailtrap.io/api/send', [
                    'from' => [
                        'email' => config('mail.from.address', 'no-reply@demomailtrap.co'),
                        'name' => config('mail.from.name', 'FCATS'),
                    ],
                    'to' => [
                        ['email' => $student->email],
                    ],
                    'subject' => "Official Receipt - {$transaction->or_number} - {$transaction->organization?->name}",
                    'text' => $this->getTextContent($transaction),
                    'html' => $this->getHtmlContent($transaction),
                    'attachments' => [
                        [
                            'content' => base64_encode($pdf),
                            'filename' => 'receipt.pdf',
                            'type' => 'application/pdf',
                        ],
                    ],
                ]);

            Log::info('Receipt email sent through Mailtrap API', [
                'transaction_id' => $transaction->id,
                'status' => $response->status(),
            ]);
        } catch (\Throwable $exception) {
            Log::warning('Receipt email delivery failed; transaction was kept posted.', [
                'transaction_id' => $transaction->id,
                'student_id' => $student->id,
                'message' => $exception->getMessage(),
            ]);
        }
    }

    private function hasHttpFake(): bool
    {
        try {
            $factory = Http::getFacadeRoot();
            $property = new \ReflectionProperty($factory, 'stubCallbacks');
            $property->setAccessible(true);

            return $property->getValue($factory)->isNotEmpty();
        } catch (\Throwable) {
            return false;
        }
    }

    private function getTextContent(Transaction $transaction): string
    {
        return "Official Receipt\n\n" .
            "OR Number: {$transaction->or_number}\n" .
            "Student: {$transaction->student->full_name}\n" .
            "Student Number: {$transaction->student->student_number}\n" .
            "Amount: ₱" . number_format($transaction->amount_paid, 2) . "\n" .
            "Payment Method: {$transaction->payment_method}\n" .
            "Date: {$transaction->created_at->format('F d, Y h:i A')}\n\n" .
            "Thank you for your payment!";
    }

    private function getHtmlContent(Transaction $transaction): string
    {
        $student = $transaction->student;
        $org = $transaction->organization;
        
        return '<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; }
        .header { background: #00491e; color: white; padding: 20px; text-align: center; }
        .content { padding: 20px; border: 1px solid #ddd; }
        .row { display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #eee; }
        .label { font-weight: bold; color: #555; }
        .total { background: #00491e; color: white; padding: 15px; text-align: center; font-size: 24px; font-weight: bold; }
        .footer { text-align: center; padding: 20px; color: #888; font-size: 12px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>' . ($org?->name ?? 'Organization') . '</h1>
        <p>OFFICIAL RECEIPT</p>
    </div>
    <div class="content">
        <div class="row"><span class="label">OR Number:</span><span>' . $transaction->or_number . '</span></div>
        <div class="row"><span class="label">Date:</span><span>' . $transaction->created_at->format('F d, Y h:i A') . '</span></div>
        <div class="row"><span class="label">Student Name:</span><span>' . $student->full_name . '</span></div>
        <div class="row"><span class="label">Student Number:</span><span>' . $student->student_number . '</span></div>
        <div class="row"><span class="label">Amount:</span><span>₱' . number_format($transaction->amount_paid, 2) . '</span></div>
        <div class="row"><span class="label">Payment Method:</span><span>' . $transaction->payment_method . '</span></div>
    </div>
    <div class="footer">
        <p>Thank you for your payment!</p>
    </div>
</body>
</html>';
    }
}
