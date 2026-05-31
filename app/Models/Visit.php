<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBranch;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Visit extends Model
{
    use BelongsToBranch, HasFactory, LogsActivity, SoftDeletes;

    protected static function boot()
    {
        parent::boot();

        static::creating(function (Visit $visit) {
            $isOnline = ($visit->consultation_type === 'online');
            if (empty($visit->booking_id) && empty($visit->package_bundle_booking_id) && ! $isOnline) {
                throw new \RuntimeException('A visit must be linked to a booking or a package bundle booking.');
            }
        });

        // Bust the patient's cached active-specialties list whenever
        // a visit is created/updated/deleted (module may have changed).
        $forget = function (Visit $visit) {
            if (! empty($visit->patient_id)) {
                Patient::forgetSpecialtyCache((int) $visit->patient_id);
            }
        };
        static::saved($forget);
        static::deleted($forget);
    }

    /**
     * Mass-assignable fields.
     * SECURITY NOTE: commission_amount and commission_rate are excluded from $fillable
     * to prevent manipulation. They should only be set by the commission calculation service.
     */
    protected $fillable = [
        'patient_id', 'doctor_id', 'receptionist_id', 'booking_id', 'booking_appointment_id',
        'package_bundle_booking_id', 'package_bundle_booking_service_id', 'package_bundle_booking_appointment_id',
        'visit_type', 'module', 'consultation_type', 'service_id', 'session_number', 'status',
        'diagnosis', 'doctor_notes', 'started_at', 'completed_at', 'visit_date', 'scheduled_time',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'visit_date' => 'date',
        'commission_amount' => 'decimal:2',
        'commission_rate' => 'decimal:2',
    ];

    // ─── Relationships ──────────────────────────────────

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function doctor()
    {
        return $this->belongsTo(Doctor::class);
    }

    public function receptionist()
    {
        return $this->belongsTo(User::class, 'receptionist_id');
    }

    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    public function photos()
    {
        return $this->hasMany(VisitPhoto::class);
    }

    public function prescriptions()
    {
        return $this->hasMany(Prescription::class);
    }

    public function invoice()
    {
        return $this->hasOne(Invoice::class);
    }

    public function supplyTransactions()
    {
        return $this->hasMany(SupplyTransaction::class);
    }

    public function dentalTreatments()
    {
        return $this->hasMany(DentalTreatment::class);
    }

    public function dentalXrays()
    {
        return $this->hasMany(DentalXray::class);
    }

    // ─── Dermatology & Cosmetic records attached to this visit ─

    public function skinConditions()
    {
        return $this->hasMany(SkinCondition::class);
    }

    public function dermaSessions()
    {
        return $this->hasMany(DermaSession::class);
    }

    public function cosmeticSessions()
    {
        return $this->hasMany(CosmeticSession::class);
    }

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    public function bookingAppointment()
    {
        return $this->belongsTo(BookingAppointment::class);
    }

    public function packageBundleBooking()
    {
        return $this->belongsTo(PackageBundleBooking::class);
    }

    public function packageBundleBookingService()
    {
        return $this->belongsTo(PackageBundleBookingService::class);
    }

    public function packageBundleBookingAppointment()
    {
        return $this->belongsTo(PackageBundleBookingAppointment::class);
    }

    public function payouts()
    {
        return $this->belongsToMany(DoctorPayout::class, 'doctor_payout_visits')
            ->withPivot('visit_amount', 'commission_rate', 'commission_amount')
            ->withTimestamps();
    }

    /**
     * Check if this visit is part of any active (non-cancelled) payout.
     */
    public function hasActivePayout(): bool
    {
        return $this->payouts()
            ->whereIn('doctor_payouts.status', ['draft', 'confirmed', 'paid'])
            ->exists();
    }

    // ─── Scopes ─────────────────────────────────────────

    public function scopeToday($query)
    {
        return $query->whereDate('visit_date', today());
    }

    public function scopeForDoctor($query, $doctorId)
    {
        return $query->where('doctor_id', $doctorId);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope: completed visits with commission that are NOT in any active payout.
     */
    public function scopeUnpaidCommission($query)
    {
        return $query->where('status', 'completed')
            ->where('commission_amount', '>', 0)
            ->whereDoesntHave('payouts', function ($q) {
                $q->whereIn('doctor_payouts.status', ['draft', 'confirmed', 'paid']);
            });
    }

    public function scopePediatric($query)
    {
        return $query->where('module', 'pediatric');
    }

    public function scopeDental($query)
    {
        return $query->where('module', 'dental');
    }

    public function scopeDerma($query)
    {
        return $query->where('module', 'derma');
    }

    public function scopeObgyn($query)
    {
        return $query->where('module', 'obgyn');
    }

    // ─── Pediatric Relationships ────────────────────────

    public function pediatricVisit()
    {
        return $this->hasOne(PediatricVisit::class);
    }

    public function pediatricPrescriptions()
    {
        return $this->hasMany(PediatricPrescription::class);
    }
}
