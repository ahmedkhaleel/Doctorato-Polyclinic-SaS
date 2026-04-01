<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $path = $request->path();

        // Admin/Secretary/Doctor/Webmaster panels: use session-based locale
        if (str_starts_with($path, 'admin') || str_starts_with($path, 'secretary') || str_starts_with($path, 'doctor') || str_starts_with($path, 'webmaster')) {
            $locale = session('admin_locale', 'ar');
            app()->setLocale(in_array($locale, ['ar', 'en']) ? $locale : 'ar');
            return $next($request);
        }

        // Frontend: URL-segment based locale
        $locale = $request->segment(1);

        if (in_array($locale, ['ar', 'en'])) {
            app()->setLocale($locale);
        } else {
            app()->setLocale(config('app.locale', 'ar'));
        }

        return $next($request);
    }
}
