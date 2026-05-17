<?php

namespace App\Observers;

use App\Models\AuditLog;
use App\Models\FeeProfile;
use Illuminate\Support\Facades\Auth;

class FeeProfileObserver
{
    public function created(FeeProfile $feeProfile): void
    {
        AuditLog::create([
            'user_id'     => Auth::id(),
            'action'      => 'FEE_PROFILE_CREATED',
            'entity_type' => 'FEE_PROFILE',
            'entity_id'   => $feeProfile->id,
            'details'     => [
                'organization_id' => $feeProfile->organization_id,
                'label'           => $feeProfile->label ?? null,
                'amount'          => (string) $feeProfile->amount,
            ],
            'ip_address' => request()?->ip(),
            'timestamp'  => now(),
        ]);
    }

    public function updated(FeeProfile $feeProfile): void
    {
        $changed = $feeProfile->getChanges();
        unset($changed['updated_at']);

        if (empty($changed)) {
            return;
        }

        AuditLog::create([
            'user_id'     => Auth::id(),
            'action'      => 'FEE_PROFILE_UPDATED',
            'entity_type' => 'FEE_PROFILE',
            'entity_id'   => $feeProfile->id,
            'details'     => [
                'organization_id' => $feeProfile->organization_id,
                'changed_fields'  => $changed,
                'original_values' => array_intersect_key($feeProfile->getOriginal(), $changed),
            ],
            'ip_address' => request()?->ip(),
            'timestamp'  => now(),
        ]);
    }
}
