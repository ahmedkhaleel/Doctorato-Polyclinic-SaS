<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBranch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** A home-exercise-program prescription for a patient. Branch-aware. */
class PhysioExercisePrescription extends Model
{
    use BelongsToBranch;

    protected $fillable = [
        'patient_id', 'doctor_id', 'treatment_plan_id', 'exercise_id', 'sets', 'reps', 'hold_sec',
        'frequency', 'resistance', 'status', 'prescribed_at', 'notes',
    ];

    protected $casts = ['prescribed_at' => 'date'];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function exercise(): BelongsTo
    {
        return $this->belongsTo(Exercise::class);
    }
}
