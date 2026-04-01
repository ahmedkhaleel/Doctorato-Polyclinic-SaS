<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\LogsActivity;

class SupplyTransaction extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'supply_id', 'transaction_type', 'quantity',
        'unit_cost', 'visit_id', 'notes', 'created_by',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_cost' => 'decimal:2',
    ];

    public function supply()
    {
        return $this->belongsTo(Supply::class);
    }

    public function visit()
    {
        return $this->belongsTo(Visit::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
