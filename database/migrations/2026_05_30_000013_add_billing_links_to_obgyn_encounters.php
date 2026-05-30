<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Idempotent billing links: each billable OB/GYN encounter records the
 * Invoice + InvoiceItem it produced, so it is never billed twice and can be
 * reversed on delete (same pattern as cosmetic/derma sessions). Idempotent.
 */
return new class extends Migration
{
    private array $tables = [
        'antenatal_visits',
        'obstetric_ultrasounds',
        'delivery_records',
        'pap_smear_screenings',
    ];

    public function up(): void
    {
        foreach ($this->tables as $name) {
            if (! Schema::hasTable($name)) {
                continue;
            }
            Schema::table($name, function (Blueprint $table) use ($name) {
                if (! Schema::hasColumn($name, 'invoice_id')) {
                    $table->foreignId('invoice_id')->nullable()->constrained()->nullOnDelete();
                }
                if (! Schema::hasColumn($name, 'invoice_item_id')) {
                    $table->foreignId('invoice_item_id')->nullable()->constrained('invoice_items')->nullOnDelete();
                }
            });
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $name) {
            if (! Schema::hasTable($name)) {
                continue;
            }
            Schema::table($name, function (Blueprint $table) use ($name) {
                foreach (['invoice_id', 'invoice_item_id'] as $col) {
                    if (Schema::hasColumn($name, $col)) {
                        $table->dropConstrainedForeignId($col);
                    }
                }
            });
        }
    }
};
