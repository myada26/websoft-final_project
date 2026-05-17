<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory; // [Lab 7]
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Transaction extends Model
{
    use HasFactory; // [Lab 7]
    protected $fillable = [
        'or_number',
        'organization_id',
        'academic_year_id',
        'student_id',
        'processed_by_user_id',
        'amount_paid',
        'payment_method',
        'reference_number',
        'fee_profile_id',
        'transaction_type',
        'student_fine_id',
        'remittance_id',
        'is_void',
    ];

    protected function casts(): array
    {
        return [
            'amount_paid' => 'decimal:2',
            'is_void'     => 'boolean',
        ];
    }

    // ── Relationships ─────────────────────────────────────────────────────

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function processedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by_user_id');
    }

    // Nullable — NULL for FINE-type transactions (FR-0013)
    public function feeProfile(): BelongsTo
    {
        return $this->belongsTo(FeeProfile::class);
    }

    // Nullable — NULL until included in a remittance batch (FR-0020)
    public function remittance(): BelongsTo
    {
        return $this->belongsTo(Remittance::class);
    }

    public function voidRequest(): HasOne
    {
        return $this->hasOne(VoidRequest::class);
    }

    // Nullable — linked when POS officer selects a specific StudentFine to pay (FR-0029)
    public function studentFine(): BelongsTo
    {
        return $this->belongsTo(StudentFine::class);
    }

    // ── Scopes ────────────────────────────────────────────────────────────

    public function scopeUnremitted($query)
    {
        return $query->whereNull('remittance_id')->where('is_void', false);
    }

    public function scopeForOrg($query, int $organizationId)
    {
        return $query->where('organization_id', $organizationId);
    }

    public function scopeForSemester($query, int $academicYearId)
    {
        return $query->where('academic_year_id', $academicYearId);
    }

    // ── Helpers ───────────────────────────────────────────────────────────

    public function isFee(): bool
    {
        return $this->transaction_type === 'FEE';
    }

    public function isFine(): bool
    {
        return $this->transaction_type === 'FINE';
    }

    public function isGcash(): bool
    {
        return $this->payment_method === 'GCASH';
    }

    public function getAmountAttribute(): string
    {
        return $this->amount_paid;
    }
}
