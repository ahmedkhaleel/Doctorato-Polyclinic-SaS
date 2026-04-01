<?php

namespace App\Services;

use App\Models\DentalLabOrder;
use App\Models\DentalTreatment;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use Illuminate\Support\Facades\DB;

class DentalInvoiceService
{
    /**
     * Generate or update invoice when a dental treatment is marked as completed.
     *
     * If the treatment's visit already has an invoice, add items to it.
     * Otherwise, create a new invoice for the patient.
     */
    public function generateForCompletedTreatment(DentalTreatment $treatment): ?Invoice
    {
        if ($treatment->status !== 'completed') {
            return null;
        }

        // Skip if treatment cost is zero
        $treatmentCost = (float) $treatment->cost;
        if ($treatmentCost <= 0) {
            return null;
        }

        // Skip if already invoiced
        if ($treatment->invoice_id) {
            return $treatment->invoice;
        }

        return DB::transaction(function () use ($treatment, $treatmentCost) {
            // Find or create invoice
            $invoice = $this->findOrCreateInvoice($treatment);

            // Add treatment cost as invoice item
            $treatmentType = str_replace('_', ' ', $treatment->treatment_type);
            $toothLabel = $treatment->tooth_number ? " (#{$treatment->tooth_number})" : '';

            InvoiceItem::create([
                'invoice_id' => $invoice->id,
                'description_en' => ucfirst($treatmentType) . $toothLabel,
                'description_ar' => $this->getArabicDescription($treatment->treatment_type) . $toothLabel,
                'quantity' => 1,
                'unit_price' => $treatmentCost,
                'discount' => 0,
                'total' => $treatmentCost,
            ]);

            // Add lab order charge as separate line item if exists
            $this->addLabOrderItem($treatment, $invoice);

            // Recalculate invoice totals
            $this->recalculateInvoice($invoice);

            // Link treatment to invoice
            $treatment->update(['invoice_id' => $invoice->id]);

            return $invoice->fresh(['items']);
        });
    }

    /**
     * Add lab order patient_charge as an invoice item when the lab order is delivered.
     */
    public function addLabOrderToInvoice(DentalLabOrder $labOrder): ?InvoiceItem
    {
        $patientCharge = (float) $labOrder->patient_charge;
        if ($patientCharge <= 0) {
            return null;
        }

        // Skip if already linked to an invoice item
        if ($labOrder->invoice_item_id) {
            return $labOrder->invoiceItem;
        }

        // Find invoice from linked treatment, or create one
        $treatment = $labOrder->treatment;
        if (!$treatment) {
            return null;
        }

        return DB::transaction(function () use ($labOrder, $treatment, $patientCharge) {
            $invoice = $treatment->invoice_id
                ? Invoice::find($treatment->invoice_id)
                : $this->findOrCreateInvoice($treatment);

            $itemType = str_replace('_', ' ', $labOrder->item_type);
            $toothLabel = $labOrder->tooth_number ? " (#{$labOrder->tooth_number})" : '';
            $materialLabel = $labOrder->material ? ' - ' . ucfirst(str_replace('_', ' ', $labOrder->material)) : '';

            $invoiceItem = InvoiceItem::create([
                'invoice_id' => $invoice->id,
                'description_en' => 'Lab: ' . ucfirst($itemType) . $materialLabel . $toothLabel,
                'description_ar' => 'معمل: ' . $this->getLabArabicDescription($labOrder->item_type) . $materialLabel . $toothLabel,
                'quantity' => 1,
                'unit_price' => $patientCharge,
                'discount' => 0,
                'total' => $patientCharge,
            ]);

            $labOrder->update(['invoice_item_id' => $invoiceItem->id]);

            // Link treatment to invoice if not already linked
            if (!$treatment->invoice_id) {
                $treatment->update(['invoice_id' => $invoice->id]);
            }

            $this->recalculateInvoice($invoice);

            return $invoiceItem;
        });
    }

