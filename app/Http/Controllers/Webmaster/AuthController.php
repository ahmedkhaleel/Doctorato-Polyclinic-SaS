<?php

namespace App\Http\Controllers\Webmaster;

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
        return Inertia::render('Webmaster/Auth/Login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $key = 'webmaster-login:'.Str::lower($request->input('email')).'|'.$request->ip();

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

                return redirect()->route('webmaster.login')
                    ->with('error', 'Your account has been deactivated.');
            }

            // Check user role is webmaster
            if (! $user->role || $user->role->name !== 'webmaster') {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->route('webmaster.login')
                    ->with('error', 'You do not have access to the webmaster panel.');
            }

            AuditLogger::authEvent('login', $request->input('email'), 'webmaster');

            return redirect()->intended(route('webmaster.dashboard'));
        }

        RateLimiter::hit($key, 60);

        AuditLogger::authEvent('failed_login', $request->input('email'), 'webmaster');

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ])->onlyInput('email');
    }

    public function logout(Request $request): RedirectResponse
    {
        AuditLogger::authEvent('logout', null, 'webmaster');

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('webmaster.login');
    }
}
