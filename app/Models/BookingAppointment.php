<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBranch;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\LogsActivity;

class BookingAppointment extends Model
{
    use BelongsToBranch;
    use HasFactory, LogsActivity;

    protected $fillable = [
        'booking_id', 'booking_service_id', 'doctor_id',
        'appointment_date', 'start_time', 'end_time',
        'session_number', 'status', 'is_retouch', 'visit_id', 'notes',
    ];

    protected $casts = [
        'appointment_date' => 'date',
        'is_retouch' => 'boolean',
    ];

    // ─── Relationships ──────────────────────────────────

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    public function bookingService()
    {
        return $this->belongsTo(BookingService::class);
    }

    public function doctor()
    {
        return $this->belongsTo(Doctor::class);
    }

    public function visit()
    {
        return $this->belongsTo(Visit::class);
    }

    // ─── Scopes ─────────────────────────────────────────

    public function scopeForDate($query, $date)
    {
        return $query->whereDate('appointment_date', $date);
    }

    public function scopeForDoctor($query, $doctorId)
    {
        return $query->where('doctor_id', $doctorId);
    }

    public function scopeUpcoming($query)
    {
        return $query->where('appointment_date', '>=', today())
            ->whereNotIn('status', ['completed', 'cancelled', 'no_show']);
    }

    public function scopeToday($query)
    {
        return $query->whereDate('appointment_date', today());
    }
}
