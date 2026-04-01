<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeadAssignmentRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'rule_type',
        'lead_source_id',
        'assign_to_user_id',
        'conditions',
        'priority',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'conditions' => 'array',
            'priority' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    // ─── Relationships ───────────────────────────────────

    public function leadSource(): BelongsTo
    {
        return $this->belongsTo(LeadSource::class);
    }

    public function assignToUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assign_to_user_id');
    }

    // ─── Scopes ──────────────────────────────────────────

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeByPriority(Builder $query): Builder
    {
        return $query->orderByDesc('priority');
    }

    // ─── Helpers ─────────────────────────────────────────

    /**
     * Find the best matching assignment rule for a new lead.
     */
    public static function findMatchingRule(Lead $lead): ?self
    {
        return static::active()
            ->byPriority()
            ->where(function ($query) use ($lead) {
                $query->whereNull('lead_source_id')
                      ->orWhere('lead_source_id', $lead->lead_source_id);
            })
            ->first();
    }
}
