<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\LogsActivity;

class MarketerCommission extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'user_id',
        'lead_id',
        'booking_id',
        'payment_id',
        'commission_type',
        'rate',
        'base_amount',
        'commission_amount',
        'status',
        'paid_date',
        'notes',
        'approved_by',
    ];

    protected function casts(): array
    {
        return [
            'rate' => 'decimal:2',
            'base_amount' => 'decimal:2',
            'commission_amount' => 'decimal:2',
            'paid_date' => 'date',
        ];
    }

    // ─── Relationships ───────────────────────────────────

    public function marketer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // ─── Scopes ──────────────────────────────────────────

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', 'approved');
    }

    public function scopePaid(Builder $query): Builder
    {
        return $query->where('status', 'paid');
    }

    public function scopeForMarketer(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    // ─── Methods ─────────────────────────────────────────

    public function calculateCommission(): void
    {
        if ($this->commission_type === 'percentage') {
            $this->commission_amount = round(($this->rate / 100) * $this->base_amount, 2);
        } else {
            $this->commission_amount = $this->rate;
        }
        $this->save();
    }

    public function approve(?int $approvedBy = null): void
    {
        $this->update([
            'status' => 'approved',
            'approved_by' => $approvedBy ?? auth()->id(),
        ]);
    }

    public function markAsPaid(): void
    {
        $this->update([
            'status' => 'paid',
            'paid_date' => now(),
        ]);
    }
}
