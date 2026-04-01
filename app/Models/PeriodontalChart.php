<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PeriodontalChart extends Model
{
    protected $fillable = [
        'patient_id',
        'doctor_id',
        'exam_date',
        'tooth_number',
        'probing_depths',
        'recession',
        'bleeding_on_probing',
        'plaque_index',
        'mobility',
        'furcation',
        'notes',
    ];

    protected $casts = [
        'probing_depths' => 'array',
        'recession' => 'array',
        'bleeding_on_probing' => 'array',
        'plaque_index' => 'array',
        'exam_date' => 'date',
        'tooth_number' => 'integer',
        'mobility' => 'integer',
        'furcation' => 'integer',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }

    public function scopeForPatient($query, int $patientId)
    {
        return $query->where('patient_id', $patientId);
    }

    public function scopeLatestExam($query, int $patientId)
    {
        return $query->where('patient_id', $patientId)
            ->orderByDesc('exam_date');
    }

    public function getMaxProbingDepthAttribute(): int
    {
        if (!$this->probing_depths) return 0;
        return max($this->probing_depths);
    }

    public function getHasBleedingAttribute(): bool
    {
        if (!$this->bleeding_on_probing) return false;
        return in_array(true, $this->bleeding_on_probing, true) || in_array(1, $this->bleeding_on_probing, true);
    }
}
