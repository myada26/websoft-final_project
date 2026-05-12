<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use Illuminate\Http\Request;

class AcademicYearController extends Controller
{
    public function index()
    {
        $academicYears = AcademicYear::orderByDesc('is_active')
            ->orderByDesc('created_at')
            ->paginate(15);
        return view('admin.academic-years.index', compact('academicYears'));
    }

    public function create()
    {
        return view('admin.academic-years.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'      => 'required|string|max:100|unique:academic_years,name',
            'is_active' => 'nullable|boolean',
        ]);
        if ($request->boolean('is_active')) {
            AcademicYear::where('is_active', true)->update(['is_active' => false]);
        }
        $data['is_active'] = $request->boolean('is_active');
        AcademicYear::create($data);
        return redirect()->route('admin.academic-years.index')->with('success', 'Academic year created.');
    }

    public function edit(AcademicYear $academicYear)
    {
        return view('admin.academic-years.edit', compact('academicYear'));
    }

    public function update(Request $request, AcademicYear $academicYear)
    {
        $data = $request->validate([
            'name' => 'required|string|max:100|unique:academic_years,name,'.$academicYear->id,
        ]);
        $academicYear->update($data);
        return redirect()->route('admin.academic-years.index')->with('success', 'Academic year updated.');
    }

    public function setActive(Request $request, AcademicYear $academicYear)
    {
        $currentActive = AcademicYear::where('is_active', true)->first();
        
        // Check for unresolved transactions in current active semester
        if ($currentActive) {
            $unremittedCount = \App\Models\Transaction::where('academic_year_id', $currentActive->id)
                ->whereNull('remittance_id')
                ->where('is_void', false)
                ->count();
            
            $pendingVoidCount = \App\Models\VoidRequest::where('status', 'PENDING')->count();
            
            if ($unremittedCount > 0 || $pendingVoidCount > 0) {
                // If force flag not set, warn and ask for confirmation
                if (!$request->has('force')) {
                    return back()->with('warning', [
                        'message' => "The current semester has {$unremittedCount} unremitted transaction(s) and {$pendingVoidCount} pending void request(s). Are you sure you want to switch?",
                        'action' => route('admin.academic-years.set-active', [$academicYear->id, 'force' => 1]),
                    ]);
                }
            }
        }
        
        AcademicYear::where('is_active', true)->update(['is_active' => false]);
        $academicYear->update(['is_active' => true]);
        return redirect()->route('admin.academic-years.index')->with('success', "'{$academicYear->name}' is now the active semester.");
    }
}
