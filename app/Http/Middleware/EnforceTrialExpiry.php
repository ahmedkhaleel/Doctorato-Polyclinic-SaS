<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Blocks accounts whose trial period has ended (trial_ends_at in the past).
 * The expired user is logged out and shown the "trial ended — contact us" page.
 * Runs on the shared web group, so it covers every panel.
 */
class EnforceTrialExpiry
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->trialExpired()) {
            return $next($request);
        }

        // Never block the expired page itself or the logout route (avoid loops).
        if ($request->routeIs('trial.expired') || $request->is('*/logout', 'logout') || $request->routeIs('*logout*')) {
            return $next($request);
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($request->expectsJson() && ! $request->header('X-Inertia')) {
            return response()->json([
                'message' => app()->getLocale() === 'ar'
                    ? 'انتهت الفترة التجريبية. يرجى التواصل مع الإدارة.'
                    : 'Your trial period has ended. Please contact the administration.',
            ], 403);
        }

        return redirect()->route('trial.expired');
    }
}
