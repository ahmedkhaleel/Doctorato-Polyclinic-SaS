<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_supplies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_id')->constrained('services')->cascadeOnDelete();
            $table->foreignId('supply_id')->constrained('supplies')->cascadeOnDelete();
            $table->decimal('quantity_per_session', 10, 2)->default(1);
            $table->timestamps();

            $table->unique(['service_id', 'supply_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_supplies');
    }
};
