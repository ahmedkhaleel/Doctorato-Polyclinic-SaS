<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TreatmentPlanStep extends Model
{
    use HasFactory;

    protected $fillable = [
        'treatment_plan_id', 'service_id', 'step_order',
        'title', 'description',
        'sessions_required', 'sessions_completed',
        'estimated_cost', 'status',
        'scheduled_date', 'completed_date', 'notes',
        'visit_id',
    ];

    protected $casts = [
        'sessions_required' => 'integer',
        'sessions_completed' => 'integer',
        'step_order' => 'integer',
        'estimated_cost' => 'decimal:2',
        'scheduled_date' => 'date',
        'completed_date' => 'date',
    ];

    // ─── Relationships ──────────────────────────────────

    public function treatmentPlan()
    {
        return $this->belongsTo(TreatmentPlan::class);
    }

    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    public function visit()
    {
        return $this->belongsTo(Visit::class);
    }

    // ─── Accessors ──────────────────────────────────────

    public function getProgressPercentAttribute(): int
    {
        if (!$this->sessions_required || $this->sessions_required == 0) return 0;
        return (int) round(($this->sessions_completed / $this->sessions_required) * 100);
    }

    public function getIsCompletedAttribute(): bool
    {
        return $this->status === 'completed'
            || ($this->sessions_required > 0 && $this->sessions_completed >= $this->sessions_required);
    }
}
