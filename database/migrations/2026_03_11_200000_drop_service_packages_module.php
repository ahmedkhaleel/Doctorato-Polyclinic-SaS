<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Remove FK columns from visits
        Schema::table('visits', function (Blueprint $table) {
            $table->dropForeign(['service_package_id']);
            $table->dropColumn('service_package_id');
        });

        // Remove FK columns from invoices
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropForeign(['service_package_id']);
            $table->dropColumn('service_package_id');
        });

        // Drop the service_packages table
        Schema::dropIfExists('service_packages');
    }

    public function down(): void
    {
        // Recreate service_packages table
        Schema::create('service_packages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_id')->constrained()->cascadeOnDelete();
            $table->integer('total_sessions')->default(1);
            $table->integer('completed_sessions')->default(0);
            $table->decimal('original_price', 10, 2)->default(0);
            $table->decimal('discount_percentage', 5, 2)->default(0);
            $table->decimal('final_price', 10, 2)->default(0);
            $table->decimal('paid_amount', 10, 2)->default(0);
            $table->enum('status', ['active', 'completed', 'cancelled'])->default('active');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // Re-add FK columns to visits
        Schema::table('visits', function (Blueprint $table) {
            $table->foreignId('service_package_id')->nullable()->constrained('service_packages')->nullOnDelete();
        });

        // Re-add FK columns to invoices
        Schema::table('invoices', function (Blueprint $table) {
            $table->foreignId('service_package_id')->nullable()->constrained('service_packages')->nullOnDelete();
        });
    }
};
