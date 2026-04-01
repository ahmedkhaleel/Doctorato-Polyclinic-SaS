<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class LeadScoringRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'name_en',
        'name_ar',
        'event',
        'points',
        'description',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'points' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    // ─── Scopes ──────────────────────────────────────────

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeForEvent(Builder $query, string $event): Builder
    {
        return $query->where('event', $event);
    }

    // ─── Helpers ─────────────────────────────────────────

    /**
     * Apply scoring rule to a lead based on an event.
     */
    public static function applyToLead(Lead $lead, string $event): int
    {
        $rules = static::active()->forEvent($event)->get();
        $totalPoints = 0;

        foreach ($rules as $rule) {
            $totalPoints += $rule->points;
        }

        if ($totalPoints !== 0) {
            $lead->addScore($totalPoints);
        }

        return $totalPoints;
    }
}
