<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('module_settings')) {
            return;
        }

        $now = now();
        $settings = [
            // Pricing group
            ['module' => 'pediatric', 'key' => 'consultation_consultant_fee', 'value' => '200', 'type' => 'number', 'label_ar' => 'رسوم استشاري الأطفال', 'label_en' => 'Pediatric Consultant Fee', 'group' => 'pricing', 'display_order' => 1],
            ['module' => 'pediatric', 'key' => 'consultation_specialist_fee', 'value' => '150', 'type' => 'number', 'label_ar' => 'رسوم أخصائي الأطفال', 'label_en' => 'Pediatric Specialist Fee', 'group' => 'pricing', 'display_order' => 2],
            ['module' => 'pediatric', 'key' => 'followup_fee', 'value' => '100', 'type' => 'number', 'label_ar' => 'رسوم المتابعة', 'label_en' => 'Follow-up Fee', 'group' => 'pricing', 'display_order' => 3],
            ['module' => 'pediatric', 'key' => 'vaccination_fee', 'value' => '50', 'type' => 'number', 'label_ar' => 'رسوم التطعيم', 'label_en' => 'Vaccination Fee', 'group' => 'pricing', 'display_order' => 4],
            ['module' => 'pediatric', 'key' => 'screening_fee', 'value' => '75', 'type' => 'number', 'label_ar' => 'رسوم الفحص', 'label_en' => 'Screening Fee', 'group' => 'pricing', 'display_order' => 5],

            // Commission group
            ['module' => 'pediatric', 'key' => 'default_commission', 'value' => '30', 'type' => 'number', 'label_ar' => 'عمولة افتراضية %', 'label_en' => 'Default Commission %', 'group' => 'commission', 'display_order' => 1],
            ['module' => 'pediatric', 'key' => 'consultation_commission', 'value' => '50', 'type' => 'number', 'label_ar' => 'عمولة الاستشارة %', 'label_en' => 'Consultation Commission %', 'group' => 'commission', 'display_order' => 2],
            ['module' => 'pediatric', 'key' => 'followup_commission', 'value' => '40', 'type' => 'number', 'label_ar' => 'عمولة المتابعة %', 'label_en' => 'Follow-up Commission %', 'group' => 'commission', 'display_order' => 3],

            // Duration group
            ['module' => 'pediatric', 'key' => 'consultation_duration', 'value' => '25', 'type' => 'number', 'label_ar' => 'مدة الاستشارة (دقيقة)', 'label_en' => 'Consultation Duration (min)', 'group' => 'duration', 'display_order' => 1],
            ['module' => 'pediatric', 'key' => 'session_duration', 'value' => '30', 'type' => 'number', 'label_ar' => 'مدة الجلسة (دقيقة)', 'label_en' => 'Session Duration (min)', 'group' => 'duration', 'display_order' => 2],
        ];

        foreach ($settings as $setting) {
            DB::table('module_settings')->updateOrInsert(
                ['module' => $setting['module'], 'key' => $setting['key']],
                array_merge($setting, ['created_at' => $now, 'updated_at' => $now])
            );
        }
    }

    public function down(): void
    {
        DB::table('module_settings')
            ->where('module', 'pediatric')
            ->whereIn('group', ['pricing', 'commission', 'duration'])
            ->delete();
    }
};
