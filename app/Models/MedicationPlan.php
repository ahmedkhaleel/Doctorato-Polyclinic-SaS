<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBranch;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/** NP4 — a psychotropic/neuro medication plan. Branch-scoped clinical event. */
class MedicationPlan extends Model
{
    use BelongsToBranch;
    use HasFactory, LogsActivity, SoftDeletes;

    protected $fillable = [
        'patient_id', 'doctor_id', 'module', 'drug', 'drug_class', 'dose',
        'frequency', 'route', 'started_at', 'stopped_at', 'is_controlled', 'notes',
    ];

    protected $casts = [
        'started_at' => 'date',
        'stopped_at' => 'date',
        'is_controlled' => 'boolean',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function doctor()
    {
        return $this->belongsTo(Doctor::class);
    }

    public function monitoring()
    {
        return $this->hasMany(MedicationMonitoring::class);
    }
}
