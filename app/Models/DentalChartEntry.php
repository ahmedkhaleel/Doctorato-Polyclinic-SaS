<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DentalChartEntry extends Model
{
    protected $fillable = [
        'patient_id',
        'doctor_id',
        'visit_id',
        'tooth_number',
        'entry_type',
        'title',
        'description',
        'condition_before',
        'condition_after',
        'surfaces',
        'cost',
        'status',
        'media',
        'entry_date',
    ];

    protected $casts = [
        'surfaces' => 'array',
        'media' => 'array',
        'cost' => 'decimal:2',
        'tooth_number' => 'integer',
        'entry_date' => 'date',
    ];

    // Entry types
    const TYPE_EXAMINATION = 'examination';
    const TYPE_TREATMENT = 'treatment';
    const TYPE_NOTE = 'note';
    const TYPE_FOLLOW_UP = 'follow_up';
    const TYPE_COMPLICATION = 'complication';
    const TYPE_MEDIA_ONLY = 'media_only';

    const ENTRY_TYPES = [
        self::TYPE_EXAMINATION,
        self::TYPE_TREATMENT,
        self::TYPE_NOTE,
        self::TYPE_FOLLOW_UP,
        self::TYPE_COMPLICATION,
        self::TYPE_MEDIA_ONLY,
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }

    public function visit(): BelongsTo
    {
        return $this->belongsTo(Visit::class);
    }

    public function scopeForTooth($query, int $toothNumber)
    {
        return $query->where('tooth_number', $toothNumber);
    }

    public function scopeForPatient($query, int $patientId)
    {
        return $query->where('patient_id', $patientId);
    }
}
