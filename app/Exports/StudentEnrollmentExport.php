<?php

namespace App\Exports;

use App\Models\StudentEnrollment;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithColumnWidths;

class StudentEnrollmentExport implements FromQuery, WithHeadings, WithMapping, WithColumnWidths
{
    public function __construct(
        private int     $academicYearId,
        private ?int    $collegeId = null,
        private ?int    $departmentId = null,
        private ?int    $programId = null
    ) {}

    public function query(): Builder
    {
        return StudentEnrollment::query()
            ->with(['student', 'program.department.college'])
            ->where('academic_year_id', $this->academicYearId)
            ->when($this->programId, fn($q) => $q->where('program_id', $this->programId))
            ->when($this->departmentId && ! $this->programId, fn($q) =>
                $q->whereHas('program', fn($pq) => $pq->where('department_id', $this->departmentId))
            )
            ->when($this->collegeId && ! $this->departmentId && ! $this->programId, fn($q) =>
                $q->whereHas('program.department', fn($dq) => $dq->where('college_id', $this->collegeId))
            )
            ->orderBy('id');
    }

    public function map($enrollment): array
    {
        $student = $enrollment->student;
        $program = $enrollment->program;
        $dept    = $program?->department;
        $college = $dept?->college;

        return [
            $student->student_number,
            $student->last_name,
            $student->first_name,
            $student->name_extension ?? '',
            $student->middle_name ?? '',
            $college?->name ?? '',
            $dept?->name ?? '',
            $program?->name ?? '',
            $enrollment->year_level,
            $student->email ?? '',
            ucfirst(strtolower($enrollment->student_type)),
        ];
    }

    public function headings(): array
    {
        return [
            'Student ID Number',
            'Last Name',
            'First Name',
            'Name Extension',
            'Middle Name',
            'College',
            'Department',
            'Program',
            'Year Level',
            'Email',
            'Student Type',
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 18, 'B' => 20, 'C' => 20, 'D' => 12, 'E' => 20,
            'F' => 25, 'G' => 25, 'H' => 35, 'I' => 10, 'J' => 28, 'K' => 14,
        ];
    }
}
