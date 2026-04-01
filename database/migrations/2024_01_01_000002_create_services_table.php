<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained('service_categories')->cascadeOnDelete();
            $table->string('name_ar');
            $table->string('name_en');
            $table->string('slug')->unique();
            $table->text('short_desc_ar')->nullable();
            $table->text('short_desc_en')->nullable();
            $table->longText('full_desc_ar')->nullable();
            $table->longText('full_desc_en')->nullable();
            $table->string('icon')->nullable();
            $table->string('featured_image')->nullable();
            $table->text('benefits_ar')->nullable();
            $table->text('benefits_en')->nullable();
            $table->string('sessions_count')->nullable();
            $table->text('results_ar')->nullable();
            $table->text('results_en')->nullable();
            $table->integer('display_order')->default(0);
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->boolean('show_on_home')->default(false);
            $table->string('seo_title_ar')->nullable();
            $table->string('seo_title_en')->nullable();
            $table->text('seo_desc_ar')->nullable();
            $table->text('seo_desc_en')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('services');
    }
};
