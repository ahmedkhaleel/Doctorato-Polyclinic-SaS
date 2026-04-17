<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cosmetic_procedures', function (Blueprint $table) {
            $table->id();
            $table->string('name_ar');
            $table->string('name_en')->nullable();
            $table->enum('category', ['injectable', 'laser', 'chemical', 'mechanical', 'thread', 'other'])->default('other');
            $table->text('description')->nullable();
            $table->decimal('default_price', 12, 2)->default(0);
            $table->unsignedInteger('default_duration_minutes')->default(30);
            $table->unsignedInteger('recovery_days')->default(0);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('display_order')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index('category');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cosmetic_procedures');
    }
};
