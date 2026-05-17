<?php

namespace App\Livewire\Pos;

use App\Models\AcademicYear;
use App\Models\FeeProfile;
use App\Models\Student;
use App\Models\StudentFine;
use App\Models\Transaction;
use Livewire\Component;

class TransactionCreate extends Component
{
    public int $step = 1;

    // ── Step 1: student search ────────────────────────────────────────────
    public string $studentQuery = '';
    public array  $searchResults = [];
    public ?int   $selectedStudentId = null;
    public array  $selectedStudent   = [];   // {id, name, number, program, yearLevel, hasPaid}

    // ── Step 2: fee selection ─────────────────────────────────────────────
    public string $feeType         = '';     // 'REGULAR'|'EXTENDEE'|'PARTIAL'|'FINE'
    public string $amount          = '';
    public ?int   $feeProfileId    = null;
    public string $transactionType = 'FEE';
    public ?int   $studentFineId   = null;
    public array  $unpaidFines     = [];
    public bool   $fineWindowOpen  = false;

    // ── Step 3: payment ───────────────────────────────────────────────────
    public string $paymentMethod = '';
    public string $gcashRef      = '';
    public string $remarks       = '';

    // ── Loaded on mount ───────────────────────────────────────────────────
    public array $feeProfiles = [];  // keyed by category: ['REGULAR' => [...], 'EXTENDEE' => [...]]
    public array $irregularProfiles = [];

    public function mount(): void
    {
        $orgId = auth()->user()->organization_id;

        foreach (FeeProfile::where('organization_id', $orgId)->active()->get() as $fp) {
            $profile = [
                'id'       => $fp->id,
                'name'     => $fp->name,
                'amount'   => (float) $fp->amount,
                'category' => $fp->category,
            ];

            if ($fp->category === 'IRREGULAR') {
                $this->irregularProfiles[] = $profile;
                continue;
            }

            $this->feeProfiles[$fp->category] = $profile;
        }

        $this->fineWindowOpen = app(\App\Services\FineCollectionWindowService::class)
            ->canCollectFine($orgId);
    }

    // ── Lifecycle hooks ───────────────────────────────────────────────────

    public function updatedStudentQuery(): void
    {
        $q = trim($this->studentQuery);
        if (mb_strlen($q) < 2) {
            $this->searchResults = [];
            return;
        }

        $activeSemester = AcademicYear::where('is_active', true)->first();
        $org = auth()->user()->organization;

        if (! $activeSemester || ! $org) {
            $this->searchResults = [];
            return;
        }

        $studentNumberQuery = preg_replace('/\D+/', '', $q);

        $this->searchResults = Student::whereHas('enrollments', function ($enrollmentQuery) use ($activeSemester, $org) {
                $enrollmentQuery
                    ->where('academic_year_id', $activeSemester->id)
                    ->whereHas('program.department', function ($departmentQuery) use ($org) {
                        if ($org->type === 'COLLEGE_COUNCIL') {
                            $departmentQuery->where('college_id', $org->linked_college_id);
                        } elseif ($org->type === 'CLASS_ORG') {
                            $departmentQuery->where('id', $org->linked_department_id);
                        }
                    });
            })
            ->where(function ($query) use ($q, $studentNumberQuery) {
                $query->where('student_number', 'like', "%{$q}%")
                    ->orWhere('first_name', 'like', "%{$q}%")
                    ->orWhere('last_name', 'like', "%{$q}%");

                if ($studentNumberQuery !== '') {
                    $query->orWhere('student_number', 'like', "%{$studentNumberQuery}%");
                }
            })
            ->with([
                'enrollments' => fn ($query) => $query
                    ->where('academic_year_id', $activeSemester->id)
                    ->with('program'),
            ])
            ->limit(8)
            ->get()
            ->map(function ($student) use ($activeSemester, $org) {
                $enrollment = $student->enrollments->first();

                return [
                    'id'        => $student->id,
                    'name'      => $student->full_name,
                    'number'    => $student->student_number,
                    'program'   => $enrollment?->program?->code ?? '',
                    'yearLevel' => $enrollment?->year_level,
                    'hasPaid'   => Transaction::where('student_id', $student->id)
                        ->where('organization_id', $org->id)
                        ->where('academic_year_id', $activeSemester->id)
                        ->where('transaction_type', 'FEE')
                        ->where('is_void', false)
                        ->exists(),
                ];
            })
            ->values()
            ->toArray();
    }

