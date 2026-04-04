<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-XSS-Protection', '1; mode=block');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(self)');

        // HSTS — enforce HTTPS for 1 year (only in production)
        if (app()->environment('production')) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        // Content Security Policy — allow self, inline styles (Tailwind), and common tracking services
        // In local dev, allow Vite dev server for HMR
        $viteDevServer = app()->environment('local') ? ' http://localhost:5173 ws://localhost:5173' : '';

        $csp = implode('; ', [
            "default-src 'self'",
            "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://unpkg.com https://www.googletagmanager.com https://www.google-analytics.com https://connect.facebook.net https://snap.licdn.com https://static.ads-twitter.com https://analytics.tiktok.com" . $viteDevServer,
            "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://unpkg.com" . $viteDevServer,
            "img-src 'self' data: blob: https: http: https://*.tile.openstreetmap.org",
            "font-src 'self' https://fonts.gstatic.com data:",
            "connect-src 'self' https://www.google-analytics.com https://analytics.google.com https://www.facebook.com https://analytics.tiktok.com" . $viteDevServer,
            "frame-src 'self' https://www.google.com https://www.youtube.com",
            "object-src 'none'",
            "base-uri 'self'",
            "form-action 'self'",
        ]);
        $response->headers->set('Content-Security-Policy', $csp);

        return $response;
    }
}
