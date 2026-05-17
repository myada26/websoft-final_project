<?php

namespace App\Http\Controllers\Org;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use Barryvdh\DomPDF\Facade\Pdf;

class ReceiptController extends Controller
{
    public function download($transaction_id)
    {
        $transaction = Transaction::with(['student', 'feeProfile', 'studentFine', 'processedBy', 'organization.linkedCollege', 'academicYear'])
            ->findOrFail($transaction_id);

        $pdf = Pdf::loadView('org.receipt', compact('transaction'));
        
        // Optional: customize paper size to 80mm thermal size, or just standard
        $pdf->setPaper([0, 0, 226.77, 600]); // approx 80mm wide by 210mm long

        return $pdf->stream('Receipt-' . $transaction->or_number . '.pdf');
    }
}
