<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/** NP5 — a headache diary entry (ICHD-3 aware). Shared at patient level. */
class HeadacheDiaryEntry extends Model
{
    use HasFactory;

    protected $table = 'headache_diary';

    protected $fillable = [
        'patient_id', 'date', 'intensity', 'duration_hours', 'ichd3_type', 'aura', 'meds_taken', 'triggers', 'entered_by',
    ];

    protected $casts = ['date' => 'date', 'aura' => 'boolean'];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }
}
