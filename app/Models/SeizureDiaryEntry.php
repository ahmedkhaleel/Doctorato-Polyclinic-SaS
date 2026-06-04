<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/** NP5 — a seizure diary entry. Shared at patient level. */
class SeizureDiaryEntry extends Model
{
    use HasFactory;

    protected $table = 'seizure_diary';

    protected $fillable = [
        'patient_id', 'occurred_at', 'seizure_type', 'duration_seconds', 'triggers', 'entered_by', 'notes',
    ];

    protected $casts = ['occurred_at' => 'datetime'];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }
}
