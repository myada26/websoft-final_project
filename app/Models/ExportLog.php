<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExportLog extends Model
{
    protected $fillable = [
        'type',
        'requested_by_user_id',
        'filters',
        'format',
        'status',
        'download_path',
        'row_count',
    ];

    protected function casts(): array
    {
        return [
            'filters' => 'array',
        ];
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by_user_id');
    }

    public function isReady(): bool
    {
        return $this->status === 'READY';
    }
}
