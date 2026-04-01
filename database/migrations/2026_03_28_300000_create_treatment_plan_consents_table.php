<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('treatment_plan_consents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dental_treatment_plan_id')->constrained('dental_treatment_plans')->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->foreignId('sent_by')->nullable()->constrained('users')->nullOnDelete();

            $table->enum('status', ['pending', 'signed', 'declined', 'expired'])->default('pending');

            $table->timestamp('sent_at')->nullable();
            $table->timestamp('signed_at')->nullable();
            $table->timestamp('declined_at')->nullable();
            $table->timestamp('expires_at')->nullable();

            $table->string('signature_image_path')->nullable();
            $table->string('patient_ip')->nullable();
            $table->text('patient_user_agent')->nullable();

            $table->json('consent_text_snapshot')->nullable();
            $table->text('risks_notes')->nullable();
            $table->string('pdf_path')->nullable();
            $table->text('declined_reason')->nullable();

            $table->timestamps();

            $table->index(['dental_treatment_plan_id', 'status']);
            $table->index(['patient_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('treatment_plan_consents');
    }
};
