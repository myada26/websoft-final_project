<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Remittance extends Model
{
    protected $fillable = [
        'control_number',
        'organization_id',
        'academic_year_id',
        'total_amount',
        'created_by_user_id',
        'verified_by_user_id',
        'accepted_by_user_id',
        'status',
        'verified_at',
        'accepted_at',
    ];

    protected function casts(): array
    {
        return [
            'total_amount'  => 'decimal:2',
            'verified_at'   => 'datetime',
            'accepted_at'   => 'datetime',
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

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by_user_id');
    }

    public function acceptedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'accepted_by_user_id');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    // ── Scopes ────────────────────────────────────────────────────────────

    public function scopePending($query)
    {
        return $query->where('status', 'PENDING');
    }

    public function scopeVerified($query)
    {
        return $query->where('status', 'VERIFIED');
    }

    public function scopeAccepted($query)
    {
        return $query->where('status', 'ACCEPTED');
    }
}
