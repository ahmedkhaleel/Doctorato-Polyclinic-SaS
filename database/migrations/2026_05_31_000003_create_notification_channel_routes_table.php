<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('notification_channel_routes')) {
            return;
        }

        Schema::create('notification_channel_routes', function (Blueprint $table) {
            $table->id();
            $table->string('event_key');
            $table->string('channel', 20);
            $table->boolean('enabled')->default(true);
            $table->unsignedSmallInteger('priority')->default(0); // lower = tried first (fallback order)
            $table->timestamps();

            $table->unique(['event_key', 'channel']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_channel_routes');
    }
};
