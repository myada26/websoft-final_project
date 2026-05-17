<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImportLog extends Model
{
    protected $fillable = [
        'type',
        'filename',
        'uploaded_by_user_id',
        'academic_year_id',
        'rows_processed',
        'failures_count',
        'failure_details',
        'status',
        'started_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'failure_details' => 'array',
            'started_at'      => 'datetime',
            'completed_at'    => 'datetime',
        ];
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by_user_id');
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function isSuccess(): bool
    {
        return $this->status === 'SUCCESS';
    }

    public function isPartial(): bool
    {
        return $this->status === 'PARTIAL';
    }
}
