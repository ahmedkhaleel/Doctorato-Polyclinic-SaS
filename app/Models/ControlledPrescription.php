<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBranch;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/** NP-Future-1 (layer B) — a controlled-substance e-prescription. Branch-scoped, sensitive. */
class ControlledPrescription extends Model
{
    use BelongsToBranch;
    use HasFactory, LogsActivity, SoftDeletes;

    const STATUSES = ['draft', 'signed', 'submitted', 'authorized', 'dispensed', 'cancelled'];

    protected $fillable = [
        'patient_id', 'doctor_id', 'module', 'drug', 'schedule', 'dose', 'quantity',
        'status', 'gateway', 'external_ref', 'signature_hash', 'signed_by', 'signed_at',
        'submitted_at', 'authorized_at', 'dispensed_at', 'notes', 'gateway_response',
    ];

    protected $casts = [
        'signed_at' => 'datetime',
        'submitted_at' => 'datetime',
        'authorized_at' => 'datetime',
        'dispensed_at' => 'datetime',
        'gateway_response' => 'array',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function doctor()
    {
        return $this->belongsTo(Doctor::class);
    }

    public function isSigned(): bool
    {
        return $this->signed_at !== null && ! empty($this->signature_hash);
    }
}
