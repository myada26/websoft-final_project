<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\AcademicYear;
use Illuminate\Http\Request;

class StudentController extends Controller
{
    public function index()
    {
        $activeSemester = AcademicYear::where('is_active', true)->first();
        $students = Student::with(['latestEnrollment.program.department.college'])
            ->when(request('search'), fn($q, $s) => $q->where(function($query) use ($s) {
                $query->where('full_name', 'like', "%$s%")
                      ->orWhere('student_number', 'like', "%$s%");
            }))
            ->orderBy('full_name')
            ->paginate(25);

        $programs = \App\Models\Program::orderBy('name')->get();

        return view('admin.students.index', compact('students', 'activeSemester', 'programs'));
    }

    public function importForm()
    {
        return view('admin.students.import');
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

        $activeSemester = AcademicYear::where('is_active', true)->first();
        if (!$activeSemester) {
            return back()->with('error', 'No active academic year found. Please create one first.');
        }

        \Illuminate\Support\Facades\DB::transaction(function() use ($data, $activeSemester) {
            $student = Student::create([
                'student_number' => $data['student_number'],
                'first_name'     => $data['first_name'],
                'last_name'      => $data['last_name'],
                'middle_name'    => $data['middle_name'],
                'created_source' => 'MANUAL_ADMIN',
            ]);

            \App\Models\StudentEnrollment::create([
                'student_id'       => $student->id,
                'academic_year_id' => $activeSemester->id,
                'program_id'       => $data['program_id'],
                'year_level'       => (int) filter_var($data['year_level'], FILTER_SANITIZE_NUMBER_INT),
                'is_regular'       => $data['student_type'] === 'Regular',
            ]);
        });

        return redirect()->route('admin.students.index')->with('success', 'Student enrolled successfully.');
    }
}
