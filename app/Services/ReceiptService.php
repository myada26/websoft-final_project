<?php

namespace App\Services;

use App\Models\Transaction;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class ReceiptService
{
    public function generate(Transaction $transaction, string $format = 'thermal'): string
    {
        $transaction->loadMissing(['student', 'organization', 'processedBy', 'academicYear', 'feeProfile']);

        $view = $format === 'a4' ? 'receipts.transaction-a4' : 'receipts.transaction-thermal';

        $pdf = Pdf::loadView($view, ['transaction' => $transaction]);

        if ($format === 'a4') {
            $pdf->setPaper('A5', 'portrait');
        } else {
            // Thermal 80mm ≈ 226pt wide
            $pdf->setPaper([0, 0, 226, 600], 'portrait');
        }

        $orgCode  = $transaction->organization->code ?? 'ORG';
        $orNumber = $transaction->or_number;
        $path     = "receipts/{$orgCode}/{$orNumber}.pdf";

        Storage::put($path, $pdf->output());

        return $path;
    }

    public function download(Transaction $transaction, string $format = 'thermal'): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $transaction->loadMissing(['student', 'organization', 'processedBy', 'academicYear', 'feeProfile']);

        $view = $format === 'a4' ? 'receipts.transaction-a4' : 'receipts.transaction-thermal';

        $pdf = Pdf::loadView($view, ['transaction' => $transaction]);

        if ($format === 'a4') {
            $pdf->setPaper('A5', 'portrait');
        } else {
            $pdf->setPaper([0, 0, 226, 600], 'portrait');
        }

        return $pdf->download("receipt-{$transaction->or_number}.pdf");
    }
}
