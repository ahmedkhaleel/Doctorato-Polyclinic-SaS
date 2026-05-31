<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Patient extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    /**
     * Highly sensitive medical fields (infectious diseases, etc.)
     * These require special permission to view and are encrypted at rest.
     */
    const SENSITIVE_MEDICAL_FIELDS = [
        'has_hepatitis', 'hepatitis_type', 'has_hiv',
        'has_bleeding_disorder', 'takes_blood_thinners', 'blood_thinner_name',
        'anesthesia_notes', 'dental_medical_notes', 'previous_dental_surgeries',
    ];

    /**
     * Mass-assignable fields.
     * SECURITY NOTE: user_id, file_number, is_active are intentionally excluded
     * to prevent account takeover and privilege escalation via mass assignment.
     * Use forceFill() or direct assignment for these fields.
     */
    protected $fillable = [
        'full_name', 'phone', 'phone2', 'email',
        'date_of_birth', 'gender', 'blood_type', 'marital_status',
        'nationality', 'address', 'occupation',
        'emergency_contact_name', 'emergency_contact_phone',
        'allergies', 'chronic_conditions', 'current_medications',
        'referral_source', 'referred_by', 'medical_notes', 'doctor_notes', 'photo',
        // Dental-specific medical fields
        'has_dental_anxiety', 'dental_anxiety_level', 'previous_dental_surgeries',
        'latex_allergy', 'anesthesia_complications', 'anesthesia_notes',
        'is_pregnant', 'is_breastfeeding',
        'has_bleeding_disorder', 'takes_blood_thinners', 'blood_thinner_name',
        'has_heart_condition', 'has_diabetes', 'diabetes_type',
        'has_hepatitis', 'hepatitis_type', 'has_hiv',
        'is_smoker', 'smoking_frequency',
        'jaw_problems', 'teeth_grinding',
        'last_dental_visit', 'dental_medical_notes',
        // Pediatric fields
        'guardian_name', 'guardian_relation', 'guardian_phone', 'guardian_phone2',
        'guardian_email', 'guardian_occupation',
        'birth_type', 'birth_place', 'gestational_age_weeks',
        'birth_weight_kg', 'birth_length_cm', 'birth_head_circumference_cm',
        'apgar_1min', 'apgar_5min', 'birth_complications', 'nicu_days',
        'newborn_screening', 'feeding_type', 'pregnancy_complications',
        // Notification preferences
        'preferred_language',
        'notify_email_bookings', 'notify_email_reminders', 'notify_email_marketing',
        'notify_sms_bookings', 'notify_sms_reminders', 'notify_sms_marketing',
        // Referral
        'referral_code',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'last_dental_visit' => 'date',
        'is_active' => 'boolean',
        'has_dental_anxiety' => 'boolean',
        'latex_allergy' => 'boolean',
        'anesthesia_complications' => 'boolean',
        'is_pregnant' => 'boolean',
        'is_breastfeeding' => 'boolean',
        'has_bleeding_disorder' => 'boolean',
        'takes_blood_thinners' => 'boolean',
        'has_heart_condition' => 'boolean',
        'has_diabetes' => 'boolean',
        'has_hepatitis' => 'boolean',
        'has_hiv' => 'boolean',
        'is_smoker' => 'boolean',
        'jaw_problems' => 'boolean',
        'teeth_grinding' => 'boolean',
        'notify_email_bookings' => 'boolean',
        'notify_email_reminders' => 'boolean',
        'notify_email_marketing' => 'boolean',
        'notify_sms_bookings' => 'boolean',
        'notify_sms_reminders' => 'boolean',
        'notify_sms_marketing' => 'boolean',
        // Pediatric casts
        'birth_complications' => 'array',
        'newborn_screening' => 'array',
        'gestational_age_weeks' => 'decimal:1',
        'birth_weight_kg' => 'decimal:3',
        'birth_length_cm' => 'decimal:1',
        'birth_head_circumference_cm' => 'decimal:1',
    ];

    /**
     * Hide highly sensitive fields from default serialization.
     * Controllers must explicitly load them using ->makeVisible().
     */
    protected $hidden = [
        'has_hiv', 'has_hepatitis', 'hepatitis_type',
        'dental_medical_notes', 'anesthesia_notes', 'previous_dental_surgeries',
    ];

    protected $appends = ['photo_url', 'age'];

    // ─── Relationships ──────────────────────────────────

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    public function visits()
    {
        return $this->hasMany(Visit::class);
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function prescriptions()
    {
        return $this->hasMany(Prescription::class);
    }

    public function photos()
    {
        return $this->hasMany(PatientPhoto::class);
    }

    public function packageBundleBookings()
    {
        return $this->hasMany(PackageBundleBooking::class);
    }

    public function onlineConsultations()
    {
        return $this->hasMany(OnlineConsultation::class);
    }

    // ─── Pediatric Relationships ─────────────────────────

    public function pediatricGrowthRecords()
    {
        return $this->hasMany(PediatricGrowthRecord::class);
    }

    public function pediatricVaccinations()
    {
        return $this->hasMany(PediatricVaccination::class);
    }

    public function pediatricMilestones()
    {
        return $this->hasMany(PediatricMilestone::class);
    }

    public function pediatricAllergies()
    {
        return $this->hasMany(PediatricAllergy::class);
    }

    public function pediatricFamilyHistory()
    {
        return $this->hasMany(PediatricFamilyHistory::class);
    }

    public function pediatricChronicConditions()
    {
        return $this->hasMany(PediatricChronicCondition::class);
    }

    public function pediatricNutritionRecords()
    {
        return $this->hasMany(PediatricNutritionRecord::class);
    }

    public function pediatricScreeningTests()
    {
        return $this->hasMany(PediatricScreeningTest::class);
    }

    // ─── OB/GYN Relationships ──────────────────────────
    public function obgynProfile()
    {
        return $this->hasOne(ObgynProfile::class);
    }

    public function pregnancies()
    {
        return $this->hasMany(Pregnancy::class)->latest('lmp');
    }

    public function papSmearScreenings()
    {
        return $this->hasMany(PapSmearScreening::class)->latest('test_date');
    }

    public function contraceptionRecords()
    {
        return $this->hasMany(ContraceptionRecord::class)->latest('start_date');
    }

    public function obgynLabTests()
    {
        return $this->hasMany(ObgynLabTest::class)->latest('result_date');
    }

    /** The active pregnancy, if any. */
    public function activePregnancy()
    {
        return $this->hasOne(Pregnancy::class)->where('status', Pregnancy::STATUS_ACTIVE)->orderByDesc('id');
    }

    // ─── Sensitive Data Access ─────────────────────────

    /**
     * Get the patient data with sensitive fields visible.
     * Only call this when user has 'patients.view_sensitive_medical' permission.
     * Automatically logs the access.
     */
    public function withSensitiveFieldsVisible(): self
    {
        $this->makeVisible(self::SENSITIVE_MEDICAL_FIELDS);

        // Log the sensitive data access
        if (\Illuminate\Support\Facades\Auth::check()) {
            MedicalDataAccessLog::logSensitiveView(
                $this->id,
                self::SENSITIVE_MEDICAL_FIELDS
            );
        }

        return $this;
    }

    /**
     * Get dental medical history data, respecting permission level.
     * If user lacks sensitive permission, redact HIV/hepatitis details.
     */
    public function getDentalMedicalHistory(bool $includeSensitive = false): array
    {
        $baseFields = [
            'has_dental_anxiety', 'dental_anxiety_level',
            'latex_allergy', 'anesthesia_complications',
            'is_pregnant', 'is_breastfeeding',
            'has_bleeding_disorder', 'takes_blood_thinners', 'blood_thinner_name',
            'has_heart_condition', 'has_diabetes', 'diabetes_type',
            'is_smoker', 'smoking_frequency',
            'jaw_problems', 'teeth_grinding',
            'last_dental_visit',
        ];

        $sensitiveFields = [
            'has_hepatitis', 'hepatitis_type', 'has_hiv',
            'anesthesia_notes', 'dental_medical_notes', 'previous_dental_surgeries',
        ];

        $data = $this->only($baseFields);

        if ($includeSensitive) {
            $data = array_merge($data, $this->only($sensitiveFields));
        } else {
            // Return redacted indicators (doctor sees "has risk" but not the detail)
            $data['has_hepatitis'] = $this->has_hepatitis;
            $data['has_hiv'] = $this->has_hiv;
            $data['hepatitis_type'] = $this->has_hepatitis ? '[RESTRICTED]' : null;
            $data['anesthesia_notes'] = $this->anesthesia_notes ? '[RESTRICTED — requires permission]' : null;
            $data['dental_medical_notes'] = $this->dental_medical_notes ? '[RESTRICTED — requires permission]' : null;
            $data['previous_dental_surgeries'] = $this->previous_dental_surgeries ? '[RESTRICTED — requires permission]' : null;
        }

        return $data;
    }

    // ─── Specialty Detection Helpers ─────────────────

    /**
     * Check if patient has any dermatology records.
     * Derma patients have visits with module = 'derma' or cosmetic/dermatology consultations.
     */
    public function hasDermaRecords(): bool
    {
        return $this->visits()->where('module', 'derma')->exists()
            || $this->bookings()->whereIn('booking_type', ['dermatology_consultation', 'cosmetic_consultation', 'service'])->where('module', 'derma')->exists();
    }

    /**
     * Check if patient has any dental records (visits, treatments, or charts).
     */
    public function hasDentalRecords(): bool
    {
        return $this->visits()->where('module', 'dental')->exists()
            || $this->dentalTreatments()->exists()
            || $this->dentalCharts()->exists();
    }

    /**
     * Check if patient is pediatric (has guardian, is under 18, or has pediatric records).
     */
    public function hasPediatricRecords(): bool
    {
        // Guardian info → definitely pediatric
        if (! empty($this->guardian_name)) {
            return true;
        }

        // Under 18 → pediatric
        if ($this->date_of_birth) {
            try {
                $age = \Carbon\Carbon::parse($this->date_of_birth)->age;
                if ($age < 18) {
                    return true;
                }
            } catch (\Throwable) { /* ignore */
            }
        }

        // Has pediatric visits or records
        return $this->visits()->where('module', 'pediatric')->exists()
            || $this->pediatricGrowthRecords()->exists()
            || $this->pediatricVaccinations()->exists();
    }

    /**
     * Return all active medical specialties for this patient (based on actual records).
     * Used to determine which tabs to show in unified patient file.
     *
     * Cached for 10 minutes; busted automatically by model event hooks on
     * Visit / DentalTreatment / PediatricGrowthRecord / PediatricVaccination.
     *
     * @return array<int, string> e.g. ['derma', 'dental', 'pediatric']
     */
    public function getActiveSpecialties(): array
    {
        return \Illuminate\Support\Facades\Cache::remember(
            "patient_{$this->id}_specialties",
            now()->addMinutes(10),
            fn () => array_values(array_filter([
                $this->hasDermaRecords() ? 'derma' : null,
                $this->hasDentalRecords() ? 'dental' : null,
                $this->hasPediatricRecords() ? 'pediatric' : null,
            ]))
        );
    }

    /**
     * Forget the cached active-specialties list for this patient.
     * Called automatically by related-model event hooks, but also callable
     * directly from services/admin tools that mutate records outside Eloquent.
     */
    public function clearSpecialtyCache(): void
    {
        \Illuminate\Support\Facades\Cache::forget("patient_{$this->id}_specialties");
    }

    /**
     * Forget the cached active-specialties list for a given patient ID
     * without needing to load the model.
     */
    public static function forgetSpecialtyCache(int $patientId): void
    {
        \Illuminate\Support\Facades\Cache::forget("patient_{$patientId}_specialties");
    }

    // ─── Dental Risk Helpers ─────────────────────────

    /**
     * Check if patient has any dental risk flags that require alerts.
     */
    public function hasDentalRiskFlags(): bool
    {
        return $this->latex_allergy
            || $this->anesthesia_complications
            || $this->has_bleeding_disorder
            || $this->takes_blood_thinners
            || $this->has_heart_condition
            || $this->has_hepatitis
            || $this->has_hiv
            || $this->is_pregnant
            || $this->is_breastfeeding
            || $this->has_dental_anxiety
            || ! empty($this->allergies);
    }

    /**
     * Get list of active dental risk flags for display.
     */
    public function getDentalRiskFlags(): array
    {
        $flags = [];

        if ($this->latex_allergy) {
            $flags[] = ['key' => 'latex_allergy', 'label_en' => 'Latex Allergy', 'label_ar' => 'حساسية اللاتكس', 'severity' => 'high'];
        }
        if ($this->anesthesia_complications) {
            $flags[] = ['key' => 'anesthesia', 'label_en' => 'Anesthesia Complications', 'label_ar' => 'مضاعفات التخدير', 'severity' => 'high'];
        }
        if ($this->has_bleeding_disorder) {
            $flags[] = ['key' => 'bleeding', 'label_en' => 'Bleeding Disorder', 'label_ar' => 'اضطراب نزيف', 'severity' => 'high'];
        }
        if ($this->takes_blood_thinners) {
            $flags[] = ['key' => 'blood_thinners', 'label_en' => 'Blood Thinners: '.($this->blood_thinner_name ?? ''), 'label_ar' => 'مميعات الدم: '.($this->blood_thinner_name ?? ''), 'severity' => 'high'];
        }
        if ($this->has_heart_condition) {
            $flags[] = ['key' => 'heart', 'label_en' => 'Heart Condition', 'label_ar' => 'مشاكل قلبية', 'severity' => 'high'];
        }
        if ($this->has_hepatitis) {
            $flags[] = ['key' => 'hepatitis', 'label_en' => 'Hepatitis '.($this->hepatitis_type ?? ''), 'label_ar' => 'التهاب كبد '.($this->hepatitis_type ?? ''), 'severity' => 'high'];
        }
        if ($this->has_hiv) {
            $flags[] = ['key' => 'hiv', 'label_en' => 'HIV Positive', 'label_ar' => 'فيروس نقص المناعة', 'severity' => 'high'];
        }
        if ($this->is_pregnant) {
            $flags[] = ['key' => 'pregnant', 'label_en' => 'Pregnant', 'label_ar' => 'حامل', 'severity' => 'medium'];
        }
        if ($this->is_breastfeeding) {
            $flags[] = ['key' => 'breastfeeding', 'label_en' => 'Breastfeeding', 'label_ar' => 'مرضعة', 'severity' => 'medium'];
        }
        if ($this->has_dental_anxiety) {
            $flags[] = ['key' => 'anxiety', 'label_en' => 'Dental Anxiety ('.($this->dental_anxiety_level ?? 'unknown').')', 'label_ar' => 'قلق من الأسنان ('.($this->dental_anxiety_level ?? 'غير محدد').')', 'severity' => 'low'];
        }
        if ($this->has_diabetes) {
            $flags[] = ['key' => 'diabetes', 'label_en' => 'Diabetes ('.($this->diabetes_type ?? '').')', 'label_ar' => 'سكري ('.($this->diabetes_type ?? '').')', 'severity' => 'medium'];
        }
        if (! empty($this->allergies)) {
            $flags[] = ['key' => 'allergies', 'label_en' => 'Allergies: '.$this->allergies, 'label_ar' => 'حساسية: '.$this->allergies, 'severity' => 'high'];
        }

        return $flags;
    }

    // ─── Dental Relationships ─────────────────────────

    public function dentalCharts()
    {
        return $this->hasMany(DentalChart::class);
    }

    public function dentalChartEntries()
    {
        return $this->hasMany(DentalChartEntry::class);
    }

    public function dentalTreatments()
    {
        return $this->hasMany(DentalTreatment::class);
    }

    public function dentalTreatmentPlans()
    {
        return $this->hasMany(DentalTreatmentPlan::class);
    }

    public function periodontalCharts()
    {
        return $this->hasMany(PeriodontalChart::class);
    }

    public function dentalXrays()
    {
        return $this->hasMany(DentalXray::class);
    }

    public function dentalLabOrders()
    {
        return $this->hasMany(DentalLabOrder::class);
    }

    // ─── Dermatology & Cosmetic Relationships ─────────

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

    public function cosmeticPackagePurchases()
    {
        return $this->hasMany(CosmeticPackagePurchase::class);
    }

    public function cosmeticConsents()
    {
        return $this->hasMany(CosmeticConsent::class);
    }

    public function vitals()
    {
        return $this->hasMany(PatientVital::class);
    }

    public function latestVitals()
    {
        return $this->hasOne(PatientVital::class)->latestOfMany('recorded_at');
    }

    public function documents()
    {
        return $this->hasMany(PatientDocument::class);
    }

    public function referrals()
    {
        return $this->hasMany(Referral::class);
    }

    public function insurances()
    {
        return $this->hasMany(PatientInsurance::class);
    }

    public function activeInsurance()
    {
        return $this->hasOne(PatientInsurance::class)
            ->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('expiry_date')->orWhere('expiry_date', '>=', now());
            })
            ->latestOfMany();
    }

    public function doctorNotes()
    {
        return $this->hasMany(DoctorPatientNote::class);
    }

    public function doctorFavorites()
    {
        return $this->hasMany(DoctorFavoritePatient::class);
    }

    // ─── Accessors ──────────────────────────────────────

    protected function photoUrl(): Attribute
    {
        return Attribute::get(fn () => $this->photo ? '/storage/'.$this->photo : null);
    }

    protected function age(): Attribute
    {
        return Attribute::get(fn () => $this->date_of_birth ? $this->date_of_birth->age : null);
    }

    // ─── Scopes ─────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeSearch($query, $term)
    {
        return $query->where(function ($q) use ($term) {
            $q->where('full_name', 'like', "%{$term}%")
                ->orWhere('phone', 'like', "%{$term}%")
                ->orWhere('file_number', 'like', "%{$term}%")
                ->orWhere('email', 'like', "%{$term}%");
        });
    }

    // ─── Auto-generate file number ──────────────────────

    public static function generateFileNumber(): string
    {
        $last = static::orderByDesc('file_number')->value('file_number');
        $number = $last ? (int) str_replace('P-', '', $last) + 1 : 1;

        $fileNumber = 'P-'.str_pad($number, 5, '0', STR_PAD_LEFT);

        // Safety: if it already exists (race condition), keep incrementing
        while (static::where('file_number', $fileNumber)->exists()) {
            $number++;
            $fileNumber = 'P-'.str_pad($number, 5, '0', STR_PAD_LEFT);
        }

        return $fileNumber;
    }

    /**
     * Patients I've referred (mine as the referrer).
     */
    public function referralsMade()
    {
        return $this->hasMany(PatientReferral::class, 'referrer_patient_id');
    }

    /**
     * The (single) row marking how I joined the clinic, if any.
     */
    public function referralReceived()
    {
        return $this->hasOne(PatientReferral::class, 'referred_patient_id');
    }

    /**
     * Generate a memorable referral code: NAME-XXXX (e.g. SARA-7K9X).
     * Easy to share verbally, hard to guess.
     */
    public static function generateReferralCode(string $name): string
    {
        $prefix = strtoupper(\Illuminate\Support\Str::slug(\Illuminate\Support\Str::words($name, 1, '')) ?: 'REF');
        $prefix = substr($prefix, 0, 8);

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $suffix = strtoupper(\Illuminate\Support\Str::random(4));
            $code = "{$prefix}-{$suffix}";
            if (! static::where('referral_code', $code)->exists()) {
                return $code;
            }
        }

        return $prefix.'-'.strtoupper(\Illuminate\Support\Str::random(6));
    }

    // ─── Model boot: guard against missing file_number ──
    protected static function booted(): void
    {
        static::creating(function (self $patient) {
            if (empty($patient->file_number)) {
                $patient->file_number = static::generateFileNumber();
            }
            if (empty($patient->referral_code)) {
                $patient->referral_code = static::generateReferralCode(
                    $patient->full_name ?: 'Patient'
                );
            }
            if (is_null($patient->is_active)) {
                $patient->is_active = true;
            }
        });
    }

    /**
     * Should this patient receive a notification of the given category
     * over the given channel?
     *
     * Defaults to TRUE if the column is null (legacy rows that pre-date
     * the preferences migration). Marketing SMS defaults to false.
     *
     * @param  'bookings'|'reminders'|'marketing'  $category
     * @param  'email'|'sms'|'whatsapp'  $channel
     */
    public function wantsNotification(string $category, string $channel): bool
    {
        $col = "notify_{$channel}_{$category}";
        if (! in_array($col, [
            'notify_email_bookings', 'notify_email_reminders', 'notify_email_marketing',
            'notify_sms_bookings', 'notify_sms_reminders', 'notify_sms_marketing',
            'notify_whatsapp_bookings', 'notify_whatsapp_reminders', 'notify_whatsapp_marketing',
        ], true)) {
            return false;
        }

        $value = $this->getAttribute($col);
        if (is_null($value)) {
            // Legacy default: marketing (sms+whatsapp) is opt-IN/off, everything else on.
            return ! in_array($col, ['notify_sms_marketing', 'notify_whatsapp_marketing'], true);
        }

        return (bool) $value;
    }
}
