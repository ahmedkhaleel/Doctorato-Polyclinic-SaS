<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\AuditLogger;
use App\Services\SmsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SettingController extends Controller
{
    /**
     * Setting key → group mapping.
     */
    private const KEY_GROUPS = [
        'site_name_ar' => 'general', 'site_name_en' => 'general',
        'site_description_ar' => 'general', 'site_description_en' => 'general',
        'logo' => 'general', 'logo_dark' => 'general', 'favicon' => 'general',
        'currency_code' => 'general', 'currency_symbol' => 'general',
        'currency_name_en' => 'general', 'currency_name_ar' => 'general',
        'currency_position' => 'general', 'currency_decimals' => 'general',
        // Branding & Theme
        'brand_primary' => 'branding', 'brand_primary_hover' => 'branding',
        'brand_secondary' => 'branding', 'brand_accent' => 'branding',
        'brand_sidebar_bg' => 'branding', 'brand_sidebar_text' => 'branding',
        'brand_header_bg' => 'branding', 'brand_footer_bg' => 'branding',
        'brand_hero_overlay' => 'branding',
        'admin_primary' => 'branding', 'admin_primary_hover' => 'branding',
        'brand_font_ar' => 'branding', 'brand_font_en' => 'branding',
        'brand_border_radius' => 'branding',
        'tagline_ar' => 'branding', 'tagline_en' => 'branding',
        'copyright_text_ar' => 'branding', 'copyright_text_en' => 'branding',
        'developer_name' => 'branding', 'developer_url' => 'branding',
        'phone' => 'contact', 'phone_secondary' => 'contact',
        'whatsapp' => 'contact', 'email' => 'contact',
        'address_ar' => 'contact', 'address_en' => 'contact',
        'google_maps_url' => 'contact',
        'working_hours_ar' => 'contact', 'working_hours_en' => 'contact',
        'facebook' => 'social', 'instagram' => 'social',
        'twitter' => 'social', 'tiktok' => 'social',
        'youtube' => 'social', 'snapchat' => 'social',
        'stat_patients' => 'statistics', 'stat_years' => 'statistics',
        'stat_doctors' => 'statistics', 'stat_services' => 'statistics',
        'default_dermatology_fee' => 'consultation', 'default_cosmetic_fee' => 'consultation',
        'dermatology_consultant_fee' => 'consultation', 'dermatology_specialist_fee' => 'consultation',
        'cosmetic_consultation_fee' => 'consultation',
        'followup_fee' => 'consultation', 'followup_window_days' => 'consultation',
        'dental_consultant_fee' => 'consultation', 'dental_specialist_fee' => 'consultation',
        'automation_enabled' => 'automation',
        'sms_enabled' => 'sms', 'sms_provider' => 'sms', 'sms_sender_name' => 'sms',
        'sms_unifonic_app_sid' => 'sms',
        'sms_twilio_account_sid' => 'sms', 'sms_twilio_auth_token' => 'sms', 'sms_twilio_from_number' => 'sms',
        'sms_gateway_url' => 'sms', 'sms_gateway_method' => 'sms', 'sms_gateway_api_key' => 'sms',
        'sms_on_booking_confirmed' => 'sms', 'sms_on_booking_reminder' => 'sms',
        'sms_on_visit_completed' => 'sms', 'sms_on_lab_order_ready' => 'sms',
        'sms_reminder_day_before' => 'sms', 'sms_reminder_same_day' => 'sms',
        'sms_recall_enabled' => 'sms', 'sms_recall_dental_months' => 'sms',
        'sms_recall_derma_months' => 'sms', 'sms_recall_max_per_day' => 'sms',
    ];

    public function index(): Response
    {
        // Convert settings to flat key→value for the form
        $allSettings = Setting::all();
        $flat = [];
        foreach ($allSettings as $s) {
            $flat[$s->key] = $s->value;
        }

        return Inertia::render('Admin/Settings/Index', [
            'settings' => $flat,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $allowedKeys = array_keys(self::KEY_GROUPS);
        $data = $request->only($allowedKeys);

        $updatedKeys = [];
        foreach ($data as $key => $value) {
            // Defensive: never overwrite a stored secret with its masked
            // placeholder ('••••') when the user leaves the field untouched.
            // (Mirrors TelemedicineSettingsController; harmless for plain keys.)
            if (Setting::isEncryptedKey($key) && is_string($value) && str_contains($value, '•')) {
                continue;
            }

            $group = self::KEY_GROUPS[$key] ?? 'general';
            Setting::set($key, $value ?? '', $group);
            $updatedKeys[] = $key;
        }

        // ADR-001 Phase 3 (dual-write): if any legacy fee key changed, mirror the
        // current values into module_settings so the resolver (which prefers a
        // positive module_settings value) stays in sync. Legacy keys remain
        // written above as the rollback path until Phase 4 retires them.
        $mirror = app(\App\Services\Pricing\PricingSettingsMirror::class);
        if ($mirror->touchesPricing($updatedKeys)) {
            $mirror->mirror();
        }

        AuditLogger::log('updated', null, ['settings' => $updatedKeys]);

        return redirect()->back()->with('success', 'Settings updated successfully.');
    }

    public function testSms(Request $request): RedirectResponse
    {
        $request->validate(['phone' => 'required|string|min:8']);

        $result = SmsService::sendTest($request->phone);

        return redirect()->back()->with(
            $result['success'] ? 'success' : 'error',
            $result['message']
        );
    }

    public function switchLocale(Request $request): RedirectResponse
    {
        $locale = $request->input('locale', 'ar');
        session()->put('admin_locale', in_array($locale, ['ar', 'en']) ? $locale : 'ar');

        return redirect()->back();
    }
}
