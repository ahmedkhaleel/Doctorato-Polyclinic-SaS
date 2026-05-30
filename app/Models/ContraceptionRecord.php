<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ContraceptionRecord extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    public const STATUS_ACTIVE = 'active';

    public const STATUS_STOPPED = 'stopped';

    protected static function booted(): void
    {
        $forget = function (ContraceptionRecord $record) {
            if (! empty($record->patient_id)) {
                Patient::forgetSpecialtyCache((int) $record->patient_id);
            }
        };
        static::saved($forget);
        static::deleted($forget);
    }

    protected $fillable = [
        'patient_id', 'doctor_id', 'method', 'start_date', 'end_date',
        'follow_up_date', 'status', 'notes',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'follow_up_date' => 'date',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function doctor()
    {
        return $this->belongsTo(Doctor::class);
    }

    public function scopeActive(Builder $q): Builder
    {
        return $q->where('status', self::STATUS_ACTIVE);
    }
}
