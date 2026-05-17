<?php

namespace App\Imports;

use App\Models\AcademicYear;
use App\Models\Program;
use App\Models\Student;
use App\Models\StudentEnrollment;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

/**
 * Bulk student enrollment importer (FR-0008).
 *
 * Performance design (fixes the 10k-row hang against Supabase pooler):
 *  - ToCollection (NOT ToModel) — each chunk is processed as a batch, so
 *    students + enrollments insert in 3 round-trips per chunk regardless of
 *    chunk size. The previous ToModel + firstOrCreate pattern cost 4 round-
 *    trips PER ROW, which over a pooled remote connection is ~20 minutes
 *    for 10k rows.
 *  - Pre-loaded lookup caches — active academic year and all programs
 *    (with department + college) are loaded ONCE in the constructor.
 *    Eliminates ~3 lookup queries per row.
 *  - WithChunkReading caps in-memory rows at chunkSize() at any moment.
 *  - WithBatchInserts intentionally removed — irrelevant once we own the
 *    inserts via upsert() and would cost an extra query per chunk.
 *  - One transaction per chunk (not per row, not per file) — the worker can
 *    crash mid-import without rolling back already-committed chunks, and
 *    PostgreSQL doesn't hold a write lock for the entire file's duration.
 */
class StudentEnrollmentImport implements ToCollection, WithHeadingRow, WithChunkReading
{
    use Importable;

    private int $rowCount = 0;
    private array $failures = [];

    private ?AcademicYear $activeYear;
    private array $programIndex = [];

    private string $orgType;
    private ?int $orgLinkedCollegeId;
    private ?int $orgLinkedDepartmentId;

    public function __construct(
        string $orgType = 'UNIVERSITY_WIDE',
        ?int $linkedCollegeId = null,
        ?int $linkedDepartmentId = null
    ) {
        $this->orgType                = $orgType;
        $this->orgLinkedCollegeId     = $linkedCollegeId;
        $this->orgLinkedDepartmentId  = $linkedDepartmentId;

        $this->activeYear   = AcademicYear::where('is_active', true)->first();
        $this->programIndex = $this->buildProgramIndex();
    }

    public function collection(Collection $rows): void
    {
        if (! $this->activeYear) {
            $this->failures[] = [
                'row'    => 0,
                'errors' => ['No active academic year is configured. Import aborted.'],
                'values' => [],
            ];
            return;
        }

        $now             = Carbon::now();
        $studentRows     = [];
        $enrollmentSeeds = [];

        // Spreadsheet row 1 = headers; first data row = 2.
        // rowCount + failures already seen tells us where we are in the file.
        $rowIndex = $this->rowCount + count($this->failures) + 2;

        foreach ($rows as $row) {
            $data = $row->toArray();

            $validator = Validator::make($data, $this->rules());
            if ($validator->fails()) {
                $this->failures[] = [
                    'row'    => $rowIndex,
                    'errors' => $validator->errors()->all(),
                    'values' => $data,
                ];
                $rowIndex++;
                continue;
            }

            $program = $this->resolveProgram(
                (string) ($data['program']    ?? ''),
                (string) ($data['department'] ?? ''),
                (string) ($data['college']    ?? '')
            );

            if (! $program) {
                $this->failures[] = [
                    'row'    => $rowIndex,
                    'errors' => ["Program '{$data['program']}' not found or out of organization scope."],
                    'values' => $data,
                ];
                $rowIndex++;
                continue;
            }

            $studentType = strtoupper((string) ($data['student_type'] ?? 'REGULAR'));
            if (! in_array($studentType, ['REGULAR', 'IRREGULAR', 'EXTENDEE'], true)) {
                $studentType = 'REGULAR';
            }

            $studentNumber = (string) $data['student_id_number'];

            $studentRows[] = [
                'student_number' => $studentNumber,
                'first_name'     => (string) $data['first_name'],
                'last_name'      => (string) $data['last_name'],
                'name_extension' => ($data['name_extension'] ?? null) ?: null,
                'middle_name'    => ($data['middle_name']    ?? null) ?: null,
                'email'          => ($data['email']          ?? null) ?: null,
                'created_source' => 'SSC_BULK',
                'created_at'     => $now,
                'updated_at'     => $now,
            ];

            // Keyed by student_number so duplicate rows in the same chunk collapse
            $enrollmentSeeds[$studentNumber] = [
                'program_id'   => $program->id,
                'year_level'   => (int) ($data['year_level'] ?? 1),
                'student_type' => $studentType,
            ];

            $this->rowCount++;
            $rowIndex++;
        }

        if (empty($studentRows)) {
            return;
        }

        DB::transaction(function () use ($studentRows, $enrollmentSeeds, $now) {
            // (1) Bulk upsert students by student_number — one query.
            Student::upsert(
                $studentRows,
                ['student_number'],
                ['first_name', 'last_name', 'name_extension', 'middle_name', 'email', 'updated_at']
            );

            // (2) Resolve numeric student IDs in one IN query.
            $studentIds = Student::whereIn('student_number', array_keys($enrollmentSeeds))
                ->pluck('id', 'student_number')
                ->all();

            // (3) Bulk upsert enrollments — one query.
            $enrollmentRows = [];
            foreach ($enrollmentSeeds as $studentNumber => $seed) {
                $sid = $studentIds[$studentNumber] ?? null;
                if (! $sid) continue;

                $enrollmentRows[] = [
                    'student_id'       => $sid,
                    'academic_year_id' => $this->activeYear->id,
                    'program_id'       => $seed['program_id'],
                    'year_level'       => $seed['year_level'],
                    'student_type'     => $seed['student_type'],
                    'created_at'       => $now,
                    'updated_at'       => $now,
                ];
            }

            if (! empty($enrollmentRows)) {
                StudentEnrollment::upsert(
                    $enrollmentRows,
                    ['student_id', 'academic_year_id'],
                    ['program_id', 'year_level', 'student_type', 'updated_at']
                );
            }
        });
    }

