<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('communication_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('channel', ['whatsapp', 'sms', 'email']);
            $table->enum('category', ['welcome', 'follow_up', 'appointment_reminder', 'promotion', 're_engagement', 'thank_you', 'custom']);
            $table->string('subject')->nullable();             // for email templates
            $table->text('body_en');
            $table->text('body_ar');
            $table->json('variables')->nullable();             // available placeholders: [{name}, {phone}, {service}, etc.]
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('usage_count')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['channel', 'category', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('communication_templates');
    }
};
