<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBranch;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * A psychiatry/neurology clinical encounter. Branch-scoped clinical event.
 * `module` discriminates psychiatry vs neurology; `mse` holds the structured
 * Mental Status Examination.
 */
class NeuropsychEncounter extends Model
{
    use BelongsToBranch;
    use HasFactory, LogsActivity, SoftDeletes;

    const MODULES = ['psychiatry', 'neurology'];

    const NOTE_FORMATS = ['soap', 'dap', 'birp'];

    protected $fillable = [
        'patient_id', 'doctor_id', 'module', 'visit_id', 'encounter_date',
        'note_format', 'subjective', 'objective', 'assessment', 'plan', 'mse',
        'cost', 'invoice_id', 'invoice_item_id', 'completed_at',
    ];

    protected $casts = [
        'encounter_date' => 'date',
        'mse' => 'array',
        'cost' => 'decimal:2',
        'completed_at' => 'datetime',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function doctor()
    {
        return $this->belongsTo(Doctor::class);
    }

    public function visit()
    {
        return $this->belongsTo(Visit::class);
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function diagnoses()
    {
        return $this->hasMany(EncounterDiagnosis::class);
    }
}
