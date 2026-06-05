<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * NP-Future-1 (layer B) — controlled-substance e-prescription workflow.
 * Vendor-agnostic: status flow draft→signed→submitted→authorized→dispensed,
 * a strong-auth signature (signature_hash + signed_by/at), and a gateway field
 * for the national platform driver (ksa|uae|egypt|internal) with external_ref
 * holding the national serial once authorized. Branch-scoped + audited.
 *
 * No live national integration here — drivers are stubs until a licensed
 * facility supplies regulator API credentials (deferred per ADR §8.2).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('controlled_prescriptions')) {
            return;
        }

        Schema::create('controlled_prescriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('doctor_id')->nullable()->constrained('doctors')->nullOnDelete();
            $table->string('module', 20)->default('psychiatry');
            $table->string('drug');
            $table->string('schedule', 12)->nullable();   // local controlled schedule (e.g. II/III)
            $table->string('dose')->nullable();
            $table->string('quantity')->nullable();
            $table->string('status', 16)->default('draft'); // draft|signed|submitted|authorized|dispensed|cancelled
            $table->string('gateway', 16)->default('internal'); // internal|ksa|uae|egypt
            $table->string('external_ref')->nullable();    // national platform serial
            $table->string('signature_hash', 128)->nullable();
            $table->string('signed_by', 12)->nullable();   // doctor
            $table->dateTime('signed_at')->nullable();
            $table->dateTime('submitted_at')->nullable();
            $table->dateTime('authorized_at')->nullable();
            $table->dateTime('dispensed_at')->nullable();
            $table->text('notes')->nullable();
            $table->json('gateway_response')->nullable();
            $table->unsignedBigInteger('branch_id')->nullable()->index();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['patient_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('controlled_prescriptions');
    }
};
