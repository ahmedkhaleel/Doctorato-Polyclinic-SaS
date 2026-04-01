<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    use HasFactory;

    protected $fillable = [
        'name_ar', 'name_en', 'code', 'phone', 'email',
        'contact_person', 'address', 'tax_number',
        'payment_terms', 'lead_time_days', 'rating',
        'notes', 'is_active',
    ];

    protected $casts = [
        'lead_time_days' => 'integer',
        'rating' => 'decimal:1',
        'is_active' => 'boolean',
    ];

    public function supplies()
    {
        return $this->hasMany(Supply::class);
    }

    public function purchaseOrders()
    {
        return $this->hasMany(PurchaseOrder::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
