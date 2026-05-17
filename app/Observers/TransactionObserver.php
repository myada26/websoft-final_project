<?php

namespace App\Observers;

use App\Models\AuditLog;
use App\Models\Transaction;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache; // [Lab 7]

class TransactionObserver
{
    public function created(Transaction $transaction): void
    {
        AuditLog::create([
            'user_id'     => Auth::id(),
            'action'      => 'TRANSACTION_CREATED',
            'entity_type' => 'TRANSACTION',
            'entity_id'   => $transaction->id,
            'details'     => [
                'or_number'        => $transaction->or_number,
                'amount_paid'      => (string) $transaction->amount_paid,
                'transaction_type' => $transaction->transaction_type,
                'payment_method'   => $transaction->payment_method,
                'student_id'       => $transaction->student_id,
                'organization_id'  => $transaction->organization_id,
            ],
            'ip_address' => request()?->ip(),
            'timestamp'  => now(),
        ]);

        // [Lab 7] Invalidate cached student/collection data after a new transaction
        $orgId      = $transaction->organization_id; // [Lab 7]
        $semesterId = $transaction->academic_year_id; // [Lab 7]
        $studentNumber = $transaction->student?->student_number; // [Lab 7]

        Cache::forget("fcats.students.enrolled.{$orgId}.{$semesterId}"); // [Lab 7]
        Cache::forget("fcats.collection.summary.{$orgId}.{$semesterId}"); // [Lab 7]
        if ($studentNumber !== null) { // [Lab 7]
            Cache::forget("fcats.student.search.{$orgId}.{$semesterId}.{$studentNumber}"); // [Lab 7]
        } // [Lab 7]
    }

    public function updated(Transaction $transaction): void
    {
        if ($transaction->wasChanged('is_void') && $transaction->is_void) {
            AuditLog::create([
                'user_id'     => Auth::id(),
                'action'      => 'TRANSACTION_VOIDED',
                'entity_type' => 'TRANSACTION',
                'entity_id'   => $transaction->id,
                'details'     => [
                    'or_number'       => $transaction->or_number,
                    'amount_paid'     => (string) $transaction->amount_paid,
                    'voided_at'       => now()->toIso8601String(),
                ],
                'ip_address' => request()?->ip(),
                'timestamp'  => now(),
            ]);
        }

        // [Lab 7] Invalidate cached student/collection data after any transaction update
        $orgId      = $transaction->organization_id; // [Lab 7]
        $semesterId = $transaction->academic_year_id; // [Lab 7]
        $studentNumber = $transaction->student?->student_number; // [Lab 7]

        Cache::forget("fcats.students.enrolled.{$orgId}.{$semesterId}"); // [Lab 7]
        Cache::forget("fcats.collection.summary.{$orgId}.{$semesterId}"); // [Lab 7]
        if ($studentNumber !== null) { // [Lab 7]
            Cache::forget("fcats.student.search.{$orgId}.{$semesterId}.{$studentNumber}"); // [Lab 7]
        } // [Lab 7]

        // [Lab 7] Voiding a transaction also invalidates the cached receipt
        if ($transaction->wasChanged('is_void')) { // [Lab 7]
            Cache::forget("fcats.transaction.receipt.{$transaction->id}"); // [Lab 7]
        } // [Lab 7]
    }
}
