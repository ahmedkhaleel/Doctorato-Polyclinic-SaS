<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_id')->unique()->comment('Our internal reference');
            $table->string('gateway')->comment('paymob, stripe, etc');
            $table->string('gateway_reference')->nullable()->comment('Gateway transaction ID');
            $table->string('intent_id')->nullable()->comment('Paymob order ID or Stripe payment intent');

            // Links
            $table->foreignId('online_consultation_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('invoice_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('patient_id')->nullable()->constrained()->nullOnDelete();

            // Financial
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('EGP');

            // Status
            $table->enum('status', ['pending', 'processing', 'succeeded', 'failed', 'refunded', 'cancelled'])->default('pending');
            $table->enum('type', ['payment', 'refund'])->default('payment');

            // Raw data
            $table->json('gateway_request')->nullable();
            $table->json('gateway_response')->nullable();
            $table->text('failure_reason')->nullable();

            // Payment URLs (for redirect flow)
            $table->string('checkout_url', 500)->nullable();

            $table->timestamp('paid_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamps();

            $table->index(['gateway', 'status']);
            $table->index('gateway_reference');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_transactions');
    }
};
