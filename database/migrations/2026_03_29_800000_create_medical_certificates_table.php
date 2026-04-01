<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('medical_certificates', function (Blueprint $table) {
            $table->id();
            $table->string('certificate_number', 30)->unique();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('doctor_id')->constrained()->cascadeOnDelete();
            $table->foreignId('visit_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->enum('type', ['sick_leave', 'fitness', 'medical_report', 'referral_letter', 'follow_up'])->default('sick_leave');
            $table->date('issue_date');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->unsignedSmallInteger('days')->nullable();
            $table->string('diagnosis')->nullable();
            $table->text('notes')->nullable();
            $table->text('recommendations')->nullable();
            $table->enum('status', ['draft', 'issued', 'cancelled'])->default('draft');
            $table->timestamp('issued_at')->nullable();
            $table->timestamps();

            $table->index(['patient_id', 'type']);
            $table->index(['doctor_id', 'issue_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medical_certificates');
    }
};
