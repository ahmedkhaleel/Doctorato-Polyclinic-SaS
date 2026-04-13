<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\LogsActivity;

class PediatricScreeningTest extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'patient_id', 'visit_id', 'doctor_id', 'test_date', 'age_months',
        'test_type', 'answers', 'total_score', 'result',
        'interpretation', 'recommendations', 'notes',
    ];

    protected $casts = [
        'test_date' => 'date',
        'age_months' => 'decimal:2',
        'answers' => 'array',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function visit()
    {
        return $this->belongsTo(Visit::class);
    }

    public function doctor()
    {
        return $this->belongsTo(Doctor::class);
    }
}
