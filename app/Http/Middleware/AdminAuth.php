<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AdminAuth
{
    /**
     * ONLY these role names may access /admin/* routes.
     *
     * SECURITY POSTURE: any visitor who is not an admin/super_admin is sent
     * to the admin LOGIN page — never silently dropped into another panel's
     * internal screens. An internal panel's tabs must only ever render after
     * the visitor authenticates with credentials valid for THAT panel.
     *
     * The session is NOT destroyed here, so a user already logged into a
     * different panel keeps that session and can simply navigate back, or
     * sign in with admin credentials (Auth::attempt swaps the session).
     *
     * Whitelist (not blacklist) is deliberate — it prevents non-admin
     * sessions from leaking into the admin panel.
     */
    private array $allowedRoles = ['admin', 'super_admin'];

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

        $roleName = $user->role?->name;

        // Only admin/super_admin roles may see ANY admin screen. Everyone
        // else is routed to the admin login — no admin tab is ever rendered
        // for them, and they are NOT dumped into another panel's internals.
        if (! in_array($roleName, $this->allowedRoles, true)) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'You do not have access to the admin panel.'], 403);
            }

            return redirect()->route('admin.login')
                ->with('error', 'يرجى تسجيل الدخول بحساب إداري للوصول إلى لوحة الإدارة.');
        }

        return $next($request);
    }
}
