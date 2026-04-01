<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeadSequenceEnrollment extends Model
{
    protected $fillable = [
        'lead_id',
        'sequence_id',
        'current_step_index',
        'status',
        'enrolled_at',
        'next_step_at',
        'completed_at',
        'cancelled_at',
    ];

    protected function casts(): array
    {
        return [
            'enrolled_at' => 'datetime',
            'next_step_at' => 'datetime',
            'completed_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    const STATUS_ACTIVE = 'active';
    const STATUS_PAUSED = 'paused';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_STOPPED_REPLY = 'stopped_reply';
    const STATUS_STOPPED_CONVERSION = 'stopped_conversion';

    // ─── Relationships ────────────────────────────────────

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function sequence(): BelongsTo
    {
        return $this->belongsTo(FollowUpSequence::class, 'sequence_id');
    }

    // ─── Scopes ───────────────────────────────────────────

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeDue(Builder $query): Builder
    {
        return $query->active()
            ->whereNotNull('next_step_at')
            ->where('next_step_at', '<=', now());
    }

    // ─── Methods ──────────────────────────────────────────

    public function markCompleted(): void
    {
        $this->update([
            'status' => self::STATUS_COMPLETED,
            'completed_at' => now(),
            'next_step_at' => null,
        ]);
    }

    public function cancel(string $reason = 'cancelled'): void
    {
        $this->update([
            'status' => $reason,
            'cancelled_at' => now(),
            'next_step_at' => null,
        ]);
    }

    public function pause(): void
    {
        $this->update(['status' => self::STATUS_PAUSED]);
    }

    public function resume(): void
    {
        $this->update(['status' => self::STATUS_ACTIVE]);
    }

    /**
     * Advance to next step and schedule it.
     */
    public function advanceToNextStep(): void
    {
        $sequence = $this->sequence()->with('steps')->first();
        $nextStepOrder = $this->current_step_index + 1;
        $nextStep = $sequence->steps->where('step_order', $nextStepOrder)->where('is_active', true)->first();

        if (! $nextStep) {
            // No more active steps, try any remaining
            $nextStep = $sequence->steps
                ->where('step_order', '>', $this->current_step_index)
                ->where('is_active', true)
                ->first();
        }

        if (! $nextStep) {
            $this->markCompleted();
            return;
        }

        $this->update([
            'current_step_index' => $nextStep->step_order,
            'next_step_at' => now()->addMinutes($nextStep->delay_minutes),
        ]);
    }
}
