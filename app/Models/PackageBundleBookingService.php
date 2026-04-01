<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use App\Traits\LogsActivity;

class PackageBundleBookingService extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'package_bundle_booking_id', 'package_bundle_service_id',
        'service_id', 'doctor_id',
        'sessions_count', 'completed_sessions',
        'bundle_price', 'discount_percentage', 'status',
    ];

    protected $casts = [
        'bundle_price' => 'decimal:2',
        'discount_percentage' => 'decimal:2',
    ];

    protected $appends = ['remaining_sessions'];

    // ─── Relationships ──────────────────────────────────

    public function bundleBooking()
    {
        return $this->belongsTo(PackageBundleBooking::class, 'package_bundle_booking_id');
    }

    public function bundleService()
    {
        return $this->belongsTo(PackageBundleService::class, 'package_bundle_service_id');
    }

    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    public function doctor()
    {
        return $this->belongsTo(Doctor::class);
    }

    public function visits()
    {
        return $this->hasMany(Visit::class);
    }

    public function appointments()
    {
        return $this->hasMany(PackageBundleBookingAppointment::class);
    }

    // ─── Accessors ──────────────────────────────────────

    protected function remainingSessions(): Attribute
    {
        return Attribute::get(fn () => $this->sessions_count - $this->completed_sessions);
    }
}
