<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBranch;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\LogsActivity;

class TreatmentPlan extends Model
{
    use BelongsToBranch;
    use HasFactory, LogsActivity;

    protected $fillable = [
        'patient_id', 'doctor_id', 'title', 'description', 'goals',
        'status', 'start_date', 'end_date', 'notes', 'created_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    protected $appends = ['progress', 'total_estimated_cost'];

    // ─── Relationships ──────────────────────────────────

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function doctor()
    {
        return $this->belongsTo(Doctor::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function steps()
    {
        return $this->hasMany(TreatmentPlanStep::class)->orderBy('step_order');
    }

    // ─── Accessors ──────────────────────────────────────

    public function getProgressAttribute(): int
    {
        $total = $this->steps->sum('sessions_required');
        if ($total === 0) return 0;
        $completed = $this->steps->sum('sessions_completed');
        return (int) round(($completed / $total) * 100);
    }

    public function getTotalEstimatedCostAttribute(): float
    {
        return (float) $this->steps->sum('estimated_cost');
    }

    // ─── Scopes ─────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeForPatient($query, $patientId)
    {
        return $query->where('patient_id', $patientId);
    }
}
