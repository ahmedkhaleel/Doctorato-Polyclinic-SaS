<?php

namespace App\Services;

use App\Models\DiscountCode;
use App\Models\DiscountUsage;
use App\Models\Invoice;
use Illuminate\Support\Facades\DB;

class PromoCodeService
{
    /**
     * Resolve a promo code by its code string (case-insensitive).
     */
    public function resolveCode(string $code): ?DiscountCode
    {
        return DiscountCode::whereRaw('LOWER(code) = ?', [strtolower(trim($code))])->first();
    }

    /**
     * Validate a promo code with full context.
     *
     * Returns ['valid' => bool, 'message' => string, 'discount_code' => ?DiscountCode, 'discount_preview' => ?float]
     */
    public function validate(
        string $code,
        string $bookingType = 'service',
        ?int $serviceId = null,
        ?int $packageBundleId = null,
        float $amount = 0,
        ?int $patientId = null,
    ): array {
        $discountCode = $this->resolveCode($code);

        if (!$discountCode) {
            return $this->fail('invalid_code');
        }

        // Check active status
        if (!$discountCode->is_active) {
            return $this->fail('code_inactive');
        }

        // Check date range
        if ($discountCode->start_date && $discountCode->start_date->gt(today())) {
            return $this->fail('code_not_started');
        }

        if ($discountCode->end_date && $discountCode->end_date->lt(today())) {
            return $this->fail('code_expired');
        }

        // Check max uses
        if ($discountCode->max_uses && $discountCode->used_count >= $discountCode->max_uses) {
            return $this->fail('max_uses_reached');
        }

        // Check applicability based on booking type
        if ($bookingType === 'service' && $serviceId) {
            if (!$discountCode->isApplicableToService($serviceId)) {
                return $this->fail('not_applicable_service');
            }
        }

        if ($bookingType === 'package' && $packageBundleId) {
            if (!$discountCode->isApplicableToPackage($packageBundleId)) {
                return $this->fail('not_applicable_package');
            }
        }

        // Check minimum order amount
        if ($amount > 0 && !$discountCode->meetsMinimumAmount($amount)) {
            return $this->fail('min_amount_not_met', [
                'min_amount' => number_format($discountCode->min_order_amount, 2),
            ]);
        }

        // Check per-patient limit
        if ($patientId && $discountCode->hasExceededPatientLimit($patientId)) {
            return $this->fail('patient_limit_exceeded');
        }

        // Check first booking only
        if (!$discountCode->isFirstBookingEligible($patientId)) {
            return $this->fail('first_booking_only');
        }

        // Calculate discount preview
        $discountPreview = $amount > 0 ? $discountCode->calculateDiscount($amount) : null;

        return [
            'valid' => true,
            'message' => 'code_valid',
            'discount_code' => $discountCode,
            'discount_preview' => $discountPreview,
            'discount_type' => $discountCode->discount_type,
            'discount_value' => $discountCode->discount_value,
            'max_discount_amount' => $discountCode->max_discount_amount,
        ];
    }

    /**
     * Apply a promo code discount to an invoice.
     * Uses DB lock to prevent race conditions on used_count.
     */
    public function applyToInvoice(
        DiscountCode $discountCode,
        Invoice $invoice,
        ?int $patientId = null,
        ?int $bookingId = null,
        ?int $packageBundleBookingId = null,
        ?string $ipAddress = null,
        ?string $userAgent = null,
    ): bool {
        // Don't double-apply
        if ($invoice->discount_code_id) {
            return false;
        }

        return DB::transaction(function () use (
            $discountCode, $invoice, $patientId,
            $bookingId, $packageBundleBookingId,
            $ipAddress, $userAgent,
        ) {
            // Lock the discount code row to prevent race conditions
            $locked = DiscountCode::where('id', $discountCode->id)->lockForUpdate()->first();

            if (!$locked || !$locked->is_active) {
                return false;
            }

            // Re-check max uses under lock
            if ($locked->max_uses && $locked->used_count >= $locked->max_uses) {
                return false;
            }

            // Re-check date validity
            if ($locked->end_date && $locked->end_date->lt(today())) {
                return false;
            }

            // Calculate the discount
            $discountAmount = $locked->calculateDiscount((float) $invoice->subtotal);

            if ($discountAmount <= 0) {
                return false;
            }

            // Apply to invoice
            $invoice->discount_code_id = $locked->id;
            $invoice->discount_amount = $discountAmount;
            $invoice->total = max(0, (float) $invoice->subtotal - $discountAmount + (float) $invoice->tax_amount);
            $invoice->save();

            // Increment used_count atomically
            DiscountCode::where('id', $locked->id)->increment('used_count');

            // Record usage
            DiscountUsage::create([
                'discount_code_id' => $locked->id,
                'patient_id' => $patientId,
                'invoice_id' => $invoice->id,
                'booking_id' => $bookingId,
                'package_bundle_booking_id' => $packageBundleBookingId,
                'discount_amount' => $discountAmount,
                'ip_address' => $ipAddress,
                'user_agent' => $userAgent,
            ]);

            return true;
        });
    }

    /**
     * Get the active website popup promo code.
     */
    public function getActiveWebsitePromo(): ?DiscountCode
    {
        return DiscountCode::websitePopup()
            ->latest()
            ->first();
    }

    /**
     * Return a validation failure result.
     */
    private function fail(string $messageKey, array $data = []): array
    {
        return array_merge([
            'valid' => false,
            'message' => $messageKey,
            'discount_code' => null,
            'discount_preview' => null,
        ], $data);
    }
}
