<?php

namespace App\Console\Commands;

use App\Models\Supply;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CheckLowStockAlerts extends Command
{
    protected $signature = 'inventory:check-low-stock';
    protected $description = 'Check for low stock items and auto-generate purchase orders if enabled';

    public function handle(): void
    {
        $lowStockItems = Supply::active()
            ->lowStock()
            ->where('auto_reorder', true)
            ->whereNotNull('supplier_id')
            ->whereNotNull('reorder_quantity')
            ->get();

        if ($lowStockItems->isEmpty()) {
            $this->info('No items need auto-reorder.');
            return;
        }

        // Group by supplier
        $grouped = $lowStockItems->groupBy('supplier_id');

        foreach ($grouped as $supplierId => $supplies) {
            // Check if there's already a pending PO for this supplier
            $existingPo = PurchaseOrder::where('supplier_id', $supplierId)
                ->whereIn('status', ['draft', 'pending_approval', 'approved', 'ordered'])
                ->exists();

            if ($existingPo) {
                $this->info("Skipping supplier #{$supplierId} — existing open PO found.");
                continue;
            }

            DB::transaction(function () use ($supplierId, $supplies) {
                $po = PurchaseOrder::create([
                    'po_number' => PurchaseOrder::generatePoNumber(),
                    'supplier_id' => $supplierId,
                    'created_by' => 1, // System user
                    'status' => 'pending_approval',
                    'order_date' => now(),
                    'notes' => 'Auto-generated from low stock alert',
                ]);

                foreach ($supplies as $supply) {
                    PurchaseOrderItem::create([
                        'purchase_order_id' => $po->id,
                        'supply_id' => $supply->id,
                        'quantity_ordered' => $supply->reorder_quantity,
                        'unit_price' => $supply->purchase_price,
                        'total_price' => $supply->reorder_quantity * $supply->purchase_price,
                    ]);
                }

                $po->recalculate();
                $this->info("Created PO #{$po->po_number} for supplier #{$supplierId} with {$supplies->count()} items.");
                Log::info("Auto-reorder PO created: {$po->po_number}");
            });
        }

        // Also log warning for low stock items WITHOUT auto-reorder
        $manualAlerts = Supply::active()->lowStock()->where('auto_reorder', false)->count();
        if ($manualAlerts > 0) {
            $this->warn("{$manualAlerts} items are low stock but need manual reorder.");
        }
    }
}
