<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Baseline security headers, applied to every response. None of this
 * requires any app state, so it's cheap enough to run globally rather
 * than opting routes in one at a time.
 *
 * The CSP intentionally allows 'unsafe-inline'/'unsafe-eval' for
 * scripts and 'unsafe-inline' for styles because the app currently
 * relies on inline <script>/<style> blocks, inline onclick/onsubmit
 * handlers, and Alpine.js (which evaluates x-data expressions via
 * `Function`, so it needs 'unsafe-eval'). That means this CSP does
 * NOT protect against XSS — its job here is narrower: constrain what
 * origins the page can load resources from and, most importantly,
 * set frame-ancestors so the app can't be embedded/click-jacked.
 * Tightening script-src further would mean moving to a nonce-based
 * CSP and removing the inline handlers, which is a bigger follow-up.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $csp = implode('; ', [
            "default-src 'self'",
            "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.jsdelivr.net",
            "style-src 'self' 'unsafe-inline'",
            "img-src 'self' data:",
            "font-src 'self' data:",
            "connect-src 'self'",
            "object-src 'none'",
            "base-uri 'self'",
            "form-action 'self'",
            "frame-ancestors 'none'",
        ]);

        $response->headers->set('Content-Security-Policy', $csp);
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        // HSTS only makes sense (and is only honoured by browsers) over
        // HTTPS — sending it on a plain-HTTP local/staging request just
        // pollutes headers, so gate it on the current request scheme.
        if ($request->secure()) {
            $response->headers->set(
                'Strict-Transport-Security',
                'max-age=31536000; includeSubDomains'
            );
        }

        return $response;
    }
}
