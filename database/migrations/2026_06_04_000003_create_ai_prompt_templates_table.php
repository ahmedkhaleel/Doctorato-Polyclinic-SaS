<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Admin-editable prompt templates (ar/en) per feature, so prompt tuning never
 * requires a code change. Edited from /admin/ai/prompts.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('ai_prompt_templates')) {
            return;
        }

        Schema::create('ai_prompt_templates', function (Blueprint $table) {
            $table->id();
            $table->string('feature', 60)->index();
            $table->string('locale', 5)->default('ar');     // ar | en
            $table->text('system_prompt')->nullable();
            $table->text('user_template')->nullable();       // supports {{placeholders}}
            $table->unsignedInteger('version')->default(1);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['feature', 'locale']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_prompt_templates');
    }
};
