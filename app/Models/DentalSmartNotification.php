<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class DentalSmartNotification extends Model
{
    protected $fillable = [
        'patient_id',
        'doctor_id',
        'type',
        'channel',
        'notifiable_type',
        'notifiable_id',
        'message_ar',
        'message_en',
        'status',
        'scheduled_at',
        'sent_at',
        'failure_reason',
        'phone',
        'sms_provider',
        'delay_hours',
        'is_auto',
        'patient_responded',
        'dedup_key',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'sent_at' => 'datetime',
        'is_auto' => 'boolean',
        'patient_responded' => 'boolean',
    ];

    // ── Types ────────────────────────────────────────────
    const TYPE_FOLLOWUP_REMINDER = 'followup_reminder';
    const TYPE_LAB_ORDER_READY = 'lab_order_ready';
    const TYPE_STALLED_PLAN = 'stalled_plan_reminder';
    const TYPE_POST_TREATMENT = 'post_treatment_checkup';
    const TYPE_PRE_APPOINTMENT_ALERT = 'pre_appointment_alert';

    const TYPES = [
        self::TYPE_FOLLOWUP_REMINDER,
        self::TYPE_LAB_ORDER_READY,
        self::TYPE_STALLED_PLAN,
        self::TYPE_POST_TREATMENT,
        self::TYPE_PRE_APPOINTMENT_ALERT,
    ];

    const TYPE_LABELS = [
        'followup_reminder' => ['ar' => 'تذكير بموعد المتابعة', 'en' => 'Follow-up Reminder'],
        'lab_order_ready' => ['ar' => 'جاهزية طلب المعمل', 'en' => 'Lab Order Ready'],
        'stalled_plan_reminder' => ['ar' => 'تذكير بإكمال خطة العلاج', 'en' => 'Stalled Plan Reminder'],
        'post_treatment_checkup' => ['ar' => 'اطمئنان بعد العلاج', 'en' => 'Post-Treatment Check'],
        'pre_appointment_alert' => ['ar' => 'تنبيه طبي قبل الموعد', 'en' => 'Pre-Appointment Medical Alert'],
    ];

    // ── Statuses ─────────────────────────────────────────
    const STATUS_PENDING = 'pending';
    const STATUS_SENT = 'sent';
    const STATUS_DELIVERED = 'delivered';
    const STATUS_FAILED = 'failed';
    const STATUS_CANCELLED = 'cancelled';

    // ── Default delay hours per type ─────────────────────
    const DEFAULT_DELAYS = [
        'followup_reminder' => 0,       // Immediate when due
        'lab_order_ready' => 0,          // Immediate when lab order status changes
        'stalled_plan_reminder' => 0,    // Immediate when detected
        'post_treatment_checkup' => 48,  // 48 hours after treatment
        'pre_appointment_alert' => 0,    // Immediate — sent day before appointment
    ];

    // ── Relationships ────────────────────────────────────
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }

    public function notifiable(): MorphTo
    {
        return $this->morphTo();
    }

    // ── Scopes ───────────────────────────────────────────
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeReadyToSend($query)
    {
        return $query->where('status', self::STATUS_PENDING)
            ->where(function ($q) {
                $q->whereNull('scheduled_at')
                  ->orWhere('scheduled_at', '<=', now());
            });
    }

    public function scopeSent($query)
    {
        return $query->where('status', self::STATUS_SENT);
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeForPatient($query, int $patientId)
    {
        return $query->where('patient_id', $patientId);
    }

    public function scopeAuto($query)
    {
        return $query->where('is_auto', true);
    }

    // ── Methods ──────────────────────────────────────────
    public function markSent(string $provider = null): void
    {
        $this->update([
            'status' => self::STATUS_SENT,
            'sent_at' => now(),
            'sms_provider' => $provider,
        ]);
    }

    public function markFailed(string $reason = null): void
    {
        $this->update([
            'status' => self::STATUS_FAILED,
            'failure_reason' => $reason,
        ]);
    }

    public function markCancelled(): void
    {
        $this->update(['status' => self::STATUS_CANCELLED]);
    }

    public function markPatientResponded(): void
    {
        $this->update(['patient_responded' => true]);
    }

    /**
     * Generate a dedup key to prevent sending the same notification twice.
     */
    public static function dedupKey(string $type, int $patientId, ?string $notifiableType, ?int $notifiableId): string
    {
        $date = now()->format('Y-m-d');
        return "{$type}:{$patientId}:{$notifiableType}:{$notifiableId}:{$date}";
    }

    /**
     * Check if a similar notification was already sent recently.
     */
    public static function alreadySent(string $dedupKey): bool
    {
        return static::where('dedup_key', $dedupKey)
            ->whereIn('status', [self::STATUS_PENDING, self::STATUS_SENT, self::STATUS_DELIVERED])
            ->exists();
    }

    /**
     * Get type label in given locale.
     */
    public function getTypeLabelAttribute(): string
    {
        $locale = app()->getLocale();
        return self::TYPE_LABELS[$this->type][$locale] ?? self::TYPE_LABELS[$this->type]['en'] ?? $this->type;
    }
}
