<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('referrals', function (Blueprint $table) {
            $table->id();
            $table->string('referral_number', 30)->unique();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('referring_doctor_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('referred_to_doctor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('visit_id')->nullable()->constrained()->nullOnDelete();
            $table->string('from_department', 50);  // dermatology, dental
            $table->string('to_department', 50);
            $table->string('urgency', 20)->default('routine'); // routine, urgent, emergency
            $table->string('status', 20)->default('pending');  // pending, accepted, scheduled, completed, declined, cancelled
            $table->string('reason')->nullable();
            $table->text('clinical_notes')->nullable();
            $table->text('referring_diagnosis')->nullable();
            $table->string('provisional_diagnosis')->nullable();
            $table->text('response_notes')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('declined_at')->nullable();
            $table->foreignId('resulting_visit_id')->nullable()->constrained('visits')->nullOnDelete();
            $table->timestamps();

            $table->index(['patient_id', 'status']);
            $table->index(['referred_to_doctor_id', 'status']);
            $table->index(['referring_doctor_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('referrals');
    }
};
