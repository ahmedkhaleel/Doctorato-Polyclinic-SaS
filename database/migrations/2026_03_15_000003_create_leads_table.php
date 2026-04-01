<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leads', function (Blueprint $table) {
            $table->id();

            // Lead identity
            $table->string('full_name');
            $table->string('phone')->nullable();
            $table->string('phone2')->nullable();
            $table->string('email')->nullable();
            $table->enum('gender', ['male', 'female'])->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('city')->nullable();
            $table->string('nationality')->nullable();

            // Pipeline
            $table->enum('status', [
                'new', 'contacted', 'qualified', 'appointment_booked',
                'consultation_done', 'negotiation', 'converted', 'lost', 'dormant',
            ])->default('new');
            $table->unsignedTinyInteger('priority')->default(2); // 1=hot, 2=warm, 3=cold
            $table->integer('score')->default(0);                 // scoring system

            // Source & Campaign
            $table->foreignId('lead_source_id')->nullable()->constrained('lead_sources')->nullOnDelete();
            $table->foreignId('campaign_id')->nullable()->constrained('crm_campaigns')->nullOnDelete();
            $table->string('referral_code')->nullable();          // if came via referral

            // Assignment
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('assigned_at')->nullable();

            // Interest
            $table->json('interested_services')->nullable();      // array of service IDs
            $table->text('notes')->nullable();

            // Conversion
            $table->foreignId('patient_id')->nullable()->constrained('patients')->nullOnDelete();
            $table->foreignId('booking_id')->nullable()->constrained('bookings')->nullOnDelete();
            $table->timestamp('converted_at')->nullable();

            // Loss
            $table->string('loss_reason')->nullable();
            $table->timestamp('lost_at')->nullable();

            // Tracking
            $table->string('utm_source')->nullable();
            $table->string('utm_medium')->nullable();
            $table->string('utm_campaign')->nullable();
            $table->string('landing_page')->nullable();
            $table->string('ip_address')->nullable();

            // Auto-management
            $table->timestamp('last_contacted_at')->nullable();
            $table->timestamp('next_follow_up_at')->nullable();
            $table->unsignedInteger('follow_up_count')->default(0);

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('status');
            $table->index('priority');
            $table->index('assigned_to');
            $table->index('lead_source_id');
            $table->index('campaign_id');
            $table->index('next_follow_up_at');
            $table->index('phone');
            $table->index('email');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};
