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
     * Any other authenticated role (patient/doctor/secretary/webmaster) will be
     * bounced back to their own panel or to the admin login page WITHOUT their
     * session being destroyed.
     */
    private array $allowedRoles = ['admin', 'super_admin'];

    /**
     * Per-role redirect destinations for authenticated users who hit the wrong panel.
     */
    private array $roleHomes = [
        'doctor'    => '/doctor',
        'secretary' => '/secretary',
        'webmaster' => '/webmaster',
        'patient'   => '/patient',
    ];

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

        // Only admin/super_admin roles are allowed in the admin panel.
        if (! in_array($roleName, $this->allowedRoles, true)) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'You do not have access to the admin panel.'], 403);
            }

            // Send user to their own panel home (or admin login if unknown role)
            $home = $this->roleHomes[$roleName] ?? null;
            if ($home) {
                return redirect($home)
                    ->with('error', 'ليس لديك صلاحية الوصول إلى لوحة الإدارة. تم توجيهك إلى لوحتك الخاصة.');
            }

            return redirect()->route('admin.login')
                ->with('error', 'You do not have access to the admin panel.');
        }

        return $next($request);
    }
}
