<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * PT-0.4 — module-level pricing/commission/duration settings for physiotherapy,
 * mirroring the neuropsych pricing seed so the module settings page shows pricing
 * and booking/billing has a module-level fee to fall back on (PricingResolver).
 * Idempotent.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('module_settings')) {
            return;
        }

        $now = now();
        $rows = [
            ['key' => 'consultation_fee', 'value' => '250', 'label_ar' => 'رسوم التقييم', 'label_en' => 'Assessment Fee', 'group' => 'pricing', 'display_order' => 1],
            ['key' => 'followup_fee', 'value' => '150', 'label_ar' => 'رسوم إعادة التقييم', 'label_en' => 'Re-assessment Fee', 'group' => 'pricing', 'display_order' => 2],
            ['key' => 'session_fee', 'value' => '200', 'label_ar' => 'رسوم الجلسة', 'label_en' => 'Session Fee', 'group' => 'pricing', 'display_order' => 3],
            ['key' => 'home_visit_surcharge', 'value' => '100', 'label_ar' => 'رسوم الزيارة المنزلية', 'label_en' => 'Home-visit Surcharge', 'group' => 'pricing', 'display_order' => 4],
            ['key' => 'default_commission', 'value' => '40', 'label_ar' => 'عمولة افتراضية %', 'label_en' => 'Default Commission %', 'group' => 'commission', 'display_order' => 1],
            ['key' => 'consultation_commission', 'value' => '50', 'label_ar' => 'عمولة التقييم %', 'label_en' => 'Assessment Commission %', 'group' => 'commission', 'display_order' => 2],
            ['key' => 'session_commission', 'value' => '45', 'label_ar' => 'عمولة الجلسة %', 'label_en' => 'Session Commission %', 'group' => 'commission', 'display_order' => 3],
            ['key' => 'consultation_duration', 'value' => '45', 'label_ar' => 'مدة التقييم (دقيقة)', 'label_en' => 'Assessment Duration (min)', 'group' => 'duration', 'display_order' => 1],
            ['key' => 'session_duration', 'value' => '45', 'label_ar' => 'مدة الجلسة (دقيقة)', 'label_en' => 'Session Duration (min)', 'group' => 'duration', 'display_order' => 2],
        ];

        foreach ($rows as $row) {
            DB::table('module_settings')->updateOrInsert(
                ['module' => 'physiotherapy', 'key' => $row['key']],
                array_merge($row, ['module' => 'physiotherapy', 'type' => 'number', 'created_at' => $now, 'updated_at' => $now])
            );
        }
    }

    public function down(): void
    {
        DB::table('module_settings')
            ->where('module', 'physiotherapy')
            ->whereIn('group', ['pricing', 'commission', 'duration'])
            ->delete();
    }
};
