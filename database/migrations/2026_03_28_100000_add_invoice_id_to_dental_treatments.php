<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('dental_treatments') && !Schema::hasColumn('dental_treatments', 'invoice_id')) {
            Schema::table('dental_treatments', function (Blueprint $table) {
                $table->foreignId('invoice_id')->nullable()->after('visit_id')->constrained('invoices')->nullOnDelete();
            });
        }

        if (Schema::hasTable('dental_lab_orders') && !Schema::hasColumn('dental_lab_orders', 'invoice_item_id')) {
            Schema::table('dental_lab_orders', function (Blueprint $table) {
                $table->foreignId('invoice_item_id')->nullable()->after('dental_treatment_id')->constrained('invoice_items')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('dental_treatments') && Schema::hasColumn('dental_treatments', 'invoice_id')) {
            Schema::table('dental_treatments', function (Blueprint $table) {
                $table->dropConstrainedForeignId('invoice_id');
            });
        }

        if (Schema::hasTable('dental_lab_orders') && Schema::hasColumn('dental_lab_orders', 'invoice_item_id')) {
            Schema::table('dental_lab_orders', function (Blueprint $table) {
                $table->dropConstrainedForeignId('invoice_item_id');
            });
        }
    }
};
