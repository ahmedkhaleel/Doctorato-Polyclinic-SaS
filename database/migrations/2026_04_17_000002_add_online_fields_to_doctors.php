<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('doctors', function (Blueprint $table) {
            if (!Schema::hasColumn('doctors', 'online_consultation_enabled')) {
                $table->boolean('online_consultation_enabled')->default(false);
            }
            if (!Schema::hasColumn('doctors', 'online_consultation_fee')) {
                $table->decimal('online_consultation_fee', 10, 2)->nullable();
            }
            if (!Schema::hasColumn('doctors', 'online_session_duration_minutes')) {
                $table->integer('online_session_duration_minutes')->default(30);
            }
            if (!Schema::hasColumn('doctors', 'online_consultation_bio_ar')) {
                $table->text('online_consultation_bio_ar')->nullable();
            }
            if (!Schema::hasColumn('doctors', 'online_consultation_bio_en')) {
                $table->text('online_consultation_bio_en')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('doctors', function (Blueprint $table) {
            foreach ([
                'online_consultation_enabled',
                'online_consultation_fee',
                'online_session_duration_minutes',
                'online_consultation_bio_ar',
                'online_consultation_bio_en',
            ] as $col) {
                if (Schema::hasColumn('doctors', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
