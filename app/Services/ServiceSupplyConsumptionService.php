<?php

namespace App\Services;

use App\Models\Supply;
use App\Models\SupplyTransaction;
use App\Models\Visit;
use Illuminate\Support\Facades\DB;

/**
 * Consumes the supplies configured for a visit's service (ServiceSupply:
 * service_id → supply_id + quantity_per_session) when the visit is completed,
 * so stock reflects what a booked service actually uses.
 *
 * Idempotent: a visit that already has usage transactions is skipped, so
 * re-completing never double-deducts. (Dental/cosmetic/vaccine consumption
 * runs through their own procedure/treatment links — this covers the generic
 * service path.)
 */
class ServiceSupplyConsumptionService
{
    public function consumeForVisit(Visit $visit): array
    {
        if (! $visit->service_id) {
            return [];
        }

        // Already consumed for this visit → no-op (idempotent).
        $already = SupplyTransaction::where('visit_id', $visit->id)
            ->where('transaction_type', 'usage')
            ->exists();
        if ($already) {
            return [];
        }

        $visit->loadMissing('service.serviceSupplies.supply');
        $links = $visit->service?->serviceSupplies ?? collect();
        if ($links->isEmpty()) {
            return [];
        }

        return DB::transaction(function () use ($visit, $links) {
            $consumed = [];

            foreach ($links as $link) {
                $qty = (float) $link->quantity_per_session;
                if ($qty <= 0) {
                    continue;
                }

                $supply = Supply::lockForUpdate()->find($link->supply_id);
                if (! $supply) {
                    continue;
                }

                SupplyTransaction::create([
                    'supply_id'        => $supply->id,
                    'transaction_type' => 'usage',
                    'quantity'         => $qty,
                    'unit_cost'        => $supply->purchase_price,
                    'visit_id'         => $visit->id,
                    'notes'            => 'Service supply usage (visit #' . $visit->id . ')',
                    'created_by'       => auth()->id(),
                ]);

                $supply->decrement('quantity', $qty);
                $consumed[] = ['supply_id' => $supply->id, 'quantity' => $qty];
            }

            return $consumed;
        });
    }
}
