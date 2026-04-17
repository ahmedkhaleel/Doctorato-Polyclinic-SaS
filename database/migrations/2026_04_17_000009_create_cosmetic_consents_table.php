<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cosmetic_consents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('procedure_id')->nullable()->constrained('cosmetic_procedures')->nullOnDelete();
            $table->foreignId('session_id')->nullable()->constrained('cosmetic_sessions')->nullOnDelete();
            $table->text('consent_text')->nullable();
            $table->dateTime('signed_at')->nullable();
            $table->string('signature_path')->nullable();
            $table->string('witnessed_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('patient_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cosmetic_consents');
    }
};
