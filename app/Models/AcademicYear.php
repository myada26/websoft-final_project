<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;

class AcademicYear extends Model
{
    // [perf] Shared cache key used by the header view composer and any controller
    // that needs the active semester. 10-minute TTL is invalidated whenever the
    // active year changes (creating/booted/AcademicYearController::setActive).
    public const ACTIVE_CACHE_KEY = 'active_academic_year';
    public const ACTIVE_CACHE_TTL = 600;

    /**
     * [perf] Cached lookup of the active academic year. Replaces hot-path
     * `AcademicYear::where('is_active', true)->first()` calls scattered through
     * controllers — these previously fired a fresh query per request against
     * remote Supabase (Tokyo), costing ~80-150ms each.
     */
    public static function getActive(): ?self
    {
        $cached = Cache::get(self::ACTIVE_CACHE_KEY);

        // [perf] Defensive: a cached value that isn't a real AcademicYear instance
        // (e.g. __PHP_Incomplete_Class from a stale entry written under a different
        // cache driver, or null sentinel) means we must refetch from the DB.
        if ($cached instanceof self) {
            return $cached;
        }

        $fresh = static::where('is_active', true)->first();
        Cache::put(self::ACTIVE_CACHE_KEY, $fresh, self::ACTIVE_CACHE_TTL);

        return $fresh;
    }

    protected $fillable = [
        'name',
        'year',
        'semester',
        'start_date',
        'end_date',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active'  => 'boolean',
            'start_date' => 'date',
            'end_date'   => 'date',
        ];
    }

    protected static function booted()
    {
        static::creating(function ($academicYear) {
            if ($academicYear->is_active) {
                static::where('is_active', true)->update(['is_active' => false]);
            }
        });

        static::updating(function ($academicYear) {
            if ($academicYear->is_active) {
                static::where('id', '!=', $academicYear->id)
                    ->where('is_active', true)
                    ->update(['is_active' => false]);
            }
        });

        // [perf] Any write to academic_years can change "which row is active",
        // so flush the shared cache here too — belt + suspenders alongside the
        // explicit Cache::forget() calls in the controller.
        static::saved(fn () => Cache::forget(self::ACTIVE_CACHE_KEY));
        static::deleted(fn () => Cache::forget(self::ACTIVE_CACHE_KEY));
    }

    // ── Scopes ────────────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // ── Relationships ─────────────────────────────────────────────────────

    public function enrollments(): HasMany
    {
        return $this->hasMany(StudentEnrollment::class);
    }

    public function remittances(): HasMany
    {
        return $this->hasMany(Remittance::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    // ── Helpers ────────────────────────────────────────────────────────────

    public static function activate(int $id): bool
    {
        $academicYear = static::findOrFail($id);
        
        // Deactivate all others
        static::where('is_active', true)->update(['is_active' => false]);
        
        // Activate selected
        $academicYear->update(['is_active' => true]);
        
        return true;
    }
}
