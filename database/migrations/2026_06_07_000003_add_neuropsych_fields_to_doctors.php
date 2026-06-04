<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * NP0.3 — psychiatry & neurology doctor fee/commission fields, mirroring the
 * obgyn/pediatric doctor fields. The doctor↔module association reuses the
 * existing `doctors.module` column (Doctor::scopeForModule) — module value is
 * 'psychiatry' or 'neurology'; no extra track flag. `treats_children` gates the
 * child ADHD / developmental workflows. Idempotent.
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
                'psychiatry_consultation_fee',
                'psychiatry_consultation_commission',
                'neurology_consultation_fee',
                'neurology_consultation_commission',
            ] as $col) {
                if (! Schema::hasColumn('doctors', $col)) {
                    $table->decimal($col, 8, 2)->nullable();
                }
            }
            if (! Schema::hasColumn('doctors', 'treats_children')) {
                $table->boolean('treats_children')->default(false);
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
                'psychiatry_consultation_fee',
                'psychiatry_consultation_commission',
                'neurology_consultation_fee',
                'neurology_consultation_commission',
                'treats_children',
            ] as $col) {
                if (Schema::hasColumn('doctors', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
