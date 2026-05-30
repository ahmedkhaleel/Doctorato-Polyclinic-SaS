<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ObgynLabTest extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    protected static function booted(): void
    {
        $forget = function (ObgynLabTest $record) {
            if (! empty($record->patient_id)) {
                Patient::forgetSpecialtyCache((int) $record->patient_id);
            }
        };
        static::saved($forget);
        static::deleted($forget);
    }

    protected $fillable = [
        'patient_id', 'pregnancy_id', 'doctor_id', 'test_type', 'value', 'unit',
        'reference_range', 'result_date', 'is_abnormal', 'notes',
    ];

    protected $casts = [
        'result_date' => 'date',
        'is_abnormal' => 'boolean',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function pregnancy()
    {
        return $this->belongsTo(Pregnancy::class);
    }

    public function doctor()
    {
        return $this->belongsTo(Doctor::class);
    }
}
