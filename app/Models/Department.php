<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;

class Department extends Model
{
    protected $fillable = [
        'college_id',
        'name',
        'code',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        $flush = fn () => Cache::forget('dropdown_departments');
        static::saved($flush);
        static::deleted($flush);
    }

    // ── Relationships ─────────────────────────────────────────────────────

    public function college(): BelongsTo
    {
        return $this->belongsTo(College::class);
    }

    public function programs(): HasMany
    {
        return $this->hasMany(Program::class);
    }

    public function organizations(): HasMany
    {
        return $this->hasMany(Organization::class, 'linked_department_id');
    }
}
