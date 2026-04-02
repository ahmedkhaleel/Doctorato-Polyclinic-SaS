<?php

namespace App\Http\Controllers\Secretary;

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
    public function showLogin(): Response
    {
        return Inertia::render('Secretary/Auth/Login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $key = 'secretary-login:'.Str::lower($request->input('email')).'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);

            return back()->withErrors([
                'email' => "Too many login attempts. Please try again in {$seconds} seconds.",
            ])->onlyInput('email');
        }

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            RateLimiter::clear($key);
            $request->session()->regenerate();

            $user = $request->user();

            // Check user is active
            if (! $user->is_active) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->route('secretary.login')
                    ->with('error', app()->getLocale() === 'ar' ? 'تم تعطيل حسابك.' : 'Your account has been deactivated.');
            }

            // Check user role is secretary
            if (! $user->role || $user->role->name !== 'secretary') {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->route('secretary.login')
                    ->with('error', app()->getLocale() === 'ar' ? 'ليس لديك صلاحية الوصول إلى لوحة السكرتارية.' : 'You do not have access to the secretary panel.');
            }

            AuditLogger::authEvent('login', $request->input('email'), 'secretary');

            return redirect()->intended(route('secretary.dashboard'));
        }

        RateLimiter::hit($key, 60);

        AuditLogger::authEvent('failed_login', $request->input('email'), 'secretary');

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ])->onlyInput('email');
    }

    public function logout(Request $request): RedirectResponse
    {
        AuditLogger::authEvent('logout', null, 'secretary');

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('secretary.login');
    }
}
