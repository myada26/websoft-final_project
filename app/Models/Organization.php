<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Organization extends Model
{
    protected $fillable = [
        'name',
        'type',
        'linked_college_id',
        'linked_department_id',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    // ── Relationships ─────────────────────────────────────────────────────

    public function college(): BelongsTo
    {
        return $this->belongsTo(College::class, 'linked_college_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'linked_department_id');
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function feeProfiles(): HasMany
    {
        return $this->hasMany(FeeProfile::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function remittances(): HasMany
    {
        return $this->hasMany(Remittance::class);
    }

    // One counter row per org — used only via OrSequenceService
    public function orSequence(): HasOne
    {
        return $this->hasOne(OrSequence::class);
    }
}
