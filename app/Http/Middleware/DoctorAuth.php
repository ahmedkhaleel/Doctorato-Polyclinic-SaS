<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class DoctorAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::check()) {
            return redirect()->route('doctor.login')
                ->with('error', 'Please log in to access the doctor panel.');
        }

        $user = $request->user();

        if (! $user->is_active) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('doctor.login')
                ->with('error', 'Your account has been deactivated.');
        }

        // Verify user role is doctor (prevent cross-portal access)
        if (! $user->role || $user->role->name !== 'doctor') {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('doctor.login')
                ->with('error', 'You do not have access to the doctor panel.');
        }

        $doctor = $user->doctor;

        if (! $doctor) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('doctor.login')
                ->with('error', 'No doctor profile is linked to your account.');
        }

        if ($doctor->status !== 'active') {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('doctor.login')
                ->with('error', 'Your doctor profile is currently inactive.');
        }

        return $next($request);
    }
}
