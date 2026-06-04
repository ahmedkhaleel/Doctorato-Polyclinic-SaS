<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBranch;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/** NP4 — an audited controlled-substance register row (record/track only). */
class ControlledSubstanceEntry extends Model
{
    use BelongsToBranch;
    use HasFactory;

    protected $table = 'controlled_substance_register';

    protected $fillable = [
        'patient_id', 'medication_plan_id', 'doctor_id', 'drug', 'quantity', 'prescribed_at', 'notes',
    ];

    protected $casts = [
        'prescribed_at' => 'datetime',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }
}
