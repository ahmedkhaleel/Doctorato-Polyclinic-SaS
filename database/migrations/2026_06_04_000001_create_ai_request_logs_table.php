<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Audit + cost ledger for every AI call. Branch-aware (stamped, not scoped while
 * BRANCHES_ENABLED is off). Drives the /admin/ai/usage and /admin/ai/logs screens
 * and the monthly budget circuit-breaker.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('ai_request_logs')) {
            return;
        }

        Schema::create('ai_request_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('branch_id')->nullable()->index();
            $table->string('feature', 60)->index();            // e.g. seo_content, clinical_note
            $table->string('provider', 30)->default('openai');
            $table->string('model', 60)->nullable();
            $table->string('actor_type', 30)->nullable();      // user | doctor | patient | system
            $table->unsignedBigInteger('actor_id')->nullable();
            $table->unsignedInteger('prompt_tokens')->default(0);
            $table->unsignedInteger('completion_tokens')->default(0);
            $table->decimal('cost_usd', 10, 5)->default(0);
            $table->unsignedInteger('latency_ms')->default(0);
            $table->string('status', 20)->default('success');  // success | failed | blocked
            $table->text('error')->nullable();
            $table->json('meta')->nullable();                  // prompt snapshot (if ai_log_prompts), etc.
            $table->timestamps();

            $table->index(['feature', 'created_at']);
            $table->index(['branch_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_request_logs');
    }
};
