<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\LogsActivity;

class PediatricAllergy extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    protected $fillable = [
        'patient_id', 'doctor_id', 'allergy_type', 'allergen', 'severity',
        'symptoms', 'discovered_date', 'treatment', 'is_active', 'notes',
    ];

    protected $casts = [
        'symptoms' => 'array',
        'discovered_date' => 'date',
        'is_active' => 'boolean',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function doctor()
    {
        return $this->belongsTo(Doctor::class);
    }
}
