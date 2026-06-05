<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * P1 — complete the per-module pricing set for obgyn / psychiatry / neurology
 * so they match derma/dental/pediatric: separate consultant & specialist
 * consultation fees, a follow-up fee, and a follow-up window (days). The base
 * `consultation_fee` was seeded earlier (…000020) and stays as the fallback.
 * Idempotent (updateOrInsert).
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
            ['key' => 'consultant_fee', 'value' => '350', 'label_ar' => 'رسوم كشف استشاري', 'label_en' => 'Consultant Consultation Fee', 'display_order' => 2],
            ['key' => 'specialist_fee', 'value' => '250', 'label_ar' => 'رسوم كشف أخصائي', 'label_en' => 'Specialist Consultation Fee', 'display_order' => 3],
            ['key' => 'followup_fee', 'value' => '150', 'label_ar' => 'رسوم المتابعة', 'label_en' => 'Follow-up Fee', 'display_order' => 5],
            ['key' => 'followup_window_days', 'value' => '14', 'label_ar' => 'مدة المتابعة (أيام)', 'label_en' => 'Follow-up Window (days)', 'display_order' => 6],
        ];

        foreach (['obgyn', 'psychiatry', 'neurology'] as $module) {
            foreach ($rows as $row) {
                DB::table('module_settings')->updateOrInsert(
                    ['module' => $module, 'key' => $row['key']],
                    array_merge($row, ['module' => $module, 'type' => 'number', 'group' => 'pricing', 'created_at' => $now, 'updated_at' => $now])
                );
            }
        }
    }

    public function down(): void
    {
        DB::table('module_settings')
            ->whereIn('module', ['obgyn', 'psychiatry', 'neurology'])
            ->whereIn('key', ['consultant_fee', 'specialist_fee', 'followup_fee', 'followup_window_days'])
            ->delete();
    }
};
