<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ObstetricUltrasound extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    protected $fillable = [
        'pregnancy_id', 'visit_id', 'doctor_id', 'scan_date', 'scan_type',
        'gestational_age_weeks', 'bpd_mm', 'hc_mm', 'ac_mm', 'fl_mm', 'efw_grams',
        'placenta_position', 'afi', 'fetal_count', 'fetal_heart', 'presentation', 'findings',
    ];

    protected $casts = [
        'scan_date' => 'date',
        'gestational_age_weeks' => 'decimal:1',
        'bpd_mm' => 'decimal:1',
        'hc_mm' => 'decimal:1',
        'ac_mm' => 'decimal:1',
        'fl_mm' => 'decimal:1',
        'efw_grams' => 'integer',
        'afi' => 'decimal:1',
        'fetal_count' => 'integer',
        'fetal_heart' => 'boolean',
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
}
