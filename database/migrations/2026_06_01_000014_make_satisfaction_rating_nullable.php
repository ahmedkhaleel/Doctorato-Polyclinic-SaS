<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * patient_satisfactions.overall_rating must be nullable: the satisfaction:send-surveys
 * cron creates a PENDING survey (source=sms) before the patient has rated anything —
 * the rating is filled in only when they respond. The original NOT NULL definition
 * made that scheduled command throw. Idempotent / data-safe (widening a constraint).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('patient_satisfactions') || ! Schema::hasColumn('patient_satisfactions', 'overall_rating')) {
            return;
        }
        Schema::table('patient_satisfactions', function (Blueprint $table) {
            $table->unsignedTinyInteger('overall_rating')->nullable()->change();
        });
    }

    public function down(): void
    {
        // Intentionally not reverted: re-adding NOT NULL would fail on pending rows.
    }
};
