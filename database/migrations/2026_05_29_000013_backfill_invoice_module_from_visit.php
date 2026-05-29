<?php

use App\Models\Invoice;
use Illuminate\Database\Migrations\Migration;

/**
 * Backfill invoices.module from the linked visit's module.
 *
 * VisitWorkflowService (and the other invoice creators) never set the
 * invoice's module, so module-filtered revenue dashboards (pediatric
 * especially) read 0. Going forward an Invoice `creating` hook fills it;
 * this backfills the historical rows. Idempotent + data-safe (only fills
 * rows where module is null and a visit module exists).
 */
return new class extends Migration
{
    public function up(): void
    {
        Invoice::whereNull('module')
            ->whereNotNull('visit_id')
            ->with('visit:id,module')
            ->chunkById(500, function ($invoices) {
                foreach ($invoices as $invoice) {
                    $module = $invoice->visit?->module;
                    if ($module) {
                        $invoice->updateQuietly(['module' => $module]);
                    }
                }
            });
    }

    public function down(): void
    {
        // No-op: module is a strict enrichment; reverting would lose nothing
        // useful and risks blanking legitimately-set values.
    }
};
