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

    protected static function booted()
    {
        static::creating(function ($organization) {
            $organization->validateTypeConstraints();
        });

        static::updating(function ($organization) {
            $organization->validateTypeConstraints();
        });
    }

    public function validateTypeConstraints(): void
    {
        switch ($this->type) {
            case 'COLLEGE_COUNCIL':
                if (empty($this->linked_college_id)) {
                    throw new \InvalidArgumentException('COLLEGE_COUNCIL must have a linked college.');
                }
                if (!empty($this->linked_department_id)) {
                    throw new \InvalidArgumentException('COLLEGE_COUNCIL cannot have a linked department.');
                }
                break;
            case 'CLASS_ORG':
                if (empty($this->linked_department_id)) {
                    throw new \InvalidArgumentException('CLASS_ORG must have a linked department.');
                }
                break;
            case 'UNIVERSITY_WIDE':
            case 'RESERVED':
                if (!empty($this->linked_college_id) || !empty($this->linked_department_id)) {
                    throw new \InvalidArgumentException($this->type . ' cannot have a linked college or department.');
                }
                break;
        }
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

    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }

    public function studentFines(): HasMany
    {
        return $this->hasMany(StudentFine::class);
    }
}
