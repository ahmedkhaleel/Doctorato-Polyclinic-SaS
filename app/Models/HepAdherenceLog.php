<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBranch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** A patient-logged home-exercise-program adherence entry. Branch-aware. */
class HepAdherenceLog extends Model
{
    use BelongsToBranch;

    protected $fillable = ['patient_id', 'prescription_id', 'log_date', 'done', 'pain_after', 'note'];

    protected $casts = ['log_date' => 'date', 'done' => 'boolean'];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }
}
