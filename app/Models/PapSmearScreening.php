<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBranch;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PapSmearScreening extends Model
{
    use BelongsToBranch;
    use HasFactory, LogsActivity, SoftDeletes;

    protected static function booted(): void
    {
        $forget = function (PapSmearScreening $record) {
            if (! empty($record->patient_id)) {
                Patient::forgetSpecialtyCache((int) $record->patient_id);
            }
        };
        static::saved($forget);
        static::deleted($forget);
    }

    protected $fillable = [
        'patient_id', 'visit_id', 'doctor_id', 'test_date', 'result',
        'hpv_status', 'next_due_date', 'notes',
        'invoice_id', 'invoice_item_id',
    ];

    protected $casts = [
        'test_date' => 'date',
        'next_due_date' => 'date',
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

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function isAbnormal(): bool
    {
        return $this->result !== null && $this->result !== 'normal';
    }
}
