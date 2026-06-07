<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBranch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** A physiotherapy treatment session (billable). Branch-aware. */
class PhysioSession extends Model
{
    use BelongsToBranch;

    protected $fillable = [
        'patient_id', 'doctor_id', 'treatment_plan_id', 'visit_id', 'invoice_id', 'invoice_item_id',
        'session_number', 'session_date', 'soap', 'modalities', 'techniques', 'exercises_done',
        'attended', 'pain_before', 'pain_after', 'cost', 'completed_at', 'notes',
    ];

    protected $casts = [
        'session_date' => 'date', 'modalities' => 'array', 'attended' => 'boolean',
        'cost' => 'decimal:2', 'completed_at' => 'datetime',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }

    public function treatmentPlan(): BelongsTo
    {
        return $this->belongsTo(PhysioTreatmentPlan::class, 'treatment_plan_id');
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }
}
