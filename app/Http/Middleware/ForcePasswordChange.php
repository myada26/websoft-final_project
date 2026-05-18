<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ForcePasswordChange
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user?->requires_password_change && ! $request->routeIs('password.force-change*')) {
            return redirect()->route('password.force-change');
        }

        return $next($request);
    }
}
