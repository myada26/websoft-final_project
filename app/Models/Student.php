<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Student extends Model
{
    protected $fillable = [
        'student_number',
        'first_name',
        'last_name',
        'middle_name',
        'created_source',
    ];

    // Internal id is never exposed in URLs — route binding uses student_number (FR-0007)
    public function getRouteKeyName(): string
    {
        return 'student_number';
    }

    // ── Relationships ─────────────────────────────────────────────────────

    public function enrollments(): HasMany
    {
        return $this->hasMany(StudentEnrollment::class);
    }

    public function latestEnrollment(): HasOne
    {
        return $this->hasOne(StudentEnrollment::class)->latestOfMany();
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    // A student may hold officer accounts in multiple organizations
    public function userAccounts(): HasMany
    {
        return $this->hasMany(User::class);
    }

    // ── Helpers ───────────────────────────────────────────────────────────

    public function getFullNameAttribute(): string
    {
        $middle = $this->middle_name ? " {$this->middle_name[0]}." : '';
        return "{$this->last_name}, {$this->first_name}{$middle}";
    }

    public function enrollmentFor(int $academicYearId): ?StudentEnrollment
    {
        return $this->enrollments()->where('academic_year_id', $academicYearId)->first();
    }
}
