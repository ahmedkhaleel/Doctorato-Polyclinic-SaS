<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patient_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();

            $table->enum('document_type', [
                'national_id', 'passport', 'iqama',             // Identity
                'insurance_card', 'insurance_approval',          // Insurance
                'medical_report', 'lab_result', 'xray_report',  // Medical
                'referral_letter', 'discharge_summary',          // Clinical
                'consent_form', 'signed_consent',                // Legal
                'prescription', 'other',                         // General
            ]);

            $table->string('title');                             // Display name
            $table->string('file_path');                         // Storage path
            $table->string('original_name');                     // Original filename
            $table->string('mime_type')->nullable();
            $table->unsignedInteger('file_size')->nullable();    // bytes

            $table->date('document_date')->nullable();           // Date of the document
            $table->date('expiry_date')->nullable();             // For IDs, insurance
            $table->text('notes')->nullable();

            $table->boolean('is_confidential')->default(false);  // Extra privacy layer
            $table->string('source')->default('admin');          // admin, patient, doctor

            $table->timestamps();

            $table->index(['patient_id', 'document_type']);
            $table->index('expiry_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patient_documents');
    }
};
