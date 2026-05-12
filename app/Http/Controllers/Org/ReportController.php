<?php

namespace App\Http\Controllers\Org;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Organization;
use App\Models\Transaction;
use App\Models\StudentEnrollment;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function sor()
    {
        $data = $this->sorData();

        return view('org.reports.sor', $data);
    }

    public function sorPdf()
    {
        $data = $this->sorData();

        $pdf = Pdf::loadView('pdf.sor', $data)
            ->setPaper('a4', 'portrait');

        $orgSlug = preg_replace('/[^a-zA-Z0-9]/', '_', $data['organization']->name);

        return $pdf->download('SOR_' . $orgSlug . '.pdf');
    }

    private function sorData(): array
    {
        $organization = auth()->user()->organization;
        $academicYear = AcademicYear::where('is_active', true)->firstOrFail();

        $semesterName = str_replace('2024-2025 ', '', $academicYear->name ?? 'N/A');
        $semesterLabel = ucfirst($semesterName);

        $totalMembers = StudentEnrollment::where('academic_year_id', $academicYear->id)
            ->whereHas('program', function ($query) use ($organization) {
                $query->where('department_id', $organization->linked_department_id)
                    ->orWhereHas('department', function ($q) use ($organization) {
                        $q->where('college_id', $organization->linked_college_id);
                    });
            })
            ->count();

        $transactions = Transaction::where('organization_id', $organization->id)
            ->where('academic_year_id', $academicYear->id)
            ->where('is_void', false)
            ->orderBy('or_number')
            ->get();

        $orMin = $transactions->min('or_number') ?? 'N/A';
        $orMax = $transactions->max('or_number') ?? 'N/A';
        $orRange = $orMin !== 'N/A' && $orMax !== 'N/A' ? $orMin . ' – ' . $orMax : 'N/A';

        $receiptCount = $transactions->count();
        $totalCollected = $transactions->sum('amount_paid');

        $regularCount = $transactions->where('transaction_type', 'FEE')->count();
        $regularAmount = $transactions->where('transaction_type', 'FEE')->sum('amount_paid');
        
        $fineCount = $transactions->where('transaction_type', 'FINE')->count();
        $fineAmount = $transactions->where('transaction_type', 'FINE')->sum('amount_paid');

        $voidCount = Transaction::where('organization_id', $organization->id)
            ->where('academic_year_id', $academicYear->id)
            ->where('is_void', true)
            ->count();

        $feeProfiles = $organization->feeProfiles()->active()->get();
        $regularRate = $feeProfiles->where('category', 'REGULAR')->first()?->amount ?? 0;
        $extendeeRate = $feeProfiles->where('category', 'EXTENDEE')->first()?->amount ?? 0;

        $extendeeCount = 0;
        $extendeeAmount = 0;
        $regularPaidAmount = 0;

        foreach ($transactions->where('transaction_type', 'FEE') as $tx) {
            if ($tx->amount_paid < $regularRate) {
                $extendeeCount++;
                $extendeeAmount += $tx->amount_paid;
            } else {
                $regularPaidAmount += $tx->amount_paid;
            }
        }

        $batchSize = 50;
        $batches = [];
        $orNumbers = $transactions->pluck('or_number')->toArray();
        
        if (!empty($orNumbers)) {
            sort($orNumbers);
            $chunks = array_chunk($orNumbers, $batchSize);
            
            foreach ($chunks as $chunk) {
                $batchTransactions = $transactions->whereIn('or_number', $chunk);
                $batchRegular = $batchTransactions->where('transaction_type', 'FEE')
                    ->filter(fn($t) => $t->amount_paid >= $regularRate)->count();
                $batchExtendee = $batchTransactions->where('transaction_type', 'FEE')
                    ->filter(fn($t) => $t->amount_paid < $regularRate)->count();
                $batchFine = $batchTransactions->where('transaction_type', 'FINE')->count();
                $batchVoid = 0;
                $batchCount = $batchTransactions->count();
                $batchSubtotal = $batchTransactions->sum('amount_paid');

                $batches[] = [
                    'range' => $chunk[0] . ' – ' . end($chunk),
                    'regular' => $batchRegular,
                    'extendee' => $batchExtendee,
                    'fine' => $batchFine,
                    'cancelled' => $batchVoid,
                    'count' => $batchCount,
                    'subtotal' => $batchSubtotal,
                ];
            }
        }

        return compact(
            'organization',
            'academicYear',
            'semesterLabel',
            'totalMembers',
            'orMin',
            'orMax',
            'orRange',
            'receiptCount',
            'totalCollected',
            'regularCount',
            'regularAmount',
            'regularPaidAmount',
            'extendeeCount',
            'extendeeAmount',
            'fineCount',
            'fineAmount',
            'voidCount',
            'regularRate',
            'extendeeRate',
            'batches'
        );
    }
}