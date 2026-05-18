<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;

class College extends Model
{
    protected $fillable = [
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
        $flush = fn () => Cache::forget('dropdown_colleges');
        static::saved($flush);
        static::deleted($flush);
    }

    // ── Relationships ─────────────────────────────────────────────────────

    public function departments(): HasMany
    {
        return $this->hasMany(Department::class);
    }

    public function organizations(): HasMany
    {
        return $this->hasMany(Organization::class, 'linked_college_id');
    }
}
