<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DentalXray extends Model
{
    protected $fillable = [
        'patient_id',
        'doctor_id',
        'type',
        'image_path',
        'tooth_number',
        'findings',
        'notes',
        'taken_date',
    ];

    protected $casts = [
        'taken_date' => 'date',
    ];

    const TYPE_PANORAMIC = 'panoramic';
    const TYPE_PERIAPICAL = 'periapical';
    const TYPE_BITEWING = 'bitewing';
    const TYPE_CEPHALOMETRIC = 'cephalometric';
    const TYPE_CBCT = 'cbct';
    const TYPE_OCCLUSAL = 'occlusal';

    const TYPES = [
        self::TYPE_PANORAMIC,
        self::TYPE_PERIAPICAL,
        self::TYPE_BITEWING,
        self::TYPE_CEPHALOMETRIC,
        self::TYPE_CBCT,
        self::TYPE_OCCLUSAL,
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }

    public function getImageUrlAttribute(): ?string
    {
        if (!$this->image_path) return null;
        if (str_starts_with($this->image_path, 'http')) return $this->image_path;
        return '/storage/' . $this->image_path;
    }

    public function scopeForPatient($query, int $patientId)
    {
        return $query->where('patient_id', $patientId);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }
}
