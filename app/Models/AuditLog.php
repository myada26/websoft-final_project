<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AuditLog extends Model
{
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
