<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrSequence extends Model
{
    protected $table = 'or_sequences';

    // organization_id is both PK and FK — not auto-incrementing
    protected $primaryKey = 'organization_id';
    public $incrementing = false;

    // Only updated_at is stored; no created_at column
    const CREATED_AT = null;

    protected $fillable = [
        'organization_id',
        'last_or_number',
    ];

    protected function casts(): array
    {
        return [
            'last_or_number' => 'integer',
        ];
    }

    // ── Relationships ─────────────────────────────────────────────────────

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}
