<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\LogsActivity;

class ServiceSupply extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = ['service_id', 'supply_id', 'quantity_per_session'];

    protected $casts = ['quantity_per_session' => 'decimal:2'];

    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    public function supply()
    {
        return $this->belongsTo(Supply::class);
    }
}
