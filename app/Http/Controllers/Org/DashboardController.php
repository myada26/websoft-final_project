<?php

namespace App\Http\Controllers\Org;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Organization;
use App\Models\StudentEnrollment;
use App\Models\Transaction;
use App\Models\VoidRequest;

class DashboardController extends Controller
{
    public function index()
    {
        $activeSemester = AcademicYear::where('is_active', true)->first();
        $org            = auth()->user()->organization;
        $orgId          = $org->id;

        $todayCount         = 0;
        $totalCollected     = 0;
        $enrolledCount      = 0;
        $pendingVoidCount   = 0;
        $recentTransactions = collect();
        $cashAmount         = 0;
        $gcashAmount        = 0;

        if ($activeSemester) {
            $semId = $activeSemester->id;

            $todayCount = Transaction::where('organization_id', $orgId)
                ->where('academic_year_id', $semId)
                ->whereDate('created_at', today())
                ->where('is_void', false)
                ->count();

            $totalCollected = Transaction::where('organization_id', $orgId)
                ->where('academic_year_id', $semId)
                ->where('is_void', false)
                ->sum('amount_paid');

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

            $cashAmount = Transaction::where('organization_id', $orgId)
                ->where('academic_year_id', $semId)
                ->where('payment_method', 'CASH')
                ->where('is_void', false)
                ->sum('amount_paid');

            $gcashAmount = Transaction::where('organization_id', $orgId)
                ->where('academic_year_id', $semId)
                ->where('payment_method', 'GCASH')
                ->where('is_void', false)
                ->sum('amount_paid');
        }

        [$chartLabels, $chartData] = $this->monthlyChart($orgId);

        return view('org.dashboard', compact(
            'activeSemester', 'todayCount', 'totalCollected', 'enrolledCount',
            'pendingVoidCount', 'recentTransactions', 'chartLabels', 'chartData',
            'cashAmount', 'gcashAmount'
        ));
    }

    private function enrolledCount(Organization $org, int $semId): int
    {
        $query = StudentEnrollment::where('academic_year_id', $semId);

        if ($org->type === 'COLLEGE_COUNCIL' && $org->linked_college_id) {
            $query->whereHas('program.department', fn ($q) =>
                $q->where('college_id', $org->linked_college_id)
            );
        } elseif ($org->type === 'DEPT_SOCIETY' && $org->linked_department_id) {
            $query->whereHas('program', fn ($q) =>
                $q->where('department_id', $org->linked_department_id)
            );
        }

        return $query->count();
    }

    private function monthlyChart(int $orgId): array
    {
        $labels = [];
        $data   = [];

        for ($i = 11; $i >= 0; $i--) {
            $month    = now()->subMonths($i);
            $labels[] = $month->format('M Y');
            $data[]   = (float) Transaction::where('organization_id', $orgId)
                ->where('is_void', false)
                ->whereYear('created_at', $month->year)
                ->whereMonth('created_at', $month->month)
                ->sum('amount_paid');
        }

        return [$labels, $data];
    }
}
