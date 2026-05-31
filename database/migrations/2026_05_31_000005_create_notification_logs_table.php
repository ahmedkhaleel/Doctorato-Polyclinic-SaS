<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('notification_logs')) {
            return;
        }

        Schema::create('notification_logs', function (Blueprint $table) {
            $table->id();
            $table->nullableMorphs('recipient');           // recipient_type + recipient_id (Patient/User/Lead)
            $table->string('to')->nullable();              // phone / email / null (in-app)
            $table->string('channel', 20);
            $table->string('provider')->nullable();
            $table->string('event_key')->nullable();
            $table->foreignId('template_id')->nullable();
            $table->string('status', 20)->default('queued'); // queued|sent|delivered|read|failed|skipped
            $table->decimal('cost', 10, 4)->nullable();
            $table->text('error')->nullable();
            $table->string('dedup_key')->nullable();
            $table->json('meta')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index('dedup_key');
            $table->index(['channel', 'status']);
            $table->index('event_key');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_logs');
    }
};
