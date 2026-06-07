<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBranch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** A pain point plotted on the body map. Branch-aware. */
class PhysioPainPoint extends Model
{
    use BelongsToBranch;

    const VIEWS = ['front', 'back'];

    const TYPES = ['sharp', 'dull', 'burning', 'tingling', 'numbness', 'aching', 'throbbing', 'stiffness', 'other'];

    protected $fillable = [
        'patient_id', 'assessment_id', 'doctor_id', 'view', 'x', 'y', 'intensity', 'pain_type', 'note', 'recorded_at',
    ];

    protected $casts = ['x' => 'decimal:2', 'y' => 'decimal:2', 'recorded_at' => 'date'];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }
}
