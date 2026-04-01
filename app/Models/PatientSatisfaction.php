<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class PatientSatisfaction extends Model
{
    use HasFactory;

    const IMPROVEMENT_AREAS = [
        'waiting_time' => ['ar' => 'وقت الانتظار', 'en' => 'Waiting Time'],
        'staff_friendliness' => ['ar' => 'تعامل الموظفين', 'en' => 'Staff Friendliness'],
        'doctor_communication' => ['ar' => 'تواصل الطبيب', 'en' => 'Doctor Communication'],
        'cleanliness' => ['ar' => 'النظافة', 'en' => 'Cleanliness'],
        'facilities' => ['ar' => 'المرافق', 'en' => 'Facilities'],
        'parking' => ['ar' => 'المواقف', 'en' => 'Parking'],
        'appointment_availability' => ['ar' => 'توفر المواعيد', 'en' => 'Appointment Availability'],
        'treatment_explanation' => ['ar' => 'شرح العلاج', 'en' => 'Treatment Explanation'],
        'pricing_transparency' => ['ar' => 'وضوح الأسعار', 'en' => 'Pricing Transparency'],
        'followup_care' => ['ar' => 'رعاية المتابعة', 'en' => 'Follow-up Care'],
    ];

    protected $fillable = [
        'patient_id', 'visit_id', 'doctor_id', 'booking_id',
        'overall_rating', 'doctor_rating', 'staff_rating',
        'cleanliness_rating', 'waiting_time_rating', 'communication_rating',
        'comments', 'would_recommend', 'improvement_areas', 'nps_score',
        'source', 'token', 'is_anonymous',
    ];

    protected $casts = [
        'overall_rating' => 'integer',
        'doctor_rating' => 'integer',
        'staff_rating' => 'integer',
        'cleanliness_rating' => 'integer',
        'waiting_time_rating' => 'integer',
        'communication_rating' => 'integer',
        'nps_score' => 'integer',
        'would_recommend' => 'boolean',
        'improvement_areas' => 'array',
        'is_anonymous' => 'boolean',
    ];

    protected $appends = ['nps_category', 'average_rating'];

    // ─── Relationships ──────────────────────────────────

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function visit()
    {
        return $this->belongsTo(Visit::class);
    }

    public function doctor()
    {
        return $this->belongsTo(Doctor::class);
    }

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    // ─── Accessors ──────────────────────────────────────

    public function getNpsCategoryAttribute(): ?array
    {
        if ($this->nps_score === null) return null;

        if ($this->nps_score >= 9) return ['key' => 'promoter', 'en' => 'Promoter', 'ar' => 'مروّج', 'color' => 'green'];
        if ($this->nps_score >= 7) return ['key' => 'passive', 'en' => 'Passive', 'ar' => 'محايد', 'color' => 'yellow'];
        return ['key' => 'detractor', 'en' => 'Detractor', 'ar' => 'منتقد', 'color' => 'red'];
    }

    public function getAverageRatingAttribute(): ?float
    {
        $ratings = array_filter([
            $this->overall_rating,
            $this->doctor_rating,
            $this->staff_rating,
            $this->cleanliness_rating,
            $this->waiting_time_rating,
            $this->communication_rating,
        ]);

        return $ratings ? round(array_sum($ratings) / count($ratings), 1) : null;
    }

    // ─── Token Generation ───────────────────────────────

    protected static function booted(): void
    {
        static::creating(function (self $satisfaction) {
            if (!$satisfaction->token) {
                $satisfaction->token = Str::random(40);
            }
        });
    }

    // ─── Scopes ─────────────────────────────────────────

    public function scopeHighRating($query)
    {
        return $query->where('overall_rating', '>=', 4);
    }

    public function scopeLowRating($query)
    {
        return $query->where('overall_rating', '<=', 2);
    }

    public function scopeForDoctor($query, int $doctorId)
    {
        return $query->where('doctor_id', $doctorId);
    }
}
