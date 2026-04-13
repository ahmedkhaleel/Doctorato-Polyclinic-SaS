<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\LogsActivity;

class PediatricGrowthRecord extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'patient_id', 'visit_id', 'doctor_id', 'measurement_date', 'age_months',
        'weight_kg', 'height_cm', 'head_circumference_cm', 'bmi',
        'weight_percentile', 'height_percentile', 'head_percentile', 'bmi_percentile',
        'weight_zscore', 'height_zscore', 'head_zscore', 'bmi_zscore',
        'notes',
    ];

    protected $casts = [
        'measurement_date' => 'date',
        'age_months' => 'decimal:2',
        'weight_kg' => 'decimal:3',
        'height_cm' => 'decimal:1',
        'head_circumference_cm' => 'decimal:1',
        'bmi' => 'decimal:2',
        'weight_percentile' => 'decimal:2',
        'height_percentile' => 'decimal:2',
        'head_percentile' => 'decimal:2',
        'bmi_percentile' => 'decimal:2',
    ];

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

    /**
     * Calculate BMI from weight and height.
     */
    public static function calculateBmi(?float $weightKg, ?float $heightCm): ?float
    {
        if (!$weightKg || !$heightCm || $heightCm <= 0) return null;
        $heightM = $heightCm / 100;
        return round($weightKg / ($heightM * $heightM), 2);
    }

    /**
     * Get growth status based on percentile.
     */
    public function getWeightStatus(): string
    {
        $p = $this->weight_percentile;
        if ($p === null) return 'unknown';
        if ($p < 3) return 'underweight';
        if ($p < 15) return 'watch';
        if ($p <= 85) return 'normal';
        if ($p <= 97) return 'overweight';
        return 'obese';
    }
}
