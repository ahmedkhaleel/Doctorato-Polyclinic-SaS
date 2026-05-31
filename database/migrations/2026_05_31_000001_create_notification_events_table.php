<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('notification_events')) {
            return;
        }

        Schema::create('notification_events', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();              // e.g. booking.confirmed
            $table->string('label_ar')->nullable();
            $table->string('label_en')->nullable();
            $table->string('category', 20)->default('transactional'); // transactional|reminder|marketing
            $table->json('default_channels')->nullable();  // ["whatsapp","sms","email","in_app"]
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('category');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_events');
    }
};
