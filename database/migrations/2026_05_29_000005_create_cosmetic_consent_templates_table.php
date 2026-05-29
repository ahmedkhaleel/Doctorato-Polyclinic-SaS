<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Reusable cosmetic consent templates + mandatory-signature enforcement.
 *
 * Before this, cosmetic_consents were free-typed and never required —
 * a cosmetic session could be performed with no signed consent on file.
 * A template can be tied to a procedure and flagged requires_signature;
 * when such a template exists for a procedure, a completed session for
 * that procedure is blocked until a signed consent exists (enforced in
 * CosmeticSessionController).
 *
 * Idempotent + data-safe.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('cosmetic_consent_templates')) {
            Schema::create('cosmetic_consent_templates', function (Blueprint $t) {
                $t->id();
                $t->foreignId('procedure_id')->nullable()->constrained('cosmetic_procedures')->nullOnDelete();
                $t->string('title_ar');
                $t->string('title_en')->nullable();
                $t->text('body_ar');
                $t->text('body_en')->nullable();
                $t->boolean('requires_signature')->default(true);
                $t->boolean('is_active')->default(true);
                $t->timestamps();
                $t->softDeletes();
                $t->index(['procedure_id', 'is_active']);
            });
        }

        if (Schema::hasTable('cosmetic_consents') && ! Schema::hasColumn('cosmetic_consents', 'template_id')) {
            Schema::table('cosmetic_consents', function (Blueprint $t) {
                $t->foreignId('template_id')
                    ->nullable()
                    ->after('procedure_id')
                    ->constrained('cosmetic_consent_templates')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('cosmetic_consents') && Schema::hasColumn('cosmetic_consents', 'template_id')) {
            Schema::table('cosmetic_consents', function (Blueprint $t) {
                $t->dropConstrainedForeignId('template_id');
            });
        }
        Schema::dropIfExists('cosmetic_consent_templates');
    }
};
