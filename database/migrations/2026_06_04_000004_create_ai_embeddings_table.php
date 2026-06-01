<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Vector store for the patient-assistant RAG. Shared (not branch-scoped) — FAQs,
 * services and doctor bios are clinic-wide. Vectors stored as JSON; cosine
 * similarity is computed in PHP (corpus is small on shared MySQL hosting).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('ai_embeddings')) {
            return;
        }

        Schema::create('ai_embeddings', function (Blueprint $table) {
            $table->id();
            $table->string('source', 40)->index();      // faq | service | doctor
            $table->string('owner_type', 60)->nullable();
            $table->unsignedBigInteger('owner_id')->nullable();
            $table->string('locale', 5)->default('ar');
            $table->text('content');                     // the text that was embedded
            $table->json('vector');                      // embedding floats
            $table->string('model', 60)->nullable();
            $table->timestamps();

            $table->index(['source', 'locale']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_embeddings');
    }
};
