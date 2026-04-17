<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\LogsActivity;

class CosmeticSession extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    protected $fillable = [
        'patient_id', 'doctor_id', 'package_id', 'procedure_id', 'visit_id',
        'session_number', 'area_treated', 'product_used', 'dose_units',
        'cost', 'before_photo_path', 'after_photo_path',
        'completed_at', 'notes',
    ];

    protected $casts = [
        'dose_units' => 'decimal:2',
        'cost' => 'decimal:2',
        'completed_at' => 'datetime',
    ];

    public function patient() { return $this->belongsTo(Patient::class); }
    public function doctor() { return $this->belongsTo(Doctor::class); }
    public function procedure() { return $this->belongsTo(CosmeticProcedure::class, 'procedure_id'); }
    public function package() { return $this->belongsTo(CosmeticPackage::class, 'package_id'); }
    public function visit() { return $this->belongsTo(Visit::class); }
    public function consents() { return $this->hasMany(CosmeticConsent::class, 'session_id'); }
}
