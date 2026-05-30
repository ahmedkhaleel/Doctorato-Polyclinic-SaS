<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('obstetric_ultrasounds')) {
            return;
        }

        Schema::create('obstetric_ultrasounds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pregnancy_id')->constrained()->cascadeOnDelete();
            $table->foreignId('visit_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('doctor_id')->nullable()->constrained('doctors')->nullOnDelete();
            $table->date('scan_date');
            $table->string('scan_type', 20)->default('growth');    // dating / anomaly / growth / doppler
            $table->decimal('gestational_age_weeks', 4, 1)->nullable();
            $table->decimal('bpd_mm', 6, 1)->nullable();           // biparietal diameter
            $table->decimal('hc_mm', 6, 1)->nullable();            // head circumference
            $table->decimal('ac_mm', 6, 1)->nullable();            // abdominal circumference
            $table->decimal('fl_mm', 6, 1)->nullable();            // femur length
            $table->unsignedInteger('efw_grams')->nullable();      // estimated fetal weight
            $table->string('placenta_position')->nullable();
            $table->decimal('afi', 5, 1)->nullable();              // amniotic fluid index
            $table->unsignedTinyInteger('fetal_count')->default(1);
            $table->boolean('fetal_heart')->default(true);
            $table->string('presentation')->nullable();
            $table->text('findings')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['pregnancy_id', 'scan_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('obstetric_ultrasounds');
    }
};
