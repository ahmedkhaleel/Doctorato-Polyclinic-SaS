<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiFeatureFlag;
use App\Models\AiPromptTemplate;
use App\Models\AiRequestLog;
use App\Models\Setting;
use App\Services\Ai\AiCostMeter;
use App\Services\Ai\AiManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AiController extends Controller
{
    private const KEYS = [
        'ai_enabled', 'ai_provider', 'ai_default_model', 'ai_clinical_model',
        'ai_vision_model', 'ai_embedding_model', 'ai_transcribe_model',
        'ai_monthly_budget_usd', 'ai_budget_alert_pct', 'ai_rate_limit_per_min',
        'ai_phi_redaction', 'ai_log_prompts', 'ai_patient_consent_required',
    ];

    // ─── Settings ────────────────────────────────────────────
    public function settings(AiCostMeter $meter): Response
    {
        $values = [];
        foreach (self::KEYS as $k) {
            $values[$k] = Setting::get($k);
        }
        // Never expose the secret; only whether it is set.
        $values['ai_openai_api_key_set'] = ! empty(Setting::get('ai_openai_api_key'));
        $values['ai_openai_org'] = Setting::get('ai_openai_org');

        return Inertia::render('Admin/Ai/Settings', [
            'settings' => $values,
            'usage' => [
                'month_to_date_usd' => round($meter->monthToDateUsd(), 4),
                'budget_usd' => $meter->budgetUsd(),
                'over_budget' => $meter->isOverBudget(),
            ],
            'driverReady' => app(AiManager::class)->isReady(),
        ]);
    }

    public function updateSettings(Request $request): RedirectResponse
    {
        $data = $request->only(array_merge(self::KEYS, ['ai_openai_api_key', 'ai_openai_org']));

        foreach (self::KEYS as $key) {
            if (array_key_exists($key, $data)) {
                Setting::set($key, (string) ($data[$key] ?? ''), 'ai');
            }
        }
        if (array_key_exists('ai_openai_org', $data)) {
            Setting::set('ai_openai_org', (string) ($data['ai_openai_org'] ?? ''), 'ai');
        }
        // Only overwrite the secret when a non-empty value is submitted.
        if (! empty($data['ai_openai_api_key'])) {
            Setting::set('ai_openai_api_key', $data['ai_openai_api_key'], 'ai');
        }

        return back()->with('success', __('Settings updated successfully'));
    }

    public function testConnection(AiManager $ai): RedirectResponse
    {
        $result = $ai->testConnection();

        return back()->with($result['ok'] ? 'success' : 'error', $result['message']);
    }

    // ─── Feature flags ───────────────────────────────────────
    public function features(): Response
    {
        return Inertia::render('Admin/Ai/Features', [
            'features' => AiFeatureFlag::orderBy('group')->orderBy('display_order')->get(),
        ]);
    }

    public function updateFeatures(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'features' => 'array',
            'features.*.id' => 'required|integer|exists:ai_feature_flags,id',
            'features.*.enabled' => 'boolean',
            'features.*.model_override' => 'nullable|string|max:60',
        ]);

        foreach ($validated['features'] ?? [] as $row) {
            AiFeatureFlag::where('id', $row['id'])->update([
                'enabled' => (bool) ($row['enabled'] ?? false),
                'model_override' => $row['model_override'] ?? null,
            ]);
        }

        return back()->with('success', __('Settings updated successfully'));
    }

    // ─── Prompt templates ────────────────────────────────────
    public function prompts(): Response
    {
        return Inertia::render('Admin/Ai/Prompts', [
            'prompts' => AiPromptTemplate::orderBy('feature')->orderBy('locale')->get(),
        ]);
    }

    public function updatePrompt(Request $request, AiPromptTemplate $prompt): RedirectResponse
    {
        $validated = $request->validate([
            'system_prompt' => 'nullable|string|max:8000',
            'user_template' => 'nullable|string|max:8000',
            'is_active' => 'boolean',
        ]);
        $validated['version'] = $prompt->version + 1;
        $prompt->update($validated);

        return back()->with('success', __('Settings updated successfully'));
    }

    // ─── Usage analytics ─────────────────────────────────────
    public function usage(AiCostMeter $meter): Response
    {
        $byFeature = AiRequestLog::selectRaw('feature, COUNT(*) as calls, SUM(cost_usd) as cost, SUM(prompt_tokens+completion_tokens) as tokens')
            ->whereYear('created_at', now()->year)->whereMonth('created_at', now()->month)
            ->groupBy('feature')->orderByDesc('cost')->get();

        return Inertia::render('Admin/Ai/Usage', [
            'monthToDateUsd' => round($meter->monthToDateUsd(), 4),
            'budgetUsd' => $meter->budgetUsd(),
            'overBudget' => $meter->isOverBudget(),
            'byFeature' => $byFeature,
            'totalCalls' => (int) AiRequestLog::whereYear('created_at', now()->year)->whereMonth('created_at', now()->month)->count(),
        ]);
    }

    // ─── Natural-language analytics (Wave 4) ─────────────────
    public function insights(\App\Services\Ai\Features\InsightAnalyst $analyst): Response
    {
        return Inertia::render('Admin/Ai/Insights', [
            'snapshot' => $analyst->snapshot(),
        ]);
    }

    public function analyticsAsk(Request $request, \App\Services\Ai\Features\InsightAnalyst $analyst): \Illuminate\Http\JsonResponse
    {
        $validated = $request->validate(['question' => 'required|string|max:500']);

        try {
            $result = $analyst->ask($validated['question'], [
                'locale' => app()->getLocale(),
                'rate_key' => 'user:'.$request->user()?->id,
                'actor' => ['type' => 'user', 'id' => $request->user()?->id],
            ]);
        } catch (\App\Services\Ai\Exceptions\AiUnavailableException $e) {
            return response()->json(['ok' => false, 'reason' => $e->reason, 'message' => $e->getMessage()], 422);
        }

        return response()->json(['ok' => true, 'text' => $result->text]);
    }

    // ─── Request logs ────────────────────────────────────────
    public function logs(Request $request): Response
    {
        $logs = AiRequestLog::query()
            ->when($request->input('feature'), fn ($q, $f) => $q->where('feature', $f))
            ->when($request->input('status'), fn ($q, $s) => $q->where('status', $s))
            ->latest()->paginate(30)->withQueryString();

        return Inertia::render('Admin/Ai/Logs', [
            'logs' => $logs,
            'filters' => $request->only(['feature', 'status']),
        ]);
    }
}
