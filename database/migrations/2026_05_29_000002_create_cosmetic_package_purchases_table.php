<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Patient package purchases — the missing commercial record.
 *
 * Before this, `cosmetic_packages` was a catalog only; there was no way to
 * record that a patient BOUGHT a package, prepaid it, and has N sessions
 * remaining. This table is the enrollment/balance ledger:
 *   - one row per purchase, linked to the prepayment Invoice
 *   - total_sessions / sessions_used → remaining balance
 *   - expires_at derived from the package validity
 *
 * Cosmetic sessions gain `package_purchase_id` so a delivered session draws
 * down a specific prepaid purchase (and is NOT billed again per-session).
 *
 * Idempotent + data-safe.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('cosmetic_package_purchases')) {
            Schema::create('cosmetic_package_purchases', function (Blueprint $t) {
                $t->id();
                $t->foreignId('patient_id')->constrained()->cascadeOnDelete();
                $t->foreignId('package_id')->nullable()->constrained('cosmetic_packages')->nullOnDelete();
                $t->foreignId('invoice_id')->nullable()->constrained('invoices')->nullOnDelete();
                $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $t->unsignedInteger('total_sessions')->default(1);
                $t->unsignedInteger('sessions_used')->default(0);
                $t->decimal('amount', 12, 2)->default(0);
                $t->date('purchased_at')->nullable();
                $t->date('expires_at')->nullable();
                $t->enum('status', ['active', 'completed', 'expired', 'cancelled'])->default('active');
                $t->text('notes')->nullable();
                $t->timestamps();
                $t->softDeletes();
                $t->index(['patient_id', 'status']);
                $t->index('expires_at');
            });
        }

        if (Schema::hasTable('cosmetic_sessions') && ! Schema::hasColumn('cosmetic_sessions', 'package_purchase_id')) {
            Schema::table('cosmetic_sessions', function (Blueprint $t) {
                $t->foreignId('package_purchase_id')
                    ->nullable()
                    ->after('package_id')
                    ->constrained('cosmetic_package_purchases')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('cosmetic_sessions') && Schema::hasColumn('cosmetic_sessions', 'package_purchase_id')) {
            Schema::table('cosmetic_sessions', function (Blueprint $t) {
                $t->dropConstrainedForeignId('package_purchase_id');
            });
        }
        Schema::dropIfExists('cosmetic_package_purchases');
    }
};
