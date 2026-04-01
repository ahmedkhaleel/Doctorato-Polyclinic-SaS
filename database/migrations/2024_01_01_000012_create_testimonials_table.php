<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('testimonials', function (Blueprint $table) {
            $table->id();
            $table->string('client_name_ar');
            $table->string('client_name_en');
            $table->foreignId('service_id')->nullable()->constrained()->nullOnDelete();
            $table->tinyInteger('rating')->default(5);
            $table->text('review_ar');
            $table->text('review_en');
            $table->string('photo')->nullable();
            $table->string('video_url')->nullable();
            $table->enum('status', ['published', 'hidden'])->default('published');
            $table->integer('display_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('testimonials');
    }
};
