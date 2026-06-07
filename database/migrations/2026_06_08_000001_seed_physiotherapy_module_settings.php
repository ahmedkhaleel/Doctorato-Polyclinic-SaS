<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * PT-0.1 — register the Physiotherapy specialty module in module_settings.
 * Ships DISABLED (enabled=0); admin turns it on from module settings once
 * configured. Idempotent (updateOrInsert).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('module_settings')) {
            return;
        }

        $info = [
            'name_ar' => 'العلاج الطبيعي',
            'name_en' => 'Physiotherapy',
            'icon' => 'M3 12h4l3 8 4-16 3 8h4',
            'color' => '#0D9488',
        ];

        $now = now();
        $rows = [
            ['key' => 'enabled', 'value' => '0', 'type' => 'boolean', 'label_ar' => 'تفعيل القسم', 'label_en' => 'Enable Module', 'display_order' => 1],
            ['key' => 'name_ar', 'value' => $info['name_ar'], 'type' => 'text', 'label_ar' => 'اسم القسم (عربي)', 'label_en' => 'Module Name (Arabic)', 'display_order' => 2],
            ['key' => 'name_en', 'value' => $info['name_en'], 'type' => 'text', 'label_ar' => 'اسم القسم (إنجليزي)', 'label_en' => 'Module Name (English)', 'display_order' => 3],
            ['key' => 'icon', 'value' => $info['icon'], 'type' => 'text', 'label_ar' => 'أيقونة القسم', 'label_en' => 'Module Icon', 'display_order' => 4],
            ['key' => 'color', 'value' => $info['color'], 'type' => 'text', 'label_ar' => 'لون القسم', 'label_en' => 'Module Color', 'display_order' => 5],
        ];

        foreach ($rows as $row) {
            DB::table('module_settings')->updateOrInsert(
                ['module' => 'physiotherapy', 'key' => $row['key']],
                array_merge($row, ['module' => 'physiotherapy', 'group' => 'general', 'created_at' => $now, 'updated_at' => $now])
            );
        }
    }

    public function down(): void
    {
        DB::table('module_settings')->where('module', 'physiotherapy')->where('group', 'general')->delete();
    }
};
