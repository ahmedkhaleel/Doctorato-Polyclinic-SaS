<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('package_bundle_services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('package_bundle_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_id')->constrained()->cascadeOnDelete();
            $table->integer('sessions_count')->default(1);
            $table->decimal('discount_percentage', 5, 2)->default(0);
            $table->decimal('bundle_price', 10, 2);
            $table->integer('display_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('package_bundle_services');
    }
};
