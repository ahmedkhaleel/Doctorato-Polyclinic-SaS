<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('credit_notes', function (Blueprint $table) {
            $table->id();
            $table->string('credit_note_number', 30)->unique();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('type', ['full_refund', 'partial_refund', 'adjustment', 'cancellation'])->default('partial_refund');
            $table->enum('status', ['draft', 'pending_approval', 'approved', 'rejected', 'refunded'])->default('draft');
            $table->decimal('amount', 12, 2);
            $table->string('reason');
            $table->text('notes')->nullable();
            $table->enum('refund_method', ['cash', 'card', 'bank_transfer', 'wallet_credit'])->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('refunded_at')->nullable();
            $table->timestamps();

            $table->index(['patient_id', 'status']);
            $table->index(['invoice_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('credit_notes');
    }
};
