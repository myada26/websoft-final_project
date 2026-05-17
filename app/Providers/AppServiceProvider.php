<?php

namespace App\Providers;

use App\Models\FeeProfile;         // [Lab 7]
use App\Models\Remittance;         // [Lab 7]
use App\Models\Transaction;        // [Lab 7]
use App\Models\VoidRequest;        // [Lab 7]
use App\Models\AcademicYear;          // [perf]
use App\Observers\FeeProfileObserver;  // [Lab 7]
use App\Observers\RemittanceObserver;  // [Lab 7]
use App\Observers\TransactionObserver; // [Lab 7]
use App\Observers\VoidRequestObserver; // [Lab 7]
use Illuminate\Support\Facades\Cache;  // [perf]
use Illuminate\Support\Facades\View;   // [perf]
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Transaction::observe(TransactionObserver::class); // [Lab 7]
        FeeProfile::observe(FeeProfileObserver::class);   // [Lab 7]
        Remittance::observe(RemittanceObserver::class);   // [Lab 7]
        VoidRequest::observe(VoidRequestObserver::class); // [Lab 7]

        // [perf] Active semester is shown on every page header. Previously the header
        // partial ran a fresh AcademicYear::first() per request (~80-150ms vs remote
        // Supabase Tokyo). View composer + 10-min cache makes navigation ~80ms faster
        // on every single page load. Cache invalidates automatically when the admin
        // sets a new active year (see AcademicYearController::setActive).
        View::composer('partials.header', function ($view) {
            $view->with('activeSem', AcademicYear::getActive());
        });

        // [perf] Sidebar reads $user->organization->name on every page; without eager
        // loading that triggers an extra SELECT per nav (~80ms remote). Pre-load it once
        // per request on the authenticated user so the relation is resolved from memory.
        View::composer(['partials.sidebar-org', 'partials.sidebar-admin'], function () {
            $user = auth()->user();
            if ($user && $user->organization_id && !$user->relationLoaded('organization')) {
                $user->load('organization');
            }
        });
    }
}
