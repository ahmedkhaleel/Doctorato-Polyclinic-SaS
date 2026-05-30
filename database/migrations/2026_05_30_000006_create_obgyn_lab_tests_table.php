<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('obgyn_lab_tests')) {
            return;
        }

        Schema::create('obgyn_lab_tests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('pregnancy_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('doctor_id')->nullable()->constrained('doctors')->nullOnDelete();
            $table->string('test_type');                           // CBC / blood_group / OGTT / TORCH / ...
            $table->string('value')->nullable();
            $table->string('unit')->nullable();
            $table->string('reference_range')->nullable();
            $table->date('result_date')->nullable();
            $table->boolean('is_abnormal')->default(false);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['patient_id', 'result_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('obgyn_lab_tests');
    }
};
