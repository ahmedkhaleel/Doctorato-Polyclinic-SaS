<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('pregnancies')) {
            return;
        }

        Schema::create('pregnancies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('doctor_id')->nullable()->constrained('doctors')->nullOnDelete();
            $table->date('lmp')->nullable();                       // last menstrual period
            $table->date('edd')->nullable();                       // estimated delivery date
            $table->string('edd_source', 10)->default('lmp');      // lmp / scan
            $table->unsignedSmallInteger('gravida')->nullable();
            $table->unsignedSmallInteger('para')->nullable();
            $table->string('conception_method')->nullable();       // natural / ivf / iui
            $table->string('blood_group', 5)->nullable();
            $table->string('rh_factor', 10)->nullable();
            $table->boolean('is_high_risk')->default(false);
            $table->json('risk_factors')->nullable();
            $table->string('status', 20)->default('active');       // active / delivered / miscarried / terminated
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['patient_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pregnancies');
    }
};
