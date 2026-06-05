<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * F2 — module-level pricing/commission/duration settings for psychiatry &
 * neurology, mirroring the obgyn pricing seed. Without these the module
 * settings page showed no pricing for these two specialties and booking/
 * billing had no module-level consultation fee to fall back on. Idempotent.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('module_settings')) {
            return;
        }

        $now = now();

        $perModule = [
            'psychiatry' => [
                ['key' => 'consultation_fee', 'value' => '300', 'label_ar' => 'رسوم الكشف', 'label_en' => 'Consultation Fee', 'group' => 'pricing', 'display_order' => 1],
                ['key' => 'followup_fee', 'value' => '200', 'label_ar' => 'رسوم المتابعة', 'label_en' => 'Follow-up Fee', 'group' => 'pricing', 'display_order' => 2],
                ['key' => 'session_fee', 'value' => '350', 'label_ar' => 'رسوم الجلسة العلاجية', 'label_en' => 'Therapy Session Fee', 'group' => 'pricing', 'display_order' => 3],
                ['key' => 'default_commission', 'value' => '40', 'label_ar' => 'عمولة افتراضية %', 'label_en' => 'Default Commission %', 'group' => 'commission', 'display_order' => 1],
                ['key' => 'consultation_commission', 'value' => '50', 'label_ar' => 'عمولة الكشف %', 'label_en' => 'Consultation Commission %', 'group' => 'commission', 'display_order' => 2],
                ['key' => 'consultation_duration', 'value' => '30', 'label_ar' => 'مدة الكشف (دقيقة)', 'label_en' => 'Consultation Duration (min)', 'group' => 'duration', 'display_order' => 1],
            ],
            'neurology' => [
                ['key' => 'consultation_fee', 'value' => '300', 'label_ar' => 'رسوم الكشف', 'label_en' => 'Consultation Fee', 'group' => 'pricing', 'display_order' => 1],
                ['key' => 'followup_fee', 'value' => '200', 'label_ar' => 'رسوم المتابعة', 'label_en' => 'Follow-up Fee', 'group' => 'pricing', 'display_order' => 2],
                ['key' => 'procedure_fee', 'value' => '400', 'label_ar' => 'رسوم الإجراء (EEG/EMG)', 'label_en' => 'Procedure Fee (EEG/EMG)', 'group' => 'pricing', 'display_order' => 3],
                ['key' => 'default_commission', 'value' => '40', 'label_ar' => 'عمولة افتراضية %', 'label_en' => 'Default Commission %', 'group' => 'commission', 'display_order' => 1],
                ['key' => 'consultation_commission', 'value' => '50', 'label_ar' => 'عمولة الكشف %', 'label_en' => 'Consultation Commission %', 'group' => 'commission', 'display_order' => 2],
                ['key' => 'consultation_duration', 'value' => '30', 'label_ar' => 'مدة الكشف (دقيقة)', 'label_en' => 'Consultation Duration (min)', 'group' => 'duration', 'display_order' => 1],
            ],
        ];

        foreach ($perModule as $module => $rows) {
            foreach ($rows as $row) {
                DB::table('module_settings')->updateOrInsert(
                    ['module' => $module, 'key' => $row['key']],
                    array_merge($row, ['module' => $module, 'type' => 'number', 'created_at' => $now, 'updated_at' => $now])
                );
            }
        }
    }

    public function down(): void
    {
        DB::table('module_settings')
            ->whereIn('module', ['psychiatry', 'neurology'])
            ->whereIn('group', ['pricing', 'commission', 'duration'])
            ->delete();
    }
};
