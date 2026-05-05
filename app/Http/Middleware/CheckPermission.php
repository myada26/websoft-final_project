<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    /**
     * Usage on a route: ->middleware('permission:pos:create')
     * Aborts with 403 if the authenticated user lacks the given slug. (FR-0005)
     */
    public function handle(Request $request, Closure $next, string $slug): Response
    {
        $user = Auth::user();

        if (!$user || !$user->hasPermission($slug)) {
            abort(403, "You do not have permission to perform this action. Required: {$slug}");
        }

        return $next($request);
    }
}
