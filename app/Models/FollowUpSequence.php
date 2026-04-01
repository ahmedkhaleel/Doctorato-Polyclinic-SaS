<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FollowUpSequence extends Model
{
    protected $fillable = [
        'name',
        'name_ar',
        'description',
        'trigger_event',
        'trigger_conditions',
        'is_active',
        'stop_on_reply',
        'stop_on_conversion',
        'max_enrollments',
        'enrollment_count',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'trigger_conditions' => 'array',
            'is_active' => 'boolean',
            'stop_on_reply' => 'boolean',
            'stop_on_conversion' => 'boolean',
        ];
    }

    // ─── Trigger Event Constants ──────────────────────────

    const EVENT_LEAD_CREATED = 'lead_created';
    const EVENT_CONTACT_FORM = 'contact_form_submitted';
    const EVENT_BOOKING_CREATED = 'booking_created';
    const EVENT_STATUS_NEW = 'status_changed_to_new';
    const EVENT_STATUS_CONTACTED = 'status_changed_to_contacted';
    const EVENT_STATUS_QUALIFIED = 'status_changed_to_qualified';
    const EVENT_STATUS_APPOINTMENT = 'status_changed_to_appointment_booked';
    const EVENT_STATUS_CONSULTATION = 'status_changed_to_consultation_done';
    const EVENT_STATUS_NEGOTIATION = 'status_changed_to_negotiation';
    const EVENT_FOLLOW_UP_MISSED = 'follow_up_missed';
    const EVENT_NO_RESPONSE_3_DAYS = 'no_response_3_days';

    const TRIGGER_EVENTS = [
        self::EVENT_LEAD_CREATED => 'Lead Created',
        self::EVENT_CONTACT_FORM => 'Contact Form Submitted',
        self::EVENT_BOOKING_CREATED => 'Booking Created',
        self::EVENT_STATUS_CONTACTED => 'Status → Contacted',
        self::EVENT_STATUS_QUALIFIED => 'Status → Qualified',
        self::EVENT_STATUS_APPOINTMENT => 'Status → Appointment Booked',
        self::EVENT_STATUS_CONSULTATION => 'Status → Consultation Done',
        self::EVENT_STATUS_NEGOTIATION => 'Status → Negotiation',
        self::EVENT_FOLLOW_UP_MISSED => 'Follow-up Missed',
        self::EVENT_NO_RESPONSE_3_DAYS => 'No Response for 3 Days',
    ];

    // ─── Relationships ────────────────────────────────────

    public function steps(): HasMany
    {
        return $this->hasMany(FollowUpSequenceStep::class, 'sequence_id')->orderBy('step_order');
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(LeadSequenceEnrollment::class, 'sequence_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ─── Scopes ───────────────────────────────────────────

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeForEvent(Builder $query, string $event): Builder
    {
        return $query->where('trigger_event', $event);
    }

    // ─── Methods ──────────────────────────────────────────

    /**
     * Check if a lead matches the trigger conditions of this sequence.
     */
    public function matchesConditions(Lead $lead): bool
    {
        $conditions = $this->trigger_conditions;
        if (empty($conditions)) {
            return true;
        }

        if (isset($conditions['lead_source_id']) && $conditions['lead_source_id']) {
            if ($lead->lead_source_id != $conditions['lead_source_id']) {
                return false;
            }
        }

        if (isset($conditions['priority']) && $conditions['priority']) {
            if ($lead->priority != $conditions['priority']) {
                return false;
            }
        }

        if (isset($conditions['campaign_id']) && $conditions['campaign_id']) {
            if ($lead->campaign_id != $conditions['campaign_id']) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if max enrollments reached.
     */
    public function canEnroll(): bool
    {
        if (! $this->is_active) {
            return false;
        }

        if ($this->max_enrollments && $this->enrollment_count >= $this->max_enrollments) {
            return false;
        }

        return true;
    }

    public function incrementEnrollment(): void
    {
        $this->increment('enrollment_count');
    }
}
