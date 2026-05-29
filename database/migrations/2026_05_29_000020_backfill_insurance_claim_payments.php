<?php

use App\Models\InsuranceClaim;
use App\Services\InsuranceClaimPaymentService;
use Illuminate\Database\Migrations\Migration;

/**
 * Backfill invoice Payments for insurance claims that were marked paid /
 * partially-paid BEFORE the claim→payment link existed, so historical
 * insurer reimbursements land on their invoices and in revenue. Idempotent
 * — the service skips claims already linked to a payment.
 */
return new class extends Migration
{
    public function up(): void
    {
        $svc = app(InsuranceClaimPaymentService::class);

        InsuranceClaim::whereIn('status', ['paid', 'partially_paid'])
            ->whereNotNull('invoice_id')
            ->whereNull('payment_id')
            ->where('paid_amount', '>', 0)
            ->chunkById(200, function ($claims) use ($svc) {
                foreach ($claims as $claim) {
                    $svc->sync($claim);
                }
            });
    }

    public function down(): void
    {
        $svc = app(InsuranceClaimPaymentService::class);

        InsuranceClaim::whereNotNull('payment_id')->chunkById(200, function ($claims) use ($svc) {
            foreach ($claims as $claim) {
                $svc->reverse($claim);
            }
        });
    }
};
