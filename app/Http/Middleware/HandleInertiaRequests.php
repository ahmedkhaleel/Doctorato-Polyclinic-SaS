<?php

namespace App\Http\Middleware;

use App\Models\Booking;
use App\Models\ContactMessage;
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

        // Update last_seen_at for online status tracking
        if ($request->user()) {
            $request->user()->updateQuietly(['last_seen_at' => now()]);
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
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
                'credentials' => fn () => $request->session()->get('credentials'),
            ],
            'settings' => fn () => $this->getSettings(),
            'modules' => fn () => ModuleManager::getForFrontend(),
            'notifications' => function () use ($request) {
                if (!$request->user()) return null;
                try {
                    return [
                        'unread_bookings' => Booking::where('is_read', false)->count(),
                        'unread_messages' => ContactMessage::where('is_read', false)->count(),
                        'unread_system' => $request->user()->unreadNotifications()->count(),
                    ];
                } catch (\Throwable) {
                    return ['unread_bookings' => 0, 'unread_messages' => 0, 'unread_system' => 0];
                }
            },
            'doctor_notifications' => function () use ($isDoctorRoute, $request) {
                if (!$isDoctorRoute || !$request->user()) return null;
                try {
                    return ['unread_count' => $request->user()->unreadNotifications()->count()];
                } catch (\Throwable) {
                    return ['unread_count' => 0];
                }
            },
            'secretary_notifications' => function () use ($isSecretaryRoute, $request) {
                if (!$isSecretaryRoute || !$request->user()) return null;
                try {
                    return [
                        'unread_bookings' => Booking::where('is_read', false)->count(),
                        'unread_messages' => ContactMessage::where('is_read', false)->count(),
                        'unread_dental' => $request->user()->unreadNotifications()->count(),
                    ];
                } catch (\Throwable) {
                    return ['unread_bookings' => 0, 'unread_messages' => 0, 'unread_dental' => 0];
                }
            },
            'chat_notifications' => fn () => $request->user() ? [
                'unread_count' => Message::where('receiver_id', $request->user()->id)
                    ->whereNull('read_at')->count(),
            ] : null,
        ];
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
