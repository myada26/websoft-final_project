<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\AcademicYear;
use App\Models\StudentEnrollment;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
        $programs = \App\Models\Program::with('department.college')->orderBy('name')->get();
        $activeSemester = AcademicYear::where('is_active', true)->first();
        
        return view('admin.students.import', compact('programs', 'activeSemester'));
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt',
            'program_id' => 'required|exists:programs,id',
        ]);

        $activeSemester = AcademicYear::where('is_active', true)->firstOrFail();
        $program = \App\Models\Program::findOrFail($request->program_id);

        $file = fopen($request->file('file')->getRealPath(), 'r');
        $headers = fgetcsv($file);
        
        // Normalize headers
        $headers = array_map(fn($h) => strtolower(trim($h)), $headers);
        $headerMap = [
            'student_number' => array_search('student_number', $headers),
            'first_name' => array_search('first_name', $headers),
            'last_name' => array_search('last_name', $headers),
            'middle_name' => array_search('middle_name', $headers),
            'year_level' => array_search('year_level', $headers),
            'is_regular' => array_search('is_regular', $headers),
        ];

        // Validate required columns
        if ($headerMap['student_number'] === false || $headerMap['first_name'] === false || $headerMap['last_name'] === false) {
            fclose($file);
            return back()->with('error', 'CSV must have columns: student_number, first_name, last_name');
        }

        $created = 0;
        $skipped = 0;
        $errors = [];

        while (($row = fgetcsv($file)) !== false) {
            try {
                $studentNumber = trim($row[$headerMap['student_number']] ?? '');
                $firstName = trim($row[$headerMap['first_name']] ?? '');
                $lastName = trim($row[$headerMap['last_name']] ?? '');
                $middleName = isset($headerMap['middle_name']) && $headerMap['middle_name'] !== false ? trim($row[$headerMap['middle_name']]) : null;
                $yearLevel = isset($headerMap['year_level']) && $headerMap['year_level'] !== false ? (int) trim($row[$headerMap['year_level']]) : 1;
                $isRegular = isset($headerMap['is_regular']) && $headerMap['is_regular'] !== false 
                    ? strtolower(trim($row[$headerMap['is_regular']])) === 'regular' 
                    : true;

                if (empty($studentNumber) || empty($firstName) || empty($lastName)) {
                    $skipped++;
                    continue;
                }

                DB::transaction(function () use ($studentNumber, $firstName, $lastName, $middleName, $yearLevel, $isRegular, $activeSemester, $program) {
                    // Check if student exists
                    $student = Student::where('student_number', $studentNumber)->first();
                    
                    if (!$student) {
                        // Create new student
                        $student = Student::create([
                            'student_number' => $studentNumber,
                            'first_name' => $firstName,
                            'last_name' => $lastName,
                            'middle_name' => $middleName,
                            'created_source' => 'SSC_BULK',
                        ]);
                    }

                    // Check if already enrolled in this semester (skip duplicate)
                    $existingEnrollment = StudentEnrollment::where('student_id', $student->id)
                        ->where('academic_year_id', $activeSemester->id)
                        ->first();

                    if ($existingEnrollment) {
                        // If shifting to different program, create new enrollment (preserve old)
                        if ($existingEnrollment->program_id !== $program->id) {
                            StudentEnrollment::create([
                                'student_id' => $student->id,
                                'academic_year_id' => $activeSemester->id,
                                'program_id' => $program->id,
                                'year_level' => $yearLevel,
                                'is_regular' => $isRegular,
                            ]);
                        }
                    } else {
                        // Create new enrollment
                        StudentEnrollment::create([
                            'student_id' => $student->id,
                            'academic_year_id' => $activeSemester->id,
                            'program_id' => $program->id,
                            'year_level' => $yearLevel,
                            'is_regular' => $isRegular,
                        ]);
                    }
                });

                $created++;
            } catch (\Exception $e) {
                $errors[] = "Error on row: " . ($e->getMessage());
            }
        }

        fclose($file);

        // Log audit
        AuditLog::create([
            'user_id' => auth()->id(),
            'action' => 'BULK_IMPORT_STUDENTS',
            'entity_type' => 'STUDENT',
            'entity_id' => null,
            'details' => [
                'program_id' => $program->id,
                'program_name' => $program->name,
                'created' => $created,
                'skipped' => $skipped,
            ],
            'ip_address' => $request->ip(),
            'timestamp' => now(),
        ]);

        $message = "Imported {$created} student(s)";
        if ($skipped > 0) $message .= ", {$skipped} skipped";
        if (!empty($errors)) $message .= ". Errors: " . implode('; ', array_slice($errors, 0, 3));

        return redirect()->route('admin.students.index')->with($errors ? 'warning' : 'success', $message);
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
