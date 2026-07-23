<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Production Readiness — Security hardening (OWASP). This is the one
 * global piece of the OWASP Secure Headers baseline this project
 * didn't already have: every prior sprint's own hardening was at the
 * application layer (Form Request validation, RBAC, RLS, the
 * JSON-only error envelope in bootstrap/app.php that never leaks a
 * stack trace in production). Headers alone don't replace any of
 * that — they're defense-in-depth for the browser-facing surfaces
 * this product has (the Super Admin Console's HTML shell, and any
 * JSON response a browser might render directly). Applied globally,
 * not just to `web` routes, since the JSON API is also served
 * directly to browsers via fetch() and deserves the same baseline.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'geolocation=(), microphone=(), camera=()');

        // HSTS only makes sense once the connection is actually HTTPS — setting it
        // over plain HTTP either does nothing or, worse, is misleading about what's
        // actually protected. Real production deployments terminate TLS in front of
        // this app (nginx/load balancer); $request->secure() reflects that correctly
        // once trustProxies() (already configured in bootstrap/app.php) is honored.
        if ($request->secure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        // A real, working Content-Security-Policy for the one HTML surface this
        // product serves (the Super Admin Console shell) — API JSON responses are
        // unaffected by CSP (browsers don't execute JSON), so this is safe to set
        // unconditionally rather than branching on route.
        $response->headers->set(
            'Content-Security-Policy',
            "default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'; ".
            "img-src 'self' data:; connect-src 'self'; frame-ancestors 'none'; base-uri 'self'; form-action 'self'"
        );

        return $response;
    }
}