    /**
     * Find existing invoice for the treatment's visit, or create a new one.
     */
    protected function findOrCreateInvoice(DentalTreatment $treatment): Invoice
    {
        // Try to find an existing unpaid/partial invoice for this visit
        if ($treatment->visit_id) {
            $existingInvoice = Invoice::where('visit_id', $treatment->visit_id)
                ->whereIn('status', ['unpaid', 'partial'])
                ->first();

            if ($existingInvoice) {
                return $existingInvoice;
            }
        }

        // Create a new invoice
        $invoice = new Invoice([
            'invoice_number' => Invoice::generateInvoiceNumber(),
            'invoice_date' => now()->toDateString(),
            'patient_id' => $treatment->patient_id,
            'visit_id' => $treatment->visit_id,
            'subtotal' => 0,
            'discount_amount' => 0,
            'tax_amount' => 0,
            'total' => 0,
            'created_by' => auth()->id(),
        ]);
        $invoice->paid_amount = 0;
        $invoice->status = 'unpaid';
        $invoice->save();

        return $invoice;
    }

    /**
     * Add lab order charge if it exists and has a patient_charge.
     */
    protected function addLabOrderItem(DentalTreatment $treatment, Invoice $invoice): void
    {
        $labOrder = $treatment->labOrder;
        if (!$labOrder) return;

        $patientCharge = (float) $labOrder->patient_charge;
        if ($patientCharge <= 0) return;
        if ($labOrder->invoice_item_id) return;

        $itemType = str_replace('_', ' ', $labOrder->item_type);
        $toothLabel = $labOrder->tooth_number ? " (#{$labOrder->tooth_number})" : '';
        $materialLabel = $labOrder->material ? ' - ' . ucfirst(str_replace('_', ' ', $labOrder->material)) : '';

        $invoiceItem = InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'description_en' => 'Lab: ' . ucfirst($itemType) . $materialLabel . $toothLabel,
            'description_ar' => 'معمل: ' . $this->getLabArabicDescription($labOrder->item_type) . $materialLabel . $toothLabel,
            'quantity' => 1,
            'unit_price' => $patientCharge,
            'discount' => 0,
            'total' => $patientCharge,
        ]);

        $labOrder->update(['invoice_item_id' => $invoiceItem->id]);
    }

    /**
     * Recalculate invoice subtotal and total from its items.
     */
    protected function recalculateInvoice(Invoice $invoice): void
    {
        $subtotal = $invoice->items()->sum('total');

        $invoice->update([
            'subtotal' => $subtotal,
            'total' => $subtotal - (float) $invoice->discount_amount + (float) $invoice->tax_amount,
        ]);

        $invoice->recalculateStatus();
    }

    protected function getArabicDescription(string $type): string
    {
        return match ($type) {
            'filling' => 'حشو',
            'extraction' => 'خلع',
            'root_canal' => 'علاج عصب',
            'crown' => 'تاج',
            'bridge' => 'جسر',
            'implant' => 'زرع',
            'cleaning' => 'تنظيف',
            'scaling' => 'تقليح',
            'whitening' => 'تبييض',
            'veneer' => 'قشرة',
            'orthodontic' => 'تقويم',
            'denture' => 'طقم أسنان',
            'sealant' => 'حشو وقائي',
            'fluoride' => 'فلورايد',
            'gum_treatment' => 'علاج لثة',
            'surgical_extraction' => 'خلع جراحي',
            'bone_graft' => 'ترقيع عظم',
            'sinus_lift' => 'رفع جيب أنفي',
            'night_guard' => 'واقي ليلي',
            'retainer' => 'مثبت',
            default => 'علاج أسنان',
        };
    }

    protected function getLabArabicDescription(string $itemType): string
    {
        return match ($itemType) {
            'crown' => 'تاج',
            'bridge' => 'جسر',
            'denture' => 'طقم أسنان',
            'retainer' => 'مثبت',
            'aligner' => 'تقويم شفاف',
            'veneer' => 'قشرة',
            'implant_abutment' => 'دعامة زرع',
            'night_guard' => 'واقي ليلي',
            'inlay_onlay' => 'حشو معملي',
            default => 'عمل معملي',
        };
    }
}
