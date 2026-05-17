<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory; // [Lab 7]
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AuditLog extends Model
{
    use HasFactory; // [Lab 7]
    protected $table = 'audit_logs';

    // Schema uses 'timestamp' as the creation column; no updated_at (FR-0025 — immutable)
    const CREATED_AT = 'timestamp';
    const UPDATED_AT = null;

    // No SoftDeletes — audit logs are immutable and must never be deleted (FR-0025)

    protected $fillable = [
        'user_id',
        'action',
        'entity_type',
        'entity_id',
        'details',
        'ip_address',
        'timestamp',
    ];

    protected static function booted(): void
    {
        // Guard against sessions that stored a non-integer auth identifier (e.g. username
        // string). The user_id column is bigint — silently null it rather than crash.
        static::creating(function (AuditLog $log) {
            if (isset($log->user_id) && !is_numeric($log->user_id)) {
                $log->user_id = null;
            }
        });
    }

    protected function casts(): array
    {
        return [
            'details'   => 'array',
            'timestamp' => 'datetime',
        ];
    }

    // ── Relationships ─────────────────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Polymorphic: any entity (Transaction, Remittance, User, etc.)
    public function entity(): MorphTo
    {
        return $this->morphTo(__FUNCTION__, 'entity_type', 'entity_id');
    }
}
