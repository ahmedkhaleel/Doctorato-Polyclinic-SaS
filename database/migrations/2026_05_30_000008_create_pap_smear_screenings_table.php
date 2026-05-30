<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('pap_smear_screenings')) {
            return;
        }

        Schema::create('pap_smear_screenings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('visit_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('doctor_id')->nullable()->constrained('doctors')->nullOnDelete();
            $table->date('test_date');
            $table->string('result', 20)->nullable();              // normal / ascus / lsil / hsil / cancer
            $table->string('hpv_status', 12)->nullable();          // positive / negative / unknown
            $table->date('next_due_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['patient_id', 'test_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pap_smear_screenings');
    }
};
