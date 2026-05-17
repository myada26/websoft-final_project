<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use App\Services\AuthLockoutService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function __construct(private AuthLockoutService $lockout) {}

    public function showForm(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect($this->dashboardRoute(Auth::user()));
        }

        return view('auth.login');
    }

    public function authenticate(LoginRequest $request): RedirectResponse
    {
        $user = User::where('username', $request->username)
                    ->where('is_active', true)
                    ->first();

        // Generic error — do not reveal whether username exists
        if (!$user) {
            return back()
                ->withInput(['username' => $request->username])
                ->withErrors(['username' => 'These credentials do not match our records.']);
        }

        // Lockout check (FR-0004)
        if ($this->lockout->isLocked($user)) {
            $minutes = $this->lockout->lockoutRemainingMinutes($user);
            return back()
                ->withInput(['username' => $request->username])
                ->withErrors(['username' => "Account locked. Try again in {$minutes} minute(s)."]);
        }

        // Password verification
        if (!Hash::check($request->password, $user->password_hash)) {
            $this->lockout->recordFailure($user);

            // Reload to get updated attempts count
            $user->refresh();

            if ($this->lockout->isLocked($user)) {
                return back()
                    ->withInput(['username' => $request->username])
                    ->withErrors(['username' => 'Too many failed attempts. Account locked for 15 minutes.']);
            }

            $remaining = self::MAX_ATTEMPTS - $user->failed_login_attempts;
            return back()
                ->withInput(['username' => $request->username])
                ->withErrors(['username' => "Invalid password. {$remaining} attempt(s) remaining."]);
        }

        // Success
        $this->lockout->recordSuccess($user);

        Auth::login($user);
        $request->session()->regenerate();

        return redirect($this->dashboardRoute($user));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    // ── Helpers ───────────────────────────────────────────────────────────

    private function dashboardRoute(User $user): string
    {
        if ($user->organization->type === 'UNIVERSITY_WIDE') {
            return route('admin.colleges.index');
        }

        return route('org.dashboard');
    }

    // Expose constant for use in the controller itself
    private const MAX_ATTEMPTS = 5;
}
