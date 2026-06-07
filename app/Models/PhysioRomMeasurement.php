<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBranch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** A range-of-motion (goniometry) reading. Branch-aware. */
class PhysioRomMeasurement extends Model
{
    use BelongsToBranch;

    const SIDES = ['left', 'right', 'bilateral'];

    protected $fillable = [
        'patient_id', 'assessment_id', 'doctor_id', 'joint', 'motion', 'side', 'arom', 'prom', 'normal_ref', 'recorded_at',
    ];

    protected $casts = ['arom' => 'decimal:1', 'prom' => 'decimal:1', 'normal_ref' => 'decimal:1', 'recorded_at' => 'date'];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }
}
