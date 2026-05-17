<?php

namespace App\Exports;

use App\Models\ImportLog;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Re-emits the failed rows of an ImportLog as a downloadable spreadsheet so
 * the operator can fix and re-upload only the rejected rows.
 *
 * Reads `failure_details` (cast to array on ImportLog), which is populated by
 * StudentEnrollmentImport::failures() with this shape per entry:
 *   { row: int, errors: string[], values: { student_id_number, last_name, ... } }
 */
class FailedImportExport implements FromArray, WithHeadings, WithStyles, WithColumnWidths, WithTitle
{
    public function __construct(private ImportLog $importLog) {}

    public function array(): array
    {
        $rows = [];
        foreach ($this->importLog->failure_details ?? [] as $failure) {
            $values = is_array($failure['values'] ?? null) ? $failure['values'] : [];
            $errors = $failure['errors'] ?? [];

            $rows[] = [
                $failure['row']                   ?? '—',
                is_array($errors) ? implode("\n", $errors) : (string) $errors,
                $values['student_id_number']      ?? '',
                $values['last_name']              ?? '',
                $values['first_name']             ?? '',
                $values['middle_name']            ?? '',
                $values['name_extension']         ?? '',
                $values['college']                ?? '',
                $values['department']             ?? '',
                $values['program']                ?? '',
                $values['year_level']             ?? '',
                $values['email']                  ?? '',
                $values['student_type']           ?? '',
            ];
        }
        return $rows;
    }

    public function headings(): array
    {
        return [
            'Row #',
            'Failure Reason(s)',
            'Student ID Number',
            'Last Name',
            'First Name',
            'Middle Name',
            'Name Extension',
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
            'A' => 8,  'B' => 60, 'C' => 18, 'D' => 18, 'E' => 18,
            'F' => 18, 'G' => 14, 'H' => 24, 'I' => 24, 'J' => 24,
            'K' => 10, 'L' => 24, 'M' => 14,
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        $sheet->getStyle('B')->getAlignment()
            ->setWrapText(true)
            ->setVertical(Alignment::VERTICAL_TOP);

        return [
            1 => [
                'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill'      => ['fillType' => 'solid', 'startColor' => ['rgb' => 'B91C1C']],
                'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
            ],
        ];
    }

    public function title(): string
    {
        return 'Failed Rows';
    }
}
