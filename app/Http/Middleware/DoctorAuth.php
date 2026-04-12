<?php

namespace App\Http\Middleware;

use App\Models\Doctor;
use App\Models\Role;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class DoctorAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::check()) {
            return redirect()->route('doctor.login')
                ->with('error', 'Please log in to access the doctor panel.');
        }

        try {
            // Fresh query to avoid stale/null relationship issues from session deserialization
            $user = User::with(['role'])->find(Auth::id());

            if (! $user) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->route('doctor.login')
                    ->with('error', 'User account not found.');
            }

            if (! $user->is_active) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->route('doctor.login')
                    ->with('error', 'Your account has been deactivated.');
            }

            // Verify user role is doctor (prevent cross-portal access)
            // Use direct DB query as fallback if relationship fails
            $roleName = $user->role?->name;
            if (! $roleName) {
                $roleName = DB::table('roles')->where('id', $user->role_id)->value('name');
            }

            if ($roleName !== 'doctor') {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->route('doctor.login')
                    ->with('error', 'You do not have access to the doctor panel.');
            }

            // Get doctor profile with direct query
            $doctor = Doctor::where('user_id', $user->id)->first();

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

        } catch (\Throwable $e) {
            // If any database error occurs, don't logout - just redirect with error
            // This prevents logout on transient DB issues
            \Illuminate\Support\Facades\Log::error('DoctorAuth middleware error: ' . $e->getMessage());

            return redirect()->route('doctor.dashboard')
                ->with('error', 'A temporary error occurred. Please try again.');
        }
    }
}
