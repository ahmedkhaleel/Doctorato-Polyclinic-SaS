<?php

namespace App\Services;

use App\Models\CosmeticPackage;
use App\Models\CosmeticPackagePurchase;
use App\Models\CosmeticSession;
use App\Models\DermaSession;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use Illuminate\Support\Facades\DB;

/**
 * Bridges the Dermatology & Cosmetic clinical layer into the financial
 * system — mirrors DentalInvoiceService.
 *
 * A cosmetic/derma session carries a `cost`; once it is COMPLETED and has a
 * positive cost, this service finds the patient's open invoice (for the
 * linked visit, if any) or creates one, appends an InvoiceItem describing the
 * procedure, recalculates totals/status, and links the session back via
 * `invoice_id`. From there the existing pipeline takes over: the invoice
 * shows in the financial reports, the patient wallet, and — when paid through
 * a completed visit — the loyalty engine.
 *
 * Idempotent: a session already linked to an invoice is never billed twice.
 */
class CosmeticDermaInvoiceService
{
    /** Invoices raised by this module are tagged under the derma module. */
    private const MODULE = 'derma';

    // ─── Cosmetic ───────────────────────────────────────────────
    public function generateForCosmeticSession(CosmeticSession $session): ?Invoice
    {
        // A session drawn from a prepaid package is NOT billed per-session —
        // it consumes the package balance instead (the package was paid up
        // front via its own invoice).
        if ($session->package_purchase_id) {
            $this->syncPackageBalance($session->packagePurchase);
            return null;
        }

        if (! $this->billable($session)) {
            return $session->invoice_id ? $session->invoice : null;
        }

        return DB::transaction(function () use ($session) {
            $invoice = $this->findOrCreateInvoice($session->patient_id, $session->visit_id);

            $procedure = $session->procedure;
            $labelEn = $procedure->name_en ?? $procedure->name_ar ?? 'Cosmetic procedure';
            $labelAr = $procedure->name_ar ?? $procedure->name_en ?? 'إجراء تجميلي';
            $area = $session->area_treated ? " — {$session->area_treated}" : '';

            InvoiceItem::create([
                'invoice_id'     => $invoice->id,
                'description_en' => $labelEn . $area,
                'description_ar' => $labelAr . $area,
                'quantity'       => 1,
                'unit_price'     => (float) $session->cost,
                'discount'       => 0,
                'total'          => (float) $session->cost,
            ]);

            $this->recalculateInvoice($invoice);
            $session->update(['invoice_id' => $invoice->id]);

            return $invoice->fresh(['items']);
        });
    }

    // ─── Derma ──────────────────────────────────────────────────
    public function generateForDermaSession(DermaSession $session): ?Invoice
    {
        if (! $this->billable($session)) {
            return $session->invoice_id ? $session->invoice : null;
        }

        return DB::transaction(function () use ($session) {
            $invoice = $this->findOrCreateInvoice($session->patient_id, $session->visit_id);

            $area = $session->area_treated ? " — {$session->area_treated}" : '';
            $progress = ($session->total_sessions && $session->total_sessions > 1)
                ? " ({$session->session_number}/{$session->total_sessions})" : '';

            InvoiceItem::create([
                'invoice_id'     => $invoice->id,
                'description_en' => ucfirst($session->session_type) . ' session' . $area . $progress,
                'description_ar' => $this->dermaArabicLabel($session->session_type) . $area . $progress,
                'quantity'       => 1,
                'unit_price'     => (float) $session->cost,
                'discount'       => 0,
                'total'          => (float) $session->cost,
            ]);

            $this->recalculateInvoice($invoice);
            $session->update(['invoice_id' => $invoice->id]);

            return $invoice->fresh(['items']);
        });
    }

    // ─── Package purchases (prepaid enrollments) ────────────────

