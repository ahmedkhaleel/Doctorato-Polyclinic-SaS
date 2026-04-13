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
            ['module' => 'pediatric', 'key' => 'enabled', 'value' => '1', 'type' => 'boolean', 'label_ar' => 'تفعيل القسم', 'label_en' => 'Enable Module', 'group' => 'general', 'display_order' => 1],
            ['module' => 'pediatric', 'key' => 'name_ar', 'value' => 'طب الأطفال', 'type' => 'text', 'label_ar' => 'اسم القسم (عربي)', 'label_en' => 'Module Name (Arabic)', 'group' => 'general', 'display_order' => 2],
            ['module' => 'pediatric', 'key' => 'name_en', 'value' => 'Pediatrics', 'type' => 'text', 'label_ar' => 'اسم القسم (إنجليزي)', 'label_en' => 'Module Name (English)', 'group' => 'general', 'display_order' => 3],
            ['module' => 'pediatric', 'key' => 'icon', 'value' => 'M12 8.25a3.75 3.75 0 100-7.5 3.75 3.75 0 000 7.5zM6.75 12a.75.75 0 00-.75.75v.008c0 .414.336.75.75.75h.008a.75.75 0 00.75-.75v-.008a.75.75 0 00-.75-.75H6.75zm10.5 0a.75.75 0 00-.75.75v.008c0 .414.336.75.75.75h.008a.75.75 0 00.75-.75v-.008a.75.75 0 00-.75-.75h-.008zM12 10.5c-3.315 0-6 2.685-6 6v3a.75.75 0 00.75.75h10.5a.75.75 0 00.75-.75v-3c0-3.315-2.685-6-6-6z', 'type' => 'text', 'label_ar' => 'أيقونة القسم', 'label_en' => 'Module Icon', 'group' => 'general', 'display_order' => 4],
            ['module' => 'pediatric', 'key' => 'color', 'value' => '#4CAF50', 'type' => 'text', 'label_ar' => 'لون القسم', 'label_en' => 'Module Color', 'group' => 'general', 'display_order' => 5],
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
        DB::table('module_settings')->where('module', 'pediatric')->delete();
    }
};
