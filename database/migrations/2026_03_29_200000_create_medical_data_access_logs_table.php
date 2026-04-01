<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('medical_data_access_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->string('access_type', 30); // view_medical, update_medical, export_medical, print_medical
            $table->string('data_category', 50); // dental_medical, risk_flags, sensitive_medical, full_record
            $table->json('fields_accessed')->nullable(); // Which specific fields were accessed
            $table->string('panel', 20)->nullable(); // admin, doctor, secretary
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->text('reason')->nullable(); // Optional justification for access
            $table->timestamps();

            $table->index(['patient_id', 'created_at']);
            $table->index(['user_id', 'created_at']);
            $table->index(['access_type', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medical_data_access_logs');
    }
};
