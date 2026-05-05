<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentEnrollment extends Model
{
    protected $fillable = [
        'student_id',
        'academic_year_id',
        'program_id',
        'year_level',
        'is_regular',
    ];

    protected function casts(): array
    {
        return [
            'is_regular'  => 'boolean',
            'year_level'  => 'integer',
        ];
    }

    // ── Relationships ─────────────────────────────────────────────────────

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    // ── Helpers ───────────────────────────────────────────────────────────

    // Derive fee category from enrollment (FR-0010, FR-0012)
    public function getFeeCategory(): string
    {
        if (!$this->is_regular) {
            return 'IRREGULAR';
        }

        return 'REGULAR';
    }
}