    // ── Actions ───────────────────────────────────────────────────────────

    public function selectStudent(int $studentId): void
    {
        $result = collect($this->searchResults)->firstWhere('id', $studentId);
        if (! $result) {
            return;
        }

        $this->selectedStudentId = $studentId;
        $this->selectedStudent   = $result;
        $this->studentQuery      = '';
        $this->searchResults     = [];

        $orgId = auth()->user()->organization_id;

        $this->unpaidFines = StudentFine::where('student_id', $studentId)
            ->where('organization_id', $orgId)
            ->where('status', 'UNPAID')
            ->with('event:id,name,date')
            ->get()
            ->map(fn ($f) => [
                'id'        => $f->id,
                'eventName' => $f->event?->name ?? 'Event Fine',
                'amount'    => (float) $f->fine_amount,
            ])
            ->values()
            ->toArray();

        // Reset fee selection when a new student is chosen
        $this->feeType         = '';
        $this->amount          = '';
        $this->feeProfileId    = null;
        $this->transactionType = 'FEE';
        $this->studentFineId   = null;

        $this->step = 2;
    }

    public function selectFeeType(string $type): void
    {
        $this->feeType         = $type;
        $this->amount          = '';
        $this->feeProfileId    = null;
        $this->transactionType = 'FEE';
        $this->studentFineId   = null;

        if ($type === 'REGULAR') {
            $this->amount       = number_format($this->feeProfiles['REGULAR']['amount'] ?? 1000, 2, '.', '');
            $this->feeProfileId = $this->feeProfiles['REGULAR']['id'] ?? null;

        } elseif ($type === 'EXTENDEE') {
            $this->amount       = number_format($this->feeProfiles['EXTENDEE']['amount'] ?? 500, 2, '.', '');
            $this->feeProfileId = $this->feeProfiles['EXTENDEE']['id'] ?? null;

        } elseif ($type === 'FINE') {
            $this->transactionType = 'FINE';
            $this->feeProfileId    = null;
            $total = collect($this->unpaidFines)->sum('amount');
            $this->amount = $total > 0 ? number_format($total, 2, '.', '') : '';
            if (count($this->unpaidFines) === 1) {
                $this->studentFineId = $this->unpaidFines[0]['id'];
            }
        }
    }

    public function selectFeeProfile(int $profileId): void
    {
        $profile = collect($this->irregularProfiles)->firstWhere('id', $profileId);

        if (! $profile) {
            return;
        }

        $this->feeType = 'IRREGULAR';
        $this->amount = number_format($profile['amount'], 2, '.', '');
        $this->feeProfileId = $profile['id'];
        $this->transactionType = 'FEE';
        $this->studentFineId = null;
    }

    public function goToStep(int $n): void
    {
        if ($n >= 1 && $n <= 4) {
            $this->step = $n;
        }
    }

    // ── Computed props ────────────────────────────────────────────────────

    public function getIsTreasurerProperty(): bool
    {
        return auth()->user()->role === 'TREASURER';
    }

    public function getAmountFloatProperty(): float
    {
        return round((float) $this->amount, 2);
    }

    public function getMaxPartialAmountProperty(): float
    {
        return (float) ($this->feeProfiles['REGULAR']['amount'] ?? 1000);
    }

    public function getSelectedFeeNameProperty(): string
    {
        return match ($this->feeType) {
            'REGULAR'  => $this->feeProfiles['REGULAR']['name']  ?? 'Membership Fee',
            'EXTENDEE' => $this->feeProfiles['EXTENDEE']['name'] ?? 'Extendee Fee',
            'IRREGULAR' => collect($this->irregularProfiles)->firstWhere('id', $this->feeProfileId)['name'] ?? 'Irregular Fee',
            'FINE'     => 'Fine Payment',
            default    => '—',
        };
    }

    public function render()
    {
        return view('livewire.pos.transaction-create');
    }
}
