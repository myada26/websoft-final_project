<?php

namespace App\Http\Controllers\Org;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function index()
    {
        $activeSemester = \App\Models\AcademicYear::where('is_active', true)->first();
        
        return view('org.reports.index', compact('activeSemester'));
    }
}
