<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── Add waiting time tracking to visits ─────────────
        Schema::table('visits', function (Blueprint $table) {
            $table->timestamp('checked_in_at')->nullable()->after('scheduled_time');
            $table->timestamp('called_in_at')->nullable()->after('checked_in_at');
            $table->unsignedInteger('actual_wait_minutes')->nullable()->after('called_in_at');
            $table->unsignedSmallInteger('queue_position')->nullable()->after('actual_wait_minutes');
        });

        // ── Add payment plan support to invoices ────────────
        Schema::table('invoices', function (Blueprint $table) {
            $table->boolean('is_installment')->default(false)->after('notes');
            $table->unsignedTinyInteger('installment_count')->nullable()->after('is_installment');
            $table->decimal('installment_amount', 10, 2)->nullable()->after('installment_count');
            $table->date('next_installment_date')->nullable()->after('installment_amount');

            // Insurance link
            $table->foreignId('patient_insurance_id')->nullable()->after('discount_code_id');
            $table->decimal('insurance_covered', 10, 2)->default(0)->after('patient_insurance_id');
            $table->decimal('patient_net_amount', 10, 2)->nullable()->after('insurance_covered');
        });
    }

    public function down(): void
    {
        Schema::table('visits', function (Blueprint $table) {
            $table->dropColumn(['checked_in_at', 'called_in_at', 'actual_wait_minutes', 'queue_position']);
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn([
                'is_installment', 'installment_count', 'installment_amount',
                'next_installment_date', 'patient_insurance_id', 'insurance_covered', 'patient_net_amount',
            ]);
        });
    }
};
