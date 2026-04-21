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

        // Standard hardening headers
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(self), microphone=(self), geolocation=(self)');

        // Cross-origin isolation for Spectre-class mitigations. "same-origin-allow-popups"
        // is used instead of strict same-origin so OAuth/payment popups from Stripe etc.
        // can still postMessage back to us.
        $response->headers->set('Cross-Origin-Opener-Policy', 'same-origin-allow-popups');
        $response->headers->set('Cross-Origin-Resource-Policy', 'same-site');

        // HSTS — enforce HTTPS for 1 year, only in production.
        if (app()->environment('production')) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        // ─── Content Security Policy ───────────────────────────────
        // Kept additive rather than strict nonce-based because the
        // Tailwind + Inertia stack uses inline styles and 'unsafe-eval'
        // for Vue template compilation in some paths.
        $viteDev = app()->environment('local') ? ' http://localhost:5173 ws://localhost:5173' : '';

        $csp = implode('; ', [
            "default-src 'self'",
            // Scripts: analytics, pixels, Stripe.js if we decide to load it client-side.
            "script-src 'self' 'unsafe-inline' 'unsafe-eval' "
                . "https://unpkg.com "
                . "https://www.googletagmanager.com https://www.google-analytics.com "
                . "https://connect.facebook.net https://snap.licdn.com "
                . "https://static.ads-twitter.com https://analytics.tiktok.com "
                . "https://js.stripe.com"
                . $viteDev,
            "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://unpkg.com" . $viteDev,
            "img-src 'self' data: blob: https: http: https://*.tile.openstreetmap.org",
            "font-src 'self' https://fonts.gstatic.com data:",
            // Telemedicine: Agora needs realtime connections. Payments: Stripe/Paymob
            // need connect for tokenisation.
            "connect-src 'self' "
                . "https://www.google-analytics.com https://analytics.google.com https://www.facebook.com https://analytics.tiktok.com "
                . "https://*.agora.io wss://*.agora.io "
                . "https://api.stripe.com https://accept.paymob.com"
                . $viteDev,
            // Frames: embedded Stripe checkout, Paymob iframe, YouTube.
            "frame-src 'self' https://www.google.com https://www.youtube.com "
                . "https://js.stripe.com https://hooks.stripe.com https://checkout.stripe.com "
                . "https://accept.paymob.com",
            // Can't be framed by anyone — modern equivalent of X-Frame-Options DENY.
            "frame-ancestors 'none'",
            "object-src 'none'",
            "base-uri 'self'",
            "form-action 'self' https://checkout.stripe.com https://accept.paymob.com",
            // Upgrade any http: sub-resources on https: pages (prevents mixed-content).
            'upgrade-insecure-requests',
        ]);
        $response->headers->set('Content-Security-Policy', $csp);

        return $response;
    }
}
