<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBranch;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\LogsActivity;

class PediatricWellChildVisit extends Model
{
    use BelongsToBranch;
    use HasFactory, LogsActivity, SoftDeletes;

    protected $fillable = [
        'patient_id', 'doctor_id', 'visit_id',
        'schedule_key', 'scheduled_age_months', 'visit_date',
        'status', // scheduled, completed, missed, skipped
        'weight_kg', 'height_cm', 'head_circumference_cm',
        'physical_exam_notes', 'development_notes',
        'feeding_notes', 'safety_guidance',
        'vaccinations_given', 'screening_tests_done',
        'referrals', 'next_visit_date', 'notes',
    ];

    protected $casts = [
        'visit_date' => 'date',
        'next_visit_date' => 'date',
        'scheduled_age_months' => 'decimal:1',
        'weight_kg' => 'decimal:3',
        'height_cm' => 'decimal:1',
        'head_circumference_cm' => 'decimal:1',
        'vaccinations_given' => 'array',
        'screening_tests_done' => 'array',
        'referrals' => 'array',
    ];

    /**
     * WHO-recommended well-child visit schedule.
     */
    public const SCHEDULE = [
        ['key' => '1w',   'age_months' => 0.25, 'label_en' => '1 Week',       'label_ar' => 'أسبوع واحد'],
        ['key' => '1m',   'age_months' => 1,    'label_en' => '1 Month',      'label_ar' => 'شهر واحد'],
        ['key' => '2m',   'age_months' => 2,    'label_en' => '2 Months',     'label_ar' => 'شهران'],
        ['key' => '4m',   'age_months' => 4,    'label_en' => '4 Months',     'label_ar' => '4 أشهر'],
        ['key' => '6m',   'age_months' => 6,    'label_en' => '6 Months',     'label_ar' => '6 أشهر'],
        ['key' => '9m',   'age_months' => 9,    'label_en' => '9 Months',     'label_ar' => '9 أشهر'],
        ['key' => '12m',  'age_months' => 12,   'label_en' => '12 Months',    'label_ar' => '12 شهر'],
        ['key' => '15m',  'age_months' => 15,   'label_en' => '15 Months',    'label_ar' => '15 شهر'],
        ['key' => '18m',  'age_months' => 18,   'label_en' => '18 Months',    'label_ar' => '18 شهر'],
        ['key' => '24m',  'age_months' => 24,   'label_en' => '2 Years',      'label_ar' => 'سنتان'],
        ['key' => '30m',  'age_months' => 30,   'label_en' => '2.5 Years',    'label_ar' => 'سنتان ونصف'],
        ['key' => '36m',  'age_months' => 36,   'label_en' => '3 Years',      'label_ar' => '3 سنوات'],
        ['key' => '48m',  'age_months' => 48,   'label_en' => '4 Years',      'label_ar' => '4 سنوات'],
        ['key' => '60m',  'age_months' => 60,   'label_en' => '5 Years',      'label_ar' => '5 سنوات'],
        ['key' => '72m',  'age_months' => 72,   'label_en' => '6 Years',      'label_ar' => '6 سنوات'],
        ['key' => '96m',  'age_months' => 96,   'label_en' => '8 Years',      'label_ar' => '8 سنوات'],
        ['key' => '120m', 'age_months' => 120,  'label_en' => '10 Years',     'label_ar' => '10 سنوات'],
        ['key' => '144m', 'age_months' => 144,  'label_en' => '12 Years',     'label_ar' => '12 سنة'],
        ['key' => '180m', 'age_months' => 180,  'label_en' => '15 Years',     'label_ar' => '15 سنة'],
        ['key' => '216m', 'age_months' => 216,  'label_en' => '18 Years',     'label_ar' => '18 سنة'],
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function doctor()
    {
        return $this->belongsTo(Doctor::class);
    }

    public function visit()
    {
        return $this->belongsTo(Visit::class);
    }
}
