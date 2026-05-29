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
     *
     * NOTE: the patient portal lives under a {locale} prefix (ar|en), so a bare
     * "/patient" is a 404. The patient destination is resolved at runtime in
     * resolveHome() with the active locale; it is intentionally absent here.
     */
    private array $roleHomes = [
        'doctor'    => '/doctor',
        'secretary' => '/secretary',
        'webmaster' => '/webmaster',
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

            // Staff roles → their own panel. Patient / unknown → admin login
            // (so an admin stuck in a patient session can sign in as admin).
            $home = $this->resolveHome($roleName, $request);
            if ($home) {
                return redirect($home)
                    ->with('error', 'ليس لديك صلاحية الوصول إلى لوحة الإدارة. تم توجيهك إلى لوحتك الخاصة.');
            }

            return redirect()->route('admin.login')
                ->with('error', 'يرجى تسجيل الدخول بحساب إداري للوصول إلى لوحة الإدارة.');
        }

        return $next($request);
    }

    /**
     * Build the correct home URL for a role bounced out of the admin panel.
     *
     * Staff roles (doctor/secretary/webmaster) have their own dedicated
     * panels, so we send them straight there — good UX, they belong there.
     *
     * The `patient` role intentionally returns null here so it falls
     * through to the admin LOGIN page instead of the patient portal.
     * Rationale: a patient never has a reason to type /admin, but an
     * ADMIN who happens to be logged into a patient account (e.g. for
     * testing) would otherwise be trapped — unable to reach the admin
     * login. Routing them to admin.login lets them sign in as admin,
     * which replaces the patient session. The session is never destroyed
     * here, so a genuine patient just sees the login and can navigate
     * back to their portal.
     */
    private function resolveHome(?string $roleName, Request $request): ?string
    {
        return $this->roleHomes[$roleName] ?? null;
    }
}
