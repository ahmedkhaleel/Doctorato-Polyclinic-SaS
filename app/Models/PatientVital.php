<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;

class PatientVital extends Model
{
    use HasFactory;

    protected $fillable = [
        'patient_id', 'visit_id', 'recorded_by',
        'blood_pressure_systolic', 'blood_pressure_diastolic',
        'heart_rate', 'temperature', 'respiratory_rate', 'oxygen_saturation',
        'weight', 'height', 'bmi',
        'blood_sugar', 'blood_sugar_type',
        'pain_level', 'pain_location',
        'notes', 'source', 'recorded_at',
    ];

    protected $casts = [
        'blood_pressure_systolic' => 'decimal:1',
        'blood_pressure_diastolic' => 'decimal:1',
        'heart_rate' => 'decimal:1',
        'temperature' => 'decimal:1',
        'respiratory_rate' => 'decimal:1',
        'oxygen_saturation' => 'decimal:2',
        'weight' => 'decimal:1',
        'height' => 'decimal:1',
        'bmi' => 'decimal:1',
        'blood_sugar' => 'decimal:1',
        'pain_level' => 'integer',
        'recorded_at' => 'datetime',
    ];

    protected $appends = ['blood_pressure', 'bp_status', 'bmi_category'];

    // ─── Relationships ──────────────────────────────────

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function visit()
    {
        return $this->belongsTo(Visit::class);
    }

    public function recorder()
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    // ─── Accessors ──────────────────────────────────────

    protected function bloodPressure(): Attribute
    {
        return Attribute::get(function () {
            if ($this->blood_pressure_systolic && $this->blood_pressure_diastolic) {
                return (int) $this->blood_pressure_systolic . '/' . (int) $this->blood_pressure_diastolic;
            }
            return null;
        });
    }

    /**
     * Blood pressure classification (AHA guidelines).
     */
    protected function bpStatus(): Attribute
    {
        return Attribute::get(function () {
            $sys = $this->blood_pressure_systolic;
            $dia = $this->blood_pressure_diastolic;
            if (!$sys || !$dia) return null;

            if ($sys < 120 && $dia < 80) return ['key' => 'normal', 'en' => 'Normal', 'ar' => 'طبيعي', 'color' => 'green'];
            if ($sys < 130 && $dia < 80) return ['key' => 'elevated', 'en' => 'Elevated', 'ar' => 'مرتفع قليلاً', 'color' => 'yellow'];
            if ($sys < 140 || $dia < 90) return ['key' => 'stage1', 'en' => 'High BP Stage 1', 'ar' => 'ضغط مرتفع مرحلة 1', 'color' => 'orange'];
            if ($sys >= 140 || $dia >= 90) return ['key' => 'stage2', 'en' => 'High BP Stage 2', 'ar' => 'ضغط مرتفع مرحلة 2', 'color' => 'red'];
            if ($sys > 180 || $dia > 120) return ['key' => 'crisis', 'en' => 'Hypertensive Crisis', 'ar' => 'أزمة ضغط', 'color' => 'red'];

            return null;
        });
    }

    /**
     * BMI classification (WHO guidelines).
     */
    protected function bmiCategory(): Attribute
    {
        return Attribute::get(function () {
            $bmi = $this->bmi;
            if (!$bmi) return null;

            if ($bmi < 18.5) return ['key' => 'underweight', 'en' => 'Underweight', 'ar' => 'نقص وزن', 'color' => 'blue'];
            if ($bmi < 25)   return ['key' => 'normal', 'en' => 'Normal', 'ar' => 'طبيعي', 'color' => 'green'];
            if ($bmi < 30)   return ['key' => 'overweight', 'en' => 'Overweight', 'ar' => 'زيادة وزن', 'color' => 'yellow'];
            if ($bmi < 35)   return ['key' => 'obese1', 'en' => 'Obese Class I', 'ar' => 'سمنة درجة 1', 'color' => 'orange'];
            if ($bmi < 40)   return ['key' => 'obese2', 'en' => 'Obese Class II', 'ar' => 'سمنة درجة 2', 'color' => 'red'];
            return ['key' => 'obese3', 'en' => 'Obese Class III', 'ar' => 'سمنة مفرطة', 'color' => 'red'];
        });
    }

