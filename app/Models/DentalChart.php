<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DentalChart extends Model
{
    protected $fillable = [
        'patient_id',
        'tooth_number',
        'condition',
        'surfaces',
        'notes',
        'status',
    ];

    protected $casts = [
        'surfaces' => 'array',
        'tooth_number' => 'integer',
    ];

    // Tooth conditions
    const CONDITION_HEALTHY = 'healthy';
    const CONDITION_DECAYED = 'decayed';
    const CONDITION_FILLED = 'filled';
    const CONDITION_MISSING = 'missing';
    const CONDITION_CROWN = 'crown';
    const CONDITION_BRIDGE = 'bridge';
    const CONDITION_IMPLANT = 'implant';
    const CONDITION_ROOT_CANAL = 'root_canal';
    const CONDITION_EXTRACTED = 'extracted';

    const CONDITIONS = [
        self::CONDITION_HEALTHY,
        self::CONDITION_DECAYED,
        self::CONDITION_FILLED,
        self::CONDITION_MISSING,
        self::CONDITION_CROWN,
        self::CONDITION_BRIDGE,
        self::CONDITION_IMPLANT,
        self::CONDITION_ROOT_CANAL,
        self::CONDITION_EXTRACTED,
    ];

    // Tooth surfaces
    const SURFACE_MESIAL = 'mesial';
    const SURFACE_DISTAL = 'distal';
    const SURFACE_OCCLUSAL = 'occlusal';
    const SURFACE_BUCCAL = 'buccal';
    const SURFACE_LINGUAL = 'lingual';

    const SURFACES = [
        self::SURFACE_MESIAL,
        self::SURFACE_DISTAL,
        self::SURFACE_OCCLUSAL,
        self::SURFACE_BUCCAL,
        self::SURFACE_LINGUAL,
    ];

    // FDI tooth numbering - upper right, upper left, lower left, lower right
    const UPPER_RIGHT = [18, 17, 16, 15, 14, 13, 12, 11];
    const UPPER_LEFT = [21, 22, 23, 24, 25, 26, 27, 28];
    const LOWER_LEFT = [38, 37, 36, 35, 34, 33, 32, 31];
    const LOWER_RIGHT = [41, 42, 43, 44, 45, 46, 47, 48];

    const ALL_TEETH = [
        18, 17, 16, 15, 14, 13, 12, 11,
        21, 22, 23, 24, 25, 26, 27, 28,
        31, 32, 33, 34, 35, 36, 37, 38,
        41, 42, 43, 44, 45, 46, 47, 48,
    ];

    // Deciduous (primary) teeth - FDI notation
    const DECIDUOUS_UPPER_RIGHT = [55, 54, 53, 52, 51];
    const DECIDUOUS_UPPER_LEFT = [61, 62, 63, 64, 65];
    const DECIDUOUS_LOWER_LEFT = [75, 74, 73, 72, 71];
    const DECIDUOUS_LOWER_RIGHT = [81, 82, 83, 84, 85];

    const ALL_DECIDUOUS_TEETH = [
        55, 54, 53, 52, 51,
        61, 62, 63, 64, 65,
        71, 72, 73, 74, 75,
        81, 82, 83, 84, 85,
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function treatments()
    {
        return DentalTreatment::where('patient_id', $this->patient_id)
            ->where('tooth_number', $this->tooth_number)
            ->get();
    }

    public function scopeForPatient($query, int $patientId)
    {
        return $query->where('patient_id', $patientId);
    }

    public function scopeByCondition($query, string $condition)
    {
        return $query->where('condition', $condition);
    }
}
