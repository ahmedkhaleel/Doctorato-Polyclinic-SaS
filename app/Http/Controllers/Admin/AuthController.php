<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use App\Services\AuditLogger;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class AuthController extends Controller
{
    /**
     * Roles that have their own separate panels and are NOT allowed to login via admin.
     */
    private array $separatePanelRoles = ['secretary', 'doctor', 'webmaster'];

    public function showLogin(): Response
    {
        return Inertia::render('Admin/Auth/Login');
    }

    public function login(Request $request): RedirectResponse
    {
        $request->validate([
            'login' => 'required|string',
            'password' => 'required|string',
        ]);

        $login = $request->input('login');
        $field = filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';
        $credentials = [
            $field => $login,
            'password' => $request->input('password'),
        ];

        $key = Str::lower($login).'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);

            return back()->withErrors([
                'login' => "Too many login attempts. Please try again in {$seconds} seconds.",
            ])->onlyInput('login');
        }

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            RateLimiter::clear($key);
            $request->session()->regenerate();

            $user = $request->user();

            if (! $user->is_active) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->route('admin.login')
                    ->with('error', 'Your account has been deactivated.');
            }

            // Block separate-panel roles from accessing admin
            if ($user->role && in_array($user->role->name, $this->separatePanelRoles)) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->route('admin.login')
                    ->with('error', 'You do not have access to the admin panel. Please use your designated panel.');
            }

            AuditLogger::authEvent('login', $login, 'admin');

            return redirect()->intended(route('admin.dashboard'));
        }

        RateLimiter::hit($key, 60);

        AuditLogger::authEvent('failed_login', $login, 'admin');

        return back()->withErrors([
            'login' => 'The provided credentials do not match our records.',
        ])->onlyInput('login');
    }

    public function logout(Request $request): RedirectResponse
    {
        AuditLogger::authEvent('logout', null, 'admin');

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }

    public function switchLocale(Request $request): RedirectResponse
    {
        $locale = $request->input('locale', 'ar');
        session()->put('admin_locale', in_array($locale, ['ar', 'en']) ? $locale : 'ar');

        return redirect()->back();
    }
}
