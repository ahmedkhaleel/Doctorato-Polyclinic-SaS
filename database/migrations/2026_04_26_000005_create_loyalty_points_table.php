<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Patient loyalty points ledger.
 *
 * Each row is one transaction:
 *   - type='earn'    points credited (visit completed, referral bonus, etc.)
 *   - type='redeem'  points debited (used at checkout for a discount)
 *   - type='expire'  points expired automatically (cron)
 *   - type='adjust'  manual admin adjustment (positive or negative)
 *
 * The patient's current balance is computed as SUM(points) — no caching.
 * For 1k+ transactions per patient we'd want a denormalized balance, but
 * at clinic scale this stays under 100 rows per patient and runs in <2ms.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loyalty_points', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->integer('points');         // signed: +earn, -redeem
            $table->enum('type', ['earn', 'redeem', 'expire', 'adjust'])->index();
            $table->string('description')->nullable();
            // Polymorphic reference to the source (Visit, Booking, Referral, etc.)
            $table->string('reference_type')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->timestamp('expires_at')->nullable()->index();
            $table->foreignId('admin_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['patient_id', 'created_at']);
            $table->index(['reference_type', 'reference_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loyalty_points');
    }
};