    // ─── Auto-Calculate BMI ─────────────────────────────

    protected static function booted(): void
    {
        static::saving(function (self $vital) {
            if ($vital->weight && $vital->height && $vital->height > 0) {
                $heightM = $vital->height / 100;
                $vital->bmi = round($vital->weight / ($heightM * $heightM), 1);
            }
        });
    }

    // ─── Alert Helpers ──────────────────────────────────

    /**
     * Returns critical alerts based on vitals readings.
     * Used to warn doctors before treatment.
     */
    public function getAlerts(): array
    {
        $alerts = [];

        // Blood pressure alerts
        if ($this->blood_pressure_systolic >= 180 || $this->blood_pressure_diastolic >= 120) {
            $alerts[] = ['type' => 'bp_crisis', 'severity' => 'critical', 'en' => 'Hypertensive Crisis — DO NOT proceed with treatment', 'ar' => 'أزمة ضغط دم — لا تبدأ العلاج'];
        } elseif ($this->blood_pressure_systolic >= 160 || $this->blood_pressure_diastolic >= 100) {
            $alerts[] = ['type' => 'bp_high', 'severity' => 'high', 'en' => 'Very High Blood Pressure — Proceed with caution', 'ar' => 'ضغط دم مرتفع جداً — تابع بحذر'];
        }

        // Heart rate alerts
        if ($this->heart_rate && ($this->heart_rate < 50 || $this->heart_rate > 120)) {
            $alerts[] = ['type' => 'hr_abnormal', 'severity' => 'high', 'en' => "Abnormal Heart Rate ({$this->heart_rate} bpm)", 'ar' => "نبض غير طبيعي ({$this->heart_rate} ن/د)"];
        }

        // Oxygen saturation
        if ($this->oxygen_saturation && $this->oxygen_saturation < 92) {
            $alerts[] = ['type' => 'spo2_low', 'severity' => 'critical', 'en' => "Low Oxygen Saturation ({$this->oxygen_saturation}%)", 'ar' => "تشبع أكسجين منخفض ({$this->oxygen_saturation}%)"];
        }

        // Temperature
        if ($this->temperature && $this->temperature >= 38.5) {
            $alerts[] = ['type' => 'fever', 'severity' => 'high', 'en' => "High Fever ({$this->temperature}°C) — Postpone elective treatment", 'ar' => "حرارة مرتفعة ({$this->temperature}°م) — أجّل العلاج غير الطارئ"];
        }

        // Blood sugar (critical for dental procedures)
        if ($this->blood_sugar) {
            if ($this->blood_sugar < 70) {
                $alerts[] = ['type' => 'sugar_low', 'severity' => 'critical', 'en' => "Hypoglycemia ({$this->blood_sugar} mg/dL)", 'ar' => "انخفاض سكر الدم ({$this->blood_sugar} مغ/دل)"];
            } elseif ($this->blood_sugar > 300) {
                $alerts[] = ['type' => 'sugar_high', 'severity' => 'critical', 'en' => "Severe Hyperglycemia ({$this->blood_sugar} mg/dL)", 'ar' => "ارتفاع شديد في السكر ({$this->blood_sugar} مغ/دل)"];
            } elseif ($this->blood_sugar > 200) {
                $alerts[] = ['type' => 'sugar_elevated', 'severity' => 'high', 'en' => "High Blood Sugar ({$this->blood_sugar} mg/dL)", 'ar' => "سكر مرتفع ({$this->blood_sugar} مغ/دل)"];
            }
        }

        // Pain level
        if ($this->pain_level && $this->pain_level >= 8) {
            $alerts[] = ['type' => 'severe_pain', 'severity' => 'high', 'en' => "Severe Pain Level ({$this->pain_level}/10)", 'ar' => "ألم شديد ({$this->pain_level}/10)"];
        }

        return $alerts;
    }

    // ─── Scopes ─────────────────────────────────────────

    public function scopeForPatient($query, int $patientId)
    {
        return $query->where('patient_id', $patientId);
    }

    public function scopeLatest($query)
    {
        return $query->orderByDesc('recorded_at');
    }

    public function scopeToday($query)
    {
        return $query->whereDate('recorded_at', today());
    }
}
