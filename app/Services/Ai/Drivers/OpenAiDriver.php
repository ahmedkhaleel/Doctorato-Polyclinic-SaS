<?php

namespace App\Services\Ai\Drivers;

use App\Models\Setting;
use App\Services\Ai\AiResult;
use App\Services\Ai\Contracts\AiDriver;
use App\Services\Ai\Exceptions\AiUnavailableException;
use Illuminate\Support\Facades\Http;

/**
 * OpenAI implementation via the Laravel HTTP client (no external SDK dependency —
 * lighter for shared hosting, and testable with Http::fake()).
 * Credentials/models come from Settings (encrypted key) with config/ai.php fallback.
 */
class OpenAiDriver implements AiDriver
{
    private function apiKey(): ?string
    {
        return Setting::get('ai_openai_api_key') ?: config('ai.openai.api_key');
    }

    private function baseUrl(): string
    {
        return rtrim(config('ai.openai.base_url'), '/');
    }

    private function http()
    {
        $req = Http::withToken($this->apiKey())
            ->timeout((int) config('ai.openai.timeout', 60))
            ->acceptJson();

        if ($org = (Setting::get('ai_openai_org') ?: config('ai.openai.organization'))) {
            $req = $req->withHeaders(['OpenAI-Organization' => $org]);
        }

        return $req;
    }

    public function isReady(): bool
    {
        return ! empty($this->apiKey());
    }

    public function chat(array $messages, array $options = []): AiResult
    {
        if (! $this->isReady()) {
            throw new AiUnavailableException('no_key', 'OpenAI API key is not configured.');
        }

        $model = $options['model'] ?? Setting::get('ai_default_model', config('ai.defaults.model'));

        $payload = [
            'model' => $model,
            'messages' => $messages,
            'temperature' => $options['temperature'] ?? (float) config('ai.defaults.temperature'),
            'max_tokens' => $options['max_tokens'] ?? (int) config('ai.defaults.max_tokens'),
        ];

        $resp = $this->http()->post($this->baseUrl().'/chat/completions', $payload);

        if ($resp->failed()) {
            throw new AiUnavailableException('provider_error', 'OpenAI error: '.$resp->status().' '.$resp->body());
        }

        $json = $resp->json();

        return new AiResult(
            text: $json['choices'][0]['message']['content'] ?? '',
            model: $json['model'] ?? $model,
            promptTokens: (int) ($json['usage']['prompt_tokens'] ?? 0),
            completionTokens: (int) ($json['usage']['completion_tokens'] ?? 0),
            raw: $json,
        );
    }

    public function embed(string|array $input, array $options = []): array
    {
        if (! $this->isReady()) {
            throw new AiUnavailableException('no_key', 'OpenAI API key is not configured.');
        }

        $model = $options['model'] ?? Setting::get('ai_embedding_model', config('ai.defaults.embedding_model'));
        $resp = $this->http()->post($this->baseUrl().'/embeddings', [
            'model' => $model,
            'input' => $input,
        ]);

        if ($resp->failed()) {
            throw new AiUnavailableException('provider_error', 'OpenAI embeddings error: '.$resp->status());
        }

        return array_map(fn ($d) => $d['embedding'], $resp->json('data', []));
    }

    public function ping(): array
    {
        if (! $this->isReady()) {
            return ['ok' => false, 'message' => 'لا يوجد مفتاح OpenAI مُهيّأ. / No OpenAI API key configured.', 'model' => null];
        }

        try {
            $result = $this->chat(
                [['role' => 'user', 'content' => 'Reply with the single word: OK']],
                ['max_tokens' => 5, 'temperature' => 0],
            );

            return ['ok' => true, 'message' => 'تم الاتصال بنجاح. / Connection successful.', 'model' => $result->model];
        } catch (\Throwable $e) {
            return ['ok' => false, 'message' => $e->getMessage(), 'model' => null];
        }
    }

    public function name(): string
    {
        return 'openai';
    }
}
