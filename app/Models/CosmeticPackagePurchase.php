<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBranch;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * A patient's prepaid enrollment in a cosmetic package.
 *
 * Tracks the session balance (total vs used) and validity. Cosmetic sessions
 * linked via package_purchase_id draw down this balance instead of being
 * billed individually.
 */
class CosmeticPackagePurchase extends Model
{
    use BelongsToBranch;
    use HasFactory, LogsActivity, SoftDeletes;

    const STATUS_ACTIVE    = 'active';
    const STATUS_COMPLETED = 'completed';
    const STATUS_EXPIRED   = 'expired';
    const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'patient_id', 'package_id', 'invoice_id', 'created_by',
        'total_sessions', 'sessions_used', 'amount',
        'purchased_at', 'expires_at', 'status', 'notes',
    ];

    protected $casts = [
        'amount'       => 'decimal:2',
        'purchased_at' => 'date',
        'expires_at'   => 'date',
    ];

    protected $appends = ['sessions_remaining', 'is_usable'];

    public function patient() { return $this->belongsTo(Patient::class); }
    public function package() { return $this->belongsTo(CosmeticPackage::class, 'package_id'); }
    public function invoice() { return $this->belongsTo(Invoice::class); }
    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
    public function sessions() { return $this->hasMany(CosmeticSession::class, 'package_purchase_id'); }

    public function getSessionsRemainingAttribute(): int
    {
        return max(0, (int) $this->total_sessions - (int) $this->sessions_used);
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    /** Can a new session be drawn against this purchase right now? */
    public function getIsUsableAttribute(): bool
    {
        return $this->status === self::STATUS_ACTIVE
            && ! $this->isExpired()
            && $this->sessions_remaining > 0;
    }

    public function scopeActive($q) { return $q->where('status', self::STATUS_ACTIVE); }
}
