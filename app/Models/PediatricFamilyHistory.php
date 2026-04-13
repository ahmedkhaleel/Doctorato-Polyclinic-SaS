<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PediatricFamilyHistory extends Model
{
    use HasFactory;

    protected $table = 'pediatric_family_history';

    protected $fillable = [
        'patient_id', 'condition', 'condition_ar', 'affected_members', 'details',
    ];

    protected $casts = [
        'affected_members' => 'array',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }
}
