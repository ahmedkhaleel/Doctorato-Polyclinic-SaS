<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * NP0.1 — register the Psychiatry and Neurology specialty modules in
 * module_settings. Both ship DISABLED (enabled=0); the admin turns them on
 * from module settings once configured. Idempotent (updateOrInsert).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('module_settings')) {
            return;
        }

        $modules = [
            'psychiatry' => [
                'name_ar' => 'الطب النفسي',
                'name_en' => 'Psychiatry',
                'icon' => 'M12 18v-5.25m0 0a6.01 6.01 0 001.5-.189m-1.5.189a6.01 6.01 0 01-1.5-.189m3.75 7.478a12.06 12.06 0 01-4.5 0m3.75 2.383a14.406 14.406 0 01-3 0M14.25 18v-.192c0-.983.658-1.823 1.508-2.316a7.5 7.5 0 10-7.517 0c.85.493 1.509 1.333 1.509 2.316V18',
                'color' => '#7C3AED',
            ],
            'neurology' => [
                'name_ar' => 'طب الأعصاب',
                'name_en' => 'Neurology',
                'icon' => 'M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z',
                'color' => '#0EA5E9',
            ],
        ];

        $now = now();
        foreach ($modules as $module => $info) {
            $rows = [
                ['key' => 'enabled', 'value' => '0', 'type' => 'boolean', 'label_ar' => 'تفعيل القسم', 'label_en' => 'Enable Module', 'display_order' => 1],
                ['key' => 'name_ar', 'value' => $info['name_ar'], 'type' => 'text', 'label_ar' => 'اسم القسم (عربي)', 'label_en' => 'Module Name (Arabic)', 'display_order' => 2],
                ['key' => 'name_en', 'value' => $info['name_en'], 'type' => 'text', 'label_ar' => 'اسم القسم (إنجليزي)', 'label_en' => 'Module Name (English)', 'display_order' => 3],
                ['key' => 'icon', 'value' => $info['icon'], 'type' => 'text', 'label_ar' => 'أيقونة القسم', 'label_en' => 'Module Icon', 'display_order' => 4],
                ['key' => 'color', 'value' => $info['color'], 'type' => 'text', 'label_ar' => 'لون القسم', 'label_en' => 'Module Color', 'display_order' => 5],
            ];

            foreach ($rows as $row) {
                DB::table('module_settings')->updateOrInsert(
                    ['module' => $module, 'key' => $row['key']],
                    array_merge($row, ['module' => $module, 'group' => 'general', 'created_at' => $now, 'updated_at' => $now])
                );
            }
        }
    }

    public function down(): void
    {
        DB::table('module_settings')->whereIn('module', ['psychiatry', 'neurology'])->where('group', 'general')->delete();
    }
};
