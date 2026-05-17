<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;

    // Explicit PK declarations — prevents Eloquent from relying on default inference,
    // which can return a string when the DB driver doesn't cast bigint columns.
    protected $primaryKey  = 'id';
    protected $keyType     = 'int';
    public    $incrementing = true;

    protected $fillable = [
        'student_id',
        'organization_id',
        'username',
        'password_hash',
        'role',
        'is_active',
        'last_login',
        'failed_login_attempts',
        'locked_until',
    ];

    protected $hidden = [
        'password_hash',
    ];

    protected function casts(): array
    {
        return [
            'is_active'              => 'boolean',
            'last_login'             => 'datetime',
            'locked_until'           => 'datetime',
            'failed_login_attempts'  => 'integer',
        ];
    }

    // ── Auth overrides ────────────────────────────────────────────────────

    // Schema uses password_hash; override so Laravel's Auth guard reads the right column
    public function getAuthPasswordName(): string
    {
        return 'password_hash';
    }

    // getAuthIdentifierName() intentionally NOT overridden — default returns 'id'.
    // Auth::id() must return the integer primary key so audit_log.user_id (bigint) is populated correctly.
    // Login uses Auth::login($user) directly (no credential lookup), so username lookup is unaffected.

    // ── Relationships ─────────────────────────────────────────────────────

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'role_permissions', 'role', 'permission_id')
                    ->where('role_permissions.role', $this->role);
    }

    public function rolePermissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'role_permissions', 'role', 'permission_id')
                    ->where('role_permissions.role', $this->role);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'processed_by_user_id');
    }

    public function requestedVoids(): HasMany
    {
        return $this->hasMany(VoidRequest::class, 'requested_by_user_id');
    }

    public function approvedVoids(): HasMany
    {
        return $this->hasMany(VoidRequest::class, 'approved_by_user_id');
    }

    public function createdRemittances(): HasMany
    {
        return $this->hasMany(Remittance::class, 'created_by_user_id');
    }

    public function verifiedRemittances(): HasMany
    {
        return $this->hasMany(Remittance::class, 'verified_by_user_id');
    }

    public function acceptedRemittances(): HasMany
    {
        return $this->hasMany(Remittance::class, 'accepted_by_user_id');
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }

    // ── Helpers ───────────────────────────────────────────────────────────

    public function hasPermission(string $slug): bool
    {
        return \Illuminate\Support\Facades\DB::table('role_permissions')
            ->join('permissions', 'role_permissions.permission_id', '=', 'permissions.id')
            ->where('role_permissions.role', $this->role)
            ->where('permissions.slug', $slug)
            ->exists();
    }

    public function hasRole(string|array $roles): bool
    {
        return in_array($this->role, (array) $roles, true);
    }

    public function isLocked(): bool
    {
        return $this->locked_until !== null && $this->locked_until->isFuture();
    }

    public function isAdmin(): bool
    {
        return $this->role === 'SSC_ADMIN';
    }

    public function canViewOrgStudents(): bool
    {
        return $this->hasRole(['CHAIRPERSON', 'TREASURER', 'COLLECTOR', 'AUDITOR', 'SECRETARY']);
    }

    public function canRecordAttendance(): bool
    {
        return $this->hasRole('SECRETARY');
    }

    public function canManageEvents(): bool
    {
        return $this->hasRole(['CHAIRPERSON', 'AUDITOR', 'SECRETARY']);
    }

    public function canEnrollStudents(): bool
    {
        return $this->hasRole('CHAIRPERSON');
    }

    public function canCreateTransactions(): bool
    {
        return $this->hasRole(['TREASURER', 'COLLECTOR']);
    }

    public function canViewTransactionHistory(): bool
    {
        return $this->hasRole(['TREASURER', 'AUDITOR']);
    }

    public function canRequestVoid(): bool
    {
        return $this->hasRole(['TREASURER', 'COLLECTOR']);
    }

    public function canApproveVoid(): bool
    {
        return $this->hasRole('CHAIRPERSON');
    }

    public function canViewVoidRequests(): bool
    {
        return $this->hasRole(['CHAIRPERSON', 'TREASURER', 'COLLECTOR', 'AUDITOR']);
    }

    public function canCreateRemittances(): bool
    {
        return $this->hasRole('TREASURER');
    }

    public function canReviewRemittances(): bool
    {
        return $this->hasRole('AUDITOR');
    }

    public function canViewRemittances(): bool
    {
        return $this->hasRole(['TREASURER', 'AUDITOR']);
    }

    public function canManageOrgUsers(): bool
    {
        return $this->hasRole('CHAIRPERSON');
    }

    public function canViewOrgAuditLogs(): bool
    {
        return $this->hasRole(['CHAIRPERSON', 'AUDITOR']);
    }
}
