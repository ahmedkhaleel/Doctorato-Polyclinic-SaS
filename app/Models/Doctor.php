<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Doctor extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    /** Commission paid via Doctor Payouts (contractor). */
    const PAY_PAYOUT = 'payout';

    /** Commission consolidated into the monthly salary slip (employee). */
    const PAY_SALARY = 'salary';

    /** True when this doctor's commission flows through payroll, not payouts. */
    public function isSalaryPaid(): bool
    {
        return $this->payment_mode === self::PAY_SALARY;
    }

    protected $fillable = [
        'name_ar', 'name_en', 'photo',
        'specialization_ar', 'specialization_en',
        'bio_ar', 'bio_en',
        'qualifications_ar', 'qualifications_en',
        'display_order', 'status', 'doctor_type', 'module',
        // Clinic management fields
        'user_id', 'phone', 'email',
        'default_commission_percentage', 'payment_mode', 'consultation_fee',
        'dermatology_fee', 'cosmetic_fee',
        'dental_consultation_fee', 'dental_service_fee',
        'dermatology_commission', 'cosmetic_commission', 'followup_commission',
        'dental_consultation_commission', 'dental_service_commission',
        'pediatric_consultation_commission', 'pediatric_followup_commission',
        'pediatric_consultation_fee',
        'obgyn_consultation_fee', 'obgyn_consultation_commission',
        'psychiatry_consultation_fee', 'psychiatry_consultation_commission',
        'neurology_consultation_fee', 'neurology_consultation_commission',
        'physiotherapy_consultation_fee', 'physiotherapy_consultation_commission',
        'physiotherapy_session_fee', 'physiotherapy_session_commission',
        'treats_children',
        'clinic_notes',
        // Telemedicine
        'online_consultation_enabled',
        'online_consultation_fee',
        'online_session_duration_minutes',
        'online_consultation_bio_ar',
        'online_consultation_bio_en',
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
        'obgyn_consultation_fee' => 'decimal:2',
        'obgyn_consultation_commission' => 'decimal:2',
        'physiotherapy_consultation_fee' => 'decimal:2',
        'physiotherapy_consultation_commission' => 'decimal:2',
        'physiotherapy_session_fee' => 'decimal:2',
        'physiotherapy_session_commission' => 'decimal:2',
        'online_consultation_enabled' => 'boolean',
        'online_consultation_fee' => 'decimal:2',
    ];

    protected $appends = ['photo_url'];

    protected function photoUrl(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->photo
                ? '/storage/'.$this->photo
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

    public function scopeObgyn($query)
    {
        return $query->where('module', 'obgyn');
    }

    public function scopeOnlineEnabled($query)
    {
        return $query->where('online_consultation_enabled', true)
            ->where('status', 'active');
    }

    protected static function booted(): void
    {
        // Multi-branch: every new doctor is assigned to the default branch so
        // they always have at least one branch (mirrors the B3 backfill).
        static::created(function (self $doctor) {
            try {
                if (\Illuminate\Support\Facades\Schema::hasTable('branch_doctor') && ! $doctor->branches()->exists()) {
                    $doctor->branches()->attach((int) config('branches.default_id', 1));
                }
            } catch (\Throwable $e) {
                // table not ready (early migrations) — ignore
            }
        });
    }

    // ─── Branch (multi-branch) ──────────────────────────

    public function branches()
    {
        return $this->belongsToMany(Branch::class, 'branch_doctor')->withTimestamps();
    }

    /** Branch ids this doctor practises at (for slot/schedule resolution). */
    public function branchIds(): array
    {
        return $this->branches()->pluck('branches.id')->map(fn ($id) => (int) $id)->all();
    }

    public function practisesAtBranch(int $branchId): bool
    {
        return $this->branches()->where('branches.id', $branchId)->exists();
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

    // ─── Telemedicine Relationships ─────────────────────

    public function onlineConsultations()
    {
        return $this->hasMany(OnlineConsultation::class);
    }
}
