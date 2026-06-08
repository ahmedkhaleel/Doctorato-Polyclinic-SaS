<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBranch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A patient's prepaid enrollment in a physiotherapy package. Tracks the session
 * balance (total vs used) + validity; physio sessions linked via
 * package_purchase_id draw down this balance instead of being billed. Branch-aware.
 */
class PhysioPackagePurchase extends Model
{
    use BelongsToBranch;

    const STATUS_ACTIVE = 'active';

    const STATUS_COMPLETED = 'completed';

    const STATUS_EXPIRED = 'expired';

    const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'patient_id', 'package_id', 'invoice_id', 'doctor_id', 'created_by',
        'total_sessions', 'sessions_used', 'amount', 'purchased_at', 'expires_at', 'status', 'notes',
    ];

    protected $casts = ['amount' => 'decimal:2', 'purchased_at' => 'date', 'expires_at' => 'date'];

    protected $appends = ['sessions_remaining', 'is_usable'];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(PhysioPackage::class, 'package_id');
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function scopeActive($q)
    {
        return $q->where('status', self::STATUS_ACTIVE);
    }

    public function getSessionsRemainingAttribute(): int
    {
        return max(0, (int) $this->total_sessions - (int) $this->sessions_used);
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    /** Can a session be drawn against this purchase right now? */
    public function getIsUsableAttribute(): bool
    {
        return $this->status === self::STATUS_ACTIVE
            && $this->sessions_remaining > 0
            && ! $this->isExpired();
    }

    /** Draw down one session; auto-completes when the balance hits zero. */
    public function drawDown(): bool
    {
        if (! $this->is_usable) {
            return false;
        }
        $this->increment('sessions_used');
        if ($this->sessions_remaining <= 0) {
            $this->update(['status' => self::STATUS_COMPLETED]);
        }

        return true;
    }
}
