<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Drops the offers table since the module has been replaced by Package Bundles.
     */
    public function up(): void
    {
        Schema::dropIfExists('offers');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('offers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title_ar');
            $table->string('title_en');
            $table->text('description_ar')->nullable();
            $table->text('description_en')->nullable();
            $table->string('image')->nullable();
            $table->decimal('original_price', 10, 2);
            $table->decimal('offer_price', 10, 2);
            $table->date('start_date');
            $table->date('end_date');
            $table->string('status')->default('active');
            $table->integer('display_order')->default(0);
            $table->timestamps();
        });
    }
};
