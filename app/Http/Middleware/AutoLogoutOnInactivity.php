<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Symfony\Component\HttpFoundation\Response;

/**
 * Server-side half of "auto logout once inactive".
 *
 * The client-side JS timer (see layouts/fuel.blade.php) is what gives
 * users the immediate warning + redirect experience, but it can be
 * defeated by disabling JS, so this middleware is the source of truth:
 * every authenticated request checks how long it has been since the
 * session last did anything, and if that exceeds the configured idle
 * window the session is invalidated server-side, regardless of what the
 * browser did or didn't run.
 */
class AutoLogoutOnInactivity
{
    public function handle(Request $request, Closure $next): Response
    {
        $timeoutMinutes = (int) Config::get('session.idle_timeout', 15);

        if ($timeoutMinutes > 0 && Auth::check()) {
            $lastActivity = $request->session()->get('last_activity_at');

            if ($lastActivity && now()->diffInSeconds($lastActivity) > ($timeoutMinutes * 60)) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->route('login')
                    ->with('idle_logout', true)
                    ->withErrors(['email' => 'You were logged out due to inactivity.']);
            }

            $request->session()->put('last_activity_at', now());
        }

        return $next($request);
    }
}
