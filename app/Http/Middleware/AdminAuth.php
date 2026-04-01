<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AdminAuth
{
    /**
     * Roles that have their own separate panels and are NOT allowed in the admin panel.
     */
    private array $separatePanelRoles = ['secretary', 'doctor', 'webmaster'];

    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::check()) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Unauthenticated.'], 401);
            }

            return redirect()->route('admin.login')
                ->with('error', __('auth.unauthenticated'));
        }

        $user = $request->user();

        if (! $user->is_active) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            if ($request->expectsJson()) {
                return response()->json(['message' => 'Your account has been deactivated.'], 403);
            }

            return redirect()->route('admin.login')
                ->with('error', 'Your account has been deactivated.');
        }

        // Block roles that have their own separate panels from accessing admin
        if ($user->role && in_array($user->role->name, $this->separatePanelRoles)) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            if ($request->expectsJson()) {
                return response()->json(['message' => 'You do not have access to the admin panel.'], 403);
            }

            return redirect()->route('admin.login')
                ->with('error', 'You do not have access to the admin panel. Please use your designated panel.');
        }

        return $next($request);
    }
}
