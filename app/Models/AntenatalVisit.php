<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBranch;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * One antenatal (or postnatal) care encounter within a pregnancy.
 * Links to a Visit row for billing (module=obgyn).
 */
class AntenatalVisit extends Model
{
    use BelongsToBranch;
    use HasFactory, LogsActivity, SoftDeletes;

    public const PHASE_ANTENATAL = 'antenatal';

    public const PHASE_POSTNATAL = 'postnatal';

    protected $fillable = [
        'pregnancy_id', 'visit_id', 'doctor_id', 'visit_date', 'phase',
        'gestational_age_weeks', 'weight_kg', 'bp_systolic', 'bp_diastolic',
        'fundal_height_cm', 'fetal_heart_rate', 'presentation', 'edema',
        'urine_protein', 'urine_glucose', 'complaints', 'plan', 'next_visit_date',
        'invoice_id', 'invoice_item_id',
    ];

    protected $casts = [
        'visit_date' => 'date',
        'next_visit_date' => 'date',
        'gestational_age_weeks' => 'decimal:1',
        'weight_kg' => 'decimal:3',
        'fundal_height_cm' => 'decimal:1',
        'bp_systolic' => 'integer',
        'bp_diastolic' => 'integer',
        'fetal_heart_rate' => 'integer',
        'edema' => 'boolean',
    ];

    public function pregnancy()
    {
        return $this->belongsTo(Pregnancy::class);
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

    /** Blood pressure as a display string, e.g. "120/80". */
    public function getBloodPressureAttribute(): ?string
    {
        if ($this->bp_systolic && $this->bp_diastolic) {
            return "{$this->bp_systolic}/{$this->bp_diastolic}";
        }

        return null;
    }
}
