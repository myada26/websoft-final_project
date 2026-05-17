<?php

namespace App\Http\Controllers\Org;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Organization;
use App\Models\StudentEnrollment;
use App\Models\Transaction;
use App\Models\VoidRequest;
use App\Services\AiInsightService; // [AI Narrator]
use Illuminate\Http\JsonResponse; // [AI Narrator]
use Illuminate\Http\Request; // [AI Narrator]
use Illuminate\Support\Facades\Log; // [AI Narrator]

class DashboardController extends Controller
{
    public function __construct(private AiInsightService $aiService) {} // [AI Narrator]

    public function index()
    {
        $activeSemester = AcademicYear::getActive();
        $org            = auth()->user()->organization;
        $orgId          = $org->id;

        $todayCount         = 0;
        $totalCollected     = 0;
        $enrolledCount      = 0;
        $pendingVoidCount   = 0;
        $recentTransactions = collect();
        $cashAmount         = 0;
        $gcashAmount        = 0;
        $feeCount           = 0; // [AI Narrator]
        $fineCount          = 0; // [AI Narrator]
        $unremittedCount    = 0; // [AI Narrator]

        if ($activeSemester) {
            $semId = $activeSemester->id;

            // [perf] Collapsed 7 separate aggregate queries (transactions table) into a
            // single round-trip using conditional sums/counts. Cuts ~6×80ms = ~480ms on
            // every dashboard load against remote Supabase (Tokyo). Same WHERE filters,
            // same semantics — only the transport changed.
            $todayStart = today()->toDateTimeString();
            $todayEnd   = today()->endOfDay()->toDateTimeString();

            $row = Transaction::where('organization_id', $orgId)
                ->where('academic_year_id', $semId)
                ->where('is_void', false)
                ->selectRaw('
                    COUNT(*) FILTER (WHERE created_at BETWEEN ? AND ?) AS today_count,
                    COALESCE(SUM(amount_paid), 0) AS total_collected,
                    COALESCE(SUM(amount_paid) FILTER (WHERE payment_method = ?), 0) AS cash_amount,
                    COALESCE(SUM(amount_paid) FILTER (WHERE payment_method = ?), 0) AS gcash_amount,
                    COUNT(*) FILTER (WHERE transaction_type = ?) AS fee_count,
                    COUNT(*) FILTER (WHERE transaction_type = ?) AS fine_count,
                    COUNT(*) FILTER (WHERE remittance_id IS NULL) AS unremitted_count
                ', [$todayStart, $todayEnd, 'CASH', 'GCASH', 'FEE', 'FINE'])
                ->first();

            $todayCount      = (int)   ($row->today_count       ?? 0);
            $totalCollected  = (float) ($row->total_collected   ?? 0);
            $cashAmount      = (float) ($row->cash_amount       ?? 0);
            $gcashAmount     = (float) ($row->gcash_amount      ?? 0);
            $feeCount        = (int)   ($row->fee_count         ?? 0);
            $fineCount       = (int)   ($row->fine_count        ?? 0);
            $unremittedCount = (int)   ($row->unremitted_count  ?? 0);

            $enrolledCount = $this->enrolledCount($org, $semId);

            $pendingVoidCount = VoidRequest::where('status', 'PENDING')
                ->whereHas('transaction', fn ($q) => $q->where('organization_id', $orgId))
                ->count();

            $recentTransactions = Transaction::where('organization_id', $orgId)
                ->where('academic_year_id', $semId)
                ->with(['student', 'feeProfile'])
                ->orderByDesc('created_at')
                ->limit(10)
                ->get();
        }

        [$chartLabels, $chartData] = $this->monthlyChart($orgId);

        // [AI Narrator] Build context and generate AI narrative
        $aiInsight = 'AI narrative unavailable at this time. Please check your collection summary above.'; // [AI Narrator]
        try { // [AI Narrator]
            $aiContext = [ // [AI Narrator]
                'org_name'           => $org->name, // [AI Narrator]
                'org_type'           => $org->type, // [AI Narrator]
                'semester'           => $activeSemester->name ?? 'N/A', // [AI Narrator]
                'total_collected'    => $totalCollected, // [AI Narrator]
                'today_count'        => $todayCount, // [AI Narrator]
                'enrolled_count'     => $enrolledCount, // [AI Narrator]
                'pending_void_count' => $pendingVoidCount, // [AI Narrator]
                'cash_amount'        => $cashAmount, // [AI Narrator]
                'gcash_amount'       => $gcashAmount, // [AI Narrator]
                'fee_count'          => $feeCount, // [AI Narrator]
                'fine_count'         => $fineCount, // [AI Narrator]
                'unremitted_count'   => $unremittedCount, // [AI Narrator]
            ]; // [AI Narrator]
            $aiInsight = $this->aiService->generateNarrative($aiContext); // [AI Narrator]
        } catch (\Throwable $e) { // [AI Narrator]
            Log::error('[AI Narrator] Dashboard AI call failed', ['error' => $e->getMessage()]); // [AI Narrator]
        } // [AI Narrator]

        return view('org.dashboard', compact(
            'activeSemester', 'todayCount', 'totalCollected', 'enrolledCount',
            'pendingVoidCount', 'recentTransactions', 'chartLabels', 'chartData',
            'cashAmount', 'gcashAmount',
            'aiInsight', 'feeCount', 'fineCount', 'unremittedCount' // [AI Narrator]
        ));
    }

    public function askAi(Request $request): \Illuminate\Http\JsonResponse // [AI Narrator]
    {
        $request->validate([ // [AI Narrator]
            'question' => ['required', 'string', 'max:200'], // [AI Narrator]
        ]); // [AI Narrator]

        $activeSemester = AcademicYear::getActive(); // [AI Narrator]
        $org     = auth()->user()->organization; // [AI Narrator]
        $orgId   = $org->id; // [AI Narrator]
        $semId   = $activeSemester?->id; // [AI Narrator]

        // [AI Narrator] Reuse same data context as index()
        $totalCollected  = $semId ? Transaction::where('organization_id', $orgId)->where('academic_year_id', $semId)->where('is_void', false)->sum('amount_paid') : 0; // [AI Narrator]
        $cashAmount      = $semId ? Transaction::where('organization_id', $orgId)->where('academic_year_id', $semId)->where('payment_method', 'CASH')->where('is_void', false)->sum('amount_paid') : 0; // [AI Narrator]
        $gcashAmount     = $semId ? Transaction::where('organization_id', $orgId)->where('academic_year_id', $semId)->where('payment_method', 'GCASH')->where('is_void', false)->sum('amount_paid') : 0; // [AI Narrator]
        $unremittedCount = $semId ? Transaction::where('organization_id', $orgId)->where('academic_year_id', $semId)->where('is_void', false)->whereNull('remittance_id')->count() : 0; // [AI Narrator]
        $enrolledCount   = $semId ? $this->enrolledCount($org, $semId) : 0; // [AI Narrator]

        $context = [ // [AI Narrator]
            'org_name'           => $org->name, // [AI Narrator]
            'org_type'           => $org->type, // [AI Narrator]
            'semester'           => $activeSemester?->name ?? 'N/A', // [AI Narrator]
            'total_collected'    => $totalCollected, // [AI Narrator]
            'enrolled_count'     => $enrolledCount, // [AI Narrator]
            'cash_amount'        => $cashAmount, // [AI Narrator]
            'gcash_amount'       => $gcashAmount, // [AI Narrator]
            'unremitted_count'   => $unremittedCount, // [AI Narrator]
            'pending_void_count' => 0, // [AI Narrator]
            'today_count'        => 0, // [AI Narrator]
            'fee_count'          => 0, // [AI Narrator]
            'fine_count'         => 0, // [AI Narrator]
        ]; // [AI Narrator]

        $answer = $this->aiService->answerQuestion($context, $request->input('question')); // [AI Narrator]

        return response()->json(['answer' => $answer]); // [AI Narrator]
    }

    private function enrolledCount(Organization $org, int $semId): int
    {
        $query = StudentEnrollment::where('academic_year_id', $semId);

        if ($org->type === 'COLLEGE_COUNCIL' && $org->linked_college_id) {
            $query->whereHas('program.department', fn ($q) =>
                $q->where('college_id', $org->linked_college_id)
            );
        } elseif ($org->type === 'CLASS_ORG' && $org->linked_department_id) {
            $query->whereHas('program', fn ($q) =>
                $q->where('department_id', $org->linked_department_id)
            );
        }

        return $query->count();
    }

    private function monthlyChart(int $orgId): array
    {
        // [perf] Was 12 separate aggregate queries in a loop (≈12×80ms = ~1s on remote
        // Supabase). Now a single GROUP BY over the last 12 months, then zero-filled
        // in PHP so months with no transactions still appear. Postgres-specific
        // date_trunc — matches DB_CONNECTION=pgsql.
        $rangeStart = now()->subMonths(11)->startOfMonth();

        $rows = Transaction::where('organization_id', $orgId)
            ->where('is_void', false)
            ->where('created_at', '>=', $rangeStart)
            ->selectRaw("to_char(date_trunc('month', created_at), 'YYYY-MM') AS ym,
                         COALESCE(SUM(amount_paid), 0) AS total")
            ->groupBy('ym')
            ->pluck('total', 'ym');

        $labels = [];
        $data   = [];

        for ($i = 11; $i >= 0; $i--) {
            $month    = now()->subMonths($i);
            $key      = $month->format('Y-m');
            $labels[] = $month->format('M Y');
            $data[]   = (float) ($rows[$key] ?? 0);
        }

        return [$labels, $data];
    }
}
