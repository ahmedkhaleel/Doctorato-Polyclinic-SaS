<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBranch;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use App\Traits\LogsActivity;

class BookingService extends Model
{
    use BelongsToBranch;
    use HasFactory, LogsActivity;

    protected $fillable = [
        'booking_id', 'service_id', 'doctor_id',
        'sessions_count', 'unit_price', 'discount_per_session', 'total_price',
        'completed_sessions', 'status', 'notes',
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'discount_per_session' => 'decimal:2',
        'total_price' => 'decimal:2',
    ];

    protected $appends = ['remaining_sessions'];

    // ─── Relationships ──────────────────────────────────

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    public function doctor()
    {
        return $this->belongsTo(Doctor::class);
    }

    public function appointments()
    {
        return $this->hasMany(BookingAppointment::class);
    }

    // ─── Accessors ──────────────────────────────────────

    protected function remainingSessions(): Attribute
    {
        return Attribute::get(fn () => $this->sessions_count - $this->completed_sessions);
    }
}
