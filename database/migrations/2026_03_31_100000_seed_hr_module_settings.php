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
            ['module' => 'hr', 'key' => 'enabled', 'value' => '1', 'type' => 'boolean', 'label_ar' => 'تفعيل القسم', 'label_en' => 'Enable Module', 'group' => 'general', 'display_order' => 1],
            ['module' => 'hr', 'key' => 'name_ar', 'value' => 'الموارد البشرية', 'type' => 'text', 'label_ar' => 'اسم القسم (عربي)', 'label_en' => 'Module Name (Arabic)', 'group' => 'general', 'display_order' => 2],
            ['module' => 'hr', 'key' => 'name_en', 'value' => 'Human Resources', 'type' => 'text', 'label_ar' => 'اسم القسم (إنجليزي)', 'label_en' => 'Module Name (English)', 'group' => 'general', 'display_order' => 3],
            ['module' => 'hr', 'key' => 'icon', 'value' => 'M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128H5.228A2 2 0 015 17.128c0-2.4 1.272-4.536 3.214-5.706M12 6.75a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zm8.25 2.25a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z', 'type' => 'text', 'label_ar' => 'أيقونة القسم', 'label_en' => 'Module Icon', 'group' => 'general', 'display_order' => 4],
            ['module' => 'hr', 'key' => 'color', 'value' => '#F59E0B', 'type' => 'text', 'label_ar' => 'لون القسم', 'label_en' => 'Module Color', 'group' => 'general', 'display_order' => 5],
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
        DB::table('module_settings')->where('module', 'hr')->delete();
    }
};
