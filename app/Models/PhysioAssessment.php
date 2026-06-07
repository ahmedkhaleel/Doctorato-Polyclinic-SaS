<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBranch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** A physiotherapy assessment (ICF/ICD-aware). Branch-aware. */
class PhysioAssessment extends Model
{
    use BelongsToBranch;

    protected $fillable = [
        'patient_id', 'visit_id', 'doctor_id', 'assessment_date', 'subjective', 'objective',
        'icd10', 'icf', 'red_flags', 'posture', 'gait', 'balance', 'special_tests',
        'diagnosis', 'plan', 'completed_at',
    ];

    protected $casts = [
        'assessment_date' => 'date',
        'icf' => 'array', 'red_flags' => 'array', 'special_tests' => 'array',
        'completed_at' => 'datetime',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }

    public function romMeasurements(): HasMany
    {
        return $this->hasMany(PhysioRomMeasurement::class, 'assessment_id');
    }

    public function strengthTests(): HasMany
    {
        return $this->hasMany(PhysioStrengthTest::class, 'assessment_id');
    }

    public function painPoints(): HasMany
    {
        return $this->hasMany(PhysioPainPoint::class, 'assessment_id');
    }
}
