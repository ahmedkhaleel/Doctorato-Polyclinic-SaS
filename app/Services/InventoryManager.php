<?php

namespace App\Services;

use App\Models\Supply;
use App\Models\SupplyTransaction;
use App\Models\Visit;
use Illuminate\Support\Facades\DB;

class InventoryManager
{
    /**
     * Deduct supplies for a completed visit (based on service_supplies mapping).
     *
     * DEPRECATED / UNUSED: visit consumption now goes exclusively through
     * ServiceSupplyConsumptionService::consumeForVisit() (idempotent). Kept
     * only for reference; do NOT re-wire this into the visit-complete path —
     * it would double-deduct. `usage` quantity is stored POSITIVE here to
     * stay consistent with every other consumption path.
     *
     * @return array List of deducted supplies with low-stock warnings
     */
    public function deductForVisit(Visit $visit): array
    {
        if (! $visit->service_id) {
            return [];
        }

        $serviceSupplies = $visit->service->serviceSupplies()->with('supply')->get();
        $results = [];

        DB::transaction(function () use ($visit, $serviceSupplies, &$results) {
            foreach ($serviceSupplies as $ss) {
                $supply = $ss->supply;
                $qty = $ss->quantity_per_session;

                // Deduct from supply
                $supply->decrement('quantity', $qty);

                // Record transaction
                SupplyTransaction::create([
                    'supply_id' => $supply->id,
                    'transaction_type' => 'usage',
                    'quantity' => $qty,
                    'visit_id' => $visit->id,
                    'notes' => "Auto-deducted for visit #{$visit->id}",
                    'created_by' => auth()->id(),
                ]);

                $supply->refresh();
                $results[] = [
                    'supply_id' => $supply->id,
                    'name_en' => $supply->name_en,
                    'deducted' => $qty,
                    'remaining' => $supply->quantity,
                    'is_low_stock' => $supply->is_low_stock,
                ];
            }
        });

        return $results;
    }

    /**
     * Return supplies for a cancelled visit (reverse deduction).
     */
    public function returnForVisit(Visit $visit): void
    {
        $transactions = SupplyTransaction::where('visit_id', $visit->id)
            ->where('transaction_type', 'usage')
            ->get();

        DB::transaction(function () use ($visit, $transactions) {
            foreach ($transactions as $tx) {
                $returnQty = abs($tx->quantity);
                $tx->supply->increment('quantity', $returnQty);

                SupplyTransaction::create([
                    'supply_id' => $tx->supply_id,
                    'transaction_type' => 'return',
                    'quantity' => $returnQty,
                    'visit_id' => $visit->id,
                    'notes' => "Returned for cancelled visit #{$visit->id}",
                    'created_by' => auth()->id(),
                ]);
            }
        });
    }
}
