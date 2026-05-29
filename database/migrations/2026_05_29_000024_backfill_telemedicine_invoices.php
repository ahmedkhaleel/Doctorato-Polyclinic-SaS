<?php

use App\Models\OnlineConsultation;
use App\Services\OnlineConsultationService;
use Illuminate\Database\Migrations\Migration;

/**
 * Backfill Invoice + Payment for online consultations that were paid BEFORE
 * the fee→invoice link existed, so historical telemedicine income appears in
 * the revenue reports and the patients' invoices. Reuses the idempotent
 * markPaid() path (skips consultations already linked to an invoice).
 */
return new class extends Migration
{
    public function up(): void
    {
        $svc = app(OnlineConsultationService::class);

        OnlineConsultation::where('payment_status', 'paid')
            ->whereNull('invoice_id')
            ->where('fee', '>', 0)
            ->chunkById(200, function ($consultations) use ($svc) {
                foreach ($consultations as $consultation) {
                    $svc->markPaid($consultation, $consultation->payment_gateway_reference ?? 'BACKFILL');
                }
            });
    }

    public function down(): void
    {
        // No-op: voiding historical telemedicine invoices/payments would lose
        // legitimately-collected revenue records.
    }
};
