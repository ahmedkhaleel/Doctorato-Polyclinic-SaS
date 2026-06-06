<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PatientInsurance extends Model
{
    use HasFactory;

    protected $fillable = [
        'patient_id', 'insurance_company_id', 'insurance_plan_id',
        'member_id', 'policy_number', 'card_number',
        'start_date', 'expiry_date',
        'relationship', 'principal_name',
        'max_annual_limit', 'used_amount',
        'card_image_front', 'card_image_back',
        'notes', 'is_active', 'is_verified', 'verified_at', 'verified_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'expiry_date' => 'date',
        'max_annual_limit' => 'decimal:2',
        'used_amount' => 'decimal:2',
        'is_active' => 'boolean',
        'is_verified' => 'boolean',
        'verified_at' => 'datetime',
    ];

    protected $appends = ['is_expired', 'remaining_balance', 'usage_percentage', 'card_image_front_url', 'card_image_back_url'];

    // ─── Relationships ──────────────────────────────────

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function company()
    {
        return $this->belongsTo(InsuranceCompany::class, 'insurance_company_id');
    }

    public function plan()
    {
        return $this->belongsTo(InsurancePlan::class, 'insurance_plan_id');
    }

    public function claims()
    {
        return $this->hasMany(InsuranceClaim::class);
    }

    public function preAuthorizations()
    {
        return $this->hasMany(InsurancePreAuthorization::class);
    }

    public function verifier()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    // ─── Accessors ──────────────────────────────────────

    protected function isExpired(): Attribute
    {
        return Attribute::get(fn () => $this->expiry_date && $this->expiry_date->isPast());
    }

    // Signed, authenticated media URLs (PHI — no longer public /storage links).
    protected function cardImageFrontUrl(): Attribute
    {
        return Attribute::get(fn () => \App\Support\SecureMedia::url($this->card_image_front));
    }

    protected function cardImageBackUrl(): Attribute
    {
        return Attribute::get(fn () => \App\Support\SecureMedia::url($this->card_image_back));
    }

    protected function remainingBalance(): Attribute
    {
        return Attribute::get(function () {
            if (! $this->max_annual_limit) {
                return null;
            }

            return max(0, $this->max_annual_limit - $this->used_amount);
        });
    }

    protected function usagePercentage(): Attribute
    {
        return Attribute::get(function () {
            if (! $this->max_annual_limit || $this->max_annual_limit <= 0) {
                return 0;
            }

            return min(100, round(($this->used_amount / $this->max_annual_limit) * 100, 1));
        });
    }

    // ─── Helpers ────────────────────────────────────────

    /**
     * Calculate patient's share for a given amount.
     */
    public function calculatePatientShare(float $totalAmount): array
    {
        $plan = $this->plan;
        if (! $plan) {
            return ['covered' => 0, 'patient_share' => $totalAmount, 'copay' => 0];
        }

        $coveredPercent = $plan->coverage_percentage / 100;
        $covered = $totalAmount * $coveredPercent;
        $copay = $plan->copay_amount;

        // Check remaining balance
        if ($this->max_annual_limit) {
            $remaining = max(0, $this->max_annual_limit - $this->used_amount);
            $covered = min($covered, $remaining);
        }

        $patientShare = $totalAmount - $covered + $copay;

        return [
            'covered' => round($covered, 2),
            'patient_share' => round(max(0, $patientShare), 2),
            'copay' => $copay,
            'coverage_percentage' => $plan->coverage_percentage,
        ];
    }

    // ─── Scopes ─────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('expiry_date')->orWhere('expiry_date', '>=', now());
            });
    }

    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }
}
