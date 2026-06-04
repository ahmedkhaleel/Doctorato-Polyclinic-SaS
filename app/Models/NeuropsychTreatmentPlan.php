<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBranch;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Problem-oriented treatment plan for a psychiatry/neurology patient.
 * Branch-scoped clinical event.
 */
class NeuropsychTreatmentPlan extends Model
{
    use BelongsToBranch;
    use HasFactory, LogsActivity, SoftDeletes;

    const STATUSES = ['active', 'achieved', 'discontinued'];

    protected $fillable = [
        'patient_id', 'doctor_id', 'module', 'title', 'items',
        'status', 'review_date', 'notes',
    ];

    protected $casts = [
        'items' => 'array',
        'review_date' => 'date',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function doctor()
    {
        return $this->belongsTo(Doctor::class);
    }
}
