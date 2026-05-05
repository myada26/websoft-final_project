<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateSession
{
    private const TIMEOUT_SECONDS = 600; // 10 minutes (NFR-005)

    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $lastActivity = $request->session()->get('last_activity');

        if ($lastActivity && (time() - $lastActivity) > self::TIMEOUT_SECONDS) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')
                ->withErrors(['session' => 'Your session has expired due to inactivity. Please log in again.']);
        }

        $request->session()->put('last_activity', time());

        return $next($request);
    }
}
