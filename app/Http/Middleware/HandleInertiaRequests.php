<?php

namespace App\Http\Middleware;

use App\Models\Booking;
use App\Models\ContactMessage;
use App\Models\LeadFollowUp;
use App\Models\Message;
use App\Models\Setting;
use App\Services\ModuleManager;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    public function share(Request $request): array
    {
        $locale = app()->getLocale();
        $dir = $locale === 'ar' ? 'rtl' : 'ltr';
        $isDoctorRoute = str_starts_with($request->path(), 'doctor');
        $isSecretaryRoute = str_starts_with($request->path(), 'secretary');
        $isWebmasterRoute = str_starts_with($request->path(), 'webmaster');
        $isPatientRoute = (bool) preg_match('#^(ar|en)/patient#', $request->path());

        if ($user = $request->user()) {
            // Eager-load the role once — it's read many times below (role name,
            // display, permissions accessor) and in panel middleware.
            $user->loadMissing('role');

            // Throttle the online-status write to once/minute per user instead
            // of an UPDATE on every single request (hot write path on shared DB).
            if (\Illuminate\Support\Facades\Cache::add('seen:'.$user->id, 1, 60)) {
                $user->updateQuietly(['last_seen_at' => now()]);
            }
        }

        return [
            ...parent::share($request),
            'locale' => $locale,
            'dir' => $dir,
            'translations' => fn () => $this->getTranslations($locale),
            'auth' => [
                'user' => $request->user() ? [
                    'id' => $request->user()->id,
                    'name' => $request->user()->name,
                    'email' => $request->user()->email,
                    'role' => $request->user()->role?->name,
                    'role_display' => $request->user()->role?->display_name_en,
                    'permissions' => $request->user()->permissions,
                ] : null,
                'doctor' => fn () => ($isDoctorRoute && $request->user()?->doctor)
                    ? [
                        'id' => $request->user()->doctor->id,
                        'name_en' => $request->user()->doctor->name_en,
                        'name_ar' => $request->user()->doctor->name_ar,
                        'specialization_en' => $request->user()->doctor->specialization_en,
                        'specialization_ar' => $request->user()->doctor->specialization_ar,
                        'module' => $request->user()->doctor->module,
                        'photo_url' => $request->user()->doctor->photo_url,
                    ]
                    : null,
                'patient' => fn () => $this->getPatientData($isPatientRoute, $request),
            ],
            'branch' => fn () => $this->getBranchData($request, $isPatientRoute),
            // AI availability for in-screen assist buttons (any page can read it).
            'ai' => fn () => $this->getAiData($request),
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
                'credentials' => fn () => $request->session()->get('credentials'),
            ],
            'settings' => fn () => $this->getSettings(),
            'modules' => fn () => ModuleManager::getForFrontend(),
            'defaultModule' => fn () => ModuleManager::getDefaultModule(),
            'notifications' => function () use ($request) {
                if (! $request->user()) {
                    return null;
                }
                try {
                    // Clinic-wide counters are identical for every admin — cache
                    // briefly so each full page load doesn't re-run 3 COUNTs.
                    $global = $this->globalUnreadCounts();

                    return [
                        'unread_bookings' => $global['unread_bookings'],
                        'unread_messages' => $global['unread_messages'],
                        'unread_system' => $request->user()->unreadNotifications()->count(),
                        'crm_overdue_count' => $global['crm_overdue_count'],
                    ];
                } catch (\Throwable) {
                    return ['unread_bookings' => 0, 'unread_messages' => 0, 'unread_system' => 0, 'crm_overdue_count' => 0];
                }
            },

            // System health flag for admin header indicator. Kept intentionally
            // minimal (1 cached bool + 1 count) so the Inertia response payload
            // doesn't bloat on every request.
            'systemHealth' => function () use ($request) {
                if (! $request->user() || ! $request->user()->role) {
                    return null;
                }
                $role = $request->user()->role->name;
                if (! in_array($role, ['admin', 'super_admin'], true)) {
                    return null;
                }

                return cache()->remember('inertia.system_health', 30, function () {
                    $blockers = [];
                    if (\App\Services\ModuleManager::isEnabled('telemedicine')) {
                        if (! app(\App\Services\Payment\PaymentGatewayManager::class)->getActive()) {
                            $blockers[] = 'no_payment_gateway';
                        }
                        if (\App\Models\Doctor::onlineEnabled()->count() === 0) {
                            $blockers[] = 'no_online_doctors';
                        }
                    }

                    return [
                        'ok' => empty($blockers),
                        'blocker_count' => count($blockers),
                    ];
                });
            },
            'doctor_notifications' => function () use ($isDoctorRoute, $request) {
                if (! $isDoctorRoute || ! $request->user()) {
                    return null;
                }
                try {
                    return ['unread_count' => $request->user()->unreadNotifications()->count()];
                } catch (\Throwable) {
                    return ['unread_count' => 0];
                }
            },
            'secretary_notifications' => function () use ($isSecretaryRoute, $request) {
                if (! $isSecretaryRoute || ! $request->user()) {
                    return null;
                }
                try {
                    $global = $this->globalUnreadCounts();

                    return [
                        'unread_bookings' => $global['unread_bookings'],
                        'unread_messages' => $global['unread_messages'],
                        'unread_dental' => $request->user()->unreadNotifications()->count(),
                        'crm_overdue_count' => LeadFollowUp::overdue()
                            ->forUser($request->user()->id)
                            ->count(),
                    ];
                } catch (\Throwable) {
                    return ['unread_bookings' => 0, 'unread_messages' => 0, 'unread_dental' => 0, 'crm_overdue_count' => 0];
                }
            },
            'chat_notifications' => fn () => $request->user() ? [
                'unread_count' => Message::where('receiver_id', $request->user()->id)
                    ->whereNull('read_at')->count(),
            ] : null,
        ];
    }

    /**
     * Clinic-wide unread counters shared by all staff. Cached 30s so a burst
     * of full page loads doesn't re-run the same 3 COUNT(*) queries each time.
     */
    private function globalUnreadCounts(): array
    {
        return \Illuminate\Support\Facades\Cache::remember('inertia.global_unread', 30, fn () => [
            'unread_bookings' => Booking::where('is_read', false)->count(),
            'unread_messages' => ContactMessage::where('is_read', false)->count(),
            'crm_overdue_count' => LeadFollowUp::overdue()->count(),
        ]);
    }

    private function getTranslations(string $locale): array
    {
        $path = lang_path("{$locale}.json");

        if (file_exists($path)) {
            return json_decode(file_get_contents($path), true) ?? [];
        }

        return [];
    }

    /**
     * Only expose public-safe settings to the frontend.
     * Tracking IDs, API keys, and custom scripts are NOT exposed here.
     */
    /** Active branch + the staff member's branch list for the switcher. */
    /**
     * AI availability for in-screen assist buttons. enabled = the layer is ready
     * AND the user may use AI; features = the list of enabled feature keys.
     */
    private function getAiData(Request $request): array
    {
        $off = ['enabled' => false, 'features' => []];
        $user = $request->user();
        if (! $user) {
            return $off;
        }

        try {
            if (! app(\App\Services\Ai\AiManager::class)->isReady()) {
                return $off;
            }

            $role = $user->role;

            // Patient accounts have no AI permission — expose only the curated
            // patient-facing features (never clinical/admin), gated by their flags.
            if ($role && $role->name === 'patient') {
                $patientFacing = ['patient_assistant', 'patient_explain'];
                $enabled = array_values(array_intersect($patientFacing, \App\Models\AiFeatureFlag::enabledKeys()));

                return $enabled ? ['enabled' => true, 'features' => $enabled] : $off;
            }

            $canUse = $role && ($role->hasPermission('ai.view') || $role->hasPermission('ai.doctor'));
            if (! $canUse) {
                return $off;
            }

            return ['enabled' => true, 'features' => \App\Models\AiFeatureFlag::enabledKeys()];
        } catch (\Throwable) {
            return $off;
        }
    }

    private function getBranchData(Request $request, bool $isPatientRoute): ?array
    {
        $user = $request->user();
        // Multi-branch is behind a kill-switch (default off) — skip the branch
        // queries entirely while it's disabled instead of querying every request.
        if ($isPatientRoute || ! $user || ! config('branches.enabled')) {
            return null;
        }

        try {
            $ctx = app(\App\Services\Branch\BranchContext::class);
            $list = $user->canSwitchAllBranches()
                ? \App\Models\Branch::where('is_active', true)->get(['id', 'name_ar', 'name_en'])
                : $user->branches()->where('branches.is_active', true)->get(['branches.id', 'name_ar', 'name_en']);

            if ($list->isEmpty()) {
                return null;
            }

            $ar = app()->getLocale() === 'ar';

            // Post the switch to the route of the panel we're in (each panel has
            // its own branch.context-protected /switch-branch endpoint).
            $path = $request->path();
            $switchUrl = '/admin/switch-branch';
            if (str_starts_with($path, 'doctor')) {
                $switchUrl = '/doctor/switch-branch';
            } elseif (str_starts_with($path, 'secretary')) {
                $switchUrl = '/secretary/switch-branch';
            }

            return [
                'enabled' => (bool) config('branches.enabled'),
                'current' => $ctx->isAllBranches() ? null : $ctx->currentId(),
                'is_all' => $ctx->isAllBranches(),
                'can_all' => $user->canSwitchAllBranches(),
                'switch_url' => $switchUrl,
                'list' => $list->map(fn ($b) => ['id' => $b->id, 'name' => $ar ? $b->name_ar : $b->name_en])->values(),
            ];
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function getPatientData(bool $isPatientRoute, Request $request): ?array
    {
        if (! $isPatientRoute || ! $request->user()) {
            return null;
        }

        try {
            $patient = $request->user()->patient;
            if (! $patient) {
                return null;
            }

            return [
                'id' => $patient->id,
                'full_name' => $patient->full_name,
                'file_number' => $patient->file_number,
                'phone' => $patient->phone,
                'email' => $patient->email,
                'photo_url' => $patient->photo_url,
            ];
        } catch (\Exception $e) {
            return null;
        }
    }

    private function getSettings(): array
    {
        // Whitelist of settings safe to expose to browser
        $publicKeys = [
            'site_name', 'site_name_ar', 'site_name_en',
            'phone_1', 'phone_2', 'email', 'whatsapp',
            'address', 'address_ar', 'address_en',
            'facebook', 'instagram', 'tiktok', 'twitter', 'snapchat', 'youtube', 'linkedin',
            'google_maps', 'working_hours', 'working_hours_ar', 'working_hours_en',
            'logo', 'logo_dark', 'favicon',
            'about_short', 'about_short_ar', 'about_short_en',
            'booking_enabled', 'contact_enabled',
            'currency_code', 'currency_symbol',
            'currency_name_en', 'currency_name_ar',
            'currency_position', 'currency_decimals',
            // Branding & Theme
            'brand_primary', 'brand_primary_hover',
            'brand_secondary', 'brand_accent',
            'brand_sidebar_bg', 'brand_sidebar_text',
            'brand_header_bg', 'brand_footer_bg',
            'brand_hero_overlay',
            'admin_primary', 'admin_primary_hover',
            'brand_font_ar', 'brand_font_en',
            'brand_border_radius',
            'tagline_ar', 'tagline_en',
            'copyright_text_ar', 'copyright_text_en',
            'developer_name', 'developer_url',
        ];

        try {
            return Setting::whereIn('key', $publicKeys)
                ->pluck('value', 'key')
                ->toArray();
        } catch (\Exception $e) {
            return [];
        }
    }
}
