<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * A single pregnancy episode. Central entity of the obstetrics flow:
 * antenatal visits, ultrasounds, lab tests and the delivery hang off it.
 */
class Pregnancy extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    public const STATUS_ACTIVE = 'active';

    public const STATUS_DELIVERED = 'delivered';

    public const STATUS_MISCARRIED = 'miscarried';

    public const STATUS_TERMINATED = 'terminated';

    protected static function booted(): void
    {
        $forget = function (Pregnancy $record) {
            if (! empty($record->patient_id)) {
                Patient::forgetSpecialtyCache((int) $record->patient_id);
            }
        };
        static::saved($forget);
        static::deleted($forget);
    }

    protected $fillable = [
        'patient_id', 'doctor_id', 'lmp', 'edd', 'edd_source', 'gravida', 'para',
        'conception_method', 'blood_group', 'rh_factor', 'is_high_risk',
        'risk_factors', 'status', 'notes',
    ];

    protected $casts = [
        'lmp' => 'date',
        'edd' => 'date',
        'is_high_risk' => 'boolean',
        'risk_factors' => 'array',
        'gravida' => 'integer',
        'para' => 'integer',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function doctor()
    {
        return $this->belongsTo(Doctor::class);
    }

    public function antenatalVisits()
    {
        return $this->hasMany(AntenatalVisit::class)->orderByDesc('visit_date');
    }

    public function ultrasounds()
    {
        return $this->hasMany(ObstetricUltrasound::class)->orderByDesc('scan_date');
    }

    public function labTests()
    {
        return $this->hasMany(ObgynLabTest::class)->orderByDesc('result_date');
    }

    public function delivery()
    {
        return $this->hasOne(DeliveryRecord::class);
    }

    public function scopeActive(Builder $q): Builder
    {
        return $q->where('status', self::STATUS_ACTIVE);
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }
}
