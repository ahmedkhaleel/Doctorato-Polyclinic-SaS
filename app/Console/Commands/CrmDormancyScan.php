<?php

namespace App\Console\Commands;

use App\Models\Lead;
use App\Services\Ai\Exceptions\AiUnavailableException;
use App\Services\Ai\Features\CrmAssistant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

/**
 * CRM-2 — weekly dormancy-risk scan over the active pipeline.
 *
 * Heuristics pre-filter the candidates (cheap, no AI); when the crm_dormancy_risk
 * feature is enabled the shortlist is ranked in ONE batched AI call. When AI is
 * off/unavailable the heuristic ranking is used as-is — the dashboard strip
 * always has data, AI just sharpens it. Result is cached for the dashboard.
 */
class CrmDormancyScan extends Command
{
    public const CACHE_KEY = 'crm_dormancy_risk_list';

    protected $signature = 'crm:dormancy-scan {--limit=20 : Max leads in the ranked list}';

    protected $description = 'Rank pipeline leads at risk of going dormant (heuristic + optional AI), cache for the CRM dashboard';

    public function handle(CrmAssistant $assistant): int
    {
        $limit = max(1, (int) $this->option('limit'));

        // ── Heuristic shortlist: in-pipeline, engaged before, now silent ──
        $candidates = Lead::inPipeline()
            ->where(function ($q) {
                $q->where('last_contacted_at', '<', now()->subDays(5))
                    ->orWhere(function ($q2) {
                        $q2->whereNull('last_contacted_at')->where('created_at', '<', now()->subDays(3));
                    });
            })
            ->with('source:id,name_en')
            ->withCount('activities')
            ->orderByRaw('COALESCE(last_contacted_at, created_at) asc')
            ->limit($limit * 2) // give the ranker headroom
            ->get();

        if ($candidates->isEmpty()) {
            Cache::put(self::CACHE_KEY, ['generated_at' => now()->toIso8601String(), 'mode' => 'heuristic', 'leads' => []], now()->addDays(8));
            $this->info('No dormancy candidates — cached empty list.');

            return self::SUCCESS;
        }

        $rows = $candidates->map(fn (Lead $lead) => [
            'lead_id' => $lead->id,
            'status' => $lead->status,
            'priority' => $lead->priority,
            'score' => $lead->score,
            'days_in_pipeline' => (int) $lead->created_at->diffInDays(now()),
            'days_silent' => (int) ($lead->last_contacted_at ?? $lead->created_at)->diffInDays(now()),
            'activities' => (int) $lead->activities_count,
            'follow_up_overdue' => (bool) ($lead->next_follow_up_at && $lead->next_follow_up_at->isPast()),
        ])->all();

        // ── Try the AI ranking; fall back to the heuristic order ──
        $mode = 'heuristic';
        $ranked = [];
        try {
            $ranked = $assistant->dormancyRank($rows);
            if ($ranked !== []) {
                $mode = 'ai';
            }
        } catch (AiUnavailableException $e) {
            $this->line("AI unavailable ({$e->reason}) — using heuristic ranking.");
        } catch (\Throwable $e) {
            report($e);
            $this->line('AI ranking failed — using heuristic ranking.');
        }

        if ($mode === 'heuristic') {
            $ranked = collect($rows)
                ->map(fn ($r) => [
                    'lead_id' => $r['lead_id'],
                    'risk' => $r['days_silent'] >= 14 || $r['follow_up_overdue'] ? 'high' : ($r['days_silent'] >= 7 ? 'medium' : 'low'),
                    'reason' => $r['follow_up_overdue']
                        ? "متابعة متأخرة وصمت منذ {$r['days_silent']} يوم"
                        : "بدون تواصل منذ {$r['days_silent']} يوم",
                ])
                ->sortBy(fn ($r) => array_search($r['risk'], ['high', 'medium', 'low']))
                ->values()
                ->all();
        }

        $byId = $candidates->keyBy('id');
        $riskOrder = ['high' => 0, 'medium' => 1, 'low' => 2];
        $leads = collect($ranked)
            ->sortBy(fn ($r) => $riskOrder[$r['risk']] ?? 1)
            ->take($limit)
            ->map(function ($r) use ($byId) {
                $lead = $byId[$r['lead_id']] ?? null;

                return $lead ? [
                    'id' => $lead->id,
                    'full_name' => $lead->full_name,
                    'phone' => $lead->phone,
                    'status' => $lead->status,
                    'source' => $lead->source?->name_en,
                    'risk' => $r['risk'],
                    'reason' => $r['reason'],
                ] : null;
            })
            ->filter()
            ->values()
            ->all();

        Cache::put(self::CACHE_KEY, [
            'generated_at' => now()->toIso8601String(),
            'mode' => $mode,
            'leads' => $leads,
        ], now()->addDays(8));

        $this->info(sprintf('Dormancy scan done (%s): %d ranked leads cached.', $mode, count($leads)));

        return self::SUCCESS;
    }
}
