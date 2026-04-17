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
        $rows = [
            ['module' => 'cosmetic', 'key' => 'enabled', 'value' => '1', 'type' => 'boolean', 'label_ar' => 'تفعيل القسم', 'label_en' => 'Enable Module', 'group' => 'general', 'display_order' => 1],
            ['module' => 'cosmetic', 'key' => 'name_ar', 'value' => 'التجميل', 'type' => 'text', 'label_ar' => 'اسم القسم (عربي)', 'label_en' => 'Module Name (Arabic)', 'group' => 'general', 'display_order' => 2],
            ['module' => 'cosmetic', 'key' => 'name_en', 'value' => 'Cosmetic Medicine', 'type' => 'text', 'label_ar' => 'اسم القسم (إنجليزي)', 'label_en' => 'Module Name (English)', 'group' => 'general', 'display_order' => 3],
            ['module' => 'cosmetic', 'key' => 'color', 'value' => '#8B5CF6', 'type' => 'text', 'label_ar' => 'لون القسم', 'label_en' => 'Module Color', 'group' => 'general', 'display_order' => 5],
            ['module' => 'cosmetic', 'key' => 'consultation_fee', 'value' => '0', 'type' => 'number', 'label_ar' => 'رسوم الاستشارة', 'label_en' => 'Consultation Fee', 'group' => 'pricing', 'display_order' => 1],
            ['module' => 'cosmetic', 'key' => 'followup_fee', 'value' => '0', 'type' => 'number', 'label_ar' => 'رسوم المتابعة', 'label_en' => 'Follow-up Fee', 'group' => 'pricing', 'display_order' => 2],

            ['module' => 'derma', 'key' => 'consultation_fee', 'value' => '0', 'type' => 'number', 'label_ar' => 'رسوم الاستشارة', 'label_en' => 'Consultation Fee', 'group' => 'pricing', 'display_order' => 1],
            ['module' => 'derma', 'key' => 'followup_fee', 'value' => '0', 'type' => 'number', 'label_ar' => 'رسوم المتابعة', 'label_en' => 'Follow-up Fee', 'group' => 'pricing', 'display_order' => 2],
        ];

        foreach ($rows as $r) {
            DB::table('module_settings')->updateOrInsert(
                ['module' => $r['module'], 'key' => $r['key']],
                array_merge($r, ['created_at' => $now, 'updated_at' => $now])
            );
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('module_settings')) {
            return;
        }
        DB::table('module_settings')->where('module', 'cosmetic')->delete();
    }
};
