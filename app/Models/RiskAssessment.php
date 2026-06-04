<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * A suicide/violence/self-harm risk assessment. SENSITIVE + shared at patient
 * level. Score/level are computed by RiskAssessmentService.
 */
class RiskAssessment extends Model
{
    use HasFactory;

    const LEVELS = ['none', 'low', 'moderate', 'high'];

    protected $fillable = [
        'patient_id', 'doctor_id', 'type', 'tool', 'answers',
        'risk_level', 'safety_plan', 'is_active', 'neuropsych_encounter_id', 'assessed_at',
    ];

    protected $casts = [
        'answers' => 'array',
        'is_active' => 'boolean',
        'assessed_at' => 'datetime',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function doctor()
    {
        return $this->belongsTo(Doctor::class);
    }
}
