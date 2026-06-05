<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/** NP4 — a scheduled monitoring task for a medication plan. */
class MedicationMonitoring extends Model
{
    use HasFactory;

    protected $table = 'medication_monitoring';

    protected $fillable = [
        'medication_plan_id', 'patient_id', 'type', 'due_at',
        'result', 'result_at', 'status', 'notes',
    ];

    protected $casts = [
        'due_at' => 'date',
        'result_at' => 'date',
    ];

    public function plan()
    {
        return $this->belongsTo(MedicationPlan::class, 'medication_plan_id');
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }
}
