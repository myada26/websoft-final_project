<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Event extends Model
{
    protected $fillable = [
        'organization_id',
        'academic_year_id',
        'name',
        'date',
        'venue',
        'time_type',
        'start_time',
        'end_time',
        'status',
        'created_by_user_id',
        'submitted_by_user_id',
        'submitted_at',
        'secretary_snapshot',
        'auditor_reviewed_by_user_id',
        'auditor_reviewed_at',
        'approved_by_user_id',
        'approved_at',
        'rejection_reason',
    ];

    protected function casts(): array
    {
        return [
            'date'                    => 'date',
            'submitted_at'            => 'datetime',
            'auditor_reviewed_at'     => 'datetime',
            'approved_at'             => 'datetime',
            'secretary_snapshot'      => 'array',
        ];
    }

    // ── Relationships ─────────────────────────────────────────────────────

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function submittedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by_user_id');
    }

    public function auditorReviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'auditor_reviewed_by_user_id');
    }

    public function students()
    {
        return $this->belongsToMany(Student::class, 'event_attendance')
            ->withPivot('slot', 'is_present', 'recorded_by_user_id', 'recorded_at')
            ->with('enrollments');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_user_id');
    }

    public function attendance(): HasMany
    {
        return $this->hasMany(EventAttendance::class);
    }

    public function fines(): HasMany
    {
        return $this->hasMany(StudentFine::class);
    }

    // ── Helpers ───────────────────────────────────────────────────────────

    public function slots(): array
    {
        return $this->time_type === 'HALF_DAY'
            ? ['MORNING_IN', 'MORNING_OUT']
            : ['MORNING_IN', 'MORNING_OUT', 'AFTERNOON_IN', 'AFTERNOON_OUT'];
    }

    public function isEditable(): bool
    {
        return $this->status === 'DRAFT';
    }

    public function isPendingAuditor(): bool
    {
        return $this->status === 'PENDING_APPROVAL';
    }

    public function isPendingChairperson(): bool
    {
        return $this->status === 'PENDING_CHAIRPERSON';
    }

    public function isApproved(): bool
    {
        return $this->status === 'APPROVED';
    }

    public function isRejected(): bool
    {
        return $this->status === 'REJECTED';
    }
}
