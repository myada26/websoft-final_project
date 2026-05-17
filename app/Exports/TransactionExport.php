<?php

namespace App\Exports;

use App\Models\Transaction;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithColumnWidths;

class TransactionExport implements FromQuery, WithHeadings, WithMapping, WithColumnWidths
{
    public function __construct(
        private int     $organizationId,
        private ?int    $academicYearId = null,
        private ?string $startDate = null,
        private ?string $endDate = null,
        private ?string $transactionType = null,
        private ?string $paymentMethod = null,
        private bool    $includeVoided = false
    ) {}

    public function query(): Builder
    {
        return Transaction::query()
            ->with(['student', 'processedBy', 'feeProfile', 'academicYear'])
            ->where('organization_id', $this->organizationId)
            ->when(! $this->includeVoided, fn($q) => $q->where('is_void', false))
            ->when($this->academicYearId,   fn($q) => $q->where('academic_year_id', $this->academicYearId))
            ->when($this->startDate,        fn($q) => $q->whereDate('created_at', '>=', $this->startDate))
            ->when($this->endDate,          fn($q) => $q->whereDate('created_at', '<=', $this->endDate))
            ->when($this->transactionType,  fn($q) => $q->where('transaction_type', $this->transactionType))
            ->when($this->paymentMethod,    fn($q) => $q->where('payment_method', $this->paymentMethod))
            ->orderBy('created_at', 'desc');
    }

    public function map($transaction): array
    {
        return [
            $transaction->or_number,
            $transaction->student->student_number,
            $transaction->student->last_name . ', ' . $transaction->student->first_name,
            $transaction->transaction_type,
            // Monetary values exported as DECIMAL strings — never as floating-point (FCATS constraint)
            number_format((float) $transaction->amount_paid, 2, '.', ''),
            $transaction->payment_method,
            $transaction->reference_number ?? 'N/A',
            $transaction->processedBy?->username ?? 'N/A',
            $transaction->is_void ? 'VOIDED' : 'ACTIVE',
            $transaction->created_at->format('Y-m-d H:i'),
        ];
    }

    public function headings(): array
    {
        return [
            'OR Number',
            'Student No.',
            'Student Name',
            'Type',
            'Amount',
            'Method',
            'GCash Ref',
            'Processed By',
            'Status',
            'Date',
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 16, 'B' => 16, 'C' => 30, 'D' => 10,
            'E' => 12, 'F' => 10, 'G' => 20, 'H' => 20, 'I' => 10, 'J' => 18,
        ];
    }
}
