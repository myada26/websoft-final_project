<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Permission extends Model
{
    // Immutable — seeded once, never modified by the app
    public $timestamps = false;

    protected $fillable = [
        'slug',
        'description',
        'module',
    ];

    // ── Relationships ─────────────────────────────────────────────────────

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_permissions')
                    ->withPivot('granted_at');
    }
}
