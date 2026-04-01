<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\LogsActivity;

class DiscountCode extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'code', 'discount_type', 'discount_value', 'max_uses', 'used_count',
        'start_date', 'end_date', 'is_active', 'applicable_services', 'notes', 'created_by',
        // Package bundle targeting
        'applicable_packages',
        // Smart conditions
        'min_order_amount', 'max_discount_amount', 'per_patient_limit', 'first_booking_only',
        // Website popup settings
        'show_on_website', 'popup_title_en', 'popup_title_ar',
        'popup_description_en', 'popup_description_ar', 'popup_image',
    ];

    protected $casts = [
        'discount_value' => 'decimal:2',
        'is_active' => 'boolean',
        'start_date' => 'date',
        'end_date' => 'date',
        'applicable_services' => 'array',
        'applicable_packages' => 'array',
        'min_order_amount' => 'decimal:2',
        'max_discount_amount' => 'decimal:2',
        'first_booking_only' => 'boolean',
        'show_on_website' => 'boolean',
    ];

    // ─── Relationships ──────────────────────────────────

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function usages()
    {
        return $this->hasMany(DiscountUsage::class);
    }

    // ─── Scopes ─────────────────────────────────────────

    public function scopeValid($query)
    {
        return $query->where('is_active', true)
            ->where(fn ($q) => $q->whereNull('start_date')->orWhere('start_date', '<=', today()))
            ->where(fn ($q) => $q->whereNull('end_date')->orWhere('end_date', '>=', today()))
            ->where(fn ($q) => $q->whereNull('max_uses')->orWhereColumn('used_count', '<', 'max_uses'));
    }

    public function scopeWebsitePopup($query)
    {
        return $query->valid()->where('show_on_website', true);
    }

    // ─── Discount Calculation ───────────────────────────

    public function calculateDiscount(float $amount): float
    {
        if ($this->discount_type === 'percentage') {
            $discount = round($amount * $this->discount_value / 100, 2);

            // Apply max discount cap if set
            if ($this->max_discount_amount && $discount > $this->max_discount_amount) {
                $discount = (float) $this->max_discount_amount;
            }

            return $discount;
        }

        return min($this->discount_value, $amount);
    }

    // ─── Validation Methods ─────────────────────────────

    public function isApplicableToService(?int $serviceId): bool
    {
        // If no services specified, applies to all
        if (empty($this->applicable_services)) {
            return true;
        }

        return in_array($serviceId, $this->applicable_services);
    }

    public function isApplicableToPackage(?int $packageBundleId): bool
    {
        // If no packages specified, applies to all
        if (empty($this->applicable_packages)) {
            return true;
        }

        return in_array($packageBundleId, $this->applicable_packages);
    }

    public function meetsMinimumAmount(float $amount): bool
    {
        return $amount >= (float) $this->min_order_amount;
    }

    public function hasExceededPatientLimit(?int $patientId): bool
    {
        if (!$this->per_patient_limit || !$patientId) {
            return false;
        }

        $usageCount = $this->usages()
            ->where('patient_id', $patientId)
            ->count();

        return $usageCount >= $this->per_patient_limit;
    }

    public function isFirstBookingEligible(?int $patientId): bool
    {
        if (!$this->first_booking_only) {
            return true;
        }

        if (!$patientId) {
            // No patient yet (guest booking), allow it - will be validated at confirmation
            return true;
        }

        // Check if patient has any previous bookings
        $hasBookings = Booking::where('patient_id', $patientId)
            ->where('status', '!=', 'cancelled')
            ->exists();

        if ($hasBookings) {
            return false;
        }

        $hasBundleBookings = PackageBundleBooking::where('patient_id', $patientId)
            ->where('status', '!=', 'cancelled')
            ->exists();

        return !$hasBundleBookings;
    }
}
