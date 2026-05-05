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

    public function setActive(AcademicYear $academicYear)
    {
        AcademicYear::where('is_active', true)->update(['is_active' => false]);
        $academicYear->update(['is_active' => true]);
        return redirect()->route('admin.academic-years.index')->with('success', "'{$academicYear->name}' is now the active semester.");
    }
}
