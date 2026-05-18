<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\AuditLog;
use App\Models\College;
use App\Models\Department;
use App\Models\ImportLog;
use App\Models\Program;
use App\Models\Student;
use App\Models\StudentEnrollment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class StudentController extends Controller
{
    public function index(Request $request)
    {
        $activeSemester = AcademicYear::getActive(); // [perf] cached helper

        // Allowed sort fields and their SQL column references
        $sortable = [
            'college'  => 'colleges.name',
            'dept'     => 'departments.name',
            'program'  => 'programs.name',
            'year'     => 'student_enrollments.year_level',
        ];

        $students = Student::select('students.*')
            ->leftJoin('student_enrollments', function ($join) use ($activeSemester) {
                $join->on('students.id', '=', 'student_enrollments.student_id')
                     ->when($activeSemester, fn($j) => $j->where('student_enrollments.academic_year_id', $activeSemester->id));
            })
            ->leftJoin('programs',    'student_enrollments.program_id',    '=', 'programs.id')
            ->leftJoin('departments', 'programs.department_id',             '=', 'departments.id')
            ->leftJoin('colleges',    'departments.college_id',             '=', 'colleges.id')
            ->when(request('search'), fn($q, $s) => $q->where(function ($query) use ($s) {
                $query->where('students.first_name',    'like', "%{$s}%")
                      ->orWhere('students.last_name',   'like', "%{$s}%")
                      ->orWhere('students.student_number', 'like', "%{$s}%");
            }))
            // Cascading filters (FR-0014) — UNIVERSITY_WIDE admin only
            ->when(request('filter_college'), fn($q, $v) => $q->where('colleges.id', $v))
            ->when(request('filter_dept'),    fn($q, $v) => $q->where('departments.id', $v))
            ->when(request('filter_program'), fn($q, $v) => $q->where('programs.id', $v))
            ->when(request('filter_year'),    fn($q, $v) => $q->where('student_enrollments.year_level', $v));

        // Apply multi-field sort (FR-0014)
        $hasSorted = false;
        foreach ($sortable as $param => $col) {
            $dir = request("sort_{$param}");
            if (in_array($dir, ['asc', 'desc'])) {
                $students->orderBy($col, $dir);
                $hasSorted = true;
            }
        }
        if (!$hasSorted) {
            $students->orderBy('students.last_name')->orderBy('students.first_name');
        }

        // Eager-load relationships used in the view and CSV export (NFR-002 — eliminates N+1)
        $students->with('latestEnrollment.program.department.college');

        if ($request->query('export') === 'csv') {
            $all = $students->get();
            $semester = $activeSemester?->name ?? 'N/A';
            $filename = 'students_roster_' . now()->format('Ymd_His') . '.csv';

            $callback = function () use ($all, $semester) {
                $out = fopen('php://output', 'w');
                fputcsv($out, ['FCATS Student Roster', 'Semester: ' . $semester, 'Exported: ' . now()->toDateTimeString()]);
                fputcsv($out, []);
                fputcsv($out, ['Student ID', 'Last Name', 'First Name', 'Middle Name', 'Name Ext.', 'Email', 'College', 'Department', 'Program', 'Year Level', 'Student Type']);
                foreach ($all as $s) {
                    $enr = $s->latestEnrollment;
                    fputcsv($out, [
                        $s->student_number,
                        $s->last_name,
                        $s->first_name,
                        $s->middle_name ?? '',
                        $s->name_extension ?? '',
                        $s->email ?? '',
                        $enr?->program?->department?->college?->name ?? '',
                        $enr?->program?->department?->name ?? '',
                        $enr?->program?->name ?? '',
                        $enr?->year_level ?? '',
                        $enr?->student_type ?? '',
                    ]);
                }
                fclose($out);
            };

            return response()->streamDownload($callback, $filename, [
                'Content-Type' => 'text/csv',
            ]);
        }

        $students = $students->paginate(25);

        // [perf] Cache as plain arrays (file cache corrupts Eloquent models on Windows),
        // then rehydrate as stdClass so Blade can still access ->id, ->name, ->code.
        $hydrate = fn (array $rows) => collect($rows)->map(fn ($row) => (object) $row);

        $programs       = $hydrate(Cache::remember('dropdown_programs',      3600, fn () => Program::orderBy('name')->get(['id', 'name', 'code', 'department_id'])->toArray()));
        $colleges       = $hydrate(Cache::remember('dropdown_colleges',      3600, fn () => College::orderBy('name')->get(['id', 'name', 'code'])->toArray()));
        $allDepartments = $hydrate(Cache::remember('dropdown_departments',   3600, fn () => Department::orderBy('name')->get(['id', 'name', 'code', 'college_id'])->toArray()));
        $allPrograms    = $hydrate(Cache::remember('dropdown_programs_slim', 3600, fn () => Program::orderBy('name')->get(['id', 'name', 'code', 'department_id'])->toArray()));

        return view('admin.students.index', compact(
            'students', 'activeSemester', 'programs',
            'colleges', 'allDepartments', 'allPrograms'
        ));
    }

    /**
     * JSON endpoint: departments filtered by college_id (for cascading dropdowns).
     */
    public function departmentsByCollege(Request $request)
    {
        $collegeId = $request->query('college_id');
        $depts = Department::when($collegeId, fn($q) => $q->where('college_id', $collegeId))
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        return response()->json($depts);
    }

    /**
     * JSON endpoint: programs filtered by department_id (for cascading dropdowns).
     */
    public function programsByDepartment(Request $request)
    {
        $deptId = $request->query('department_id');
        $programs = Program::when($deptId, fn($q) => $q->where('department_id', $deptId))
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        return response()->json($programs);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'student_number' => ['required', 'string', 'regex:/^\d{10}$/', 'unique:students,student_number'],
            'first_name'     => 'required|string|max:100',
            'last_name'      => 'required|string|max:100',
            'name_extension' => 'nullable|string|max:20',
            'middle_name'    => 'nullable|string|max:100',
            'email'          => 'nullable|email|max:150',
            'program_id'     => 'required|exists:programs,id',
            'year_level'     => 'required|integer|min:1|max:9',
            'student_type'   => 'required|in:REGULAR,IRREGULAR,EXTENDEE',
        ]);

        $activeSemester = AcademicYear::where('is_active', true)->first();
        if (!$activeSemester) {
            return back()->with('error', 'No active academic year found. Please create one first.');
        }

        DB::transaction(function () use ($data, $activeSemester) {
            $student = Student::create([
                'student_number' => $data['student_number'],
                'first_name'     => $data['first_name'],
                'last_name'      => $data['last_name'],
                'name_extension' => $data['name_extension'] ?? null,
                'middle_name'    => $data['middle_name'] ?? null,
                'email'          => $data['email'] ?? null,
                'created_source' => 'MANUAL',
            ]);

            StudentEnrollment::create([
                'student_id'       => $student->id,
                'academic_year_id' => $activeSemester->id,
                'program_id'       => $data['program_id'],
                'year_level'       => $data['year_level'],
                'student_type'     => $data['student_type'],
            ]);
        });

        return redirect()->route('admin.students.index')->with('success', 'Student enrolled successfully.');
    }

    public function downloadTemplate()
    {
        $csv = "student_id_number,last_name,first_name,name_extension,middle_name,email,college,department,program,year_level,student_type\n";
        $csv .= "2024000001,Dela Cruz,Juan,Jr.,Santos,jdelacruz@example.com,College of Engineering,Computer Engineering,BSCS,1,REGULAR\n";
        $csv .= "2024000002,Reyes,Maria,,Gomez,,College of Business,Business Admin,BSBA,2,IRREGULAR\n";

        return response($csv, 200, [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename="enrollment_template.csv"',
        ]);
    }

    public function importForm()
    {
        // Modal on the index page handles the upload — redirect rather than render a missing view.
        return redirect()->route('admin.students.index');
    }

    public function import(Request $request)
    {
        $request->validate([
            // Bug fix: mimes:csv,txt is unreliable across OS/browsers (detects text/plain or
            // application/vnd.ms-excel for .csv on Windows). Validate by extension instead.
            'file' => 'required|file|max:10240',
        ]);

        $ext = strtolower($request->file('file')->getClientOriginalExtension());
        if (!in_array($ext, ['csv', 'txt'], true)) {
            return back()->with('error', 'Please upload a .csv or .txt file.');
        }

        $activeSemester = AcademicYear::where('is_active', true)->first();
        if (!$activeSemester) {
            return back()->with('error', 'No active academic year is set. Please activate one before importing.');
        }

        $file    = fopen($request->file('file')->getRealPath(), 'r');
        $rawHdrs = fgetcsv($file);

        // Bug fix: empty file returns false from fgetcsv — catch before array_map
        if ($rawHdrs === false) {
            fclose($file);
            return back()->with('error', 'The uploaded file is empty.');
        }

        // Bug fix: strip UTF-8 BOM that Excel adds to CSV exports
        $rawHdrs[0] = ltrim($rawHdrs[0], "\xEF\xBB\xBF");

        // Normalise headers: lowercase, replace spaces/dashes with underscores
        $headers = array_map(
            fn($h) => strtolower(trim(preg_replace('/[\s\-]+/', '_', $h))),
            $rawHdrs
        );

        $colIdx = static function (string $name) use ($headers): ?int {
            $idx = array_search($name, $headers, true);
            return $idx !== false ? (int) $idx : null;
        };

        $map = [
            'student_id_number' => $colIdx('student_id_number'),
            'last_name'         => $colIdx('last_name'),
            'first_name'        => $colIdx('first_name'),
            'name_extension'    => $colIdx('name_extension'),
            'middle_name'       => $colIdx('middle_name'),
            'college'           => $colIdx('college'),
            'department'        => $colIdx('department'),
            'program'           => $colIdx('program'),
            'year_level'        => $colIdx('year_level'),
            'email'             => $colIdx('email'),
            'student_type'      => $colIdx('student_type'),
        ];

        // Bug fix: include 'program' in the required-column check.
        // Previously only the 3 identity columns were checked, so a missing
        // 'program' column silently skipped every row with "program '' not found".
        $required = ['student_id_number', 'last_name', 'first_name', 'program'];
        $missing  = array_filter($required, fn($col) => $map[$col] === null);
        if (!empty($missing)) {
            fclose($file);
            return back()->with('error',
                'CSV is missing required column(s): ' . implode(', ', $missing) .
                '. Please download the template and try again.'
            );
        }

        // Pre-load all programs once (in-memory resolver — zero extra DB queries per row).
        set_time_limit(120);
        $allPrograms = Program::with('department.college')->get();

        // ── Pass 1: parse the file in memory, validate per row, collect rows for bulk write ──
        $now             = now();
        $studentRows     = [];
        $enrollmentSeeds = [];
        $created         = 0;
        $skipped         = 0;
        $failureDetails  = [];   // structured failures for the audit/import log
        $errors          = [];   // short error strings for the flash message

        $rowIndex = 2;           // row 1 is the header
        while (($row = fgetcsv($file)) !== false) {
            $get = fn(string $key) => $map[$key] !== null ? trim((string)($row[$map[$key]] ?? '')) : '';

            $studentNumber = $get('student_id_number');
            $lastName      = $get('last_name');
            $firstName     = $get('first_name');
            $programName   = $get('program');
            $yearLevelRaw  = $get('year_level');

            $rowValues = [
                'student_id_number' => $studentNumber,
                'last_name'         => $lastName,
                'first_name'        => $firstName,
                'name_extension'    => $get('name_extension'),
                'middle_name'       => $get('middle_name'),
                'college'           => $get('college'),
                'department'        => $get('department'),
                'program'           => $programName,
                'year_level'        => $yearLevelRaw,
                'email'             => $get('email'),
                'student_type'      => $get('student_type'),
            ];

            $rowErrors = [];
            if (empty($studentNumber))          $rowErrors[] = 'Missing student_id_number.';
            if (empty($lastName))               $rowErrors[] = 'Missing last_name.';
            if (empty($firstName))              $rowErrors[] = 'Missing first_name.';
            if (empty($programName))            $rowErrors[] = 'Missing program.';
            if (!empty($studentNumber) && !preg_match('/^\d{10}$/', $studentNumber)) {
                $rowErrors[] = 'student_id_number must be exactly 10 digits.';
            }

            if (!empty($rowErrors)) {
                $skipped++;
                $errors[] = "Row {$rowIndex} ({$studentNumber}): " . implode(' ', $rowErrors);
                $failureDetails[] = ['row' => $rowIndex, 'errors' => $rowErrors, 'values' => $rowValues];
                $rowIndex++;
                continue;
            }

            $program = $this->resolveProgram($get('college'), $get('department'), $programName, $allPrograms);
            if (!$program) {
                $skipped++;
                $msg = "Program '{$programName}' not found.";
                $errors[] = "Row {$rowIndex} ({$studentNumber}): {$msg}";
                $failureDetails[] = ['row' => $rowIndex, 'errors' => [$msg], 'values' => $rowValues];
                $rowIndex++;
                continue;
            }

            $yearLevel   = (int) ($yearLevelRaw ?: 1);
            $studentType = strtoupper($get('student_type') ?: 'REGULAR');
            if (!in_array($studentType, ['REGULAR', 'IRREGULAR', 'EXTENDEE'], true)) {
                $studentType = 'REGULAR';
            }

            $studentRows[] = [
                'student_number' => $studentNumber,
                'first_name'     => $firstName,
                'last_name'      => $lastName,
                'name_extension' => $get('name_extension') ?: null,
                'middle_name'    => $get('middle_name') ?: null,
                'email'          => $get('email') ?: null,
                'created_source' => 'SSC_BULK',
                'created_at'     => $now,
                'updated_at'     => $now,
            ];

            // Keyed by student_number so duplicates inside the same file collapse to one write
            $enrollmentSeeds[$studentNumber] = [
                'program_id'   => $program->id,
                'year_level'   => $yearLevel,
                'student_type' => $studentType,
            ];

            $created++;
            $rowIndex++;
        }

        fclose($file);

        // ── Pass 2: bulk upsert (3 queries total, regardless of row count) ──
        if (!empty($studentRows)) {
            DB::transaction(function () use ($studentRows, $enrollmentSeeds, $activeSemester, $now) {
                Student::upsert(
                    $studentRows,
                    ['student_number'],
                    ['first_name', 'last_name', 'name_extension', 'middle_name', 'email', 'updated_at']
                );

                $studentIds = Student::whereIn('student_number', array_keys($enrollmentSeeds))
                    ->pluck('id', 'student_number')
                    ->all();

                $enrollmentRows = [];
                foreach ($enrollmentSeeds as $sn => $seed) {
                    $sid = $studentIds[$sn] ?? null;
                    if (!$sid) continue;
                    $enrollmentRows[] = [
                        'student_id'       => $sid,
                        'academic_year_id' => $activeSemester->id,
                        'program_id'       => $seed['program_id'],
                        'year_level'       => $seed['year_level'],
                        'student_type'     => $seed['student_type'],
                        'created_at'       => $now,
                        'updated_at'       => $now,
                    ];
                }

                if (!empty($enrollmentRows)) {
                    StudentEnrollment::upsert(
                        $enrollmentRows,
                        ['student_id', 'academic_year_id'],
                        ['program_id', 'year_level', 'student_type', 'updated_at']
                    );
                }
            });
        }

        // ── Persist ImportLog so the failures can be downloaded from the audit log view ──
        $status = match (true) {
            $created > 0 && empty($failureDetails) => 'SUCCESS',
            $created === 0                          => 'FAILED',
            default                                 => 'PARTIAL',
        };

        $importLog = ImportLog::create([
            'type'                => 'STUDENT_ENROLLMENT',
            'filename'            => $request->file('file')->getClientOriginalName(),
            'uploaded_by_user_id' => auth()->id(),
            'academic_year_id'    => $activeSemester->id,
            'rows_processed'      => $created,
            'failures_count'      => count($failureDetails),
            'failure_details'     => !empty($failureDetails) ? $failureDetails : null,
            'status'              => $status,
            'started_at'          => $now,
            'completed_at'        => now(),
        ]);

        AuditLog::create([
            'user_id'     => auth()->id(),
            'action'      => 'BULK_IMPORT_STUDENTS',
            'entity_type' => 'IMPORT_LOG',
            'entity_id'   => $importLog->id,
            'details'     => [
                'filename'       => $importLog->filename,
                'rows_processed' => $created,
                'skipped'        => $skipped,
                'failures_count' => count($failureDetails),
                'status'         => $status,
            ],
            'ip_address'  => $request->ip(),
            'timestamp'   => now(),
        ]);

        $msg = "Imported {$created} student(s)";
        if ($skipped > 0) $msg .= ", {$skipped} skipped";
        if (!empty($errors)) {
            $shown = array_slice($errors, 0, 5);
            $extra = count($errors) - count($shown);
            $msg  .= '. Errors: ' . implode('; ', $shown);
            if ($extra > 0) {
                $msg .= " … and {$extra} more. Download the failure report from the audit log entry.";
            }
        }

        return redirect()->route('admin.students.index')->with($errors ? 'warning' : 'success', $msg);
    }

    // ── Helpers ───────────────────────────────────────────────────────────

    private function resolveProgram(string $collegeName, string $deptName, string $programName, $allPrograms = null): ?Program
    {
        if (empty($programName)) {
            return null;
        }

        // When a pre-loaded collection is provided, resolve entirely in memory.
        if ($allPrograms !== null) {
            $nameUpper = strtoupper($programName);
            return $allPrograms->first(function ($p) use ($programName, $nameUpper, $deptName, $collegeName) {
                $nameMatch = strcasecmp($p->name, $programName) === 0 || $p->code === $nameUpper;
                if (!$nameMatch) return false;
                if ($deptName && $p->department) {
                    $deptMatch = strcasecmp($p->department->name, $deptName) === 0
                        || strcasecmp($p->department->code ?? '', $deptName) === 0;
                    if (!$deptMatch) return false;
                }
                if ($collegeName && $p->department?->college) {
                    $colMatch = strcasecmp($p->department->college->name, $collegeName) === 0
                        || strcasecmp($p->department->college->code ?? '', $collegeName) === 0;
                    if (!$colMatch) return false;
                }
                return true;
            });
        }

        return Program::where(fn($q) => $q->where('name', $programName)->orWhere('code', strtoupper($programName)))
            ->when($deptName, fn($q) => $q->whereHas('department', fn($dq) =>
                $dq->where('name', $deptName)->orWhere('code', strtoupper($deptName))
            ))
            ->when($collegeName, fn($q) => $q->whereHas('department.college', fn($cq) =>
                $cq->where('name', $collegeName)->orWhere('code', strtoupper($collegeName))
            ))
            ->first();
    }
}
