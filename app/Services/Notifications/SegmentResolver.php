<?php

namespace App\Services\Notifications;

use App\Models\Patient;
use Illuminate\Database\Eloquent\Builder;

/**
 * Turns a campaign's audience rules into a Patient query. Rules (all optional):
 *   gender: male|female
 *   age_min / age_max: integer years
 *   created_within_days: N  (new patients in the last N days)
 *   inactive_days: N         (no visit in the last N days)
 *   marketing_channel: email|sms|whatsapp  (only patients opted into marketing there)
 */
class SegmentResolver
{
    public function query(array $rules): Builder
    {
        $q = Patient::query()->where('is_active', true)->whereNotNull('phone');

        if (! empty($rules['gender']) && in_array($rules['gender'], ['male', 'female'], true)) {
            $q->where('gender', $rules['gender']);
        }

        if (! empty($rules['age_min'])) {
            $q->whereNotNull('date_of_birth')->whereDate('date_of_birth', '<=', now()->subYears((int) $rules['age_min'])->toDateString());
        }
        if (! empty($rules['age_max'])) {
            $q->whereNotNull('date_of_birth')->whereDate('date_of_birth', '>=', now()->subYears((int) $rules['age_max'] + 1)->toDateString());
        }

        if (! empty($rules['created_within_days'])) {
            $q->where('created_at', '>=', now()->subDays((int) $rules['created_within_days']));
        }

        if (! empty($rules['inactive_days'])) {
            $cutoff = now()->subDays((int) $rules['inactive_days'])->toDateString();
            $q->whereDoesntHave('visits', fn ($v) => $v->whereDate('visit_date', '>=', $cutoff));
        }

        if (! empty($rules['marketing_channel']) && in_array($rules['marketing_channel'], ['email', 'sms', 'whatsapp'], true)) {
            $col = "notify_{$rules['marketing_channel']}_marketing";
            $q->where($col, true);
        }

        return $q;
    }

    public function count(array $rules): int
    {
        return $this->query($rules)->count();
    }
}
