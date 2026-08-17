<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Route-level RBAC gate.
 *
 * Usage: ->middleware('role:superadmin,admin')
 *
 * This is the primary defence against broken-access-control / IDOR-style
 * issues in this app: every authenticated user shares the same tables
 * (there's no per-tenant data split), so the thing that must be checked
 * on every request is not "does this record belong to you" but "does
 * your role allow this action at all". Abort with a 403 rather than a
 * 404 so legitimate users get a clear "not allowed" instead of a
 * confusing "not found".
 */
class EnsureUserHasRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user || ! $user->hasRole(...$roles)) {
            abort(403, 'You do not have permission to access this page.');
        }

        return $next($request);
    }
}
