<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\NeuropsychEncounter;
use Illuminate\Support\Facades\DB;

/**
 * Bridges the psychiatry/neurology clinical layer into the financial system —
 * mirrors ObgynBillingService / CosmeticDermaInvoiceService.
 *
 * A completed encounter with cost > 0 gets an open Invoice (tagged with the
 * encounter's module so module-revenue reports pick it up) and an InvoiceItem;
 * the encounter is linked back via invoice_id + invoice_item_id, making this
 * idempotent and reversible. Zero-cost or not-yet-completed encounters are not
 * billed.
 */
class NeuroPsychBillingService
{
    /** Bill a completed encounter. Idempotent; no-op when not billable. */
    public function billEncounter(NeuropsychEncounter $encounter): ?Invoice
    {
        if ($encounter->invoice_item_id) {
            return $encounter->invoice; // already billed
        }
        if ($encounter->completed_at === null || (float) $encounter->cost <= 0) {
            return null;
        }

        return DB::transaction(function () use ($encounter) {
            $invoice = $this->findOrCreateInvoice($encounter);
            $labelEn = ($encounter->module === 'neurology' ? 'Neurology' : 'Psychiatry').' consultation';
            $labelAr = ($encounter->module === 'neurology' ? 'كشف عصبية' : 'كشف نفسية');

            $item = InvoiceItem::create([
                'invoice_id' => $invoice->id,
                'description_en' => $labelEn,
                'description_ar' => $labelAr,
                'quantity' => 1,
                'unit_price' => (float) $encounter->cost,
                'discount' => 0,
                'total' => (float) $encounter->cost,
            ]);

            $this->recalculateInvoice($invoice);
            $encounter->forceFill(['invoice_id' => $invoice->id, 'invoice_item_id' => $item->id])->save();

            return $invoice->fresh(['items']);
        });
    }

    /** Void the invoice line an encounter produced and clear its links. No-op when unbilled. */
    public function reverse(NeuropsychEncounter $encounter): void
    {
        if (! $encounter->invoice_item_id) {
            return;
        }

        DB::transaction(function () use ($encounter) {
            $item = InvoiceItem::find($encounter->invoice_item_id);
            $invoice = $item?->invoice;
            $item?->delete();
            $encounter->forceFill(['invoice_id' => null, 'invoice_item_id' => null])->save();
            if ($invoice) {
                $this->recalculateInvoice($invoice);
            }
        });
    }

    protected function findOrCreateInvoice(NeuropsychEncounter $encounter): Invoice
    {
        if ($encounter->visit_id) {
            $existing = Invoice::where('visit_id', $encounter->visit_id)
                ->whereIn('status', ['unpaid', 'partial'])
                ->first();
            if ($existing) {
                return $existing;
            }
        }

        $invoice = new Invoice([
            'invoice_number' => Invoice::generateInvoiceNumber(),
            'invoice_date' => now()->toDateString(),
            'patient_id' => $encounter->patient_id,
            'visit_id' => $encounter->visit_id,
            'subtotal' => 0,
            'discount_amount' => 0,
            'tax_amount' => 0,
            'total' => 0,
            'module' => $encounter->module,
            'created_by' => auth()->id(),
        ]);
        $invoice->paid_amount = 0;
        $invoice->status = 'unpaid';
        $invoice->save();

        return $invoice;
    }

    protected function recalculateInvoice(Invoice $invoice): void
    {
        $subtotal = $invoice->items()->sum('total');
        $invoice->update([
            'subtotal' => $subtotal,
            'total' => $subtotal - (float) $invoice->discount_amount + (float) $invoice->tax_amount,
        ]);
        $invoice->recalculateStatus();
    }
}
