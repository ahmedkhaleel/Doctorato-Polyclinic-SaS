<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('doctor_schedules', function (Blueprint $table) {
            if (!Schema::hasColumn('doctor_schedules', 'mode')) {
                $table->enum('mode', ['in_person', 'online', 'both'])
                      ->default('in_person')
                      ->after('end_time');
            }
            if (!Schema::hasColumn('doctor_schedules', 'slot_duration_minutes')) {
                $table->integer('slot_duration_minutes')->default(30);
            }
            if (!Schema::hasColumn('doctor_schedules', 'buffer_minutes')) {
                $table->integer('buffer_minutes')->default(5);
            }
        });
    }

    public function down(): void
    {
        Schema::table('doctor_schedules', function (Blueprint $table) {
            foreach (['mode', 'slot_duration_minutes', 'buffer_minutes'] as $col) {
                if (Schema::hasColumn('doctor_schedules', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
