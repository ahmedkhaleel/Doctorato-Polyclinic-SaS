<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeadFollowUp extends Model
{
    use HasFactory;

    protected $fillable = [
        'lead_id',
        'assigned_to',
        'type',
        'scheduled_at',
        'completed_at',
        'notes',
        'result',
        'status',
        'reminder_sent',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime',
            'completed_at' => 'datetime',
            'reminder_sent' => 'integer',
        ];
    }

    // ─── Relationships ───────────────────────────────────

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ─── Scopes ──────────────────────────────────────────

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }

    public function scopeOverdue(Builder $query): Builder
    {
        return $query->where('status', 'pending')
            ->where('scheduled_at', '<', now());
    }

    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->where('status', 'pending')
            ->where('scheduled_at', '>=', now());
    }

    public function scopeToday(Builder $query): Builder
    {
        return $query->where('status', 'pending')
            ->whereDate('scheduled_at', today());
    }

    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('assigned_to', $userId);
    }

    // ─── Methods ─────────────────────────────────────────

    public function markCompleted(?string $result = null): void
    {
        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
            'result' => $result ?? $this->result,
        ]);
    }

    public function markMissed(): void
    {
        $this->update(['status' => 'missed']);
    }

    public function reschedule(\DateTime $newDate): void
    {
        $this->update([
            'status' => 'rescheduled',
        ]);

        // Create a new follow-up at the rescheduled date
        static::create([
            'lead_id' => $this->lead_id,
            'assigned_to' => $this->assigned_to,
            'type' => $this->type,
            'scheduled_at' => $newDate,
            'notes' => $this->notes,
            'status' => 'pending',
            'created_by' => auth()->id(),
        ]);
    }

    public function getIsOverdueAttribute(): bool
    {
        return $this->status === 'pending' && $this->scheduled_at->isPast();
    }
}
