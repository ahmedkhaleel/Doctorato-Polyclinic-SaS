<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * NP5 — neurology procedures (EMG/NCS, lumbar puncture, EEG, botulinum toxin,
 * nerve block). Branch-scoped clinical event with billing + inventory links
 * (botox draws from supplies, mirroring the cosmetic-session pattern).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('neuro_procedures')) {
            return;
        }
        Schema::create('neuro_procedures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('doctor_id')->nullable()->constrained('doctors')->nullOnDelete();
            $table->string('type', 30); // emg_ncs | lumbar_puncture | eeg | botox | nerve_block
            $table->date('performed_at');
            $table->json('findings')->nullable();
            $table->string('report_path')->nullable();   // attached MRI/CT/EEG report (no DICOM viewer — deferred)
            $table->decimal('cost', 12, 2)->default(0);
            $table->foreignId('invoice_id')->nullable()->constrained('invoices')->nullOnDelete();
            $table->unsignedBigInteger('invoice_item_id')->nullable();
            $table->foreignId('supply_id')->nullable()->constrained('supplies')->nullOnDelete();
            $table->decimal('consumption_qty', 10, 2)->nullable();
            $table->unsignedBigInteger('supply_transaction_id')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('branch_id')->nullable()->index();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['patient_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('neuro_procedures');
    }
};
