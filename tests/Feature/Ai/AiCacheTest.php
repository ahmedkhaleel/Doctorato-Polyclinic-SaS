<?php

namespace Tests\Feature\Ai;

use App\Models\AiFeatureFlag;
use App\Models\Setting;
use App\Services\Ai\AiManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AiCacheTest extends TestCase
{
    use RefreshDatabase;

    private function setup_ai(bool $cache = true, bool $phi = false): void
    {
        Setting::set('ai_enabled', '1', 'ai');
        Setting::set('ai_openai_api_key', 'sk-test', 'ai');
        Setting::set('ai_cache_enabled', $cache ? '1' : '0', 'ai');
        Setting::set('ai_phi_redaction', $phi ? '1' : '0', 'ai');
        AiFeatureFlag::create(['key' => 'translation', 'enabled' => true, 'group' => 'wave1']);
        Http::fake(['*/chat/completions' => Http::response([
            'model' => 'gpt-4o-mini',
            'choices' => [['message' => ['content' => 'Hello']]],
            'usage' => ['prompt_tokens' => 5, 'completion_tokens' => 3],
        ], 200)]);
    }

    private function gen(string $content = 'مرحبا'): string
    {
        return app(AiManager::class)->generate('translation',
            [['role' => 'user', 'content' => $content]],
            ['cacheable' => true])->text;
    }

    public function test_identical_request_is_served_from_cache(): void
    {
        $this->setup_ai();
        $this->assertSame('Hello', $this->gen());
        $this->assertSame('Hello', $this->gen()); // second = cache hit
        Http::assertSentCount(1); // provider hit only once
    }

    public function test_cache_disabled_calls_provider_each_time(): void
    {
        $this->setup_ai(cache: false);
        $this->gen();
        $this->gen();
        Http::assertSentCount(2);
    }

    public function test_phi_requests_are_not_cached(): void
    {
        $this->setup_ai(phi: true);
        $this->gen('اتصل على 01012345678'); // contains a phone → redacted → not cached
        $this->gen('اتصل على 01012345678');
        Http::assertSentCount(2);
    }

    public function test_cached_hit_is_logged_with_zero_cost(): void
    {
        $this->setup_ai();
        $this->gen();
        $this->gen();
        $this->assertDatabaseHas('ai_request_logs', ['feature' => 'translation', 'cost_usd' => 0]);
    }
}
