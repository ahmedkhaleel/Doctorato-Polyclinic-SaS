<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Patient-to-patient referral. Distinct from the medical Referral model
 * (which is doctor-to-doctor). Tracks "Sara invited Lina who registered
 * + booked using SARA-7K9X".
 */
class PatientReferral extends Model
{
    protected $table = 'patient_referrals';

    protected $fillable = [
        'referrer_patient_id', 'referred_patient_id',
        'code', 'discount_amount', 'discount_currency',
        'first_booking_id', 'redeemed_at',
    ];

    protected $casts = [
        'discount_amount' => 'decimal:2',
        'redeemed_at'     => 'datetime',
    ];

    public function referrer(): BelongsTo
    {
        return $this->belongsTo(Patient::class, 'referrer_patient_id');
    }

    public function referred(): BelongsTo
    {
        return $this->belongsTo(Patient::class, 'referred_patient_id');
    }

    public function firstBooking(): BelongsTo
    {
        return $this->belongsTo(Booking::class, 'first_booking_id');
    }
}
