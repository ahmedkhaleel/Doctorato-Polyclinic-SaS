<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\LogsActivity;

class Booking extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'patient_id', 'booking_number', 'source', 'module', 'booking_type',
        'full_name', 'phone', 'email',
        'service_id', 'doctor_id',
        'preferred_date', 'preferred_time',
        'notes', 'promo_code', 'status', 'admin_notes',
        'invoice_id', 'is_read',
    ];

    protected $casts = [
        'preferred_date' => 'date',
        'is_read' => 'boolean',
    ];

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function bookingServices()
    {
        return $this->hasMany(BookingService::class);
    }

    public function appointments()
    {
        return $this->hasMany(BookingAppointment::class);
    }

    public function consents()
    {
        return $this->hasMany(BookingConsent::class);
    }

    // ─── Static Methods ─────────────────────────────────

    public static function generateBookingNumber(): string
    {
        $prefix = 'BK-' . now()->format('Ym') . '-';
        $last = static::where('booking_number', 'like', $prefix . '%')
            ->orderByDesc('booking_number')
            ->value('booking_number');
        $number = $last ? (int) substr($last, -4) + 1 : 1;

        return $prefix . str_pad($number, 4, '0', STR_PAD_LEFT);
    }
}
