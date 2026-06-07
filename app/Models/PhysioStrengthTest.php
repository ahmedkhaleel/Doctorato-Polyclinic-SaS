<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBranch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** A manual-muscle-test (Oxford 0–5) reading. Branch-aware. */
class PhysioStrengthTest extends Model
{
    use BelongsToBranch;

    protected $fillable = ['patient_id', 'assessment_id', 'doctor_id', 'muscle_group', 'side', 'grade', 'recorded_at'];

    protected $casts = ['recorded_at' => 'date'];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }
}
