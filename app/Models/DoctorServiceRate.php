<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\LogsActivity;

class DoctorServiceRate extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = ['doctor_id', 'service_id', 'commission_percentage'];

    protected $casts = ['commission_percentage' => 'decimal:2'];

    public function doctor()
    {
        return $this->belongsTo(Doctor::class);
    }

    public function service()
    {
        return $this->belongsTo(Service::class);
    }
}
