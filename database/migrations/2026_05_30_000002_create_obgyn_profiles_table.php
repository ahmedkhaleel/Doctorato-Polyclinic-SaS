<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('obgyn_profiles')) {
            return;
        }

        Schema::create('obgyn_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('doctor_id')->nullable()->constrained('doctors')->nullOnDelete();
            $table->unsignedTinyInteger('menarche_age')->nullable();
            $table->unsignedSmallInteger('gravida')->default(0);
            $table->unsignedSmallInteger('para')->default(0);
            $table->unsignedSmallInteger('abortus')->default(0);
            $table->unsignedSmallInteger('living_children')->default(0);
            $table->string('blood_group', 5)->nullable();
            $table->string('rh_factor', 10)->nullable();     // positive / negative
            $table->date('lmp')->nullable();
            $table->unsignedSmallInteger('cycle_length_days')->nullable();
            $table->string('contraception_method')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique('patient_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('obgyn_profiles');
    }
};
