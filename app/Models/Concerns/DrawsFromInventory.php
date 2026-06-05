<?php

namespace App\Models\Concerns;

use App\Models\Supply;
use App\Models\SupplyTransaction;
use Illuminate\Support\Facades\DB;

/**
 * Shared supply-consumption for clinical event records that carry
 * `supply_id` + `consumption_qty` + `supply_transaction_id` (obstetric
 * ultrasounds, deliveries, cosmetic sessions). Mirrors the hand-rolled
 * PediatricVaccination pattern but generalized + auto-wired:
 *
 *  - on save: draw `consumption_qty` from the linked supply once (idempotent,
 *    guarded by supply_transaction_id), recording a `usage` SupplyTransaction;
 *  - on delete: restore it via a `return` transaction.
 *
 * Stock is decremented even below zero (negative stock is allowed clinic-wide
 * and surfaced for reconciliation) — consistent with the other consumption
 * paths. Uses saveQuietly() so it never re-triggers model events.
 */
trait DrawsFromInventory
{
    public static function bootDrawsFromInventory(): void
    {
        static::saved(fn ($model) => $model->consumeStock());
        static::deleted(fn ($model) => $model->reverseStock());
    }

    public function consumeStock(): void
    {
        $qty = (float) ($this->consumption_qty ?? 0);
        if (! $this->supply_id || $qty <= 0 || $this->supply_transaction_id) {
            return;
        }

        DB::transaction(function () use ($qty) {
            $supply = Supply::lockForUpdate()->find($this->supply_id);
            if (! $supply) {
                return;
            }
            $txn = SupplyTransaction::create([
                'supply_id' => $supply->id,
                'transaction_type' => 'usage',
                'quantity' => $qty,
                'unit_cost' => $supply->purchase_price,
                'notes' => class_basename($this).' #'.$this->id.' consumption',
                'created_by' => auth()->id(),
            ]);
            $supply->decrement('quantity', $qty);
            $this->forceFill(['supply_transaction_id' => $txn->id])->saveQuietly();
        });
    }

    public function reverseStock(): void
    {
        if (! $this->supply_transaction_id) {
            return;
        }

        DB::transaction(function () {
            $usage = SupplyTransaction::find($this->supply_transaction_id);
            if ($usage) {
                $supply = Supply::lockForUpdate()->find($usage->supply_id);
                if ($supply) {
                    SupplyTransaction::create([
                        'supply_id' => $supply->id,
                        'transaction_type' => 'return',
                        'quantity' => $usage->quantity,
                        'unit_cost' => $usage->unit_cost,
                        'notes' => 'Reversal — '.class_basename($this).' #'.$this->id,
                        'created_by' => auth()->id(),
                    ]);
                    $supply->increment('quantity', $usage->quantity);
                }
            }
            $this->forceFill(['supply_transaction_id' => null])->saveQuietly();
        });
    }
}
