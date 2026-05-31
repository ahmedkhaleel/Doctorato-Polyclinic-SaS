<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBranch;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OnlineConsultation extends Model
{
    use BelongsToBranch, HasFactory, SoftDeletes;

    protected $fillable = [
        'consultation_number', 'patient_id', 'doctor_id',
        'booking_id', 'booking_appointment_id', 'visit_id', 'invoice_id',
        'scheduled_date', 'start_time', 'end_time',
        'module', 'consultation_type',
        'agora_channel_name', 'agora_doctor_token', 'agora_patient_token',
        'agora_tokens_expire_at',
        'doctor_joined_at', 'patient_joined_at',
        'session_started_at', 'session_ended_at', 'duration_seconds',
        'status',
        'chief_complaint', 'diagnosis', 'doctor_notes',
        'patient_feedback', 'patient_rating',
        'recording_enabled', 'recording_url', 'recording_resource_id',
        'fee', 'payment_status', 'payment_gateway_reference', 'recovery_email_sent_at',
        'cancellation_reason', 'cancelled_by', 'cancelled_at',
    ];

    protected $casts = [
        'scheduled_date' => 'date',
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i',
        'agora_tokens_expire_at' => 'datetime',
        'doctor_joined_at' => 'datetime',
        'patient_joined_at' => 'datetime',
        'session_started_at' => 'datetime',
        'session_ended_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'recovery_email_sent_at' => 'datetime',
        'fee' => 'decimal:2',
        'recording_enabled' => 'boolean',
    ];

    public const STATUS_SCHEDULED = 'scheduled';

    public const STATUS_WAITING = 'waiting';

    public const STATUS_IN_PROGRESS = 'in_progress';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_MISSED_PATIENT = 'missed_patient';

    public const STATUS_MISSED_DOCTOR = 'missed_doctor';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_REFUNDED = 'refunded';

    public const STATUS_TECHNICAL_ISSUE = 'technical_issue';

    protected static function booted()
    {
        static::creating(function (self $model) {
            if (empty($model->consultation_number)) {
                $model->consultation_number = static::generateConsultationNumber();
            }
            if (empty($model->agora_channel_name)) {
                $model->agora_channel_name = 'consult_'.Str::random(16);
            }
        });
    }

    public static function generateConsultationNumber(): string
    {
        $prefix = \App\Services\Branch\BranchNumber::prefix('TELE');
        $lastNumber = (int) (self::where('consultation_number', 'like', $prefix.'%')
            ->orderByDesc('id')
            ->value(DB::raw("SUBSTRING_INDEX(consultation_number, '-', -1)")) ?? 0);

        return $prefix.str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
    }

    // Relationships
    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function doctor()
    {
        return $this->belongsTo(Doctor::class);
    }

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    public function bookingAppointment()
    {
        return $this->belongsTo(BookingAppointment::class);
    }

    public function visit()
    {
        return $this->belongsTo(Visit::class);
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function cancelledBy()
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function paymentTransactions()
    {
        return $this->hasMany(PaymentTransaction::class);
    }

    // Scopes
    public function scopeUpcoming($q)
    {
        return $q->where('scheduled_date', '>=', today())
            ->whereIn('status', ['scheduled', 'waiting']);
    }

    public function scopeForPatient($q, $patientId)
    {
        return $q->where('patient_id', $patientId);
    }

    public function scopeForDoctor($q, $doctorId)
    {
        return $q->where('doctor_id', $doctorId);
    }

    public function scopePaid($q)
    {
        return $q->where('payment_status', 'paid');
    }

    // Helpers
    public function isJoinableNow(int $windowMinutes = 15): bool
    {
        if (! in_array($this->status, [self::STATUS_SCHEDULED, self::STATUS_WAITING, self::STATUS_IN_PROGRESS])) {
            return false;
        }
        if ($this->payment_status !== 'paid') {
            return false;
        }

        $start = Carbon::parse($this->scheduled_date->format('Y-m-d').' '.$this->start_time);
        $end = Carbon::parse($this->scheduled_date->format('Y-m-d').' '.$this->end_time);
        $now = now();

        return $now->greaterThanOrEqualTo($start->copy()->subMinutes($windowMinutes))
            && $now->lessThanOrEqualTo($end->copy()->addMinutes(30));
    }

    public function isCancellable(int $hoursBeforeStart = 24): bool
    {
        if (! in_array($this->status, [self::STATUS_SCHEDULED, self::STATUS_WAITING])) {
            return false;
        }
        $start = Carbon::parse($this->scheduled_date->format('Y-m-d').' '.$this->start_time);

        return now()->lessThan($start->copy()->subHours($hoursBeforeStart));
    }

    public function getFullStartDateTimeAttribute(): Carbon
    {
        return Carbon::parse($this->scheduled_date->format('Y-m-d').' '.$this->start_time);
    }
}
