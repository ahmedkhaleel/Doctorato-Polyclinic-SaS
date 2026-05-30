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

        $settings = [
            ['module' => 'obgyn', 'key' => 'enabled', 'value' => '1', 'type' => 'boolean', 'label_ar' => 'تفعيل القسم', 'label_en' => 'Enable Module', 'group' => 'general', 'display_order' => 1],
            ['module' => 'obgyn', 'key' => 'name_ar', 'value' => 'النساء والتوليد', 'type' => 'text', 'label_ar' => 'اسم القسم (عربي)', 'label_en' => 'Module Name (Arabic)', 'group' => 'general', 'display_order' => 2],
            ['module' => 'obgyn', 'key' => 'name_en', 'value' => 'Obstetrics & Gynecology', 'type' => 'text', 'label_ar' => 'اسم القسم (إنجليزي)', 'label_en' => 'Module Name (English)', 'group' => 'general', 'display_order' => 3],
            ['module' => 'obgyn', 'key' => 'icon', 'value' => 'M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z', 'type' => 'text', 'label_ar' => 'أيقونة القسم', 'label_en' => 'Module Icon', 'group' => 'general', 'display_order' => 4],
            ['module' => 'obgyn', 'key' => 'color', 'value' => '#DB2777', 'type' => 'text', 'label_ar' => 'لون القسم', 'label_en' => 'Module Color', 'group' => 'general', 'display_order' => 5],
        ];

        $now = now();
        foreach ($settings as $setting) {
            DB::table('module_settings')->updateOrInsert(
                ['module' => $setting['module'], 'key' => $setting['key']],
                array_merge($setting, ['created_at' => $now, 'updated_at' => $now])
            );
        }
    }

    public function down(): void
    {
        DB::table('module_settings')->where('module', 'obgyn')->where('group', 'general')->delete();
    }
};
