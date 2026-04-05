<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    /**
     * Security headers applied to every response.
     *
     * CSP notes:
     * - 'unsafe-inline' for scripts is required by Alpine.js (inline x-data handlers).
     *   Ideal upgrade: generate per-request nonces and pass them to Alpine via @alpinejs-csp.
     * - fonts.bunny.net is the GDPR-compliant Bunny CDN used for the Figtree font.
     * - frame-ancestors 'none' supersedes X-Frame-Options; both are sent for older browsers.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=()');
        // X-XSS-Protection 0 is the modern recommendation — enabling it can introduce new vulnerabilities in older browsers.
        $response->headers->set('X-XSS-Protection', '0');
        $response->headers->set('Content-Security-Policy', $this->csp());

        // HSTS: only over HTTPS to avoid locking out HTTP-only dev environments
        if ($request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains; preload');
        }

        return $response;
    }

    private function csp(): string
    {
        return implode('; ', [
            "default-src 'self'",
            "script-src 'self' 'unsafe-inline'",   // Alpine.js requires unsafe-inline; upgrade to nonces when possible
            "style-src 'self' 'unsafe-inline' https://fonts.bunny.net",
            "font-src https://fonts.bunny.net",
            "img-src 'self' data:",
            "connect-src 'self'",
            "object-src 'none'",
            "base-uri 'self'",
            "form-action 'self'",
            "frame-ancestors 'none'",
            "upgrade-insecure-requests",
        ]);
    }
}
