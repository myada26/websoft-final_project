<?php

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\FineCollectionWindow;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class FineCollectionWindowService
{
    public function openWindow(Organization $organization, User $user): FineCollectionWindow
    {
        if (!$user->hasPermission('remit:create')) {
            throw new \Illuminate\Auth\Access\AuthorizationException('Only Treasurer can open fine collection window.');
        }

        $academicYear = AcademicYear::where('is_active', true)->firstOrFail();

        $existingWindow = FineCollectionWindow::where('organization_id', $organization->id)
            ->where('academic_year_id', $academicYear->id)
            ->first();

        if ($existingWindow && $existingWindow->isOpen()) {
            return $existingWindow;
        }

        return DB::transaction(function () use ($organization, $academicYear, $user) {
            $window = FineCollectionWindow::updateOrCreate(
                [
                    'organization_id' => $organization->id,
                    'academic_year_id' => $academicYear->id,
                ],
                [
                    'opened_by_user_id' => $user->id,
                    'closed_by_user_id' => null,
                    'opened_at' => now(),
                    'closed_at' => null,
                    'status' => 'OPEN',
                ]
            );

            return $window;
        });
    }

    public function closeWindow(Organization $organization, User $user): FineCollectionWindow
    {
        if (!$user->hasPermission('remit:create')) {
            throw new \Illuminate\Auth\Access\AuthorizationException('Only Treasurer can close fine collection window.');
        }

        $academicYear = AcademicYear::where('is_active', true)->firstOrFail();

        $window = FineCollectionWindow::where('organization_id', $organization->id)
            ->where('academic_year_id', $academicYear->id)
            ->where('status', 'OPEN')
            ->firstOrFail();

        return DB::transaction(function () use ($window, $user) {
            $window->update([
                'closed_by_user_id' => $user->id,
                'closed_at' => now(),
                'status' => 'CLOSED',
            ]);

            return $window;
        });
    }

    public function isWindowOpen(?int $organizationId = null): bool
    {
        $academicYear = AcademicYear::where('is_active', true)->first();

        if (!$academicYear) {
            return false;
        }

        $query = FineCollectionWindow::where('academic_year_id', $academicYear->id)
            ->where('status', 'OPEN');

        if ($organizationId) {
            $query->where('organization_id', $organizationId);
        }

        return $query->exists();
    }

    public function getWindow(?int $organizationId = null): ?FineCollectionWindow
    {
        $academicYear = AcademicYear::where('is_active', true)->first();

        if (!$academicYear) {
            return null;
        }

        $query = FineCollectionWindow::where('academic_year_id', $academicYear->id);

        if ($organizationId) {
            $query->where('organization_id', $organizationId);
        }

        return $query->first();
    }

    public function canCollectFine(?int $organizationId = null): bool
    {
        return $this->isWindowOpen($organizationId);
    }
}
