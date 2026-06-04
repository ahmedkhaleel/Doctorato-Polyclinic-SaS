<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBranch;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/** NP6 — ECT/rTMS/ketamine treatment course. Branch-scoped. */
class TreatmentCourse extends Model
{
    use BelongsToBranch;
    use HasFactory, LogsActivity, SoftDeletes;

    const TYPES = ['ect', 'rtms', 'ketamine'];

    protected $fillable = [
        'patient_id', 'doctor_id', 'module', 'type', 'planned_sessions', 'session_fee',
        'consent_required', 'consent_signed_at', 'consent_signed_by', 'status', 'notes',
    ];

    protected $casts = [
        'consent_required' => 'boolean',
        'consent_signed_at' => 'datetime',
        'session_fee' => 'decimal:2',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function sessions()
    {
        return $this->hasMany(CourseSession::class);
    }

    public function consentOk(): bool
    {
        return ! $this->consent_required || $this->consent_signed_at !== null;
    }
}
