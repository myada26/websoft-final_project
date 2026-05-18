<?php

namespace App\Http\Controllers\Org;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Organization;
use App\Models\Transaction;
use App\Models\StudentEnrollment;
use App\Services\AiInsightService; // [AI Narrator]
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function __construct( // [AI Narrator]
        private readonly AiInsightService $aiService, // [AI Narrator]
    ) {} // [AI Narrator]

    public function index(Request $request)
    {
        $semesters = AcademicYear::orderByDesc('id')->get();
        $payload = $this->reportPayload($request);

        if ($request->get('export') === 'pdf') {
            return $this->exportPdf($request);
        }

        if ($request->get('export') === 'csv') {
            return $this->exportCsv($request);
        }

        return view('org.reports.index', [
            'semesters' => $semesters,
            ...$payload,
        ]);
    }

    public function exportPdf(Request $request)
    {
        $payload = $this->reportPayload($request);

        $pdf = Pdf::loadView('pdf.report', [
            'org' => auth()->user()->organization,
            'semester' => $payload['semester'],
            'reportTitle' => $payload['reportTitle'],
            'reportColumns' => $payload['reportColumns'],
            'reportData' => $payload['reportData'],
        ])->setPaper('a4', 'portrait');

        return $pdf->stream('collection-summary.pdf');
    }

    public function exportCsv(Request $request)
    {
        $payload = $this->reportPayload($request);

        return response()->streamDownload(function () use ($payload) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, $payload['reportColumns']);

            foreach ($payload['reportData'] as $row) {
                fputcsv($handle, $row);
            }

            fclose($handle);
        }, 'collection-summary.csv', ['Content-Type' => 'text/csv']);
    }

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

    public function exportAiReport(Request $request): \Symfony\Component\HttpFoundation\Response // [AI Narrator]
    {
        $request->validate([ // [AI Narrator]
            'charts'            => ['nullable', 'array'], // [AI Narrator]
            'charts.collection' => ['nullable', 'string'], // [AI Narrator]
            'charts.payment'    => ['nullable', 'string'], // [AI Narrator]
        ]); // [AI Narrator]

        $activeSemester  = AcademicYear::where('is_active', true)->first(); // [AI Narrator]
        $org             = auth()->user()->organization; // [AI Narrator]
        $orgId           = $org->id; // [AI Narrator]
        $semId           = $activeSemester?->id; // [AI Narrator]

        // [AI Narrator] Fetch fresh report data
        $totalCollected  = $semId ? Transaction::where('organization_id', $orgId)->where('academic_year_id', $semId)->where('is_void', false)->sum('amount_paid') : 0; // [AI Narrator]
        $cashAmount      = $semId ? Transaction::where('organization_id', $orgId)->where('academic_year_id', $semId)->where('payment_method', 'CASH')->where('is_void', false)->sum('amount_paid') : 0; // [AI Narrator]
        $gcashAmount     = $semId ? Transaction::where('organization_id', $orgId)->where('academic_year_id', $semId)->where('payment_method', 'GCASH')->where('is_void', false)->sum('amount_paid') : 0; // [AI Narrator]
        $txnCount        = $semId ? Transaction::where('organization_id', $orgId)->where('academic_year_id', $semId)->where('is_void', false)->count() : 0; // [AI Narrator]
        $feeCount        = $semId ? Transaction::where('organization_id', $orgId)->where('academic_year_id', $semId)->where('transaction_type', 'FEE')->where('is_void', false)->count() : 0; // [AI Narrator]
        $fineCount       = $semId ? Transaction::where('organization_id', $orgId)->where('academic_year_id', $semId)->where('transaction_type', 'FINE')->where('is_void', false)->count() : 0; // [AI Narrator]
        $unremittedCount = $semId ? Transaction::where('organization_id', $orgId)->where('academic_year_id', $semId)->where('is_void', false)->whereNull('remittance_id')->count() : 0; // [AI Narrator]
        $pendingVoids    = $semId ? \App\Models\VoidRequest::whereHas('transaction', fn($q) => $q->where('organization_id', $orgId)->where('academic_year_id', $semId))->where('status', 'PENDING')->count() : 0; // [AI Narrator]

        // [AI Narrator] Generate AI narrative
        $aiService = app(\App\Services\AiInsightService::class); // [AI Narrator]
        $aiInsight = $aiService->generateNarrative([ // [AI Narrator]
            'org_name'           => $org->name, // [AI Narrator]
            'org_type'           => $org->type, // [AI Narrator]
            'semester'           => $activeSemester?->name ?? 'N/A', // [AI Narrator]
            'total_collected'    => $totalCollected, // [AI Narrator]
            'today_count'        => 0, // [AI Narrator]
            'enrolled_count'     => 0, // [AI Narrator]
            'pending_void_count' => $pendingVoids, // [AI Narrator]
            'cash_amount'        => $cashAmount, // [AI Narrator]
            'gcash_amount'       => $gcashAmount, // [AI Narrator]
            'fee_count'          => $feeCount, // [AI Narrator]
            'fine_count'         => $fineCount, // [AI Narrator]
            'unremitted_count'   => $unremittedCount, // [AI Narrator]
        ]); // [AI Narrator]

        $charts = $request->input('charts', []); // [AI Narrator]

        $pdf = Pdf::loadView('org.reports.ai-report-pdf', [ // [AI Narrator]
            'org'             => $org, // [AI Narrator]
            'semester'        => $activeSemester, // [AI Narrator]
            'totalCollected'  => $totalCollected, // [AI Narrator]
            'txnCount'        => $txnCount, // [AI Narrator]
            'cashAmount'      => $cashAmount, // [AI Narrator]
            'gcashAmount'     => $gcashAmount, // [AI Narrator]
            'feeCount'        => $feeCount, // [AI Narrator]
            'fineCount'       => $fineCount, // [AI Narrator]
            'unremittedCount' => $unremittedCount, // [AI Narrator]
            'aiInsight'       => $aiInsight, // [AI Narrator]
            'charts'          => $charts, // [AI Narrator]
            'officer'         => auth()->user(), // [AI Narrator]
            'generatedAt'     => now()->format('F d, Y h:i A'), // [AI Narrator]
        ])->setPaper('a4', 'portrait'); // [AI Narrator]

        return $pdf->download('fcats-ai-report.pdf'); // [AI Narrator]
    }

    private function reportPayload(Request $request): array
    {
        $org = auth()->user()->organization;
        $semester = $request->filled('semester_id')
            ? AcademicYear::findOrFail($request->integer('semester_id'))
            : AcademicYear::where('is_active', true)->firstOrFail();

        $query = Transaction::where('organization_id', $org->id)
            ->where('academic_year_id', $semester->id)
            ->where('is_void', false)
            ->with(['student', 'feeProfile', 'processedBy']);

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date('date_to'));
        }

        $transactions = $query->orderBy('created_at')->get();

        $reportColumns = ['Type', 'Payment Method', 'Transactions', 'Total Amount'];
        $reportData = $transactions
            ->groupBy(fn (Transaction $transaction) => $transaction->transaction_type . '|' . $transaction->payment_method)
            ->map(function ($rows, string $key) {
                [$type, $paymentMethod] = explode('|', $key);

                return [
                    $type,
                    $paymentMethod,
                    (string) $rows->count(),
                    'PHP ' . number_format((float) $rows->sum('amount_paid'), 2),
                ];
            })
            ->values()
            ->toArray();

        if ($reportData === []) {
            $reportData[] = ['No records', '-', '0', 'PHP 0.00'];
        }

        return [
            'semester' => $semester,
            'reportTitle' => 'Collection Summary',
            'reportPeriod' => $semester->name,
            'reportColumns' => $reportColumns,
            'reportData' => $reportData,
        ];
    }

    private function sorData(): array
    {
        $organization = auth()->user()->organization;
        $academicYear = AcademicYear::where('is_active', true)->firstOrFail();

        $treasurer = \App\Models\User::where('organization_id', $organization->id)
            ->where('role', 'TREASURER')
            ->where('is_active', true)
            ->with('student')
            ->first();

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
            ->with('feeProfile')
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
            if ($tx->feeProfile?->category === 'EXTENDEE') {
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
                $feeTxs = $batchTransactions->where('transaction_type', 'FEE');

                $batchRegular        = $feeTxs->filter(fn($t) => $t->feeProfile?->category !== 'EXTENDEE')->count();
                $batchExtendee       = $feeTxs->filter(fn($t) => $t->feeProfile?->category === 'EXTENDEE')->count();
                $batchRegularAmount  = $feeTxs->filter(fn($t) => $t->feeProfile?->category !== 'EXTENDEE')->sum('amount_paid');
                $batchExtendeeAmount = $feeTxs->filter(fn($t) => $t->feeProfile?->category === 'EXTENDEE')->sum('amount_paid');
                $batchFine           = $batchTransactions->where('transaction_type', 'FINE')->count();
                $batchFineAmount     = $batchTransactions->where('transaction_type', 'FINE')->sum('amount_paid');
                $batchCount          = $batchTransactions->count();
                $batchSubtotal       = $batchTransactions->sum('amount_paid');

                $batches[] = [
                    'range'           => $chunk[0] . ' – ' . end($chunk),
                    'regular'         => $batchRegular,
                    'extendee'        => $batchExtendee,
                    'regular_amount'  => $batchRegularAmount,
                    'extendee_amount' => $batchExtendeeAmount,
                    'fine'            => $batchFine,
                    'fine_amount'     => $batchFineAmount,
                    'cancelled'       => 0,
                    'count'           => $batchCount,
                    'subtotal'        => $batchSubtotal,
                ];
            }
        }

        return compact(
            'organization',
            'academicYear',
            'semesterLabel',
            'treasurer',
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
