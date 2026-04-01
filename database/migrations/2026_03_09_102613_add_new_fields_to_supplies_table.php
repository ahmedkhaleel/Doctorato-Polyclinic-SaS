<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('supplies', function (Blueprint $table) {
            $table->foreignId('supply_category_id')->nullable()->after('id')->constrained('supply_categories')->nullOnDelete();
            $table->string('image')->nullable()->after('supplier');
            $table->date('expiry_date')->nullable()->after('image');
            $table->string('batch_number')->nullable()->after('expiry_date');
            $table->text('description')->nullable()->after('batch_number');
            $table->string('barcode')->nullable()->unique()->after('sku');
        });
    }

    public function down(): void
    {
        Schema::table('supplies', function (Blueprint $table) {
            $table->dropForeign(['supply_category_id']);
            $table->dropColumn(['supply_category_id', 'image', 'expiry_date', 'batch_number', 'description', 'barcode']);
        });
    }
};
