<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBranch;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\LogsActivity;

class PediatricNutritionRecord extends Model
{
    use BelongsToBranch;
    use HasFactory, LogsActivity, SoftDeletes;

    protected $fillable = [
        'patient_id', 'visit_id', 'doctor_id', 'age_months',
        'feeding_type', 'feeds_per_day', 'feed_duration_min',
        'formula_brand', 'formula_ml_per_feed',
        'complementary_start_age', 'introduced_foods',
        'meals_per_day', 'diet_quality', 'milk_intake',
        'fruits_vegetables', 'fast_food', 'supplements',
        'food_allergies', 'eating_problems', 'notes',
    ];

    protected $casts = [
        'age_months' => 'decimal:2',
        'introduced_foods' => 'array',
        'supplements' => 'array',
        'food_allergies' => 'array',
        'eating_problems' => 'array',
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
