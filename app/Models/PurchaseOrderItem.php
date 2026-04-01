<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseOrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'purchase_order_id', 'supply_id',
        'quantity_ordered', 'quantity_received', 'unit_price', 'total_price',
        'batch_number', 'expiry_date', 'notes',
    ];

    protected $casts = [
        'quantity_ordered' => 'decimal:2',
        'quantity_received' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
        'expiry_date' => 'date',
    ];

    protected $appends = ['remaining_quantity'];

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function supply()
    {
        return $this->belongsTo(Supply::class);
    }

    public function getRemainingQuantityAttribute(): float
    {
        return max(0, $this->quantity_ordered - $this->quantity_received);
    }

    protected static function booted(): void
    {
        static::saving(function (self $item) {
            $item->total_price = $item->quantity_ordered * $item->unit_price;
        });
    }
}
