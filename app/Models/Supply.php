<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBranch;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Supply extends Model
{
    use BelongsToBranch, HasFactory, LogsActivity;

    protected $fillable = [
        'supply_category_id', 'module', 'name_ar', 'name_en', 'sku', 'barcode', 'category', 'unit',
        'quantity', 'min_quantity', 'purchase_price', 'supplier',
        'image', 'expiry_date', 'batch_number', 'description', 'is_active',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'min_quantity' => 'decimal:2',
        'purchase_price' => 'decimal:2',
        'is_active' => 'boolean',
        'expiry_date' => 'date',
    ];

    protected $appends = ['is_low_stock'];

    public function supplyCategory()
    {
        return $this->belongsTo(SupplyCategory::class);
    }

    public function supplierRecord()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    public function transactions()
    {
        return $this->hasMany(SupplyTransaction::class);
    }

    public function serviceSupplies()
    {
        return $this->hasMany(ServiceSupply::class);
    }

    protected function isLowStock(): Attribute
    {
        return Attribute::get(fn () => $this->quantity <= $this->min_quantity);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeLowStock($query)
    {
        return $query->whereColumn('quantity', '<=', 'min_quantity');
    }

    public function scopeForModule($query, string $module)
    {
        if ($module === 'all') {
            return $query;
        }

        return $query->where(function ($q) use ($module) {
            $q->where('module', $module)->orWhere('module', 'shared');
        });
    }
}
