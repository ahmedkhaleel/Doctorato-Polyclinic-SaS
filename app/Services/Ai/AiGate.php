<?php

namespace App\Services\Ai;

use App\Models\AiFeatureFlag;
use App\Models\Setting;
use App\Services\Ai\Exceptions\AiUnavailableException;
use Illuminate\Support\Facades\RateLimiter;

/** Enforces: global kill-switch, per-feature flag, monthly budget, per-actor rate limit. */
class AiGate
{
    public function __construct(private readonly AiCostMeter $meter) {}

    public function globallyEnabled(): bool
    {
        return (bool) Setting::get('ai_enabled', false);
    }

    /**
     * Throws AiUnavailableException when a call must be blocked.
     * $feature may be null for system checks (e.g. test connection).
     */
    public function authorize(?string $feature, ?string $rateKey = null): void
    {
        if (! $this->globallyEnabled()) {
            throw new AiUnavailableException('disabled', 'AI is disabled by the administrator.');
        }

        if ($feature !== null && ! AiFeatureFlag::isEnabled($feature)) {
            throw new AiUnavailableException('feature_off', "AI feature [{$feature}] is disabled.");
        }

        if ($this->meter->isOverBudget()) {
            throw new AiUnavailableException('over_budget', 'Monthly AI budget has been reached.');
        }

        $perMin = (int) Setting::get('ai_rate_limit_per_min', 20);
        if ($perMin > 0 && $rateKey !== null) {
            $key = 'ai:'.$rateKey;
            if (RateLimiter::tooManyAttempts($key, $perMin)) {
                throw new AiUnavailableException('rate_limited', 'Too many AI requests, please slow down.');
            }
            RateLimiter::hit($key, 60);
        }
    }
}
