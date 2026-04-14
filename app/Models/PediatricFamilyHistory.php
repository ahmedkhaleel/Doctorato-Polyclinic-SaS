<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\LogsActivity;

class PediatricFamilyHistory extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    protected $table = 'pediatric_family_history';

    protected $fillable = [
        'patient_id', 'doctor_id', 'condition', 'condition_ar', 'affected_members', 'details',
    ];

    protected $casts = [
        'affected_members' => 'array',
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
