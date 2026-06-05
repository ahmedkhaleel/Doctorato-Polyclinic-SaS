<?php

namespace App\Http\Middleware;

use App\Services\ModuleManager;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckModule
{
    /**
     * Accepts one or more module slugs. With several (e.g.
     * `module:psychiatry,neurology`) access is granted if ANY of them is
     * enabled — used by shared surfaces like the neuropsych patient pages.
     */
    public function handle(Request $request, Closure $next, string ...$modules): Response
    {
        $anyEnabled = collect($modules)->contains(fn ($m) => ModuleManager::isEnabled($m));

        if (! $anyEnabled) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'message' => 'This module is not enabled.',
                ], 403);
            }

            // Redirect to appropriate dashboard based on route prefix
            $prefix = $request->segment(1);
            $route = match ($prefix) {
                'doctor' => 'doctor.dashboard',
                'secretary' => 'secretary.dashboard',
                'admin' => 'admin.dashboard',
                default => 'admin.dashboard',
            };

            // For patient portal (locale prefix)
            if (in_array($prefix, ['ar', 'en']) && $request->segment(2) === 'patient') {
                $route = 'patient.dashboard';

                return redirect()->route($route, ['locale' => $prefix])
                    ->with('error', 'هذا القسم غير مفعل / This module is not enabled.');
            }

            return redirect()->route($route)
                ->with('error', 'هذا القسم غير مفعل / This module is not enabled.');
        }

        return $next($request);
    }
}
