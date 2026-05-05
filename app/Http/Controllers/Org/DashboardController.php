<?php

namespace App\Http\Controllers\Org;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Transaction;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $activeSemester = AcademicYear::where('is_active', true)->first();
        $orgId = auth()->user()->organization_id;

        return view('org.dashboard', [
            'activeSemester'     => $activeSemester,
            'todayCount'         => 0,
            'totalCollected'     => '0.00',
            'enrolledCount'      => 0,
            'pendingVoidCount'   => 0,
            'recentTransactions' => collect(),
            'chartLabels'        => ['Jan','Feb','Mar','Apr','May','Jun'],
            'chartData'          => [0,0,0,0,0,0],
            'cashAmount'         => 0,
            'gcashAmount'        => 0,
        ]);
    }
}
