<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\LogsActivity;

class PackageBundleService extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'package_bundle_id', 'service_id',
        'sessions_count', 'discount_percentage', 'bundle_price', 'display_order',
    ];

    protected $casts = [
        'discount_percentage' => 'decimal:2',
        'bundle_price' => 'decimal:2',
    ];

    // ─── Relationships ──────────────────────────────────

    public function packageBundle()
    {
        return $this->belongsTo(PackageBundle::class);
    }

    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    public function bookingServices()
    {
        return $this->hasMany(PackageBundleBookingService::class);
    }
}
