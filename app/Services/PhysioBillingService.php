<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\PhysioSession;
use Illuminate\Support\Facades\DB;

/**
 * Bridges the physiotherapy clinical layer into the financial system — mirrors
 * ObgynBillingService / DentalInvoiceService.
 *
 * A completed session with a positive cost gets an InvoiceItem on an open
 * invoice (the visit's invoice when the session is tied to a visit, else a
 * standalone module-tagged invoice). The session links back via invoice_id +
 * invoice_item_id, making billing idempotent and reversible. The session fee
 * falls back to the physiotherapy `session_fee` module setting when the caller
 * passes none and the session carries no cost.
 */
class PhysioBillingService
{
    public const MODULE = 'physiotherapy';

    /**
     * Bill a session. Idempotent (skips if already billed) and skips zero-fee.
     * When $fee is null the session's own `cost` is used, then the module
     * `session_fee` setting as a last resort.
     */
    public function billSession(PhysioSession $session, ?float $fee = null): ?Invoice
    {
        if ($session->invoice_item_id) {
            return $session->invoice; // already billed
        }

        $fee ??= (float) $session->cost;
        if ($fee <= 0) {
            $fee = $this->moduleFee('session_fee', 0);
        }
        // Home-visit surcharge is added on top of the base session fee.
        $homeVisit = (bool) $session->home_visit;
        if ($homeVisit) {
            $fee += $this->moduleFee('home_visit_surcharge', 0);
        }
        if ($fee <= 0) {
            return null;
        }

        $n = $session->session_number ? " #{$session->session_number}" : '';
        $hvEn = $homeVisit ? ' (home visit)' : '';
        $hvAr = $homeVisit ? ' (زيارة منزلية)' : '';

        return DB::transaction(function () use ($session, $fee, $n, $hvEn, $hvAr) {
            $invoice = $this->findOrCreateInvoice($session->patient_id, $session->visit_id);

            $item = InvoiceItem::create([
                'invoice_id' => $invoice->id,
                'description_en' => 'Physiotherapy session'.$n.$hvEn,
                'description_ar' => 'جلسة علاج طبيعي'.$n.$hvAr,
                'quantity' => 1,
                'unit_price' => $fee,
                'discount' => 0,
                'total' => $fee,
            ]);

            $this->recalculateInvoice($invoice);
            $session->forceFill([
                'invoice_id' => $invoice->id,
                'invoice_item_id' => $item->id,
                'cost' => $fee,
            ])->save();

            return $invoice->fresh(['items']);
        });
    }

    /** Void the invoice line a session produced and clear its links. No-op when unbilled. */
    public function reverse(PhysioSession $session): void
    {
        if (! $session->invoice_item_id) {
            return;
        }

        DB::transaction(function () use ($session) {
            $item = InvoiceItem::find($session->invoice_item_id);
            $invoice = $item?->invoice;

            $item?->delete();
            $session->forceFill(['invoice_id' => null, 'invoice_item_id' => null])->save();

            if ($invoice) {
                $this->recalculateInvoice($invoice);
            }
        });
    }

    protected function findOrCreateInvoice(int $patientId, ?int $visitId): Invoice
    {
        if ($visitId) {
            $existing = Invoice::where('visit_id', $visitId)
                ->whereIn('status', ['unpaid', 'partial'])
                ->first();
            if ($existing) {
                return $existing;
            }
        }

        $invoice = new Invoice([
            'invoice_number' => Invoice::generateInvoiceNumber(),
            'invoice_date' => now()->toDateString(),
            'patient_id' => $patientId,
            'visit_id' => $visitId,
            'subtotal' => 0,
            'discount_amount' => 0,
            'tax_amount' => 0,
            'total' => 0,
            'module' => self::MODULE,
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

    protected function moduleFee(string $key, float $default): float
    {
        $value = DB::table('module_settings')
            ->where('module', self::MODULE)
            ->where('key', $key)
            ->value('value');

        return is_numeric($value) ? (float) $value : $default;
    }
}
