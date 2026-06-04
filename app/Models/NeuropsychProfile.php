<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Baseline psychiatric/neurological profile — one row per patient, SHARED by
 * the psychiatry and neurology modules (patient-level clinical state, not
 * branch-scoped). Mirrors ObgynProfile.
 */
class NeuropsychProfile extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    protected static function booted(): void
    {
        $forget = function (NeuropsychProfile $record) {
            if (! empty($record->patient_id)) {
                Patient::forgetSpecialtyCache((int) $record->patient_id);
            }
        };
        static::saved($forget);
        static::deleted($forget);
    }

    protected $fillable = [
        'patient_id', 'doctor_id', 'chief_complaint', 'hpi',
        'past_psych_history', 'past_neuro_history', 'family_history',
        'social_history', 'substance_history', 'developmental_history',
        'risk_factors', 'current_meds', 'notes',
    ];

    protected $casts = [
        'substance_history' => 'array',
        'risk_factors' => 'array',
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
