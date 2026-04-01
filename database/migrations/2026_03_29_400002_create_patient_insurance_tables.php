<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── Insurance Companies ─────────────────────────────
        Schema::create('insurance_companies', function (Blueprint $table) {
            $table->id();
            $table->string('name_ar');
            $table->string('name_en');
            $table->string('code')->unique()->nullable();        // e.g., BUPA, TAWUNIYA
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('contact_person')->nullable();
            $table->string('logo')->nullable();
            $table->text('address')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // ── Insurance Plans/Classes ─────────────────────────
        Schema::create('insurance_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('insurance_company_id')->constrained()->cascadeOnDelete();
            $table->string('name_ar');
            $table->string('name_en');
            $table->string('plan_code')->nullable();
            $table->enum('class', ['VIP', 'A', 'B', 'C', 'D', 'E'])->default('B');

            // ── Coverage Rules ──────────────────────────────
            $table->decimal('coverage_percentage', 5, 2)->default(80);       // default 80%
            $table->decimal('max_coverage_amount', 10, 2)->nullable();       // Annual limit
            $table->decimal('copay_amount', 8, 2)->default(0);              // Fixed copay
            $table->decimal('deductible', 8, 2)->default(0);                // Annual deductible

            // ── Service Coverage ────────────────────────────
            $table->boolean('covers_dental')->default(false);
            $table->boolean('covers_dermatology')->default(true);
            $table->boolean('covers_cosmetic')->default(false);
            $table->boolean('covers_lab')->default(true);
            $table->boolean('covers_xray')->default(true);
            $table->boolean('covers_medication')->default(true);

            $table->text('exclusions')->nullable();                          // What's NOT covered
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('insurance_company_id');
        });

        // ── Patient Insurance Records ───────────────────────
        Schema::create('patient_insurances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('insurance_company_id')->constrained();
            $table->foreignId('insurance_plan_id')->nullable()->constrained()->nullOnDelete();

            $table->string('member_id');                                     // Policy/Member number
            $table->string('policy_number')->nullable();                     // Group policy
            $table->string('card_number')->nullable();
            $table->date('start_date');
            $table->date('expiry_date')->nullable();

            // ── Relationship to insured ─────────────────────
            $table->enum('relationship', ['self', 'spouse', 'child', 'parent', 'other'])->default('self');
            $table->string('principal_name')->nullable();                    // If dependent

            // ── Financial Tracking ──────────────────────────
            $table->decimal('max_annual_limit', 10, 2)->nullable();
            $table->decimal('used_amount', 10, 2)->default(0);              // Running total

            $table->string('card_image_front')->nullable();
            $table->string('card_image_back')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_verified')->default(false);                  // Admin-verified
            $table->timestamp('verified_at')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            $table->index(['patient_id', 'is_active']);
            $table->index('expiry_date');
            $table->index('member_id');
        });

        // ── Insurance Claims ────────────────────────────────
        Schema::create('insurance_claims', function (Blueprint $table) {
            $table->id();
            $table->string('claim_number')->unique();
            $table->foreignId('patient_insurance_id')->constrained()->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained();
            $table->foreignId('invoice_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('visit_id')->nullable()->constrained()->nullOnDelete();

            // ── Claim Details ───────────────────────────────
            $table->date('service_date');
            $table->text('diagnosis')->nullable();
            $table->text('services_description');                            // What was done

            // ── Financial ───────────────────────────────────
            $table->decimal('total_amount', 10, 2);                         // Full invoice total
            $table->decimal('covered_amount', 10, 2);                       // Insurance pays
            $table->decimal('patient_share', 10, 2);                        // Patient copay
            $table->decimal('approved_amount', 10, 2)->nullable();          // After approval
            $table->decimal('paid_amount', 10, 2)->default(0);             // Actually received

            // ── Status ──────────────────────────────────────
            $table->enum('status', [
                'draft', 'submitted', 'under_review',
                'approved', 'partially_approved', 'rejected',
                'paid', 'partially_paid',
            ])->default('draft');

            $table->date('submitted_at')->nullable();
            $table->date('approved_at')->nullable();
            $table->date('paid_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->text('notes')->nullable();
            $table->string('reference_number')->nullable();                 // Insurance company ref

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['patient_insurance_id', 'status']);
            $table->index('claim_number');
            $table->index(['patient_id', 'service_date']);
        });

        // ── Pre-Authorizations ──────────────────────────────
        Schema::create('insurance_pre_authorizations', function (Blueprint $table) {
            $table->id();
            $table->string('auth_number')->unique();
            $table->foreignId('patient_insurance_id')->constrained()->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained();
            $table->foreignId('doctor_id')->nullable()->constrained()->nullOnDelete();

            $table->text('procedure_description');
            $table->string('icd_code')->nullable();                         // Diagnosis code
            $table->string('cpt_code')->nullable();                         // Procedure code
            $table->decimal('estimated_cost', 10, 2);

            $table->enum('status', [
                'pending', 'approved', 'partially_approved', 'rejected', 'expired',
            ])->default('pending');

            $table->decimal('approved_amount', 10, 2)->nullable();
            $table->date('valid_from')->nullable();
            $table->date('valid_until')->nullable();
            $table->text('conditions')->nullable();                         // Approval conditions
            $table->text('rejection_reason')->nullable();

            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['patient_insurance_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('insurance_pre_authorizations');
        Schema::dropIfExists('insurance_claims');
        Schema::dropIfExists('patient_insurances');
        Schema::dropIfExists('insurance_plans');
        Schema::dropIfExists('insurance_companies');
    }
};
