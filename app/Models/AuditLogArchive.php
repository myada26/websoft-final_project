<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLogArchive extends Model
{
    protected $table = 'audit_logs_archive';

    const CREATED_AT = 'archived_at';
    const UPDATED_AT = null;

    protected $fillable = [
        'original_id',
        'user_id',
        'action',
        'entity_type',
        'entity_id',
        'details',
        'ip_address',
        'content_hash',
        'timestamp',
        'archived_at',
    ];

    protected function casts(): array
    {
        return [
            'details'     => 'array',
            'timestamp'   => 'datetime',
            'archived_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
