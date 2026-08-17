<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Simple session login for the custom Blade front-end, using the same
 * `users` table and `web` guard Filament already authenticates against.
 * Log in here or at /admin/login — both share the same session.
 *
 * Every outcome here — success, wrong credentials, deactivated account
 * — is written to the audit trail (App\Models\AuditLog), since this is
 * the one place in the app where "who did what" starts.
 *
 * Two independent layers guard against brute-forcing this endpoint:
 *   1. The 'throttle:login' route middleware (fast, cache-backed,
 *      resets every minute — see AppServiceProvider::configureLoginRateLimiting()).
 *   2. The lockout below (slower, backed by the audit_logs table
 *      itself rather than the cache, so it survives a cache flush/
 *      deploy and doubles as a record an admin can actually see on
 *      the Audit Log page).
 */
class AuthController extends Controller
{
    /**
     * Failed attempts (for one email) within LOCKOUT_WINDOW_MINUTES
     * that trip the lockout.
     */
    private const MAX_ATTEMPTS = 5;

    /**
     * How far back we look for prior failures, and how long a lockout
     * lasts once tripped (a fresh blocked attempt made while locked
     * re-extends the window, so a sustained attack stays locked out
     * rather than getting a fresh attempt every 15 minutes).
     */
    private const LOCKOUT_WINDOW_MINUTES = 15;

    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $email = $credentials['email'];

        if ($this->isLockedOut($email)) {
            AuditLog::record(
                'login_failed',
                description: "Login blocked — too many failed attempts for {$email}",
                newValues: ['email' => $email],
            );

            return back()->withErrors([
                'email' => 'Too many failed login attempts. Please try again in '.self::LOCKOUT_WINDOW_MINUTES.' minutes.',
            ]);
        }

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            AuditLog::record(
                'login_failed',
                description: "Failed login attempt for {$email}",
                newValues: ['email' => $email],
            );

            return back()->withErrors(['email' => 'Those credentials do not match our records.']);
        }

        if (! Auth::user()->is_active) {
            $deactivatedUser = Auth::user();
            Auth::logout();

            AuditLog::record(
                'login_failed',
                description: "Login blocked — account deactivated ({$deactivatedUser->email})",
                newValues: ['email' => $deactivatedUser->email],
                actorId: $deactivatedUser->id,
            );

            return back()->withErrors(['email' => 'Your account has been deactivated. Contact an administrator.']);
        }

        $request->session()->regenerate();
        $request->session()->put('last_activity_at', now());

        AuditLog::record('login', Auth::user(), description: "{$request->user()->name} logged in");

        return redirect()->intended(route('fuel-dashboard'));
    }

    public function logout(Request $request)
    {
        $user = Auth::user();

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($user) {
            AuditLog::record('logout', $user, description: "{$user->name} logged out");
        }

        return redirect()->route('login');
    }

    /**
     * Whether this email has racked up MAX_ATTEMPTS+ failed logins
     * (wrong password, blocked-deactivated, or previously-blocked
     * attempts — all recorded as 'login_failed') within the last
     * LOCKOUT_WINDOW_MINUTES. Reads the audit trail directly rather
     * than keeping a separate counter, so it stays accurate even if
     * the cache is cleared and gives admins one place (Audit Log) to
     * see both the attempts and the resulting lockout.
     */
    private function isLockedOut(string $email): bool
    {
        return AuditLog::query()
            ->where('action', 'login_failed')
            ->where('new_values->email', $email)
            ->where('created_at', '>=', now()->subMinutes(self::LOCKOUT_WINDOW_MINUTES))
            ->count() >= self::MAX_ATTEMPTS;
    }
}
