<?php

namespace App\Livewire;

use App\Models\AcademicYear;
use App\Models\Event;
use App\Models\FeeProfile;
use App\Models\Student;
use App\Models\StudentFine;
use App\Models\Transaction;
use Livewire\Attributes\Validate;
use Livewire\Component;

class StudentAccountabilityPortal extends Component
{
    #[Validate('required|string|max:30', message: 'Please enter a student ID number.')]
    public string $studentNumber = '';

    public bool $searched        = false;
    public bool $notFound        = false;
    public ?array $studentInfo   = null;
    public ?string $semesterName = null;
    public array $feeData        = [];
    public array $finesData      = [];
    public float $totalUnpaidFines = 0.0;

    public function checkStatus(): void
    {
        $this->validate();

        $this->searched        = true;
        $this->notFound        = false;
        $this->studentInfo     = null;
        $this->feeData         = [];
        $this->finesData       = [];
        $this->totalUnpaidFines = 0.0;

        $activeYear = AcademicYear::where('is_active', true)->first();
        $this->semesterName = $activeYear?->name ?? 'N/A';

        $student = Student::where('student_number', trim($this->studentNumber))
            ->with(['latestEnrollment.program', 'latestEnrollment.academicYear'])
            ->first();

        if (!$student) {
            $this->notFound = true;
            return;
        }

        $enrollment = $student->latestEnrollment;
        $yr         = $enrollment?->year_level;
        $suffix     = $yr ? match ((int) $yr) { 1 => 'st', 2 => 'nd', 3 => 'rd', default => 'th' } : '';

        $this->studentInfo = [
            'id'             => $student->id,
            'full_name'      => $student->full_name,
            'student_number' => $student->student_number,
            'program'        => $enrollment?->program?->name ?? '—',
            'year_level'     => $yr ? $yr . $suffix . ' Year' : '—',
        ];

        if (!$activeYear) {
            return;
        }

        // ── Fee accountability (scoped to active semester, per org) ───────
        $orgs = $student->getMemberOrganizations($activeYear->id);

        foreach ($orgs as $org) {
            $feeTxs = Transaction::where('student_id', $student->id)
                ->where('organization_id', $org->id)
                ->where('academic_year_id', $activeYear->id)
                ->where('transaction_type', 'FEE')
                ->where('is_void', false)
                ->with('feeProfile')
                ->orderBy('created_at')
                ->get();

            $paidAmount = (float) $feeTxs->sum('amount_paid');

            // Use the fee profile from the first transaction, otherwise fall back
            // to the org's active REGULAR profile to get the applicable rate
            $usedProfile = $feeTxs->first()?->feeProfile
                ?? FeeProfile::where('organization_id', $org->id)
                    ->where('category', 'REGULAR')
                    ->where('is_active', true)
                    ->first();

            $feeRate = $usedProfile ? (float) $usedProfile->amount : 0.0;
            $balance = max(0.0, $feeRate - $paidAmount);

            $status = match (true) {
                $feeRate > 0 && $paidAmount >= $feeRate => 'PAID',
                $paidAmount > 0                          => 'PARTIAL',
                default                                  => 'UNPAID',
            };

            $this->feeData[] = [
                'org_name' => $org->name,
                'fee_name' => $usedProfile?->name ?? 'Membership Fee',
                'fee_rate' => $feeRate,
                'paid'     => $paidAmount,
                'balance'  => $balance,
                'status'   => $status,
                'receipts' => $feeTxs->pluck('or_number')->filter()->values()->toArray(),
            ];
        }

        // ── Attendance fines (active semester only, FR-0030) ─────────────
        $fines = StudentFine::where('student_id', $student->id)
            ->where('academic_year_id', $activeYear->id)
            ->with([
                'event:id,name,date,time_type',
                'organization:id,name',
                'transaction:id,or_number',
            ])
            ->orderByDesc('created_at')
            ->get();

        foreach ($fines as $fine) {
            $totalSlots = $fine->event
                ? count((new Event(['time_type' => $fine->event->time_type ?? 'FULL_DAY']))->slots())
                : 4;

            $this->finesData[] = [
                'event_name'   => $fine->event?->name ?? '—',
                'event_date'   => $fine->event?->date?->format('M d, Y') ?? '—',
                'org_name'     => $fine->organization?->name ?? '—',
                'slots_missed' => $fine->slots_missed,
                'total_slots'  => $totalSlots,
                'fine_amount'  => (float) $fine->fine_amount,
                'status'       => $fine->status,
                'or_number'    => $fine->transaction?->or_number,
            ];
        }

        $this->totalUnpaidFines = (float) $fines->where('status', 'UNPAID')->sum('fine_amount');
    }

    public function render()
    {
        return view('livewire.student-accountability-portal');
    }
}
