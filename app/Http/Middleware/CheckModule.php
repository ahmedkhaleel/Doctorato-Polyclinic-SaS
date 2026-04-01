<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Services\ModuleManager;
use Symfony\Component\HttpFoundation\Response;

class CheckModule
{
    public function handle(Request $request, Closure $next, string $module): Response
    {
        if (!ModuleManager::isEnabled($module)) {
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
