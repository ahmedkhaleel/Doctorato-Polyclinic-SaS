<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\LogsActivity;

class PediatricChronicCondition extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'patient_id', 'condition_type', 'condition_name', 'condition_name_ar',
        'diagnosed_date', 'severity', 'current_medications',
        'treatment_plan', 'action_plan', 'is_active', 'notes',
    ];

    protected $casts = [
        'diagnosed_date' => 'date',
        'current_medications' => 'array',
        'is_active' => 'boolean',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }
}
