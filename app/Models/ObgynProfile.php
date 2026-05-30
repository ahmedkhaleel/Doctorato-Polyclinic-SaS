<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Baseline reproductive/gynaecological profile — one row per (female) patient.
 */
class ObgynProfile extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    protected static function booted(): void
    {
        $forget = function (ObgynProfile $record) {
            if (! empty($record->patient_id)) {
                Patient::forgetSpecialtyCache((int) $record->patient_id);
            }
        };
        static::saved($forget);
        static::deleted($forget);
    }

    protected $fillable = [
        'patient_id', 'doctor_id', 'menarche_age', 'gravida', 'para', 'abortus',
        'living_children', 'blood_group', 'rh_factor', 'lmp', 'cycle_length_days',
        'contraception_method', 'notes',
    ];

    protected $casts = [
        'lmp' => 'date',
        'menarche_age' => 'integer',
        'gravida' => 'integer',
        'para' => 'integer',
        'abortus' => 'integer',
        'living_children' => 'integer',
        'cycle_length_days' => 'integer',
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
