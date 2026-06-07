<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBranch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** A physiotherapy plan of care (SMART goals + modalities + dosage). Branch-aware. */
class PhysioTreatmentPlan extends Model
{
    use BelongsToBranch;

    const STATUS_PLANNED = 'planned';

    const STATUS_IN_PROGRESS = 'in_progress';

    const STATUS_ON_HOLD = 'on_hold';

    const STATUS_COMPLETED = 'completed';

    const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'patient_id', 'doctor_id', 'title_ar', 'title_en', 'problem_list', 'goals', 'modalities',
        'frequency', 'duration_weeks', 'estimated_sessions', 'completed_sessions', 'status',
        'start_date', 'notes',
    ];

    protected $casts = [
        'goals' => 'array', 'modalities' => 'array', 'start_date' => 'date',
        'estimated_sessions' => 'integer', 'completed_sessions' => 'integer',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(PhysioSession::class, 'treatment_plan_id');
    }

    public function scopeActive($q)
    {
        return $q->whereIn('status', [self::STATUS_PLANNED, self::STATUS_IN_PROGRESS]);
    }

    public function getProgressPercentageAttribute(): int
    {
        if (! $this->estimated_sessions) {
            return 0;
        }

        return min(100, (int) round(($this->completed_sessions / $this->estimated_sessions) * 100));
    }

    public function getSessionsRemainingAttribute(): int
    {
        return max(0, (int) $this->estimated_sessions - (int) $this->completed_sessions);
    }
}
