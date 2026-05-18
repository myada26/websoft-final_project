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

    // [perf] Per-request memoization — file cache occasionally returns
    // __PHP_Incomplete_Class on Windows, forcing a DB refetch. This static
    // makes repeat calls within the same request free regardless of cache state.
    protected static ?self $requestCache = null;
    protected static bool $requestCacheResolved = false;

    public static function getActive(): ?self
    {
        if (self::$requestCacheResolved) {
            return self::$requestCache;
        }

        // [perf] Cache the ID only — full Eloquent models corrupt to
        // __PHP_Incomplete_Class through Windows file cache. ID lookup
        // is fast (single PK) and round-trip-stable.
        $cachedId = Cache::get(self::ACTIVE_CACHE_KEY);

        $fresh = null;

        if (is_int($cachedId) || (is_string($cachedId) && ctype_digit($cachedId))) {
            $fresh = static::find((int) $cachedId);
            if ($fresh && ! $fresh->is_active) {
                $fresh = null;
            }
        }

        if (! $fresh) {
            $fresh = static::where('is_active', true)->first();
            Cache::put(self::ACTIVE_CACHE_KEY, $fresh?->id, self::ACTIVE_CACHE_TTL);
        }

        self::$requestCache = $fresh;
        self::$requestCacheResolved = true;
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
        static::saved(function () {
            Cache::forget(self::ACTIVE_CACHE_KEY);
            self::$requestCache = null;
            self::$requestCacheResolved = false;
        });
        static::deleted(function () {
            Cache::forget(self::ACTIVE_CACHE_KEY);
            self::$requestCache = null;
            self::$requestCacheResolved = false;
        });
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
