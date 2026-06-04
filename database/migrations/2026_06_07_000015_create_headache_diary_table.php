<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** NP5 — headache diary (ICHD-3 aware). SHARED at patient level. */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('headache_diary')) {
            return;
        }
        Schema::create('headache_diary', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->unsignedTinyInteger('intensity')->nullable(); // 0-10
            $table->unsignedInteger('duration_hours')->nullable();
            $table->string('ichd3_type', 40)->nullable();   // migraine|tension|cluster|medication_overuse|other
            $table->boolean('aura')->default(false);
            $table->string('meds_taken')->nullable();
            $table->string('triggers')->nullable();
            $table->string('entered_by', 12)->default('patient');
            $table->timestamps();
            $table->index(['patient_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('headache_diary');
    }
};
