<?php

namespace App\Providers;

use App\Models\User;
use App\Policies\UserPolicy;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(User::class, UserPolicy::class);

        $this->configureLoginRateLimiting();
        $this->configurePasswordDefaults();
    }

    /**
     * Throttle POST /login the same way Fortify does out of the box:
     * 5 attempts per minute, keyed by email + IP together so one
     * attacker can't lock out a real user's email by spraying bad
     * passwords from a single machine, and so a single IP can't be
     * used to hammer many different email addresses at 5/min each
     * without also being slowed down per-address.
     *
     * Applied via the 'throttle:login' middleware on the login route
     * (routes/web.php). This is a fast, cache-backed first line of
     * defence; App\Http\Controllers\AuthController layers a slower,
     * audit-log-backed lockout on top for sustained brute-force
     * attempts (see AuthController::login()).
     */
    protected function configureLoginRateLimiting(): void
    {
        RateLimiter::for('login', function (Request $request) {
            $key = Str::transliterate(
                Str::lower((string) $request->input('email')).'|'.$request->ip()
            );

            return Limit::perMinute(5)->by($key)->response(
                fn (Request $request, array $headers) => back()
                    ->withHeaders($headers)
                    ->withInput($request->only('email', 'remember'))
                    ->withErrors(['email' => 'Too many login attempts. Please wait a minute and try again.'])
                    ->with('retry_after', (int) ($headers['Retry-After'] ?? 60))
            );
        });
    }

    /**
     * Single source of truth for Password::defaults(), read by every
     * form that sets or changes a password (currently UserController).
     *
     * uncompromised() calls the (k-anonymised) HaveIBeenPwned API, which
     * needs outbound internet access — skip it outside production so
     * local dev/tests aren't dependent on network access.
     */
    protected function configurePasswordDefaults(): void
    {
        Password::defaults(function () {
            $rule = Password::min(10)->mixedCase()->numbers();

            return $this->app->isProduction() ? $rule->uncompromised() : $rule;
        });
    }
}