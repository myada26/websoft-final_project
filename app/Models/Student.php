<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory; // [Lab 7]
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Student extends Model
{
    use HasFactory; // [Lab 7]
    protected $fillable = [
        'student_number',
        'first_name',
        'last_name',
        'name_extension',
        'middle_name',
        'email',
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

    public function attendanceRecords(): HasMany
    {
        return $this->hasMany(EventAttendance::class);
    }

    public function fines(): HasMany
    {
        return $this->hasMany(StudentFine::class);
    }

    // ── Helpers ───────────────────────────────────────────────────────────

    public function getFullNameAttribute(): string
    {
        $ext    = $this->name_extension ? " {$this->name_extension}" : '';
        $middle = $this->middle_name ? " {$this->middle_name[0]}." : '';
        return "{$this->last_name}{$ext}, {$this->first_name}{$middle}";
    }

    public function enrollmentFor(int $academicYearId): ?StudentEnrollment
    {
        return $this->enrollments()->where('academic_year_id', $academicYearId)->first();
    }

    // FR-0010: Cascading Membership Logic
    // Student in Program X → member of:
    //   - Dept Society (linked to Program's Department)
    //   - College Council (linked to Program's College)
    public function getMemberOrganizations(?int $academicYearId = null)
    {
        $activeYear = $academicYearId 
            ? AcademicYear::find($academicYearId) 
            : AcademicYear::where('is_active', true)->first();

        if (!$activeYear) {
            return collect();
        }

        // Get student's enrollment for active semester
        $enrollment = $this->enrollments()
            ->where('academic_year_id', $activeYear->id)
            ->with('program.department.college')
            ->first();

        if (!$enrollment) {
            return collect();
        }

        $program = $enrollment->program;
        $department = $program->department;
        $college = $department->college;

        // Find organizations student is member of based on hierarchy
        return Organization::where(function ($query) use ($college, $department) {
            // Member of College Council (COLLEGE_COUNCIL linked to student's college)
            $query->where('type', 'COLLEGE_COUNCIL')
                ->where('linked_college_id', $college->id);
        })->orWhere(function ($query) use ($department) {
            // Member of Class Organization (CLASS_ORG linked to student's department)
            $query->where('type', 'CLASS_ORG')
                ->where('linked_department_id', $department->id);
        })->where('is_active', true)->get();
    }

    // Check if student is member of a specific organization
    public function isMemberOf(int $organizationId, ?int $academicYearId = null): bool
    {
        return $this->getMemberOrganizations($academicYearId)->contains('id', $organizationId);
    }

    public function getHasPaidThisSemesterAttribute(): bool
    {
        $activeYear = AcademicYear::where('is_active', true)->first();
        if (!$activeYear) {
            return false;
        }

        return $this->transactions()
            ->where('academic_year_id', $activeYear->id)
            ->where('transaction_type', 'FEE')
            ->where('is_void', false)
            ->exists();
    }
}