    /**
     * Enroll a patient in a package: create the purchase ledger row and a
     * prepayment invoice for the package price. The patient then pays that
     * invoice once; subsequent sessions draw down the balance for free.
     */
    public function createPackagePurchase(
        int $patientId,
        CosmeticPackage $package,
        ?float $amount = null,
        ?string $notes = null
    ): CosmeticPackagePurchase {
        return DB::transaction(function () use ($patientId, $package, $amount, $notes) {
            $price = $amount ?? (float) $package->total_price;

            $invoice = new Invoice([
                'invoice_number'  => Invoice::generateInvoiceNumber(),
                'invoice_date'    => now()->toDateString(),
                'patient_id'      => $patientId,
                'subtotal'        => $price,
                'discount_amount' => 0,
                'tax_amount'      => 0,
                'total'           => $price,
                'module'          => self::MODULE,
                'created_by'      => auth()->id(),
            ]);
            $invoice->paid_amount = 0;
            $invoice->status = 'unpaid';
            $invoice->save();

            $label = $package->name_ar ?? $package->name_en ?? 'باقة تجميل';
            InvoiceItem::create([
                'invoice_id'     => $invoice->id,
                'description_en' => ($package->name_en ?? $package->name_ar ?? 'Cosmetic package')
                    . " ({$package->total_sessions} sessions)",
                'description_ar' => $label . " ({$package->total_sessions} جلسات)",
                'quantity'       => 1,
                'unit_price'     => $price,
                'discount'       => 0,
                'total'          => $price,
            ]);

            $validity = (int) ($package->validity_days ?: 365);

            return CosmeticPackagePurchase::create([
                'patient_id'     => $patientId,
                'package_id'     => $package->id,
                'invoice_id'     => $invoice->id,
                'created_by'     => auth()->id(),
                'total_sessions' => (int) $package->total_sessions,
                'sessions_used'  => 0,
                'amount'         => $price,
                'purchased_at'   => now()->toDateString(),
                'expires_at'     => now()->addDays($validity)->toDateString(),
                'status'         => CosmeticPackagePurchase::STATUS_ACTIVE,
                'notes'          => $notes,
            ]);
        });
    }

    /**
     * Recompute a purchase's used-session count from its completed sessions.
     * Idempotent by construction (counts rows rather than incrementing), so
     * it self-corrects on edits/deletes and never double-charges a session.
     */
    public function syncPackageBalance(?CosmeticPackagePurchase $purchase): void
    {
        if (! $purchase) {
            return;
        }

        $used = $purchase->sessions()->whereNotNull('completed_at')->count();
        $status = $purchase->status;

        if ($status !== CosmeticPackagePurchase::STATUS_CANCELLED) {
            if ($used >= $purchase->total_sessions) {
                $status = CosmeticPackagePurchase::STATUS_COMPLETED;
            } elseif ($purchase->isExpired()) {
                $status = CosmeticPackagePurchase::STATUS_EXPIRED;
            } else {
                $status = CosmeticPackagePurchase::STATUS_ACTIVE;
            }
        }

        $purchase->update(['sessions_used' => $used, 'status' => $status]);
    }

    // ─── Shared ─────────────────────────────────────────────────

    /** A session is billable when completed, priced, and not yet invoiced. */
    private function billable($session): bool
    {
        return $session->completed_at !== null
            && (float) $session->cost > 0
            && ! $session->invoice_id;
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
            'invoice_number'  => Invoice::generateInvoiceNumber(),
            'invoice_date'    => now()->toDateString(),
            'patient_id'      => $patientId,
            'visit_id'        => $visitId,
            'subtotal'        => 0,
            'discount_amount' => 0,
            'tax_amount'      => 0,
            'total'           => 0,
            'module'          => self::MODULE,
            'created_by'      => auth()->id(),
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
            'total'    => $subtotal - (float) $invoice->discount_amount + (float) $invoice->tax_amount,
        ]);
        $invoice->recalculateStatus();
    }

    protected function dermaArabicLabel(string $type): string
    {
        return match ($type) {
            'laser'        => 'جلسة ليزر',
            'peel'         => 'جلسة تقشير',
            'phototherapy' => 'جلسة علاج ضوئي',
            'injection'    => 'جلسة حقن',
            'cryotherapy'  => 'جلسة تبريد',
            default        => 'جلسة جلدية',
        };
    }
}
