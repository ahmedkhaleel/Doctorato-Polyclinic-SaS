<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── Suppliers ───────────────────────────────────────
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('name_ar');
            $table->string('name_en');
            $table->string('code')->unique()->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('contact_person')->nullable();
            $table->text('address')->nullable();
            $table->string('tax_number')->nullable();
            $table->string('payment_terms')->nullable();       // e.g., "Net 30"
            $table->unsignedInteger('lead_time_days')->nullable(); // Avg delivery days
            $table->decimal('rating', 3, 1)->nullable();        // 1-5
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // ── Link supplies to preferred suppliers ────────────
        Schema::table('supplies', function (Blueprint $table) {
            $table->foreignId('supplier_id')->nullable()->after('supplier')->constrained('suppliers')->nullOnDelete();
            $table->decimal('reorder_point', 8, 2)->nullable()->after('min_quantity');
            $table->decimal('reorder_quantity', 8, 2)->nullable()->after('reorder_point');
            $table->boolean('auto_reorder')->default(false)->after('reorder_quantity');
        });

        // ── Purchase Orders ─────────────────────────────────
        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->string('po_number')->unique();
            $table->foreignId('supplier_id')->constrained();
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();

            $table->enum('status', [
                'draft', 'pending_approval', 'approved',
                'ordered', 'partially_received', 'received',
                'cancelled',
            ])->default('draft');

            $table->decimal('subtotal', 10, 2)->default(0);
            $table->decimal('tax_amount', 10, 2)->default(0);
            $table->decimal('total', 10, 2)->default(0);

            $table->date('order_date')->nullable();
            $table->date('expected_delivery_date')->nullable();
            $table->date('received_date')->nullable();

            $table->text('notes')->nullable();
            $table->text('delivery_notes')->nullable();
            $table->timestamps();

            $table->index(['status', 'order_date']);
        });

        // ── Purchase Order Items ────────────────────────────
        Schema::create('purchase_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('supply_id')->constrained();

            $table->decimal('quantity_ordered', 8, 2);
            $table->decimal('quantity_received', 8, 2)->default(0);
            $table->decimal('unit_price', 8, 2);
            $table->decimal('total_price', 10, 2);

            $table->string('batch_number')->nullable();
            $table->date('expiry_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_order_items');
        Schema::dropIfExists('purchase_orders');
        Schema::table('supplies', function (Blueprint $table) {
            $table->dropForeign(['supplier_id']);
            $table->dropColumn(['supplier_id', 'reorder_point', 'reorder_quantity', 'auto_reorder']);
        });
        Schema::dropIfExists('suppliers');
    }
};
