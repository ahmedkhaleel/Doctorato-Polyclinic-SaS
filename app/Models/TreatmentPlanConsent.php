<?php

namespace App\Models;

use App\Traits\HasStatusTransitions;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TreatmentPlanConsent extends Model
{
    use HasStatusTransitions;

    /**
     * Allowed status transitions:
     *   pending → signed, declined, expired
     *   signed → (none — final)
     *   declined → pending (re-send)
     *   expired → pending (re-send)
     */
    const ALLOWED_TRANSITIONS = [
        'pending' => ['signed', 'declined', 'expired'],
        'signed' => [],
        'declined' => ['pending'],
        'expired' => ['pending'],
    ];
    protected $fillable = [
        'dental_treatment_plan_id',
        'patient_id',
        'sent_by',
        'status',
        'sent_at',
        'declined_at',
        'expires_at',
        'signature_image_path',
        'patient_ip',
        'patient_user_agent',
        'consent_text_snapshot',
        'risks_notes',
        'pdf_path',
        'declined_reason',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'signed_at' => 'datetime',
        'declined_at' => 'datetime',
        'expires_at' => 'datetime',
        'consent_text_snapshot' => 'array',
    ];

    protected $appends = ['signature_url', 'pdf_url'];

    const STATUS_PENDING = 'pending';
    const STATUS_SIGNED = 'signed';
    const STATUS_DECLINED = 'declined';
    const STATUS_EXPIRED = 'expired';

    // ─── Relationships ──────────────────────────────────────

    public function treatmentPlan(): BelongsTo
    {
        return $this->belongsTo(DentalTreatmentPlan::class, 'dental_treatment_plan_id');
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sent_by');
    }

    // ─── Accessors ──────────────────────────────────────────

    public function getSignatureUrlAttribute(): ?string
    {
        if (!$this->signature_image_path) return null;
        return asset('storage/' . $this->signature_image_path);
    }

    public function getPdfUrlAttribute(): ?string
    {
        if (!$this->pdf_path) return null;
        return asset('storage/' . $this->pdf_path);
    }

    // ─── Scopes ─────────────────────────────────────────────

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeSigned($query)
    {
        return $query->where('status', self::STATUS_SIGNED);
    }

    // ─── Helpers ────────────────────────────────────────────

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isSigned(): bool
    {
        return $this->status === self::STATUS_SIGNED;
    }

    public function isDeclined(): bool
    {
        return $this->status === self::STATUS_DECLINED;
    }

    public function isExpired(): bool
    {
        if ($this->status === self::STATUS_EXPIRED) return true;
        if ($this->status === self::STATUS_PENDING && $this->expires_at && $this->expires_at->isPast()) {
            $this->update(['status' => self::STATUS_EXPIRED]);
            return true;
        }
        return false;
    }
}
