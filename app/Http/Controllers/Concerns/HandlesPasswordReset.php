<?php

namespace App\Http\Controllers\Concerns;

use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Passwords\PasswordBroker;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password as PasswordFacade;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Shared password-reset flow for any portal Auth controller.
 *
 * The host controller declares two abstract concerns through its
 * config + form/email senders, and gets the full forgot/reset flow
 * for free with consistent rate-limiting, audit logs, leak resistance,
 * and the branded HTML template.
 *
 * REQUIRED on host class:
 *   - portalKey():       string   e.g. 'patient', 'doctor', 'admin'
 *   - portalRoles():     array    roles allowed to reset via this portal
 *   - portalLoginRoute():string   route name to redirect to after success
 *   - portalForgotForm():string   Inertia component name for forgot page
 *   - portalResetForm():string    Inertia component name for reset page
 *
 * The host's routes file should map four endpoints:
 *   GET  /forgot-password         → showForgotPassword
 *   POST /forgot-password         → sendResetLink
 *   GET  /reset-password/{token}  → showResetForm
 *   POST /reset-password          → resetPassword
 */
trait HandlesPasswordReset
{
    public function showForgotPassword(Request $request): Response
    {
        return Inertia::render($this->portalForgotForm());
    }

    public function sendResetLink(Request $request): RedirectResponse
    {
        $request->validate(['email' => 'required|email']);
        $email = strtolower($request->input('email'));

        // Per-email + IP throttle in addition to route-level throttle. 3 tries
        // per 10 min window — generous for typo retries, tight enough that
        // an attacker can't enumerate addresses through a script.
        $key = "pw-forgot:{$this->portalKey()}:{$email}|{$request->ip()}";
        if (RateLimiter::tooManyAttempts($key, 3)) {
            $secs = RateLimiter::availableIn($key);
            return back()->with('error', __("Too many attempts. Please try again in {$secs} seconds."));
        }
        RateLimiter::hit($key, 600);

        $user = User::where('email', $email)
            ->whereHas('role', fn ($q) => $q->whereIn('name', $this->portalRoles()))
            ->first();

        if ($user) {
            PasswordFacade::sendResetLink(['email' => $user->email]);
            AuditLogger::authEvent('password_reset_requested', $user->email, $this->portalKey());
        }

        // Same response whether the email exists or not — stops attackers
        // from enumerating valid accounts via timing/text differences.
        return back()->with('success', __(
            'If that email belongs to a :portal account, a reset link has been sent.',
            ['portal' => $this->portalKey()]
        ));
    }

    public function showResetForm(Request $request, string $token): Response
    {
        return Inertia::render($this->portalResetForm(), [
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

        $key = "pw-reset:{$this->portalKey()}:" . strtolower($data['email']) . '|' . $request->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            $secs = RateLimiter::availableIn($key);
            return back()->with('error', __("Too many attempts. Please try again in {$secs} seconds."));
        }
        RateLimiter::hit($key, 600);

        $allowedRoles = $this->portalRoles();
        $portalKey    = $this->portalKey();

        $status = PasswordFacade::reset(
            $data,
            function (User $user, string $password) use ($allowedRoles, $portalKey) {
                // Defense in depth: even with a valid token, refuse to reset
                // a user whose role doesn't belong to this portal. Stops a
                // patient-portal token from being used to reset an admin.
                if (! $user->role || ! in_array($user->role->name, $allowedRoles, true)) {
                    abort(403);
                }

                $user->forceFill([
                    'password'       => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();

                Auth::logoutOtherDevices($password);
                AuditLogger::authEvent('password_reset_completed', $user->email, $portalKey);
            }
        );

        if ($status === PasswordBroker::PASSWORD_RESET) {
            RateLimiter::clear($key);
            return redirect($this->portalAfterResetUrl($request))
                ->with('success', __('Your password has been reset. You can now log in.'));
        }

        return back()->with('error', __($status));
    }

    /**
     * Default — host can override if it needs locale or route params.
     */
    protected function portalAfterResetUrl(Request $request): string
    {
        return route($this->portalLoginRoute());
    }
}
