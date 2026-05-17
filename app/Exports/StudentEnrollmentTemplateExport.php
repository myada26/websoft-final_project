<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class StudentEnrollmentTemplateExport implements FromArray, WithStyles
{
    public function array(): array
    {
        return [
            [
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
            ],
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
