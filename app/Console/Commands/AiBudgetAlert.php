<?php

namespace App\Console\Commands;

use App\Models\Setting;
use App\Services\Ai\AiCostMeter;
use App\Services\Notifications\StaffNotifier;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Early-warning for the AI budget: alerts admins once the month-to-date spend
 * crosses ai_budget_alert_pct (default 80%) of ai_monthly_budget_usd. The hard
 * circuit-breaker still stops calls at 100% — this is the heads-up before that.
 * Fires at most once per calendar month (tracked via a Setting flag).
 */
class AiBudgetAlert extends Command
{
    protected $signature = 'ai:budget-alert';

    protected $description = 'Alert admins when AI spend reaches the configured budget alert threshold.';

    public function handle(AiCostMeter $meter): int
    {
        if (! Setting::get('ai_enabled', false)) {
            $this->info('AI disabled — no budget check.');

            return self::SUCCESS;
        }

        $budget = $meter->budgetUsd();
        if ($budget <= 0) {
            $this->info('No AI budget set — nothing to check.');

            return self::SUCCESS;
        }

        $spent = $meter->monthToDateUsd();
        $pct = $budget > 0 ? round($spent / $budget * 100, 1) : 0;
        $threshold = (float) Setting::get('ai_budget_alert_pct', 80);
        $monthFlag = 'ai_budget_alerted_'.now()->format('Ym');

        if ($pct < $threshold) {
            $this->info("AI spend {$pct}% of budget — below {$threshold}% threshold.");

            return self::SUCCESS;
        }

        if (Setting::get($monthFlag) === '1') {
            $this->info('Budget alert already sent this month.');

            return self::SUCCESS;
        }

        $data = [
            'title' => 'تنبيه ميزانية الذكاء الاصطناعي / AI budget alert',
            'message' => "بلغ إنفاق الذكاء الاصطناعي {$pct}% من الميزانية (\${$spent} من \${$budget}). / AI spend reached {$pct}% of budget.",
            'url' => '/admin/ai/usage',
        ];

        try {
            StaffNotifier::toRoles(['super_admin', 'admin'], 'ai_budget_alert', $data);
        } catch (\Throwable $e) {
            Log::warning('[ai.budget-alert] notify failed: '.$e->getMessage());
        }

        Log::warning('[ai.budget-alert] '.$data['message']);
        Setting::set($monthFlag, '1', 'ai');

        $this->warn("AI budget alert sent: {$pct}% of \${$budget}.");

        return self::SUCCESS;
    }
}
