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
 */
class AuthController extends Controller
{
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

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            AuditLog::record(
                'login_failed',
                description: "Failed login attempt for {$credentials['email']}"
            );

            return back()->withErrors(['email' => 'Those credentials do not match our records.']);
        }

        if (! Auth::user()->is_active) {
            $deactivatedUser = Auth::user();
            Auth::logout();

            AuditLog::record(
                'login_failed',
                description: "Login blocked — account deactivated ({$deactivatedUser->email})",
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
}
