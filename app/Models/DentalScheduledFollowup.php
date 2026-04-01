<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DentalScheduledFollowup extends Model
{
    protected $fillable = [
        'dental_treatment_id',
        'patient_id',
        'doctor_id',
        'followup_rule_id',
        'booking_id',
        'scheduled_date',
        'status',
        'booking_created_at',
        'sms_sent_at',
        'reminder_sent_at',
        'notes',
    ];

    protected $casts = [
        'scheduled_date' => 'date',
        'booking_created_at' => 'datetime',
        'sms_sent_at' => 'datetime',
        'reminder_sent_at' => 'datetime',
    ];

    const STATUS_PENDING = 'pending';
    const STATUS_BOOKING_CREATED = 'booking_created';
    const STATUS_SMS_SENT = 'sms_sent';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';

    public function treatment(): BelongsTo
    {
        return $this->belongsTo(DentalTreatment::class, 'dental_treatment_id');
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }

    public function rule(): BelongsTo
    {
        return $this->belongsTo(DentalFollowupRule::class, 'followup_rule_id');
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeUpcoming($query)
    {
        return $query->whereIn('status', [self::STATUS_PENDING, self::STATUS_BOOKING_CREATED, self::STATUS_SMS_SENT])
            ->where('scheduled_date', '>=', today());
    }

    public function scopeNeedsBooking($query)
    {
        return $query->where('status', self::STATUS_PENDING)
            ->whereNull('booking_id');
    }

    public function scopeNeedsSmsReminder($query, int $daysBefore = 1)
    {
        return $query->whereIn('status', [self::STATUS_PENDING, self::STATUS_BOOKING_CREATED])
            ->whereNull('reminder_sent_at')
            ->whereDate('scheduled_date', today()->addDays($daysBefore));
    }
}
