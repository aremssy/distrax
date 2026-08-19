<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Attach browser security headers to every web response.
 *
 * The CSP is intentionally limited to frame-ancestors: the Blade views rely on
 * inline scripts/styles (Alpine, Vite dev server), so a script-src policy would
 * require nonce plumbing across every layout. frame-ancestors 'none' +
 * X-Frame-Options still closes the clickjacking vector for the admin panel and
 * authenticated dashboard.
 */
class SetSecurityHeaders
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Content-Security-Policy', "frame-ancestors 'none'");

        if ($request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }
}
