<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Carbon;

class AuthLockoutService
{
    private const MAX_ATTEMPTS = 5;
    private const LOCKOUT_MINUTES = 15;

    /**
     * Increment failure counter; lock the account after MAX_ATTEMPTS. (FR-0004)
     */
    public function recordFailure(User $user): void
    {
        $attempts = $user->failed_login_attempts + 1;

        $update = ['failed_login_attempts' => $attempts];

        if ($attempts >= self::MAX_ATTEMPTS) {
            $update['locked_until'] = Carbon::now()->addMinutes(self::LOCKOUT_MINUTES);
        }

        $user->update($update);
    }

    /**
     * Reset the failure counter on successful login.
     */
    public function recordSuccess(User $user): void
    {
        $user->update([
            'failed_login_attempts' => 0,
            'locked_until'          => null,
            'last_login'            => Carbon::now(),
        ]);
    }

    /**
     * True if the account is currently in the lockout window.
     */
    public function isLocked(User $user): bool
    {
        return $user->locked_until !== null && $user->locked_until->isFuture();
    }

    /**
     * Human-readable time remaining on the lockout.
     */
    public function lockoutRemainingMinutes(User $user): int
    {
        if (!$this->isLocked($user)) {
            return 0;
        }

        return (int) ceil(Carbon::now()->diffInSeconds($user->locked_until) / 60);
    }
}
