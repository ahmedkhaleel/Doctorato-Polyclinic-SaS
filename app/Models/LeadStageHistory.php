<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class LeadStageHistory extends Model
{
    protected $table = 'lead_stage_history';

    protected $fillable = [
        'lead_id',
        'from_status',
        'to_status',
        'changed_by',
        'duration_minutes',
        'changed_at',
    ];

    protected function casts(): array
    {
        return [
            'changed_at' => 'datetime',
            'duration_minutes' => 'integer',
        ];
    }

    // ─── Relationships ───────────────────────────────────

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }

    // ─── Scopes ──────────────────────────────────────────

    public function scopeForLead(Builder $query, int $leadId): Builder
    {
        return $query->where('lead_id', $leadId);
    }

    public function scopeForStatus(Builder $query, string $status): Builder
    {
        return $query->where('from_status', $status)
            ->orWhere('to_status', $status);
    }
}
