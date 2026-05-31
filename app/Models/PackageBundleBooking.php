<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBranch;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PackageBundleBooking extends Model
{
    use BelongsToBranch, HasFactory, LogsActivity;

    protected $fillable = [
        'booking_number', 'package_bundle_id', 'patient_id',
        'receptionist_id', 'status', 'total_price', 'total_paid', 'balance_due',
        'source', 'notes', 'promo_code', 'started_at', 'completed_at',
    ];

    protected $casts = [
        'total_price' => 'decimal:2',
        'total_paid' => 'decimal:2',
        'balance_due' => 'decimal:2',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    protected $appends = ['progress_percentage'];

    // ─── Relationships ──────────────────────────────────

    public function packageBundle()
    {
        return $this->belongsTo(PackageBundle::class);
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function receptionist()
    {
        return $this->belongsTo(User::class, 'receptionist_id');
    }

    public function bundleServices()
    {
        return $this->hasMany(PackageBundleBookingService::class);
    }

    public function visits()
    {
        return $this->hasMany(Visit::class);
    }

    public function invoice()
    {
        return $this->hasOne(Invoice::class);
    }

    public function appointments()
    {
        return $this->hasMany(PackageBundleBookingAppointment::class);
    }

    // ─── Accessors ──────────────────────────────────────

    protected function progressPercentage(): Attribute
    {
        return Attribute::get(function () {
            $totalSessions = $this->bundleServices->sum('sessions_count');
            $completedSessions = $this->bundleServices->sum('completed_sessions');

            return $totalSessions > 0 ? round(($completedSessions / $totalSessions) * 100) : 0;
        });
    }

    // ─── Scopes ─────────────────────────────────────────

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    // ─── Helpers ────────────────────────────────────────

    public static function generateBookingNumber(): string
    {
        $prefix = \App\Services\Branch\BranchNumber::prefix('PB');
        $last = static::where('booking_number', 'like', $prefix.'%')
            ->orderByDesc('booking_number')
            ->value('booking_number');
        $number = $last ? (int) substr($last, -4) + 1 : 1;

        return $prefix.str_pad($number, 4, '0', STR_PAD_LEFT);
    }

    public function recalculateBalance(): void
    {
        $invoice = $this->invoice;
        if ($invoice) {
            $this->total_paid = $invoice->paid_amount;
            $this->balance_due = $this->total_price - $this->total_paid;
            $this->save();
        }
    }
}
