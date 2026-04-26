<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * SMS templates editable from the admin UI.
 *
 * Each template is keyed by a stable code (e.g. 'booking_confirmed_24h')
 * and carries ar + en bodies with {{variable}} placeholders. Lets the
 * clinic adjust copy without touching code.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sms_templates', function (Blueprint $table) {
            $table->id();
            $table->string('key', 80)->unique();      // e.g. 'booking_confirmed'
            $table->string('category', 40)->index();  // 'bookings' | 'reminders' | 'marketing'
            $table->text('body_ar');
            $table->text('body_en');
            $table->string('description')->nullable();
            $table->json('placeholders')->nullable(); // ['patient_name','date','time']
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sms_templates');
    }
};
