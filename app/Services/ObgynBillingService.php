<?php

namespace App\Services;

use App\Models\AntenatalVisit;
use App\Models\DeliveryRecord;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\ObstetricUltrasound;
use App\Models\PapSmearScreening;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Bridges the OB/GYN clinical layer into the financial system — mirrors
 * CosmeticDermaInvoiceService / DentalInvoiceService.
 *
 * A billable encounter (antenatal visit, ultrasound, delivery, pap smear)
 * gets a Visit row tagged module=obgyn (so it shows in the clinical visit
 * log and feeds the module-revenue reports), an open Invoice for that visit,
 * and an InvoiceItem describing the service. The encounter is linked back via
 * invoice_id + invoice_item_id, making the operation idempotent and reversible.
 *
 * Fees fall back to the obgyn module_settings pricing group when not passed.
 */
class ObgynBillingService
{
    public const MODULE = 'obgyn';

    public function billAntenatalVisit(AntenatalVisit $anc, ?float $fee = null): ?Invoice
    {
        $fee ??= $this->moduleFee('anc_fee', 150);
        $ga = $anc->gestational_age_weeks ? " — {$anc->gestational_age_weeks}w" : '';

        return $this->bill(
            $anc,
            patientId: $anc->pregnancy->patient_id,
            descEn: 'Antenatal care visit'.$ga,
            descAr: 'زيارة متابعة حمل'.$ga,
            fee: (float) $fee,
        );
    }

    public function billUltrasound(ObstetricUltrasound $scan, ?float $fee = null): ?Invoice
    {
        $fee ??= $this->moduleFee('ultrasound_fee', 200);

        return $this->bill(
            $scan,
            patientId: $scan->pregnancy->patient_id,
            descEn: 'Obstetric ultrasound ('.$scan->scan_type.')',
            descAr: 'سونار توليد ('.$this->scanTypeAr($scan->scan_type).')',
            fee: (float) $fee,
        );
    }

    public function billDelivery(DeliveryRecord $delivery, ?float $fee = null): ?Invoice
    {
        $fee ??= $this->moduleFee('delivery_fee', 5000);
        $modeAr = $this->deliveryModeAr($delivery->delivery_mode);

        return $this->bill(
            $delivery,
            patientId: $delivery->pregnancy->patient_id,
            descEn: 'Delivery ('.$delivery->delivery_mode.')',
            descAr: 'ولادة ('.$modeAr.')',
            fee: (float) $fee,
        );
    }

    public function billPapSmear(PapSmearScreening $pap, ?float $fee = null): ?Invoice
    {
        $fee ??= $this->moduleFee('pap_smear_fee', 180);

        return $this->bill(
            $pap,
            patientId: $pap->patient_id,
            descEn: 'Pap smear screening',
            descAr: 'مسحة عنق الرحم',
            fee: (float) $fee,
        );
    }

    /**
     * Core: ensure an Invoice + InvoiceItem for an encounter and link it back.
     *
     * Attaches to the encounter's existing Visit (from the booking→visit flow)
     * when present; otherwise raises a standalone invoice with the module tag
     * set explicitly (still counted by module-revenue reports). We never
     * fabricate a bare Visit — the Visit model requires a booking link.
     *
     * Idempotent (skips if already billed) and skips zero-fee items.
     */
    protected function bill(Model $encounter, int $patientId, string $descEn, string $descAr, float $fee): ?Invoice
    {
        if ($encounter->invoice_item_id) {
            return $encounter->invoice; // already billed
        }
        if ($fee <= 0) {
            return null;
        }

        return DB::transaction(function () use ($encounter, $patientId, $descEn, $descAr, $fee) {
            $invoice = $this->findOrCreateInvoice($patientId, $encounter->visit_id);

            $item = InvoiceItem::create([
                'invoice_id' => $invoice->id,
                'description_en' => $descEn,
                'description_ar' => $descAr,
                'quantity' => 1,
                'unit_price' => $fee,
                'discount' => 0,
                'total' => $fee,
            ]);

            $this->recalculateInvoice($invoice);
            $encounter->forceFill([
                'invoice_id' => $invoice->id,
                'invoice_item_id' => $item->id,
            ])->save();

            return $invoice->fresh(['items']);
        });
    }

    /**
     * Void the invoice line an encounter produced and clear its links.
     * No-op when nothing was billed.
     */
    public function reverse(Model $encounter): void
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

    private function scanTypeAr(string $type): string
    {
        return match ($type) {
            'dating' => 'تأريخ',
            'anomaly' => 'تشوهات',
            'growth' => 'نمو',
            'doppler' => 'دوبلر',
            default => $type,
        };
    }

    private function deliveryModeAr(string $mode): string
    {
        return match ($mode) {
            'nvd' => 'طبيعية',
            'cesarean' => 'قيصرية',
            'instrumental' => 'بمساعدة أدوات',
            default => $mode,
        };
    }
}
