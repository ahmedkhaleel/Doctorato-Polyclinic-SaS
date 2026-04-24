<?php

namespace App\Http\Controllers\Patient;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Patient;
use App\Models\Role;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use App\Models\Setting;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Auth\Passwords\PasswordBroker;
use Illuminate\Support\Facades\Password as PasswordFacade;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class PatientAuthController extends Controller
{
    private function locale(Request $request): string
    {
        return $request->route('locale', app()->getLocale());
    }

    public function showLogin(Request $request): Response
    {
        return Inertia::render('Patient/Auth/Login');
    }

    public function login(Request $request): RedirectResponse
    {
        $locale = $this->locale($request);

        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $key = 'patient-login:' . Str::lower($request->input('email')) . '|' . $request->ip();

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

            if (! $user->is_active) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
                return redirect()->route('patient.login', ['locale' => $locale])
                    ->with('error', 'Your account has been deactivated.');
            }

            if (! $user->role || $user->role->name !== 'patient') {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
                return redirect()->route('patient.login', ['locale' => $locale])
                    ->with('error', 'This account is not authorized for the patient portal.');
            }

            if (! $user->patient) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
                return redirect()->route('patient.login', ['locale' => $locale])
                    ->with('error', 'No patient profile is linked to your account.');
            }

            AuditLogger::authEvent('login', $request->input('email'), 'patient');

            return redirect()->intended(route('patient.dashboard', ['locale' => $locale]));
        }

        RateLimiter::hit($key, 60);
        AuditLogger::authEvent('failed_login', $request->input('email'), 'patient');

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ])->onlyInput('email');
    }

    public function showRegister(Request $request): Response
    {
        return Inertia::render('Patient/Auth/Register');
    }

    public function storeRegister(Request $request): RedirectResponse
    {
        $locale = $this->locale($request);

        $data = $request->validate([
            'full_name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        $patientRole = Role::where('name', 'patient')->first();

        if (! $patientRole) {
            return back()->with('error', 'Registration is not available at this time.');
        }

        return DB::transaction(function () use ($data, $patientRole, $request, $locale) {
            // Create user account
            $user = User::create([
                'name' => $data['full_name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'role_id' => $patientRole->id,
                'is_active' => true,
            ]);

            // Always create a new patient record for security.
            // Linking existing records by phone is disabled to prevent
            // account takeover (anyone knowing a phone number could claim records).
            // Admins can manually link patient records if needed.
            $patient = new Patient([
                'full_name' => $data['full_name'],
                'phone' => $data['phone'],
                'email' => $data['email'],
            ]);
            $patient->user_id = $user->id;
            $patient->file_number = Patient::generateFileNumber();
            $patient->is_active = true;
            $patient->save();

            // Link only anonymous bookings (no patient_id) with matching phone AND email
            // Require both phone AND email match for safety
            Booking::whereNull('patient_id')
                ->where('phone', $data['phone'])
                ->where('email', $data['email'])
                ->update(['patient_id' => $patient->id]);

            Auth::login($user, true);
            $request->session()->regenerate();

            AuditLogger::authEvent('register', $data['email'], 'patient');

            // Best-effort welcome email. Non-fatal — registration completes
            // even if Mail throws (misconfigured SMTP, etc).
            $this->sendWelcomeEmail($user, $patient, $locale);

            return redirect()->route('patient.dashboard', ['locale' => $locale])
                ->with('success', 'Welcome! Your account has been created successfully.');
        });
    }

    private function sendWelcomeEmail(User $user, Patient $patient, string $locale): void
    {
        try {
            $appUrl = rtrim(config('app.url'), '/');
            $view = [
                'patientName'  => $patient->full_name ?? $user->name,
                'locale'       => $locale,
                'portalUrl'    => "{$appUrl}/{$locale}/patient",
                'supportEmail' => Setting::get('email', 'info@doctorato.com'),
            ];

            Mail::send('emails.patient-welcome', $view, function ($m) use ($user, $locale) {
                $subject = $locale === 'ar'
                    ? 'أهلاً بك في Doctorato Polyclinic'
                    : 'Welcome to Doctorato Polyclinic';
                $m->to($user->email, $user->name)->subject($subject);
            });
        } catch (\Throwable $e) {
            Log::warning('[patient-welcome] mail failed', [
                'user_id' => $user->id,
                'error'   => $e->getMessage(),
            ]);
        }
    }

    /* ============================================================
     * Password reset
     *
     * Two-step flow:
     *   1. POST /{locale}/patient/forgot-password  → emails a signed
     *      token good for 60 min (Laravel default).
     *   2. GET  /{locale}/patient/reset-password/{token}?email=…
     *      → renders the reset form.
     *   3. POST /{locale}/patient/reset-password   → consumes the
     *      token (single use), updates the password, fires session
     *      logout for any other devices.
     *
     * Both POSTs are throttled per-email+IP to defeat password guessing
     * and to limit reset-email flooding to legitimate-looking traffic.
     * ============================================================ */

    public function showForgotPassword(Request $request): Response
    {
        return Inertia::render('Patient/Auth/ForgotPassword');
    }

    public function sendResetLink(Request $request): RedirectResponse
    {
        $request->validate(['email' => 'required|email']);

        $key = 'patient-forgot:' . Str::lower($request->input('email')) . '|' . $request->ip();
        if (RateLimiter::tooManyAttempts($key, 3)) {
            $seconds = RateLimiter::availableIn($key);
            return back()->with('error', "Too many attempts. Please try again in {$seconds} seconds.");
        }
        RateLimiter::hit($key, 600); // 10-minute window for 3 attempts

        // Only patients can use this flow — no cross-portal reset.
        $user = User::where('email', $request->input('email'))
            ->whereHas('role', fn ($q) => $q->where('name', 'patient'))
            ->first();

        if ($user) {
            PasswordFacade::sendResetLink(['email' => $user->email]);
            AuditLogger::authEvent('password_reset_requested', $user->email, 'patient');
        }

        // Always show the same response — never reveal whether the email exists.
        return back()->with('success', 'If that email belongs to a patient account, a reset link has been sent.');
    }

    public function showResetForm(Request $request, string $locale, string $token): Response
    {
        return Inertia::render('Patient/Auth/ResetPassword', [
            'token' => $token,
            'email' => $request->query('email', ''),
        ]);
    }

    public function resetPassword(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'token'    => 'required|string',
            'email'    => 'required|email',
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        $key = 'patient-reset:' . Str::lower($data['email']) . '|' . $request->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);
            return back()->with('error', "Too many attempts. Please try again in {$seconds} seconds.");
        }
        RateLimiter::hit($key, 600);

        $status = PasswordFacade::reset(
            $data,
            function (User $user, string $password) use ($request) {
                // Reject reset for non-patient roles (defense in depth).
                if ($user->role?->name !== 'patient') {
                    abort(403);
                }
                $user->forceFill([
                    'password'       => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();

                // Log out all of this user's other sessions.
                Auth::logoutOtherDevices($password);

                AuditLogger::authEvent('password_reset_completed', $user->email, 'patient');
            }
        );

        $locale = $this->locale($request);
        if ($status === PasswordBroker::PASSWORD_RESET) {
            RateLimiter::clear($key);
            return redirect()->route('patient.login', ['locale' => $locale])
                ->with('success', 'Your password has been reset. You can now log in.');
        }

        return back()->with('error', __($status));
    }

    public function logout(Request $request): RedirectResponse
    {
        $locale = $this->locale($request);

        AuditLogger::authEvent('logout', null, 'patient');

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('patient.login', ['locale' => $locale]);
    }
}
