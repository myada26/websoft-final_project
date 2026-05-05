<?php

namespace App\Http\Controllers\Org;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class StudentController extends Controller
{
    public function index()
    {
        $orgId = auth()->user()->organization_id;
        $org = auth()->user()->organization;

        // Students who are enrolled in programs within this organization's scope
        $students = \App\Models\Student::whereHas('latestEnrollment.program.department', function ($q) use ($org) {
            if ($org->type === 'COLLEGE_COUNCIL') {
                $q->where('college_id', $org->linked_college_id);
            } elseif ($org->type === 'DEPT_SOCIETY') {
                $q->where('id', $org->linked_department_id);
            }
        })
        ->when(request('search'), fn($q, $s) => $q->where(function($query) use ($s) {
            $query->where('first_name', 'like', "%$s%")
                  ->orWhere('last_name', 'like', "%$s%")
                  ->orWhere('student_number', 'like', "%$s%");
        }))
        ->orderBy('last_name')
        ->orderBy('first_name')
        ->paginate(25);

        // For the manual enrollment modal
        $programs = \App\Models\Program::whereHas('department', function($q) use ($org) {
            if ($org->type === 'COLLEGE_COUNCIL') {
                $q->where('college_id', $org->linked_college_id);
            } elseif ($org->type === 'DEPT_SOCIETY') {
                $q->where('id', $org->linked_department_id);
            }
        })->orderBy('name')->get();

        $activeSemester = \App\Models\AcademicYear::where('is_active', true)->first();

        return view('org.students.index', compact('students', 'programs', 'activeSemester'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'student_number' => 'required|string|max:20|unique:students,student_number',
            'first_name'     => 'required|string|max:100',
            'last_name'      => 'required|string|max:100',
            'middle_name'    => 'nullable|string|max:100',
            'program_id'     => 'required|exists:programs,id',
            'year_level'     => 'required|string',
            'student_type'   => 'required|in:Regular,Irregular',
        ]);

        $activeSemester = \App\Models\AcademicYear::where('is_active', true)->first();
        if (!$activeSemester) {
            return back()->with('error', 'No active academic year found.');
        }

        \Illuminate\Support\Facades\DB::transaction(function() use ($data, $activeSemester) {
            $student = \App\Models\Student::create([
                'student_number' => $data['student_number'],
                'first_name'     => $data['first_name'],
                'last_name'      => $data['last_name'],
                'middle_name'    => $data['middle_name'],
                'created_source' => 'MANUAL_ORG',
            ]);

            \App\Models\StudentEnrollment::create([
                'student_id'       => $student->id,
                'academic_year_id' => $activeSemester->id,
                'program_id'       => $data['program_id'],
                'year_level'       => (int) filter_var($data['year_level'], FILTER_SANITIZE_NUMBER_INT),
                'is_regular'       => $data['student_type'] === 'Regular',
            ]);
        });

        return redirect()->route('org.students.index')->with('success', 'Student enrolled successfully.');
    }

    public function create()
    {
        return redirect()->route('org.students.index');
    }

    public function edit(\App\Models\Student $student)
    {
        return view('org.students.edit', compact('student'));
    }

    public function update(Request $request, \App\Models\Student $student)
    {
        $data = $request->validate([
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'middle_name' => 'nullable|string|max:100',
        ]);

        $student->update($data);

        return redirect()->route('org.students.index')->with('success', 'Student updated.');
    }

    public function importForm()
    {
        return redirect()->route('org.students.index');
    }

    public function import(Request $request)
    {
        return redirect()->route('org.students.index')->with('error', 'Bulk import is not implemented yet.');
    }
}
