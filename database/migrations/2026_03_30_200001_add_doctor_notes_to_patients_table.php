<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('patients') && ! Schema::hasColumn('patients', 'doctor_notes')) {
            Schema::table('patients', function (Blueprint $table) {
                $table->text('doctor_notes')->nullable()->after('medical_notes');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('patients') && Schema::hasColumn('patients', 'doctor_notes')) {
            Schema::table('patients', function (Blueprint $table) {
                $table->dropColumn('doctor_notes');
            });
        }
    }
};
