<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBranch;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\LogsActivity;

class PackageBundleBookingAppointment extends Model
{
    use BelongsToBranch;
    use HasFactory, LogsActivity;

    protected $fillable = [
        'package_bundle_booking_id', 'package_bundle_booking_service_id',
        'doctor_id', 'appointment_date', 'start_time', 'end_time',
        'session_number', 'status', 'is_retouch', 'visit_id', 'notes',
    ];

    protected $casts = [
        'appointment_date' => 'date',
        'is_retouch' => 'boolean',
    ];

    // --- Relationships ---

    public function bundleBooking()
    {
        return $this->belongsTo(PackageBundleBooking::class, 'package_bundle_booking_id');
    }

    public function bundleBookingService()
    {
        return $this->belongsTo(PackageBundleBookingService::class, 'package_bundle_booking_service_id');
    }

    public function doctor()
    {
        return $this->belongsTo(Doctor::class);
    }

    public function visit()
    {
        return $this->belongsTo(Visit::class);
    }

    // --- Scopes ---

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
