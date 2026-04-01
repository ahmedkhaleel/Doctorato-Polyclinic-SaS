<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class WebmasterAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::check()) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Unauthenticated.'], 401);
            }

            return redirect()->route('webmaster.login')
                ->with('error', 'Please log in to access the webmaster panel.');
        }

        $user = $request->user();

        if (! $user->is_active) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            if ($request->expectsJson()) {
                return response()->json(['message' => 'Your account has been deactivated.'], 403);
            }

            return redirect()->route('webmaster.login')
                ->with('error', 'Your account has been deactivated.');
        }

        // Check user role is webmaster
        if (! $user->role || $user->role->name !== 'webmaster') {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            if ($request->expectsJson()) {
                return response()->json(['message' => 'You do not have access to the webmaster panel.'], 403);
            }

            return redirect()->route('webmaster.login')
                ->with('error', 'You do not have access to the webmaster panel.');
        }

        return $next($request);
    }
}
