<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * PT-0.3 — physiotherapy doctor fee/commission fields, mirroring the
 * neuropsych/obgyn doctor fields. The doctor↔module association reuses the
 * existing `doctors.module` column (Doctor::scopeForModule) — module value is
 * 'physiotherapy'. Idempotent.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('doctors')) {
            return;
        }

        Schema::table('doctors', function (Blueprint $table) {
            foreach ([
                'physiotherapy_consultation_fee',
                'physiotherapy_consultation_commission',
                'physiotherapy_session_fee',
                'physiotherapy_session_commission',
            ] as $col) {
                if (! Schema::hasColumn('doctors', $col)) {
                    $table->decimal($col, 8, 2)->nullable();
                }
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('doctors')) {
            return;
        }

        Schema::table('doctors', function (Blueprint $table) {
            foreach ([
                'physiotherapy_consultation_fee',
                'physiotherapy_consultation_commission',
                'physiotherapy_session_fee',
                'physiotherapy_session_commission',
            ] as $col) {
                if (Schema::hasColumn('doctors', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
