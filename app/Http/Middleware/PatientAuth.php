<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class PatientAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->route('locale', app()->getLocale());

        if (! Auth::check()) {
            return redirect()->route('patient.login', ['locale' => $locale])
                ->with('error', 'Please log in to access the patient portal.');
        }

        $user = $request->user();

        if (! $user->is_active) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('patient.login', ['locale' => $locale])
                ->with('error', 'Your account has been deactivated.');
        }

        // Verify user role is patient (prevent cross-portal access)
        if (! $user->role || $user->role->name !== 'patient') {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('patient.login', ['locale' => $locale])
                ->with('error', 'You do not have access to the patient portal.');
        }

        try {
            $patient = $user->patient;
        } catch (\Exception $e) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('patient.login', ['locale' => $locale])
                ->with('error', 'Patient portal is being set up. Please run database migrations.');
        }

        if (! $patient) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('patient.login', ['locale' => $locale])
                ->with('error', 'No patient profile is linked to your account.');
        }

        if (! $patient->is_active) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('patient.login', ['locale' => $locale])
                ->with('error', 'Your patient profile is currently inactive.');
        }

        return $next($request);
    }
}
