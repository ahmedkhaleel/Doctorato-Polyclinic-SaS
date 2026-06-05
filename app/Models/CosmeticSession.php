<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBranch;
use App\Models\Concerns\DrawsFromInventory;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CosmeticSession extends Model
{
    use BelongsToBranch;
    use DrawsFromInventory;
    use HasFactory, LogsActivity, SoftDeletes;

    protected $fillable = [
        'patient_id', 'doctor_id', 'package_id', 'package_purchase_id', 'procedure_id', 'visit_id',
        'supply_id', 'consumption_qty', 'supply_transaction_id',
        'session_number', 'area_treated', 'product_used', 'dose_units',
        'cost', 'invoice_id', 'invoice_item_id', 'before_photo_path', 'after_photo_path',
        'completed_at', 'notes',
    ];

    protected $casts = [
        'dose_units' => 'decimal:2',
        'consumption_qty' => 'decimal:2',
        'cost' => 'decimal:2',
        'completed_at' => 'datetime',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function doctor()
    {
        return $this->belongsTo(Doctor::class);
    }

    public function procedure()
    {
        return $this->belongsTo(CosmeticProcedure::class, 'procedure_id');
    }

    public function package()
    {
        return $this->belongsTo(CosmeticPackage::class, 'package_id');
    }

    public function packagePurchase()
    {
        return $this->belongsTo(CosmeticPackagePurchase::class, 'package_purchase_id');
    }

    public function supply()
    {
        return $this->belongsTo(Supply::class);
    }

    public function supplyTransaction()
    {
        return $this->belongsTo(SupplyTransaction::class);
    }

    public function visit()
    {
        return $this->belongsTo(Visit::class);
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function consents()
    {
        return $this->hasMany(CosmeticConsent::class, 'session_id');
    }
}
