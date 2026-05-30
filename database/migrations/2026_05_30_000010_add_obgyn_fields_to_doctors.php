<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * OB/GYN doctor fee + commission, mirroring the pediatric doctor fields.
 * Doctor↔module association uses the existing `doctors.module` column
 * (Doctor::scopeForModule) — no is_obgyn flag. Idempotent.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('doctors')) {
            return;
        }

        Schema::table('doctors', function (Blueprint $table) {
            if (! Schema::hasColumn('doctors', 'obgyn_consultation_fee')) {
                $table->decimal('obgyn_consultation_fee', 8, 2)->nullable();
            }
            if (! Schema::hasColumn('doctors', 'obgyn_consultation_commission')) {
                $table->decimal('obgyn_consultation_commission', 5, 2)->nullable();
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('doctors')) {
            return;
        }

        Schema::table('doctors', function (Blueprint $table) {
            foreach (['obgyn_consultation_fee', 'obgyn_consultation_commission'] as $col) {
                if (Schema::hasColumn('doctors', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
