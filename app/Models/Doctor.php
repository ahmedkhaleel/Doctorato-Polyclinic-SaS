<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\LogsActivity;

class Doctor extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'name_ar', 'name_en', 'photo',
        'specialization_ar', 'specialization_en',
        'bio_ar', 'bio_en',
        'qualifications_ar', 'qualifications_en',
        'display_order', 'status', 'doctor_type', 'module',
        // Clinic management fields
        'user_id', 'phone', 'email',
        'default_commission_percentage', 'consultation_fee',
        'dermatology_fee', 'cosmetic_fee',
        'dental_consultation_fee', 'dental_service_fee',
        'dermatology_commission', 'cosmetic_commission', 'followup_commission',
        'dental_consultation_commission', 'dental_service_commission',
        'pediatric_consultation_commission', 'pediatric_followup_commission',
        'pediatric_consultation_fee',
        'clinic_notes',
    ];

    protected $casts = [
        'default_commission_percentage' => 'decimal:2',
        'consultation_fee' => 'decimal:2',
        'dermatology_fee' => 'decimal:2',
        'cosmetic_fee' => 'decimal:2',
        'dermatology_commission' => 'decimal:2',
        'cosmetic_commission' => 'decimal:2',
        'followup_commission' => 'decimal:2',
        'dental_consultation_fee' => 'decimal:2',
        'dental_service_fee' => 'decimal:2',
        'dental_consultation_commission' => 'decimal:2',
        'dental_service_commission' => 'decimal:2',
        'pediatric_consultation_commission' => 'decimal:2',
        'pediatric_followup_commission' => 'decimal:2',
        'pediatric_consultation_fee' => 'decimal:2',
    ];

    protected $appends = ['photo_url'];

    protected function photoUrl(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->photo
                ? '/storage/' . $this->photo
                : null,
        );
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeForModule($query, string $module)
    {
        return $query->where('module', $module);
    }

    public function scopeDental($query)
    {
        return $query->where('module', 'dental');
    }

    public function scopeDerma($query)
    {
        return $query->where('module', 'derma');
    }

    public function scopePediatric($query)
    {
        return $query->where('module', 'pediatric');
    }

    // ─── Clinic Relationships ───────────────────────────

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function schedules()
    {
        return $this->hasMany(DoctorSchedule::class)->orderBy('day_of_week');
    }

    public function vacations()
    {
        return $this->hasMany(DoctorVacation::class);
    }

    public function serviceRates()
    {
        return $this->hasMany(DoctorServiceRate::class);
    }

    public function visits()
    {
        return $this->hasMany(Visit::class);
    }

    public function prescriptions()
    {
        return $this->hasMany(Prescription::class);
    }

    public function bookingAppointments()
    {
        return $this->hasMany(BookingAppointment::class);
    }

    public function bookingServices()
    {
        return $this->hasMany(BookingService::class);
    }

    public function payouts()
    {
        return $this->hasMany(DoctorPayout::class);
    }

    public function packageBundleBookingServices()
    {
        return $this->hasMany(PackageBundleBookingService::class);
    }

    public function patientNotes()
    {
        return $this->hasMany(DoctorPatientNote::class);
    }

    public function favoritePatients()
    {
        return $this->hasMany(DoctorFavoritePatient::class);
    }

    // ─── Pediatric Relationships ────────────────────────

    public function pediatricVisits()
    {
        return $this->hasMany(PediatricVisit::class);
    }

    public function pediatricPrescriptions()
    {
        return $this->hasMany(PediatricPrescription::class);
    }
}
