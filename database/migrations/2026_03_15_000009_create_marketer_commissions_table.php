<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketer_commissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();  // the marketer
            $table->foreignId('lead_id')->constrained('leads')->cascadeOnDelete();
            $table->foreignId('booking_id')->nullable()->constrained('bookings')->nullOnDelete();
            $table->foreignId('payment_id')->nullable()->constrained('payments')->nullOnDelete();

            $table->enum('commission_type', ['fixed', 'percentage'])->default('percentage');
            $table->decimal('rate', 8, 2)->default(0);           // percentage or fixed amount
            $table->decimal('base_amount', 12, 2)->default(0);   // the amount commission is calculated on
            $table->decimal('commission_amount', 12, 2)->default(0);
            $table->enum('status', ['pending', 'approved', 'paid', 'cancelled'])->default('pending');
            $table->date('paid_date')->nullable();
            $table->text('notes')->nullable();

            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index('lead_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketer_commissions');
    }
};
