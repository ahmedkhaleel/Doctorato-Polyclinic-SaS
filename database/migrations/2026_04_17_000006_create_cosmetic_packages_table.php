<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cosmetic_packages', function (Blueprint $table) {
            $table->id();
            $table->string('name_ar');
            $table->string('name_en')->nullable();
            $table->foreignId('procedure_id')->nullable()->constrained('cosmetic_procedures')->nullOnDelete();
            $table->unsignedInteger('total_sessions')->default(1);
            $table->decimal('total_price', 12, 2)->default(0);
            $table->unsignedInteger('validity_days')->default(365);
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cosmetic_packages');
    }
};
