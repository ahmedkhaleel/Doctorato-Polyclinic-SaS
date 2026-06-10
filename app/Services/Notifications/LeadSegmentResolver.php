<?php

namespace App\Services\Notifications;

use App\Models\Lead;
use Illuminate\Database\Eloquent\Builder;

/**
 * CRM-3 — turns campaign audience rules into a Lead query (the lead-side
 * sibling of SegmentResolver). Used when rules.audience === 'leads'.
 * Rules (all optional):
 *   statuses: array of pipeline statuses (default: all in-pipeline)
 *   priority: 1|2|3
 *   module: medical module slug
 *   lead_source_id: integer
 *   created_within_days: N
 *   inactive_days: N (no contact in the last N days)
 */
class LeadSegmentResolver
{
    public function query(array $rules): Builder
    {
        $q = Lead::query()->whereNotNull('phone');

        $statuses = array_values(array_intersect((array) ($rules['statuses'] ?? []), Lead::STATUSES));
        if ($statuses !== []) {
            $q->whereIn('status', $statuses);
        } else {
            $q->inPipeline(); // never blast converted/lost by default
        }

        if (! empty($rules['priority']) && in_array((int) $rules['priority'], [1, 2, 3], true)) {
            $q->where('priority', (int) $rules['priority']);
        }

        if (! empty($rules['module'])) {
            $q->where('module', $rules['module']);
        }

        if (! empty($rules['lead_source_id'])) {
            $q->where('lead_source_id', (int) $rules['lead_source_id']);
        }

        if (! empty($rules['created_within_days'])) {
            $q->where('created_at', '>=', now()->subDays((int) $rules['created_within_days']));
        }

        if (! empty($rules['inactive_days'])) {
            $cutoff = now()->subDays((int) $rules['inactive_days']);
            $q->where(function ($w) use ($cutoff) {
                $w->where('last_contacted_at', '<', $cutoff)
                    ->orWhere(fn ($w2) => $w2->whereNull('last_contacted_at')->where('created_at', '<', $cutoff));
            });
        }

        return $q;
    }

    public function count(array $rules): int
    {
        return $this->query($rules)->count();
    }
}
