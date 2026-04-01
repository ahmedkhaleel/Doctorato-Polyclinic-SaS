<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Referral extends Model
{
    use HasFactory;

    const DEPARTMENTS = ['dermatology', 'dental', 'general', 'cosmetic', 'laser'];

    const URGENCY_LEVELS = ['routine', 'urgent', 'emergency'];

    const STATUS_PENDING = 'pending';
    const STATUS_ACCEPTED = 'accepted';
    const STATUS_SCHEDULED = 'scheduled';
    const STATUS_COMPLETED = 'completed';
    const STATUS_DECLINED = 'declined';
    const STATUS_CANCELLED = 'cancelled';

    const ALLOWED_TRANSITIONS = [
        'pending' => ['accepted', 'declined', 'cancelled'],
        'accepted' => ['scheduled', 'completed', 'cancelled'],
        'scheduled' => ['completed', 'cancelled'],
    ];

    protected $fillable = [
        'referral_number', 'patient_id', 'referring_doctor_id', 'referred_to_doctor_id',
        'visit_id', 'from_department', 'to_department', 'urgency', 'status',
        'reason', 'clinical_notes', 'referring_diagnosis', 'provisional_diagnosis',
        'response_notes', 'accepted_at', 'scheduled_at', 'completed_at', 'declined_at',
        'resulting_visit_id',
    ];

    protected $casts = [
        'accepted_at' => 'datetime',
        'scheduled_at' => 'datetime',
        'completed_at' => 'datetime',
        'declined_at' => 'datetime',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function referringDoctor()
    {
        return $this->belongsTo(User::class, 'referring_doctor_id');
    }

    public function referredToDoctor()
    {
        return $this->belongsTo(User::class, 'referred_to_doctor_id');
    }

    public function visit()
    {
        return $this->belongsTo(Visit::class);
    }

    public function resultingVisit()
    {
        return $this->belongsTo(Visit::class, 'resulting_visit_id');
    }

    public static function generateReferralNumber(): string
    {
        $prefix = 'REF-' . now()->format('Ym') . '-';
        $last = static::where('referral_number', 'like', $prefix . '%')
            ->orderByDesc('referral_number')
            ->value('referral_number');
        $number = $last ? (int) substr($last, -4) + 1 : 1;

        return $prefix . str_pad($number, 4, '0', STR_PAD_LEFT);
    }

    public function canTransitionTo(string $status): bool
    {
        return in_array($status, self::ALLOWED_TRANSITIONS[$this->status] ?? []);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeActive($query)
    {
        return $query->whereNotIn('status', ['completed', 'declined', 'cancelled']);
    }

    public function scopeForDoctor($query, int $doctorId)
    {
        return $query->where(function ($q) use ($doctorId) {
            $q->where('referring_doctor_id', $doctorId)
                ->orWhere('referred_to_doctor_id', $doctorId);
        });
    }
}
