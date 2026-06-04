<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * A diagnosis (ICD-11 or DSM-5-TR) attached to a neuropsych encounter. Scoped
 * to a branch transitively through its parent encounter.
 */
class EncounterDiagnosis extends Model
{
    use HasFactory;

    const CODE_SYSTEMS = ['icd11', 'dsm5'];

    protected $fillable = [
        'neuropsych_encounter_id', 'code_system', 'code', 'label', 'is_primary',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
    ];

    public function encounter()
    {
        return $this->belongsTo(NeuropsychEncounter::class, 'neuropsych_encounter_id');
    }
}
