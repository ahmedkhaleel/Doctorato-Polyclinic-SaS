<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class SecretaryAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::check()) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Unauthenticated.'], 401);
            }

            return redirect()->route('secretary.login')
                ->with('error', 'Please log in to access the secretary panel.');
        }

        $user = $request->user();

        if (! $user->is_active) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            if ($request->expectsJson()) {
                return response()->json(['message' => 'Your account has been deactivated.'], 403);
            }

            return redirect()->route('secretary.login')
                ->with('error', 'Your account has been deactivated.');
        }

        // Check user role is secretary
        if (! $user->role || $user->role->name !== 'secretary') {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            if ($request->expectsJson()) {
                return response()->json(['message' => 'You do not have access to the secretary panel.'], 403);
            }

            return redirect()->route('secretary.login')
                ->with('error', 'You do not have access to the secretary panel.');
        }

        return $next($request);
    }
}
