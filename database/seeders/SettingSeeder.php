<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    /**
     * Seed clinic settings based on production data.
     */
    public function run(): void
    {
        $settings = [
            // ── Contact ─────────────────────────────────────────────────
            ['key' => 'phone_1',          'value' => '01007729159',                                      'group' => 'contact'],
            ['key' => 'phone_2',          'value' => '0238244047',                                       'group' => 'contact'],
            ['key' => 'whatsapp',         'value' => '01007729159',                                      'group' => 'contact'],
            ['key' => 'email',            'value' => 'info@aura-clinic.net',                             'group' => 'contact'],

            // ── Address ─────────────────────────────────────────────────
            ['key' => 'address_ar',       'value' => '٦ أكتوبر - كايرو ميديكال سنتر - المحور المركزي - الدور الثاني - عيادة 71', 'group' => 'contact'],
            ['key' => 'address_en',       'value' => 'CMC (Cairo Medical Center), Central Axis, 6th of October City, 2nd Floor, Clinic No. 71', 'group' => 'contact'],
            ['key' => 'google_maps',      'value' => 'https://maps.app.goo.gl/AGMjNFK4ketaUnGH8',       'group' => 'contact'],

            // ── Social Media ────────────────────────────────────────────
            ['key' => 'facebook',         'value' => 'https://www.facebook.com/auradermaclinic',         'group' => 'social'],
            ['key' => 'instagram',        'value' => 'https://www.instagram.com/auradermaclinic',        'group' => 'social'],
            ['key' => 'tiktok',           'value' => 'https://www.tiktok.com/@auradermaclinic',          'group' => 'social'],

            // ── Working Hours ───────────────────────────────────────────
            ['key' => 'working_hours_ar', 'value' => 'يومياً من 10:00 صباحاً حتى 10:00 مساءً',          'group' => 'contact'],
            ['key' => 'working_hours_en', 'value' => 'Daily from 10:00 AM to 10:00 PM',                 'group' => 'contact'],

            // ── Homepage Stats ──────────────────────────────────────────
            ['key' => 'stats_clients',    'value' => '1000',                                             'group' => 'stats'],
            ['key' => 'stats_doctors',    'value' => '10',                                               'group' => 'stats'],
            ['key' => 'stats_services',   'value' => '20',                                               'group' => 'stats'],
            ['key' => 'stats_devices',    'value' => '8',                                                'group' => 'stats'],

            // ── General ─────────────────────────────────────────────────
            ['key' => 'site_name_ar',         'value' => '', 'group' => 'general'],
            ['key' => 'site_name_en',         'value' => '', 'group' => 'general'],
            ['key' => 'site_description_ar',  'value' => '', 'group' => 'general'],
            ['key' => 'site_description_en',  'value' => '', 'group' => 'general'],
            ['key' => 'logo',                 'value' => '', 'group' => 'general'],
            ['key' => 'favicon',              'value' => '', 'group' => 'general'],

            // ── Additional Contact ──────────────────────────────────────
            ['key' => 'phone',            'value' => '', 'group' => 'contact'],
            ['key' => 'phone_secondary',  'value' => '', 'group' => 'contact'],
            ['key' => 'google_maps_url',  'value' => '', 'group' => 'contact'],

            // ── Additional Social ───────────────────────────────────────
            ['key' => 'twitter',          'value' => '', 'group' => 'social'],
            ['key' => 'youtube',          'value' => '', 'group' => 'social'],
            ['key' => 'snapchat',         'value' => '', 'group' => 'social'],

            // ── Statistics ──────────────────────────────────────────────
            ['key' => 'stat_patients',    'value' => '', 'group' => 'statistics'],
            ['key' => 'stat_years',       'value' => '', 'group' => 'statistics'],
            ['key' => 'stat_doctors',     'value' => '', 'group' => 'statistics'],
            ['key' => 'stat_services',    'value' => '', 'group' => 'statistics'],

            // ── Currency ───────────────────────────────────────────────
            ['key' => 'currency_code',     'value' => 'EGP',            'group' => 'general'],
            ['key' => 'currency_symbol',   'value' => 'E£',             'group' => 'general'],
            ['key' => 'currency_name_en',  'value' => 'Egyptian Pound', 'group' => 'general'],
            ['key' => 'currency_name_ar',  'value' => 'جنيه مصري',      'group' => 'general'],
            ['key' => 'currency_position', 'value' => 'after',          'group' => 'general'],
            ['key' => 'currency_decimals', 'value' => '0',              'group' => 'general'],

            // ── Consultation Fees ───────────────────────────────────────
            ['key' => 'default_dermatology_fee',   'value' => '400', 'group' => 'consultation'],
            ['key' => 'default_cosmetic_fee',      'value' => '200', 'group' => 'consultation'],
            ['key' => 'dermatology_consultant_fee', 'value' => '500', 'group' => 'consultation'],
            ['key' => 'dermatology_specialist_fee', 'value' => '400', 'group' => 'consultation'],
            ['key' => 'cosmetic_consultation_fee',  'value' => '200', 'group' => 'consultation'],
            ['key' => 'followup_fee',              'value' => '100', 'group' => 'consultation'],
            ['key' => 'followup_window_days',      'value' => '15',  'group' => 'consultation'],

            // ── Automation ────────────────────────────────────────
            ['key' => 'automation_enabled',  'value' => '1', 'group' => 'automation'],
        ];

        foreach ($settings as $setting) {
            Setting::updateOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }
    }
}