    public function chunkSize(): int
    {
        return 500;
    }

    public function rules(): array
    {
        return [
            'student_id_number' => ['required', 'digits:10'],
            'last_name'         => ['required', 'string', 'max:255'],
            'first_name'        => ['required', 'string', 'max:255'],
            'program'           => ['required', 'string'],
            'year_level'        => ['required', 'integer', 'between:1,6'],
            'student_type'      => ['required', 'in:Regular,Irregular,Extendee,REGULAR,IRREGULAR,EXTENDEE'],
        ];
    }

    public function getRowCount(): int { return $this->rowCount; }
    public function failures(): array  { return $this->failures; }

    // ─── Internal lookup helpers ──────────────────────────────────────────

    private function buildProgramIndex(): array
    {
        $index    = [];
        $programs = Program::with('department.college')->get();

        foreach ($programs as $program) {
            if ($program->name) {
                $index['n:' . strtolower(trim($program->name))][] = $program;
            }
            if ($program->code) {
                $index['c:' . strtolower(trim($program->code))][] = $program;
            }
        }

        return $index;
    }

    private function resolveProgram(string $programName, string $deptName, string $collegeName): ?Program
    {
        $programName = trim($programName);
        if ($programName === '') return null;

        $key        = strtolower($programName);
        $candidates = $this->programIndex['n:' . $key]
            ?? $this->programIndex['c:' . $key]
            ?? [];

        if (empty($candidates)) return null;

        foreach ($candidates as $program) {
            if ($deptName !== '' && $program->department) {
                $matchDept = strcasecmp($program->department->name, $deptName) === 0
                    || strcasecmp((string) $program->department->code, $deptName) === 0;
                if (! $matchDept) continue;
            }

            if ($collegeName !== '' && $program->department?->college) {
                $matchCollege = strcasecmp($program->department->college->name, $collegeName) === 0
                    || strcasecmp((string) $program->department->college->code, $collegeName) === 0;
                if (! $matchCollege) continue;
            }

            if ($this->orgType === 'COLLEGE_COUNCIL' && $this->orgLinkedCollegeId) {
                if ($program->department?->college_id !== $this->orgLinkedCollegeId) continue;
            } elseif ($this->orgType === 'CLASS_ORG' && $this->orgLinkedDepartmentId) {
                if ($program->department_id !== $this->orgLinkedDepartmentId) continue;
            }

            return $program;
        }

        return null;
    }
}
