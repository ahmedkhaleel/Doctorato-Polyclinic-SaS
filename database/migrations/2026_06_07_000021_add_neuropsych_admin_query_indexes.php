<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Performance (P4): the admin neuropsych screens filter encounters by `module`
 * alone (and with doctor_id/date) and scale_results by `scale_key` alone, but
 * the existing composite indexes lead with patient_id, so those scans can't
 * use them. Add module-leading / scale_key-leading indexes. Idempotent.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('neuropsych_encounters')) {
            Schema::table('neuropsych_encounters', function (Blueprint $t) {
                if (! $this->hasIndex('neuropsych_encounters', 'npenc_module_date_idx')) {
                    $t->index(['module', 'encounter_date'], 'npenc_module_date_idx');
                }
                if (! $this->hasIndex('neuropsych_encounters', 'npenc_module_doctor_idx')) {
                    $t->index(['module', 'doctor_id'], 'npenc_module_doctor_idx');
                }
            });
        }

        if (Schema::hasTable('scale_results')) {
            Schema::table('scale_results', function (Blueprint $t) {
                if (! $this->hasIndex('scale_results', 'scaleres_key_takenat_idx')) {
                    $t->index(['scale_key', 'taken_at'], 'scaleres_key_takenat_idx');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('neuropsych_encounters')) {
            Schema::table('neuropsych_encounters', function (Blueprint $t) {
                $t->dropIndex('npenc_module_date_idx');
                $t->dropIndex('npenc_module_doctor_idx');
            });
        }
        if (Schema::hasTable('scale_results')) {
            Schema::table('scale_results', function (Blueprint $t) {
                $t->dropIndex('scaleres_key_takenat_idx');
            });
        }
    }

    private function hasIndex(string $table, string $index): bool
    {
        // MySQL-specific (prod + CI both run MySQL).
        return ! empty(DB::select('SHOW INDEX FROM `'.$table.'` WHERE Key_name = ?', [$index]));
    }
};
