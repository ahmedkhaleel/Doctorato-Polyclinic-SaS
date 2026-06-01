<?php

namespace App\Services\Ai;

use App\Models\AiFeatureFlag;
use App\Models\AiPromptTemplate;
use App\Models\Setting;
use App\Services\Ai\Contracts\AiDriver;

/**
 * Single entry point for all AI features. Resolves the driver, applies the gate
 * (kill-switch / feature flag / budget / rate limit), redacts PHI, calls the
 * provider, logs cost, and restores PHI in the output.
 */
class AiManager
{
    public function __construct(
        private readonly AiDriver $driver,
        private readonly AiGate $gate,
        private readonly AiCostMeter $meter,
        private readonly PhiRedactor $redactor,
    ) {}

    public function driverName(): string
    {
        return $this->driver->name();
    }

    public function isReady(): bool
    {
        return $this->gate->globallyEnabled() && $this->driver->isReady();
    }

    /**
     * Run a feature-gated chat generation.
     *
     * @param  array<int,array{role:string,content:string}>  $messages
     */
    public function generate(string $feature, array $messages, array $options = []): AiResult
    {
        $rateKey = $options['rate_key'] ?? null;
        $actor = $options['actor'] ?? [];

        $this->gate->authorize($feature, $rateKey);

        // Redact PHI from every message before it leaves the system.
        $this->redactor->reset();
        $messages = array_map(function ($m) {
            $m['content'] = $this->redactor->redact($m['content'] ?? '');

            return $m;
        }, $messages);

        $options['model'] = $options['model']
            ?? AiFeatureFlag::modelFor($feature)
            ?? Setting::get('ai_default_model', config('ai.defaults.model'));

        $start = microtime(true);
        try {
            $result = $this->driver->chat($messages, $options);
        } catch (\Throwable $e) {
            $this->meter->recordFailure($feature, 'failed', $e->getMessage(), [
                'actor_type' => $actor['type'] ?? null,
                'actor_id' => $actor['id'] ?? null,
                'model' => $options['model'],
            ]);
            throw $e;
        }
        $latency = (int) round((microtime(true) - $start) * 1000);

        $logPrompts = (bool) Setting::get('ai_log_prompts', false);
        $this->meter->record($feature, $result, [
            'actor_type' => $actor['type'] ?? null,
            'actor_id' => $actor['id'] ?? null,
            'latency_ms' => $latency,
            'meta' => $logPrompts ? ['messages' => $messages] : null,
        ]);

        // Restore any redacted identifiers in the model output.
        return new AiResult(
            text: $this->redactor->restore($result->text),
            model: $result->model,
            promptTokens: $result->promptTokens,
            completionTokens: $result->completionTokens,
            raw: $result->raw,
        );
    }

    /**
     * Create embeddings for one or more strings. Gated by the global kill-switch
     * (no per-feature flag — used internally by the RAG index/search).
     *
     * @param  string|array<int,string>  $input
     * @return array<int,array<float>>
     */
    public function embed(string|array $input, array $options = []): array
    {
        $this->gate->authorize(null);

        return $this->driver->embed($input, $options);
    }

    /** Resolve the admin-editable prompt for a feature/locale. */
    public function prompt(string $feature, string $locale = 'ar'): ?AiPromptTemplate
    {
        return AiPromptTemplate::resolve($feature, $locale);
    }

    /** Connectivity check for the admin Settings screen (bypasses feature flag). */
    public function testConnection(): array
    {
        if (! $this->gate->globallyEnabled()) {
            return ['ok' => false, 'message' => 'AI معطّل من الإعدادات. فعّله أولًا. / AI is disabled in settings.', 'model' => null];
        }

        return $this->driver->ping();
    }
}
