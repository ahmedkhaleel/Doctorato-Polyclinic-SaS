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
        if (! Auth::check()) {
            // For AJAX/fetch requests, return 401 JSON instead of redirect
            // This prevents Inertia from following the redirect and logging out
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['message' => 'Unauthenticated'], 401);
            }

            return redirect()->route('doctor.login')
                ->with('error', 'Please log in to access the doctor panel.');
        }

        try {
            // Fresh query to avoid stale/null relationship issues from session deserialization
            $user = User::with(['role'])->find(Auth::id());

            if (! $user) {
                return $this->handleFailure($request, 'User account not found.');
            }

            if (! $user->is_active) {
                return $this->handleFailure($request, 'Your account has been deactivated.');
            }

            // Verify user role is doctor (prevent cross-portal access)
            $roleName = $user->role?->name;
            if (! $roleName) {
                $roleName = DB::table('roles')->where('id', $user->role_id)->value('name');
            }

            if ($roleName !== 'doctor') {
                return $this->handleFailure($request, 'You do not have access to the doctor panel.');
            }

            // Get doctor profile with direct query
            $doctor = Doctor::where('user_id', $user->id)->first();

            if (! $doctor) {
                return $this->handleFailure($request, 'No doctor profile is linked to your account.');
            }

            if ($doctor->status !== 'active') {
                return $this->handleFailure($request, 'Your doctor profile is currently inactive.');
            }

            return $next($request);

        } catch (\Throwable $e) {
            Log::error('DoctorAuth middleware error: ' . $e->getMessage());

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['message' => 'A temporary error occurred'], 500);
            }

            return back()->with('error', 'A temporary error occurred. Please try again.');
        }
    }

    /**
     * Handle auth failure - return JSON for AJAX, redirect for page loads.
     */
    private function handleFailure(Request $request, string $message): Response
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['message' => $message], 401);
        }

        return redirect()->route('doctor.login')->with('error', $message);
    }
}
