<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * PT-4 — seed a starter exercise catalog (shared, no branch) so the HEP
 * (home-exercise-program) picker is usable the moment the module is enabled.
 * Idempotent (updateOrInsert keyed on name_en); admins extend it from the UI.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('exercises')) {
            return;
        }

        $now = now();
        $rows = [
            ['name_en' => 'Hamstring stretch', 'name_ar' => 'تمدد أوتار الركبة', 'region' => 'knee', 'category' => 'stretch', 'default_sets' => 3, 'default_reps' => 1, 'default_hold_sec' => 30],
            ['name_en' => 'Quadriceps set', 'name_ar' => 'تقوية العضلة الرباعية', 'region' => 'knee', 'category' => 'strength', 'default_sets' => 3, 'default_reps' => 10, 'default_hold_sec' => 5],
            ['name_en' => 'Straight leg raise', 'name_ar' => 'رفع الساق المستقيمة', 'region' => 'knee', 'category' => 'strength', 'default_sets' => 3, 'default_reps' => 10, 'default_hold_sec' => 3],
            ['name_en' => 'Heel slides', 'name_ar' => 'انزلاق الكعب', 'region' => 'knee', 'category' => 'mobility', 'default_sets' => 2, 'default_reps' => 15, 'default_hold_sec' => 0],
            ['name_en' => 'Calf stretch', 'name_ar' => 'تمدد عضلة الساق', 'region' => 'ankle', 'category' => 'stretch', 'default_sets' => 3, 'default_reps' => 1, 'default_hold_sec' => 30],
            ['name_en' => 'Ankle pumps', 'name_ar' => 'ضخ الكاحل', 'region' => 'ankle', 'category' => 'mobility', 'default_sets' => 3, 'default_reps' => 20, 'default_hold_sec' => 0],
            ['name_en' => 'Pendulum exercise', 'name_ar' => 'تمرين البندول', 'region' => 'shoulder', 'category' => 'mobility', 'default_sets' => 2, 'default_reps' => 10, 'default_hold_sec' => 0],
            ['name_en' => 'Shoulder external rotation', 'name_ar' => 'تدوير الكتف للخارج', 'region' => 'shoulder', 'category' => 'strength', 'default_sets' => 3, 'default_reps' => 12, 'default_hold_sec' => 2],
            ['name_en' => 'Scapular retraction', 'name_ar' => 'سحب لوح الكتف', 'region' => 'shoulder', 'category' => 'strength', 'default_sets' => 3, 'default_reps' => 12, 'default_hold_sec' => 5],
            ['name_en' => 'Chin tuck', 'name_ar' => 'سحب الذقن', 'region' => 'neck', 'category' => 'mobility', 'default_sets' => 3, 'default_reps' => 10, 'default_hold_sec' => 5],
            ['name_en' => 'Cat-camel', 'name_ar' => 'تمرين القط والجمل', 'region' => 'spine', 'category' => 'mobility', 'default_sets' => 2, 'default_reps' => 10, 'default_hold_sec' => 0],
            ['name_en' => 'Pelvic tilt', 'name_ar' => 'إمالة الحوض', 'region' => 'spine', 'category' => 'core', 'default_sets' => 3, 'default_reps' => 10, 'default_hold_sec' => 5],
            ['name_en' => 'Bird-dog', 'name_ar' => 'تمرين الطائر والكلب', 'region' => 'core', 'category' => 'core', 'default_sets' => 3, 'default_reps' => 10, 'default_hold_sec' => 5],
            ['name_en' => 'Glute bridge', 'name_ar' => 'جسر الأرداف', 'region' => 'hip', 'category' => 'strength', 'default_sets' => 3, 'default_reps' => 12, 'default_hold_sec' => 3],
            ['name_en' => 'Clamshell', 'name_ar' => 'تمرين المحارة', 'region' => 'hip', 'category' => 'strength', 'default_sets' => 3, 'default_reps' => 15, 'default_hold_sec' => 2],
            ['name_en' => 'Wrist flexor stretch', 'name_ar' => 'تمدد ثني الرسغ', 'region' => 'wrist', 'category' => 'stretch', 'default_sets' => 3, 'default_reps' => 1, 'default_hold_sec' => 20],
        ];

        foreach ($rows as $row) {
            DB::table('exercises')->updateOrInsert(
                ['name_en' => $row['name_en']],
                array_merge($row, ['is_active' => 1, 'updated_at' => $now, 'created_at' => $now])
            );
        }
    }

    public function down(): void
    {
        // Non-destructive: catalog rows may be referenced by prescriptions.
    }
};
