<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBranch;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\LogsActivity;

class DiscountUsage extends Model
{
    use BelongsToBranch;
    use HasFactory, LogsActivity;

    protected $table = 'discount_usage';

    protected $fillable = [
        'discount_code_id', 'patient_id', 'invoice_id',
        'booking_id', 'package_bundle_booking_id',
        'discount_amount', 'ip_address', 'user_agent',
    ];

    protected $casts = ['discount_amount' => 'decimal:2'];

    public function discountCode()
    {
        return $this->belongsTo(DiscountCode::class);
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    public function packageBundleBooking()
    {
        return $this->belongsTo(PackageBundleBooking::class);
    }
}
