<?php

namespace App\Models;

use App\Traits\HasStatusTransitions;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class DentalTreatmentPlan extends Model
{
    use HasStatusTransitions;

    /**
     * Allowed status transitions:
     *   draft → approved, cancelled
     *   approved → in_progress, cancelled
     *   in_progress → completed, cancelled
     *   completed → (none — final)
     *   cancelled → draft (re-open)
     */
    const ALLOWED_TRANSITIONS = [
        'draft' => ['approved', 'cancelled'],
        'approved' => ['in_progress', 'cancelled'],
        'in_progress' => ['completed', 'cancelled'],
        'completed' => [],
        'cancelled' => ['draft'],
    ];
    protected $fillable = [
        'patient_id',
        'doctor_id',
        'title_ar',
        'title_en',
        'description',
        'estimated_cost',
        'actual_cost',
        'estimated_sessions',
        'completed_sessions',
        'priority',
        'status',
        'start_date',
        'expected_end_date',
        'completed_at',
        'notes',
        'overdue_notified_at',
        'approaching_notified_at',
    ];

    protected $casts = [
        'estimated_cost' => 'decimal:2',
        'actual_cost' => 'decimal:2',
        'start_date' => 'date',
        'expected_end_date' => 'date',
        'completed_at' => 'date',
        'overdue_notified_at' => 'datetime',
        'approaching_notified_at' => 'datetime',
    ];

    const STATUS_DRAFT = 'draft';
    const STATUS_APPROVED = 'approved';
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';

    const PRIORITY_URGENT = 'urgent';
    const PRIORITY_HIGH = 'high';
    const PRIORITY_NORMAL = 'normal';
    const PRIORITY_LOW = 'low';

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }

    public function treatments(): HasMany
    {
        return $this->hasMany(DentalTreatment::class, 'treatment_plan_id');
    }

    public function consent(): HasOne
    {
        return $this->hasOne(TreatmentPlanConsent::class, 'dental_treatment_plan_id')->latest();
    }

    public function consents(): HasMany
    {
        return $this->hasMany(TreatmentPlanConsent::class, 'dental_treatment_plan_id');
    }

    public function hasSignedConsent(): bool
    {
        return $this->consents()->where('status', TreatmentPlanConsent::STATUS_SIGNED)->exists();
    }

    public function hasPendingConsent(): bool
    {
        return $this->consents()->where('status', TreatmentPlanConsent::STATUS_PENDING)->exists();
    }

    public function getProgressAttribute(): int
    {
        if ($this->estimated_sessions == 0) return 0;
        return min(100, round(($this->completed_sessions / $this->estimated_sessions) * 100));
    }

    public function getCompletedTreatmentsCountAttribute(): int
    {
        return $this->treatments()->where('status', 'completed')->count();
    }

    public function getTotalTreatmentsCountAttribute(): int
    {
        return $this->treatments()->count();
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', [self::STATUS_APPROVED, self::STATUS_IN_PROGRESS]);
    }

    public function scopeForPatient($query, int $patientId)
    {
        return $query->where('patient_id', $patientId);
    }
}
