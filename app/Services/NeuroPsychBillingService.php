<?php

namespace App\Services;

use App\Models\CourseSession;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\NeuroProcedure;
use App\Models\NeuropsychEncounter;
use App\Models\Supply;
use App\Models\SupplyTransaction;
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

    /**
     * NP5 — bill a completed neurology procedure (cost>0) and draw its supply
     * (e.g. botox) from inventory. Idempotent.
     */
    public function billProcedure(NeuroProcedure $proc): ?Invoice
    {
        $this->consumeProcedureInventory($proc);

        if ($proc->invoice_item_id || $proc->completed_at === null || (float) $proc->cost <= 0) {
            return $proc->invoice_id ? $proc->invoice()->first() : null;
        }

        return DB::transaction(function () use ($proc) {
            $invoice = $this->findOrCreateProcedureInvoice($proc);
            $labels = [
                'emg_ncs' => ['EMG/NCS', 'تخطيط عضلات/أعصاب'],
                'lumbar_puncture' => ['Lumbar puncture', 'بزل قطني'],
                'eeg' => ['EEG', 'تخطيط دماغ'],
                'botox' => ['Botulinum toxin injection', 'حقن بوتولينَم'],
                'nerve_block' => ['Nerve block', 'حصار عصبي'],
            ];
            [$en, $ar] = $labels[$proc->type] ?? [$proc->type, $proc->type];

            $item = InvoiceItem::create([
                'invoice_id' => $invoice->id,
                'description_en' => $en,
                'description_ar' => $ar,
                'quantity' => 1,
                'unit_price' => (float) $proc->cost,
                'discount' => 0,
                'total' => (float) $proc->cost,
            ]);

            $this->recalculateInvoice($invoice);
            $proc->forceFill(['invoice_id' => $invoice->id, 'invoice_item_id' => $item->id])->save();

            return $invoice->fresh(['items']);
        });
    }

    /** Draw a procedure's supply (botox vials, etc.) from stock. Idempotent. */
    public function consumeProcedureInventory(NeuroProcedure $proc): ?SupplyTransaction
    {
        if ($proc->supply_transaction_id || $proc->completed_at === null) {
            return $proc->supply_transaction_id ? $proc->supplyTransaction : null;
        }
        $qty = (float) ($proc->consumption_qty ?? 0);
        if (! $proc->supply_id || $qty <= 0) {
            return null;
        }

        return DB::transaction(function () use ($proc, $qty) {
            $supply = Supply::lockForUpdate()->find($proc->supply_id);
            if (! $supply) {
                return null;
            }
            $txn = SupplyTransaction::create([
                'supply_id' => $supply->id,
                'transaction_type' => 'usage',
                'quantity' => $qty,
                'unit_cost' => $supply->purchase_price,
                'notes' => 'Neuro procedure #'.$proc->id.' ('.$proc->type.')',
                'created_by' => auth()->id(),
            ]);
            $supply->decrement('quantity', $qty);
            $proc->forceFill(['supply_transaction_id' => $txn->id])->save();

            return $txn;
        });
    }

    protected function findOrCreateProcedureInvoice(NeuroProcedure $proc): Invoice
    {
        $invoice = new Invoice([
            'invoice_number' => Invoice::generateInvoiceNumber(),
            'invoice_date' => now()->toDateString(),
            'patient_id' => $proc->patient_id,
            'subtotal' => 0, 'discount_amount' => 0, 'tax_amount' => 0, 'total' => 0,
            'module' => 'neurology',
            'created_by' => auth()->id(),
        ]);
        $invoice->paid_amount = 0;
        $invoice->status = 'unpaid';
        $invoice->save();

        return $invoice;
    }

    /** NP6 — bill a completed treatment-course session (cost>0). Idempotent. */
    public function billCourseSession(CourseSession $session, string $module = 'psychiatry'): ?Invoice
    {
        if ($session->invoice_item_id || $session->completed_at === null || (float) $session->cost <= 0) {
            return $session->invoice_id ? $session->invoice()->first() : null;
        }

        return DB::transaction(function () use ($session, $module) {
            $invoice = new Invoice([
                'invoice_number' => Invoice::generateInvoiceNumber(),
                'invoice_date' => now()->toDateString(),
                'patient_id' => $session->patient_id,
                'subtotal' => 0, 'discount_amount' => 0, 'tax_amount' => 0, 'total' => 0,
                'module' => $module,
                'created_by' => auth()->id(),
            ]);
            $invoice->paid_amount = 0;
            $invoice->status = 'unpaid';
            $invoice->save();

            $item = InvoiceItem::create([
                'invoice_id' => $invoice->id,
                'description_en' => 'Treatment course session #'.$session->session_number,
                'description_ar' => 'جلسة دورة علاجية #'.$session->session_number,
                'quantity' => 1,
                'unit_price' => (float) $session->cost,
                'discount' => 0,
                'total' => (float) $session->cost,
            ]);

            $this->recalculateInvoice($invoice);
            $session->forceFill(['invoice_id' => $invoice->id, 'invoice_item_id' => $item->id])->save();

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
