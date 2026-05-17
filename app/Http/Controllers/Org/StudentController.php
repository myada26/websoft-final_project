<?php

namespace App\Http\Controllers\Org;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Department;
use App\Models\Program;
use App\Models\Student;
use App\Models\StudentEnrollment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StudentController extends Controller
{
    public function index()
    {
        $org            = auth()->user()->organization;
        $orgType        = $org->type;
        $activeSemester = AcademicYear::getActive(); // [perf] cached helper

        // Allowed sort fields and their SQL column references (FR-0014)
        $sortable = [
            'dept'    => 'departments.name',
            'program' => 'programs.name',
            'year'    => 'student_enrollments.year_level',
        ];

        $students = Student::select('students.*')
            ->join('student_enrollments', fn($j) => $j
                ->on('students.id', '=', 'student_enrollments.student_id')
                ->when($activeSemester, fn($j2) => $j2->where('student_enrollments.academic_year_id', $activeSemester->id))
            )
            ->join('programs',    'student_enrollments.program_id', '=', 'programs.id')
            ->join('departments', 'programs.department_id',          '=', 'departments.id')
            ->join('colleges',    'departments.college_id',          '=', 'colleges.id')
            // Org scope (FR-0006) — always applied first, filters below are sub-selections
            ->when($orgType === 'COLLEGE_COUNCIL' && $org->linked_college_id,
                fn($q) => $q->where('departments.college_id', $org->linked_college_id)
            )
            ->when($orgType === 'CLASS_ORG' && $org->linked_department_id,
                fn($q) => $q->where('programs.department_id', $org->linked_department_id)
            )
            // Cascading filter bar params (FR-0014)
            ->when(request('filter_dept'),    fn($q, $v) => $q->where('departments.id', $v))
            ->when(request('filter_program'), fn($q, $v) => $q->where('programs.id', $v))
            ->when(request('filter_year'),    fn($q, $v) => $q->where('student_enrollments.year_level', $v))
            ->when(request('search'), fn($q, $s) => $q->where(fn($w) =>
                $w->where('students.first_name',      'like', "%{$s}%")
                  ->orWhere('students.last_name',     'like', "%{$s}%")
                  ->orWhere('students.student_number', 'like', "%{$s}%")
            ));

        // Multi-field sort (FR-0014)
        $hasSorted = false;
        foreach ($sortable as $param => $sqlCol) {
            $dir = request("sort_{$param}");
            if (in_array($dir, ['asc', 'desc'], true)) {
                $students->orderBy($sqlCol, $dir);
                $hasSorted = true;
            }
        }
        if (!$hasSorted) {
            $students->orderBy('students.last_name')->orderBy('students.first_name');
        }

        // Eager-load relationships used in the view and CSV export (NFR-002 — eliminates N+1)
        $students->with('latestEnrollment.program.department');

        if (request()->query('export') === 'csv') {
            $all = $students->get();
            $semester = $activeSemester?->name ?? 'N/A';
            $orgName  = $org->name;
            $filename = 'students_roster_' . now()->format('Ymd_His') . '.csv';

            $callback = function () use ($all, $semester, $orgName) {
                $out = fopen('php://output', 'w');
                fputcsv($out, ['FCATS Student Roster', $orgName, 'Semester: ' . $semester, 'Exported: ' . now()->toDateTimeString()]);
                fputcsv($out, []);
                fputcsv($out, ['Student ID', 'Last Name', 'First Name', 'Middle Name', 'Name Ext.', 'Email', 'Program', 'Year Level', 'Student Type', 'Payment Status']);
                foreach ($all as $s) {
                    $enr = $s->latestEnrollment;
                    fputcsv($out, [
                        $s->student_number,
                        $s->last_name,
                        $s->first_name,
                        $s->middle_name ?? '',
                        $s->name_extension ?? '',
                        $s->email ?? '',
                        $enr?->program?->name ?? '',
                        $enr?->year_level ?? '',
                        $enr?->student_type ?? '',
                        ($s->hasPaidThisSemester ?? false) ? 'Paid' : 'Pending',
                    ]);
                }
                fclose($out);
            };

            return response()->streamDownload($callback, $filename, [
                'Content-Type' => 'text/csv',
            ]);
        }

        $students = $students->paginate(25);

        // Programs scoped to this org for the enroll modal
        $programs = Program::whereHas('department', fn($q) =>
            $orgType === 'COLLEGE_COUNCIL'
                ? $q->where('college_id', $org->linked_college_id)
                : $q->where('id', $org->linked_department_id)
        )->orderBy('name')->get();

        // Scoped JSON data for Alpine.js cascading filter bar (FR-0014)
        $allDepartments = match ($orgType) {
            'COLLEGE_COUNCIL' => Department::where('college_id', $org->linked_college_id)
                ->orderBy('name')->get(['id', 'name', 'code', 'college_id']),
            default => collect(),
        };

        $allPrograms = match ($orgType) {
            'COLLEGE_COUNCIL' => Program::whereHas('department',
                fn($q) => $q->where('college_id', $org->linked_college_id)
            )->orderBy('name')->get(['id', 'name', 'code', 'department_id']),
            'CLASS_ORG' => Program::where('department_id', $org->linked_department_id)
                ->orderBy('name')->get(['id', 'name', 'code', 'department_id']),
            default => collect(),
        };

        $hasFilters = request()->hasAny(['filter_dept', 'filter_program', 'filter_year', 'search']);

        return view('org.students.index', compact(
            'students', 'programs', 'activeSemester',
            'orgType', 'allDepartments', 'allPrograms', 'hasFilters'
        ));
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
            return back()->with('error', 'No active academic year found.');
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

        return redirect()->route('org.students.index')->with('success', 'Student enrolled successfully.');
    }

    public function create()
    {
        return redirect()->route('org.students.index');
    }

    public function edit(Student $student)
    {
        return view('org.students.edit', compact('student'));
    }

    public function update(Request $request, Student $student)
    {
        $data = $request->validate([
            'first_name'     => 'required|string|max:100',
            'last_name'      => 'required|string|max:100',
            'name_extension' => 'nullable|string|max:20',
            'middle_name'    => 'nullable|string|max:100',
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
        $request->validate([
            'file' => 'required|file|max:10240',
        ]);

        $ext = strtolower($request->file('file')->getClientOriginalExtension());
        if (!in_array($ext, ['csv', 'txt'], true)) {
            return back()->with('error', 'Please upload a .csv or .txt file.');
        }

        $org = auth()->user()->organization;

        $activeSemester = AcademicYear::where('is_active', true)->first();
        if (!$activeSemester) {
            return back()->with('error', 'No active academic year is set. Please contact the admin.');
        }

        $file    = fopen($request->file('file')->getRealPath(), 'r');
        $rawHdrs = fgetcsv($file);

        // Bug fix: empty file returns false — catch before array_map
        if ($rawHdrs === false) {
            fclose($file);
            return back()->with('error', 'The uploaded file is empty.');
        }

        // Bug fix: strip UTF-8 BOM that Excel adds to CSV exports
        $rawHdrs[0] = ltrim($rawHdrs[0], "\xEF\xBB\xBF");

        // Normalise headers: lowercase, spaces/dashes → underscores
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

        // Bug fix: validate 'program' as required (previously only the 3 identity
        // columns were checked, silently skipping all rows when program was missing).
        $required = ['student_id_number', 'last_name', 'first_name', 'program'];
        $missing  = array_filter($required, fn($col) => $map[$col] === null);
        if (!empty($missing)) {
            fclose($file);
            return back()->with('error',
                'CSV is missing required column(s): ' . implode(', ', $missing) .
                '. Please download the template and try again.'
            );
        }

        // Performance: pre-load scoped programs once before the loop.
        // Per-row resolveOrgProgram() queries cost ~30-50 ms each against remote Supabase;
        // 100 rows × 3 queries × 40 ms = 12 s minimum, easily exceeding the 30 s limit.
        set_time_limit(300);
        $scopedPrograms = Program::with('department')
            ->when($org->type === 'COLLEGE_COUNCIL' && $org->linked_college_id,
                fn($q) => $q->whereHas('department', fn($dq) =>
                    $dq->where('college_id', $org->linked_college_id)
                )
            )
            ->when($org->type === 'CLASS_ORG' && $org->linked_department_id,
                fn($q) => $q->where('department_id', $org->linked_department_id)
            )
            ->get();

        $created = 0;
        $skipped = 0;
        $errors  = [];

        DB::transaction(function () use (
            $file, $map, $scopedPrograms, $activeSemester, &$created, &$skipped, &$errors
        ) {
            while (($row = fgetcsv($file)) !== false) {
                try {
                    $get = fn(string $key) => $map[$key] !== null ? trim($row[$map[$key]] ?? '') : '';

                    $studentNumber = $get('student_id_number');
                    $lastName      = $get('last_name');
                    $firstName     = $get('first_name');

                    if (empty($studentNumber) || empty($lastName) || empty($firstName)) {
                        $skipped++;
                        continue;
                    }

                    // Resolve program from in-memory collection — zero extra DB queries per row
                    $program = $this->resolveOrgProgram($get('program'), $scopedPrograms);
                    if (!$program) {
                        $errors[] = "Row {$studentNumber}: program '{$get('program')}' not found in your org's scope.";
                        $skipped++;
                        continue;
                    }

                    $yearLevel   = (int) ($get('year_level') ?: 1);
                    $studentType = strtoupper($get('student_type') ?: 'REGULAR');
                    if (!in_array($studentType, ['REGULAR', 'IRREGULAR', 'EXTENDEE'], true)) {
                        $studentType = 'REGULAR';
                    }

                    $student = Student::firstOrCreate(
                        ['student_number' => $studentNumber],
                        [
                            'first_name'     => $firstName,
                            'last_name'      => $lastName,
                            'name_extension' => $get('name_extension') ?: null,
                            'middle_name'    => $get('middle_name') ?: null,
                            'email'          => $get('email') ?: null,
                            'created_source' => 'BULK',
                        ]
                    );

                    StudentEnrollment::firstOrCreate(
                        ['student_id' => $student->id, 'academic_year_id' => $activeSemester->id],
                        [
                            'program_id'   => $program->id,
                            'year_level'   => $yearLevel,
                            'student_type' => $studentType,
                        ]
                    );

                    $created++;
                } catch (\Exception $e) {
                    $errors[] = $e->getMessage();
                }
            }
        });

        fclose($file);

        $msg = "Imported {$created} student(s)";
        if ($skipped > 0) $msg .= ", {$skipped} skipped";
        if (!empty($errors)) {
            $shown = array_slice($errors, 0, 5);
            $extra = count($errors) - count($shown);
            $msg  .= '. Errors: ' . implode('; ', $shown);
            if ($extra > 0) {
                $msg .= " … and {$extra} more.";
            }
        }

        return redirect()->route('org.students.index')->with($errors ? 'warning' : 'success', $msg);
    }

    // ── Helpers ───────────────────────────────────────────────────────────

    // $scopedPrograms is an already-filtered Eloquent Collection (pre-loaded before the loop).
    private function resolveOrgProgram(string $programName, $scopedPrograms): ?Program
    {
        if (empty($programName)) {
            return null;
        }

        $nameUpper = strtoupper($programName);
        return $scopedPrograms->first(
            fn($p) => strcasecmp($p->name, $programName) === 0 || $p->code === $nameUpper
        );
    }
}
