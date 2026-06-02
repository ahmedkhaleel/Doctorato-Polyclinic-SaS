<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

/**
 * "Demo & Trial" control screen (super_admin only).
 *
 * Surfaces the demo accounts handed to prospects, their login links per panel,
 * and the trial window — plus actions to: edit trial duration + contact URL,
 * extend/re-activate every demo trial, and rotate the shared demo password.
 *
 * Sensitive (resets credentials for all demo accounts at once), so every action
 * is hard-gated to super_admin regardless of the route permission.
 */
class DemoTrialController extends Controller
{
    /** Panel login route per role name. */
    private function panelFor(?string $role): array
    {
        return match ($role) {
            'doctor' => ['url' => '/doctor/login', 'en' => 'Doctor', 'ar' => 'بوابة الطبيب'],
            'secretary' => ['url' => '/secretary/login', 'en' => 'Secretary', 'ar' => 'بوابة السكرتارية'],
            'patient' => ['url' => '/ar/patient/login', 'en' => 'Patient', 'ar' => 'بوابة المريض'],
            default => ['url' => '/admin/login', 'en' => 'Admin', 'ar' => 'بوابة الأدمن'],
        };
    }

    private function guardSuperAdmin(Request $request): void
    {
        abort_unless($request->user()?->hasRole('super_admin'), 403);
    }

    public function index(Request $request): Response
    {
        $this->guardSuperAdmin($request);

        $base = rtrim((string) config('app.url'), '/');

        $accounts = User::where('is_demo', true)
            ->with('role')
            ->orderBy('id')
            ->get()
            ->map(function (User $u) use ($base) {
                $panel = $this->panelFor($u->role?->name);

                return [
                    'id' => $u->id,
                    'name' => $u->name,
                    'email' => $u->email,
                    'role' => $u->role?->name,
                    'is_active' => (bool) $u->is_active,
                    'trial_ends_at' => $u->trial_ends_at?->toIso8601String(),
                    'trial_days_left' => $u->trialDaysLeft(),
                    'trial_expired' => $u->trialExpired(),
                    'panel_label_en' => $panel['en'],
                    'panel_label_ar' => $panel['ar'],
                    'login_path' => $panel['url'],
                    'login_url' => $base.$panel['url'],
                ];
            })
            ->values();

        return Inertia::render('Admin/DemoTrial/Index', [
            'accounts' => $accounts,
            'settings' => [
                'trial_days' => (int) Setting::get('trial_days', 14),
                'trial_contact_url' => (string) Setting::get('trial_contact_url', 'https://doctorato.com/contact'),
            ],
            'appUrl' => $base,
        ]);
    }

    public function updateSettings(Request $request): RedirectResponse
    {
        $this->guardSuperAdmin($request);

        $data = $request->validate([
            'trial_days' => ['required', 'integer', 'min:1', 'max:3650'],
            'trial_contact_url' => ['required', 'url', 'max:255'],
        ]);

        Setting::set('trial_days', (string) $data['trial_days'], 'general');
        Setting::set('trial_contact_url', $data['trial_contact_url'], 'general');

        return back()->with('success', __('Trial settings saved.'));
    }

    /** Extend (and re-activate) the trial window for every demo account. */
    public function extendTrials(Request $request): RedirectResponse
    {
        $this->guardSuperAdmin($request);

        $data = $request->validate([
            'days' => ['required', 'integer', 'min:1', 'max:3650'],
        ]);

        $newEnd = now()->addDays((int) $data['days']);
        $count = 0;

        foreach (User::where('is_demo', true)->get() as $u) {
            $u->forceFill([
                'trial_ends_at' => $newEnd,
                'is_active' => true,
            ])->save();
            $count++;
        }

        Log::info("[demo-trial] super_admin {$request->user()->id} extended {$count} demo trial(s) to {$newEnd->toDateString()}.");

        return back()->with('success', __(':n demo accounts extended.', ['n' => $count]));
    }

    /** Set a new shared password for every demo account (and re-activate them). */
    public function resetPassword(Request $request): RedirectResponse
    {
        $this->guardSuperAdmin($request);

        $data = $request->validate([
            'password' => ['required', 'string', 'min:8', 'max:72', 'confirmed'],
        ]);

        $hash = Hash::make($data['password']);
        $count = 0;

        foreach (User::where('is_demo', true)->get() as $u) {
            $u->forceFill([
                'password' => $hash,
                'is_active' => true,
            ])->save();
            $count++;
        }

        Log::info("[demo-trial] super_admin {$request->user()->id} rotated the password for {$count} demo account(s).");

        return back()->with('success', __('Demo password updated for :n accounts.', ['n' => $count]));
    }
}
