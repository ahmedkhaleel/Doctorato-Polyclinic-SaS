<?php

namespace App\Services\Ai;

use App\Models\AiRequestLog;
use App\Models\Setting;

/** Estimates token cost, writes the audit log, and answers budget questions. */
class AiCostMeter
{
    public function estimate(string $model, int $promptTokens, int $completionTokens): float
    {
        $pricing = config('ai.pricing.'.$model) ?? config('ai.pricing.gpt-4o-mini');

        return round(
            ($promptTokens / 1000) * ($pricing['in'] ?? 0)
            + ($completionTokens / 1000) * ($pricing['out'] ?? 0),
            5,
        );
    }

    public function record(string $feature, AiResult $result, array $extra = []): AiRequestLog
    {
        return AiRequestLog::create(array_merge([
            'feature' => $feature,
            'provider' => Setting::get('ai_provider', 'openai'),
            'model' => $result->model,
            'prompt_tokens' => $result->promptTokens,
            'completion_tokens' => $result->completionTokens,
            'cost_usd' => $this->estimate($result->model, $result->promptTokens, $result->completionTokens),
            'status' => 'success',
        ], $extra));
    }

    public function recordFailure(string $feature, string $status, string $error, array $extra = []): AiRequestLog
    {
        return AiRequestLog::create(array_merge([
            'feature' => $feature,
            'provider' => Setting::get('ai_provider', 'openai'),
            'status' => $status,
            'error' => mb_substr($error, 0, 1000),
        ], $extra));
    }

    /** Total USD spent in the current calendar month. */
    public function monthToDateUsd(): float
    {
        return (float) AiRequestLog::whereYear('created_at', now()->year)
            ->whereMonth('created_at', now()->month)
            ->sum('cost_usd');
    }

    public function budgetUsd(): float
    {
        return (float) Setting::get('ai_monthly_budget_usd', 0);
    }

    public function isOverBudget(): bool
    {
        $budget = $this->budgetUsd();

        return $budget > 0 && $this->monthToDateUsd() >= $budget;
    }
}
