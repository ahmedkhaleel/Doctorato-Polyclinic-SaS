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
            ['module' => 'insurance', 'key' => 'enabled', 'value' => '1', 'type' => 'boolean', 'label_ar' => 'تفعيل القسم', 'label_en' => 'Enable Module', 'group' => 'general', 'display_order' => 1],
            ['module' => 'insurance', 'key' => 'name_ar', 'value' => 'التأمين', 'type' => 'text', 'label_ar' => 'اسم القسم (عربي)', 'label_en' => 'Module Name (Arabic)', 'group' => 'general', 'display_order' => 2],
            ['module' => 'insurance', 'key' => 'name_en', 'value' => 'Insurance', 'type' => 'text', 'label_ar' => 'اسم القسم (إنجليزي)', 'label_en' => 'Module Name (English)', 'group' => 'general', 'display_order' => 3],
            ['module' => 'insurance', 'key' => 'icon', 'value' => 'M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z', 'type' => 'text', 'label_ar' => 'أيقونة القسم', 'label_en' => 'Module Icon', 'group' => 'general', 'display_order' => 4],
            ['module' => 'insurance', 'key' => 'color', 'value' => '#10B981', 'type' => 'text', 'label_ar' => 'لون القسم', 'label_en' => 'Module Color', 'group' => 'general', 'display_order' => 5],
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
        DB::table('module_settings')->where('module', 'insurance')->delete();
    }
};
