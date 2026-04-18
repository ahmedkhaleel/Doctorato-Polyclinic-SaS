<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Cosmetic is NOT a standalone module — it's a sub-type of the "Dermatology & Cosmetic"
 * (derma) module. This migration only registers PRICING settings for cosmetic consultations
 * (used by CommissionCalculator + BookingWorkflowService) and the derma consultation fee.
 *
 * The module metadata rows (enabled, name_ar, name_en, color) were removed because
 * ModuleManager no longer registers 'cosmetic' as a standalone module. Only the
 * 'derma' module appears in the UI — it covers both dermatology and cosmetic features.
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
            // ── Cosmetic pricing (sub-type under derma) ──────────────────────────
            ['module' => 'cosmetic', 'key' => 'consultation_fee', 'value' => '0', 'type' => 'number', 'label_ar' => 'رسوم استشارة التجميل', 'label_en' => 'Cosmetic Consultation Fee', 'group' => 'pricing', 'display_order' => 1],
            ['module' => 'cosmetic', 'key' => 'followup_fee',     'value' => '0', 'type' => 'number', 'label_ar' => 'رسوم متابعة التجميل',    'label_en' => 'Cosmetic Follow-up Fee',    'group' => 'pricing', 'display_order' => 2],

            // ── Derma pricing (main dermatology module) ──────────────────────────
            ['module' => 'derma', 'key' => 'consultation_fee', 'value' => '0', 'type' => 'number', 'label_ar' => 'رسوم الاستشارة', 'label_en' => 'Consultation Fee', 'group' => 'pricing', 'display_order' => 1],
            ['module' => 'derma', 'key' => 'followup_fee',     'value' => '0', 'type' => 'number', 'label_ar' => 'رسوم المتابعة', 'label_en' => 'Follow-up Fee',    'group' => 'pricing', 'display_order' => 2],
        ];

        foreach ($rows as $r) {
            DB::table('module_settings')->updateOrInsert(
                ['module' => $r['module'], 'key' => $r['key']],
                array_merge($r, ['created_at' => $now, 'updated_at' => $now])
            );
        }

        // Clean up any leftover stand-alone module metadata for cosmetic
        DB::table('module_settings')
            ->where('module', 'cosmetic')
            ->whereIn('key', ['enabled', 'name_ar', 'name_en', 'color'])
            ->delete();
    }

    public function down(): void
    {
        if (! Schema::hasTable('module_settings')) {
            return;
        }
        DB::table('module_settings')
            ->where('module', 'cosmetic')
            ->whereIn('key', ['consultation_fee', 'followup_fee'])
            ->delete();
    }
};
