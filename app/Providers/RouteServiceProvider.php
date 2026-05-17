<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Rate Limiting Tiers — Lab Activity 6, Section 7.1 (FR-0030, NFR-012)
 *
 * Tier       Limit                Scope           Endpoint
 * ─────────────────────────────────────────────────────────────────────
 * public     20 req/min per IP    Anonymous       GET /check-fees/{sn}
 * officer    120 req/min per UID  Auth officers   Students, Reports
 * pos        30 req/min + 3/sec   POS submission  POST /transactions
 * admin      500 req/min per UID  SSC Admin ops   Import, Org config
 * auth       10 req/min per IP    Login/reset     POST /login
 */
class RouteServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->configureRateLimiting();

        parent::boot();
    }

    protected function configureRateLimiting(): void
    {
        // Public student accountability portal (FR-0030)
        RateLimiter::for('public', function (Request $request) {
            return Limit::perMinute(20)->by($request->ip())
                ->response(fn() => response()->json([
                    'message'     => 'Too many requests. Please wait before trying again.',
                    'retry_after' => 60,
                ], 429));
        });

        // Authenticated officer endpoints (student search, reports)
        RateLimiter::for('officer', function (Request $request) {
            return $request->user()
                ? Limit::perMinute(120)->by($request->user()->id)
                : Limit::perMinute(20)->by($request->ip());
        });

        // POS transaction submission — per-second burst protection to prevent duplicates
        RateLimiter::for('pos', function (Request $request) {
            return [
                Limit::perMinute(30)->by($request->user()?->id ?? $request->ip()),
                Limit::perSecond(3)->by($request->user()?->id ?? $request->ip()),
            ];
        });

        // SSC Admin operations (high volume: imports, org config, fee setup)
        RateLimiter::for('admin', function (Request $request) {
            return Limit::perMinute(500)->by($request->user()?->id ?? $request->ip());
        });

        // Authentication endpoints (login + password reset)
        RateLimiter::for('auth', function (Request $request) {
            return Limit::perMinute(10)->by($request->ip())
                ->response(fn() => response()->json([
                    'message'     => 'Too many login attempts. Please try again in a minute.',
                    'retry_after' => 60,
                ], 429));
        });
    }
}
