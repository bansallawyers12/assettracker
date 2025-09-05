<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Only apply security headers if enabled
        if (!config('security.headers.enabled', true)) {
            return $response;
        }

        // Force HTTPS
        if (config('security.headers.force_https', true) && !$request->secure()) {
            return redirect()->secure($request->getRequestUri(), 301);
        }

        // Security Headers
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', config('security.headers.x_frame_options', 'DENY'));
        $response->headers->set('X-XSS-Protection', config('security.headers.x_xss_protection', '1; mode=block'));
        $response->headers->set('Referrer-Policy', config('security.headers.referrer_policy', 'strict-origin-when-cross-origin'));

        // Content Security Policy
        $csp = config('security.headers.content_security_policy');
        if ($csp) {
            $response->headers->set('Content-Security-Policy', $csp);
        }

        // HSTS (HTTP Strict Transport Security)
        if ($request->secure()) {
            $hstsMaxAge = config('security.headers.hsts_max_age', 31536000);
            $hstsIncludeSubdomains = config('security.headers.hsts_include_subdomains', true) ? '; includeSubDomains' : '';
            $response->headers->set('Strict-Transport-Security', "max-age={$hstsMaxAge}{$hstsIncludeSubdomains}");
        }

        // Remove server information
        $response->headers->remove('Server');
        $response->headers->remove('X-Powered-By');

        return $response;
    }
}
