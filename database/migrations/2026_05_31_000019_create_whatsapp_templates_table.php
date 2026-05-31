<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Registry of Meta-approved WhatsApp templates. Business-initiated WhatsApp
 * outside the 24h customer-service window must use an approved template; this
 * maps a hub event_key to a template name + ordered body variables.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('whatsapp_templates')) {
            return;
        }

        Schema::create('whatsapp_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');                  // Meta template name
            $table->string('language', 10)->default('ar');
            $table->string('event_key')->nullable(); // hub event this template fulfils
            $table->json('variables')->nullable();   // ordered data keys → {{1}},{{2}}…
            $table->text('body_preview')->nullable(); // human reference of the approved copy
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['event_key', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_templates');
    }
};
