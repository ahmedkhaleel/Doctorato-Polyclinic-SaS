<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBranch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A lesion plotted on the derma body map (CV-3). Branch-aware (stamped on create;
 * scoped only when BRANCHES_ENABLED).
 */
class DermaLesion extends Model
{
    use BelongsToBranch;

    const TYPES = ['nevus', 'macule', 'papule', 'plaque', 'nodule', 'patch', 'vesicle', 'pustule', 'ulcer', 'scar', 'other'];

    const VIEWS = ['front', 'back'];

    protected $fillable = [
        'patient_id', 'visit_id', 'doctor_id', 'view', 'x', 'y',
        'lesion_type', 'size_mm', 'note', 'recorded_at',
    ];

    protected $casts = [
        'x' => 'decimal:2',
        'y' => 'decimal:2',
        'size_mm' => 'decimal:1',
        'recorded_at' => 'date',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }
}
