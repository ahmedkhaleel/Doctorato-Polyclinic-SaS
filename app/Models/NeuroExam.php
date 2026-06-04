<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/** NP5 — structured neurological examination (tied to an encounter). */
class NeuroExam extends Model
{
    use HasFactory;

    protected $fillable = [
        'neuropsych_encounter_id', 'patient_id', 'cranial_nerves', 'motor',
        'sensory', 'reflexes', 'coordination', 'gait', 'romberg', 'notes',
    ];

    protected $casts = [
        'cranial_nerves' => 'array',
        'motor' => 'array',
        'sensory' => 'array',
        'reflexes' => 'array',
    ];

    public function encounter()
    {
        return $this->belongsTo(NeuropsychEncounter::class, 'neuropsych_encounter_id');
    }
}
