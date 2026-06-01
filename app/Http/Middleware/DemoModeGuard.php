<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Read-mostly guard for demo accounts (is_demo). Demo users may view, add and
 * edit freely so prospects can explore the system, but they can NEVER:
 *   1. delete anything, or
 *   2. change core system settings (settings, users, roles, modules, branches,
 *      AI configuration / prompts).
 * GET requests are always allowed. Applied on the shared `web` group, so it
 * covers all panels (admin / doctor / secretary / patient).
 */
class DemoModeGuard
{
    /** Route-name fragments whose MUTATIONS are core-settings (blocked for demo). */
    private const PROTECTED_NAME_FRAGMENTS = [
        'settings.update', 'settings.test', 'settings.testSms',
        'crm-settings', '.settings.', 'telemedicine',
        'modules', 'roles.', 'permissions.', 'users.',
        'branches.store', 'branches.update', 'branches.members', 'branches.destroy',
        'ai.settings', 'ai.features', 'ai.prompts', 'ai.patient-assistant.rebuild',
        'switch-branch',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! ($user->is_demo ?? false)) {
            return $next($request);
        }

        // Read-only requests are always fine.
        if (in_array($request->method(), ['GET', 'HEAD', 'OPTIONS'], true)) {
            return $next($request);
        }

        $name = (string) optional($request->route())->getName();
        $path = '/'.ltrim($request->path(), '/');

        if ($this->isDelete($request, $name, $path)) {
            return $this->blocked($request, 'delete');
        }

        if ($this->isProtectedSettings($name)) {
            return $this->blocked($request, 'settings');
        }

        return $next($request);
    }

    private function isDelete(Request $request, string $name, string $path): bool
    {
        if ($request->isMethod('DELETE')) {
            return true;
        }
        if (preg_match('/\.(destroy|delete|bulk-delete|forceDelete|empty-trash)$/i', $name)) {
            return true;
        }

        return (bool) preg_match('#/(delete|destroy)(/|$)#i', $path);
    }

    private function isProtectedSettings(string $name): bool
    {
        if ($name === '') {
            return false;
        }
        foreach (self::PROTECTED_NAME_FRAGMENTS as $frag) {
            if (str_contains($name, $frag)) {
                return true;
            }
        }

        return false;
    }

    private function blocked(Request $request, string $kind): Response
    {
        $message = $kind === 'delete'
            ? (app()->getLocale() === 'ar'
                ? 'وضع العرض التجريبي: الحذف غير متاح. يمكنك الإضافة والتعديل فقط.'
                : 'Demo mode: deleting is disabled. You can add and edit only.')
            : (app()->getLocale() === 'ar'
                ? 'وضع العرض التجريبي: تعديل الإعدادات الأساسية غير متاح.'
                : 'Demo mode: changing core settings is disabled.');

        // Inertia / browser POST → redirect back with a flash so the UI shows it.
        if ($request->header('X-Inertia') || ! $request->expectsJson()) {
            return back()->with('error', $message);
        }

        return response()->json(['message' => $message], 403);
    }
}
