<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lead_scoring_rules', function (Blueprint $table) {
            $table->id();
            $table->string('name_en');
            $table->string('name_ar');
            $table->enum('event', [
                'lead_created', 'phone_provided', 'email_provided',
                'call_answered', 'call_no_answer', 'whatsapp_replied',
                'appointment_booked', 'appointment_attended', 'appointment_missed',
                'visit_completed', 'payment_made',
                'no_response_3_days', 'no_response_7_days', 'no_response_14_days',
                'interested_in_service', 'referral_provided',
            ]);
            $table->integer('points');                          // positive or negative
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('event');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lead_scoring_rules');
    }
};
