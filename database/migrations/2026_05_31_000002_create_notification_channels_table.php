<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('notification_channels')) {
            return;
        }

        Schema::create('notification_channels', function (Blueprint $table) {
            $table->id();
            $table->string('channel', 20)->unique();   // whatsapp | sms | email | in_app
            $table->boolean('enabled')->default(false);
            $table->string('provider')->nullable();    // twilio|smsmisr|unifonic|gateway | cloud_api|bridge | smtp
            $table->text('config')->nullable();        // encrypted JSON (credentials)
            $table->string('from_name')->nullable();
            $table->unsignedInteger('daily_cap')->nullable();
            $table->unsignedInteger('monthly_cap')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_channels');
    }
};
