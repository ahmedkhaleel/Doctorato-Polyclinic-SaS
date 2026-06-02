<?php

namespace App\Services\Ai;

use App\Models\AiFeatureFlag;
use App\Models\AiPromptTemplate;
use App\Models\Setting;
use App\Services\Ai\Contracts\AiDriver;
use Illuminate\Support\Facades\Cache;

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

        // Redact PHI from every message before it leaves the system. Vision
        // messages carry array content (text + image parts) — only redact strings.
        $this->redactor->reset();
        $messages = array_map(function ($m) {
            if (is_string($m['content'] ?? null)) {
                $m['content'] = $this->redactor->redact($m['content']);
            }

            return $m;
        }, $messages);

        $options['model'] = $options['model']
            ?? AiFeatureFlag::modelFor($feature)
            ?? Setting::get('ai_default_model', config('ai.defaults.model'));

        // Cache deterministic, PHI-free generations to avoid paying twice for the
        // same request (translations, SEO copy, message drafts...). Only when the
        // caller opts in, caching is enabled, and NO identifier was redacted (so
        // the cached text needs no per-request restore).
        $cacheable = ($options['cacheable'] ?? false)
            && (bool) Setting::get('ai_cache_enabled', true)
            && ! $this->redactor->hasReplacements();
        $cacheKey = 'ai_gen:'.md5($feature.'|'.$options['model'].'|'.json_encode($messages));

        if ($cacheable && ($hit = Cache::get($cacheKey))) {
            $this->meter->record($feature, new AiResult($hit['text'], $hit['model'], 0, 0), [
                'actor_type' => $actor['type'] ?? null,
                'actor_id' => $actor['id'] ?? null,
                'latency_ms' => 0,
                'meta' => ['cached' => true],
            ]);

            return new AiResult($hit['text'], $hit['model']);
        }

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

        // Store PHI-free results for reuse (output has no placeholders to restore).
        if ($cacheable) {
            Cache::put($cacheKey, ['text' => $result->text, 'model' => $result->model],
                (int) Setting::get('ai_cache_ttl', 86400));
        }

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
     * Streamed feature-gated generation. Calls $onDelta($chunk) per token and
     * returns the final AiResult (cost logged at the end). Not cached.
     *
     * @param  array<int,array{role:string,content:string}>  $messages
     */
    public function stream(string $feature, array $messages, callable $onDelta, array $options = []): AiResult
    {
        $actor = $options['actor'] ?? [];
        $this->gate->authorize($feature, $options['rate_key'] ?? null);

        $this->redactor->reset();
        $messages = array_map(function ($m) {
            if (is_string($m['content'] ?? null)) {
                $m['content'] = $this->redactor->redact($m['content']);
            }

            return $m;
        }, $messages);

        $options['model'] = $options['model']
            ?? AiFeatureFlag::modelFor($feature)
            ?? Setting::get('ai_default_model', config('ai.defaults.model'));

        $start = microtime(true);
        try {
            $result = $this->driver->chatStream($messages, $onDelta, $options);
        } catch (\Throwable $e) {
            $this->meter->recordFailure($feature, 'failed', $e->getMessage(), [
                'actor_type' => $actor['type'] ?? null,
                'actor_id' => $actor['id'] ?? null,
                'model' => $options['model'],
            ]);
            throw $e;
        }

        $this->meter->record($feature, $result, [
            'actor_type' => $actor['type'] ?? null,
            'actor_id' => $actor['id'] ?? null,
            'latency_ms' => (int) round((microtime(true) - $start) * 1000),
            'meta' => ['streamed' => true],
        ]);

        return new AiResult($this->redactor->restore($result->text), $result->model);
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

    /**
     * Transcribe audio via Whisper. Gated by the feature flag + budget; logs cost.
     */
    public function transcribe(string $feature, string $contents, string $filename, array $options = []): string
    {
        $this->gate->authorize($feature, $options['rate_key'] ?? null);
        $model = $options['model'] ?? Setting::get('ai_transcribe_model', config('ai.defaults.transcribe_model'));

        $start = microtime(true);
        try {
            $text = $this->driver->transcribe($contents, $filename, $options);
        } catch (\Throwable $e) {
            $this->meter->recordFailure($feature, 'failed', $e->getMessage(), ['model' => $model]);
            throw $e;
        }

        $this->meter->record($feature, new AiResult($text, $model, 0, 0), [
            'latency_ms' => (int) round((microtime(true) - $start) * 1000),
            'actor_type' => $options['actor']['type'] ?? null,
            'actor_id' => $options['actor']['id'] ?? null,
        ]);

        return $this->redactor->restore($text);
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
