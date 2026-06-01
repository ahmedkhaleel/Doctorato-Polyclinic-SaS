<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-feature on/off + optional model override, controlled from /admin/ai/features.
 * Every AI feature in the system gates on a row here (default disabled).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('ai_feature_flags')) {
            return;
        }

        Schema::create('ai_feature_flags', function (Blueprint $table) {
            $table->id();
            $table->string('key', 60)->unique();          // e.g. seo_content
            $table->boolean('enabled')->default(false);
            $table->string('model_override', 60)->nullable();
            $table->string('label_ar')->nullable();
            $table->string('label_en')->nullable();
            $table->string('group', 40)->default('general'); // wave1, patient, clinical, vision, predictive
            $table->integer('display_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_feature_flags');
    }
};
