<?php

namespace App\Http\Middleware;

use App\Models\Doctor;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class DoctorAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        // === DEBUG: Log every check to find the exact failure point ===
        $sessionId = session()->getId();
        $authId = Auth::id();

        Log::info("DoctorAuth: START", [
            'url' => $request->url(),
            'session_id' => substr($sessionId, 0, 12) . '...',
            'auth_id' => $authId,
            'auth_check' => Auth::check(),
            'has_session_cookie' => $request->hasCookie(config('session.cookie')),
        ]);

        if (! Auth::check()) {
            Log::warning("DoctorAuth: FAIL - Auth::check() is false", [
                'session_id' => substr($sessionId, 0, 12) . '...',
                'auth_id' => $authId,
                'session_data_exists' => ! empty(session()->all()),
            ]);

            return redirect()->route('doctor.login')
                ->with('error', 'Please log in to access the doctor panel.');
        }

        try {
            $user = User::with(['role'])->find(Auth::id());

            if (! $user) {
                Log::warning("DoctorAuth: FAIL - User not found in DB", ['auth_id' => Auth::id()]);
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
                return redirect()->route('doctor.login')
                    ->with('error', 'User account not found.');
            }

            if (! $user->is_active) {
                Log::warning("DoctorAuth: FAIL - User not active", ['user_id' => $user->id, 'is_active' => $user->is_active]);
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
                return redirect()->route('doctor.login')
                    ->with('error', 'Your account has been deactivated.');
            }

            $roleName = $user->role?->name;
            if (! $roleName) {
                $roleName = DB::table('roles')->where('id', $user->role_id)->value('name');
                Log::info("DoctorAuth: Role fallback used", ['role_id' => $user->role_id, 'role_name' => $roleName]);
            }

            if ($roleName !== 'doctor') {
                Log::warning("DoctorAuth: FAIL - Role is not doctor", ['user_id' => $user->id, 'role_name' => $roleName, 'role_id' => $user->role_id]);
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
                return redirect()->route('doctor.login')
                    ->with('error', 'You do not have access to the doctor panel.');
            }

            $doctor = Doctor::where('user_id', $user->id)->first();

            if (! $doctor) {
                Log::warning("DoctorAuth: FAIL - No doctor profile", ['user_id' => $user->id]);
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
                return redirect()->route('doctor.login')
                    ->with('error', 'No doctor profile is linked to your account.');
            }

            if ($doctor->status !== 'active') {
                Log::warning("DoctorAuth: FAIL - Doctor not active", ['doctor_id' => $doctor->id, 'status' => $doctor->status]);
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
                return redirect()->route('doctor.login')
                    ->with('error', 'Your doctor profile is currently inactive.');
            }

            Log::info("DoctorAuth: PASS", ['user_id' => $user->id, 'doctor_id' => $doctor->id]);

            return $next($request);

        } catch (\Throwable $e) {
            Log::error('DoctorAuth: EXCEPTION', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return redirect()->route('doctor.dashboard')
                ->with('error', 'A temporary error occurred. Please try again.');
        }
    }
}
