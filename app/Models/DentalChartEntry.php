<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBranch;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DentalChartEntry extends Model
{
    use BelongsToBranch;

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
        'cost' => 'decimal:2',
        'tooth_number' => 'integer',
        'entry_date' => 'date',
    ];

    /**
     * Media is a JSON array of {path,type,original_name,...}. On read we inject a
     * signed, authenticated `url` per item (PHI — paths are no longer public).
     * `path` is preserved so deletion/migration keep working.
     */
    protected function media(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                $items = is_array($value) ? $value : (json_decode($value ?? '[]', true) ?: []);

                return array_map(function ($m) {
                    if (is_array($m) && ! empty($m['path'])) {
                        $m['url'] = \App\Support\SecureMedia::url($m['path']);
                    }

                    return $m;
                }, $items);
            },
            set: fn ($value) => json_encode($value),
        );
    }

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
