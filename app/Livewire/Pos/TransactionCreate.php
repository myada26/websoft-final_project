<?php

namespace App\Livewire\Pos;

use App\Models\AcademicYear;
use App\Models\FeeProfile;
use App\Models\Student;
use App\Models\StudentFine;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class TransactionCreate extends Component
{
    private const MIN_SEARCH_LENGTH = 2;
    private const SEARCH_LIMIT = 8;

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

        // Empty-query safety: short or empty input never hits the DB.
        if (mb_strlen($q) < self::MIN_SEARCH_LENGTH) {
            $this->searchResults = [];
            return;
        }

        $activeSemester = AcademicYear::getActive();
        $org            = auth()->user()->organization;

        if (! $activeSemester || ! $org) {
            $this->searchResults = [];
            return;
        }

        $hasDigit      = preg_match('/\d/', $q) === 1;
        $numericQuery  = preg_replace('/\D+/', '', $q) ?? '';
        $studentPrefix = $this->likePrefix($q);
        $numericPrefix = $numericQuery !== '' ? $this->likePrefix($numericQuery) : null;
        $namePrefix    = $this->likePrefix($q);
        $nameOperator  = DB::connection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';

        $students = Student::query()
            ->from('students')
            ->join('student_enrollments as se', function ($join) use ($activeSemester) {
                $join->on('se.student_id', '=', 'students.id')
                    ->where('se.academic_year_id', $activeSemester->id);
            })
            ->join('programs as p', 'p.id', '=', 'se.program_id')
            ->join('departments as d', 'd.id', '=', 'p.department_id')
            ->when($org->type === 'COLLEGE_COUNCIL' && $org->linked_college_id, function ($query) use ($org) {
                $query->where('d.college_id', $org->linked_college_id);
            })
            ->when($org->type === 'CLASS_ORG' && $org->linked_department_id, function ($query) use ($org) {
                $query->where('p.department_id', $org->linked_department_id);
            })
            ->where(function ($w) use ($hasDigit, $studentPrefix, $numericPrefix, $namePrefix, $nameOperator) {
                if ($hasDigit) {
                    // Handles both dashed IDs (2023-004) and normalized input (2023004).
                    $w->where('students.student_number', 'like', $studentPrefix);

                    if ($numericPrefix) {
                        $w->orWhere('students.student_number', 'like', $numericPrefix);
                        $this->orWhereNormalizedStudentNumber($w, $numericPrefix);
                    }

                    return;
                }

                // Text input: case-insensitive prefix match on names.
                $w->where('students.first_name', $nameOperator, $namePrefix)
                    ->orWhere('students.last_name', $nameOperator, $namePrefix);
            })
            ->select([
                'students.id',
                'students.first_name',
                'students.last_name',
                'students.middle_name',
                'students.name_extension',
                'students.student_number',
                'p.code as program_code',
                'se.year_level',
            ])
            ->orderBy('students.last_name')
            ->orderBy('students.first_name')
            ->limit(self::SEARCH_LIMIT)
            ->get();

        if ($students->isEmpty()) {
            $this->searchResults = [];
            return;
        }

        // Bulk paid-status check: one query instead of N exists() calls.
        $paidIds = Transaction::whereIn('student_id', $students->pluck('id'))
            ->where('organization_id', $org->id)
            ->where('academic_year_id', $activeSemester->id)
            ->where('transaction_type', 'FEE')
            ->where('is_void', false)
            ->pluck('student_id')
            ->flip();

        $this->searchResults = $students->map(function ($s) use ($paidIds) {
            return [
                'id'        => $s->id,
                'name'      => $s->full_name,
                'number'    => $s->student_number,
                'program'   => $s->program_code ?? '',
                'yearLevel' => $s->year_level !== null ? (int) $s->year_level : null,
                'hasPaid'   => $paidIds->has($s->id),
            ];
        })->values()->toArray();
    }

    // ── Actions ───────────────────────────────────────────────────────────

    public function clearStudentSearch(): void
    {
        $this->studentQuery = '';
        $this->searchResults = [];
    }

    private function likePrefix(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], trim($value)) . '%';
    }

    private function orWhereNormalizedStudentNumber($query, string $numericPrefix): void
    {
        if (DB::connection()->getDriverName() === 'pgsql') {
            $query->orWhereRaw("regexp_replace(students.student_number::text, '[^0-9]', '', 'g') like ?", [$numericPrefix]);
            return;
        }

        $query->orWhereRaw("replace(replace(replace(students.student_number, '-', ''), ' ', ''), '.', '') like ?", [$numericPrefix]);
    }

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
