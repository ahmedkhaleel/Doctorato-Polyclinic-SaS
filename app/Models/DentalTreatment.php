<?php

namespace App\Models;

use App\Traits\HasStatusTransitions;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;

class DentalTreatment extends Model
{
    use HasStatusTransitions;

    protected static function booted(): void
    {
        // Bust dashboard cache on any treatment change
        $bustCache = function () {
            $month = now()->startOfMonth()->format('Y-m');
            Cache::forget("dental_dashboard_monthly:{$month}");
            Cache::forget('dental_report_monthly_revenue');
        };

        // Bust the patient's cached active-specialties list when
        // dental treatments change (may toggle the patient's "dental" specialty).
        $forgetPatientCache = function (DentalTreatment $treatment) {
            if (!empty($treatment->patient_id)) {
                Patient::forgetSpecialtyCache((int) $treatment->patient_id);
            }
        };

        static::saved(function ($treatment) use ($bustCache, $forgetPatientCache) {
            $bustCache();
            $forgetPatientCache($treatment);
        });
        static::deleted(function ($treatment) use ($bustCache, $forgetPatientCache) {
            $bustCache();
            $forgetPatientCache($treatment);
        });

        static::updated(function (DentalTreatment $treatment) {
            // Auto-sync treatment plan progress when a treatment status changes
            if ($treatment->isDirty('status') && $treatment->treatment_plan_id) {
                $treatment->syncPlanProgress();
            }

            // Auto-schedule followup when treatment is completed
            if ($treatment->isDirty('status') && $treatment->status === 'completed') {
                $treatment->scheduleAutoFollowup();
            }
        });
    }

    /**
     * Auto-schedule a follow-up based on active followup rules.
     */
    public function scheduleAutoFollowup(): void
    {
        $rule = DentalFollowupRule::active()
            ->forType($this->treatment_type)
            ->first();

        if (!$rule) return;

        // Don't create duplicate followups
        $exists = DentalScheduledFollowup::where('dental_treatment_id', $this->id)
            ->where('followup_rule_id', $rule->id)
            ->whereIn('status', ['pending', 'booking_created', 'sms_sent'])
            ->exists();

        if ($exists) return;

        DentalScheduledFollowup::create([
            'dental_treatment_id' => $this->id,
            'patient_id' => $this->patient_id,
            'doctor_id' => $this->doctor_id,
            'followup_rule_id' => $rule->id,
            'scheduled_date' => now()->addDays($rule->followup_days),
            'status' => DentalScheduledFollowup::STATUS_PENDING,
            'notes' => "Auto-scheduled: {$rule->label_en} ({$rule->followup_days} days)",
        ]);
    }

    /**
     * Sync parent treatment plan's progress counters and status.
     */
    public function syncPlanProgress(): void
    {
        $plan = $this->treatmentPlan;
        if (!$plan) return;

        $completedCount = $plan->treatments()->where('status', 'completed')->count();
        $totalCount = $plan->treatments()->count();
        $actualCost = (float) $plan->treatments()->where('status', 'completed')->sum(\DB::raw('cost + lab_cost'));

        $plan->update([
            'completed_sessions' => $completedCount,
            'actual_cost' => $actualCost,
        ]);

        // Auto-complete plan if all treatments are done
        if ($totalCount > 0 && $completedCount >= $totalCount && $plan->status === 'in_progress') {
            $plan->update([
                'status' => 'completed',
                'completed_at' => now(),
            ]);
        }

        // Auto-start plan if first treatment begins
        if ($this->status === 'in_progress' && $plan->status === 'approved') {
            $plan->update(['status' => 'in_progress']);
        }
    }

    /**
     * Allowed status transitions:
     *   planned → in_progress, completed, cancelled
     *   in_progress → completed, cancelled
     *   completed → (none — final)
     *   cancelled → planned (re-activate)
     */
    const ALLOWED_TRANSITIONS = [
        'planned' => ['in_progress', 'completed', 'cancelled'],
        'in_progress' => ['completed', 'cancelled'],
        'completed' => [],
        'cancelled' => ['planned'],
    ];
    protected $fillable = [
        'patient_id',
        'doctor_id',
        'visit_id',
        'invoice_id',
        'treatment_plan_id',
        'tooth_number',
        'treatment_type',
        'surfaces',
        'description',
        'cost',
        'lab_cost',
        'status',
        'completed_at',
        'notes',
        'followup_reminder_at',
    ];

    protected $casts = [
        'surfaces' => 'array',
        'cost' => 'decimal:2',
        'lab_cost' => 'decimal:2',
        'completed_at' => 'date',
        'tooth_number' => 'integer',
        'followup_reminder_at' => 'datetime',
    ];

    const TYPE_FILLING = 'filling';
    const TYPE_EXTRACTION = 'extraction';
    const TYPE_ROOT_CANAL = 'root_canal';
    const TYPE_CROWN = 'crown';
    const TYPE_BRIDGE = 'bridge';
    const TYPE_IMPLANT = 'implant';
    const TYPE_CLEANING = 'cleaning';
    const TYPE_SCALING = 'scaling';
    const TYPE_WHITENING = 'whitening';
    const TYPE_VENEER = 'veneer';
    const TYPE_ORTHODONTIC = 'orthodontic';
    const TYPE_DENTURE = 'denture';
    const TYPE_SEALANT = 'sealant';
    const TYPE_FLUORIDE = 'fluoride';
    const TYPE_GUM_TREATMENT = 'gum_treatment';
    const TYPE_SURGICAL_EXTRACTION = 'surgical_extraction';
    const TYPE_BONE_GRAFT = 'bone_graft';
    const TYPE_SINUS_LIFT = 'sinus_lift';
    const TYPE_NIGHT_GUARD = 'night_guard';
    const TYPE_RETAINER = 'retainer';

    const TYPES = [
        self::TYPE_FILLING,
        self::TYPE_EXTRACTION,
        self::TYPE_ROOT_CANAL,
        self::TYPE_CROWN,
        self::TYPE_BRIDGE,
        self::TYPE_IMPLANT,
        self::TYPE_CLEANING,
        self::TYPE_SCALING,
        self::TYPE_WHITENING,
        self::TYPE_VENEER,
        self::TYPE_ORTHODONTIC,
        self::TYPE_DENTURE,
        self::TYPE_SEALANT,
        self::TYPE_FLUORIDE,
        self::TYPE_GUM_TREATMENT,
        self::TYPE_SURGICAL_EXTRACTION,
        self::TYPE_BONE_GRAFT,
        self::TYPE_SINUS_LIFT,
        self::TYPE_NIGHT_GUARD,
        self::TYPE_RETAINER,
    ];

    const STATUS_PLANNED = 'planned';
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }

    public function visit(): BelongsTo
    {
        return $this->belongsTo(Visit::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function treatmentPlan(): BelongsTo
    {
        return $this->belongsTo(DentalTreatmentPlan::class, 'treatment_plan_id');
    }

    public function labOrder()
    {
        return $this->hasOne(DentalLabOrder::class, 'dental_treatment_id');
    }

    public function prescription()
    {
        return $this->hasOne(Prescription::class, 'dental_treatment_id');
    }

    public function scheduledFollowups()
    {
        return $this->hasMany(DentalScheduledFollowup::class, 'dental_treatment_id');
    }

    public function scopeForPatient($query, int $patientId)
    {
        return $query->where('patient_id', $patientId);
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopePlanned($query)
    {
        return $query->where('status', self::STATUS_PLANNED);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    public function getTotalCostAttribute(): float
    {
        return $this->cost + $this->lab_cost;
    }
}
