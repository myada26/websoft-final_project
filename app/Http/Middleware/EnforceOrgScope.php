<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnforceOrgScope
{
    /**
     * Reject requests where a route-bound model belongs to a different org. (FR-0006)
     *
     * Checks every resolved route binding for an organization_id and compares it
     * to the authenticated user's organization_id. Returns 403 on mismatch.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if (!$user) {
            return redirect()->route('login');
        }

        foreach ($request->route()->parameters() as $parameter) {
            if (!$parameter instanceof Model) {
                continue;
            }

            if (!isset($parameter->organization_id)) {
                continue;
            }

            if ((int) $parameter->organization_id !== (int) $user->organization_id) {
                abort(403, 'You are not authorized to access resources from another organization.');
            }
        }

        return $next($request);
    }
}
