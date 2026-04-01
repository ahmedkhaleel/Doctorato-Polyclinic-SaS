<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CaptureUtmParameters
{
    /**
     * UTM parameters to capture from the URL query string.
     */
    protected array $utmParams = [
        'utm_source',
        'utm_medium',
        'utm_campaign',
        'utm_term',
        'utm_content',
    ];

    /**
     * Cookie lifetime in minutes (30 days).
     */
    protected int $cookieLifetime = 43200;

    /**
     * Capture UTM parameters from URL and store in cookies for lead attribution.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Only capture on GET requests to frontend pages
        if (! $request->isMethod('GET')) {
            return $response;
        }

        // Capture UTM parameters if present in URL
        foreach ($this->utmParams as $param) {
            if ($request->has($param)) {
                $response->cookie(
                    $param,
                    $request->input($param),
                    $this->cookieLifetime,
                    '/',
                    null,
                    $request->secure(),
                    false // httpOnly = false so JS can read if needed
                );
            }
        }

        // Capture the landing page (first page visited with UTM params)
        if ($request->hasAny($this->utmParams) && ! $request->cookie('landing_page')) {
            $response->cookie(
                'landing_page',
                $request->fullUrl(),
                $this->cookieLifetime,
                '/',
                null,
                $request->secure(),
                false
            );
        }

        return $response;
    }
}
