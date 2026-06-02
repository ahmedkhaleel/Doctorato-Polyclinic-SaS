<?php

namespace Tests\Feature\Ai;

use App\Models\AiFeatureFlag;
use App\Models\Setting;
use App\Services\Ai\AiManager;
use App\Services\Ai\Exceptions\AiUnavailableException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AiStreamTest extends TestCase
{
    use RefreshDatabase;

    private function enableAi(): void
    {
        Setting::set('ai_enabled', '1', 'ai');
        Setting::set('ai_openai_api_key', 'sk-test', 'ai');
        Setting::set('ai_phi_redaction', '0', 'ai');
        AiFeatureFlag::create(['key' => 'patient_assistant', 'enabled' => true, 'group' => 'patient']);
    }

    private function fakeSse(): void
    {
        // Minimal OpenAI streaming SSE body: three deltas then [DONE].
        $sse = implode("\n", [
            'data: '.json_encode(['choices' => [['delta' => ['content' => 'Hel']]]]),
            'data: '.json_encode(['choices' => [['delta' => ['content' => 'lo ']]]]),
            'data: '.json_encode(['choices' => [['delta' => ['content' => 'world']]]]),
            'data: [DONE]',
            '',
        ]);
        Http::fake(['*/chat/completions' => Http::response($sse, 200)]);
    }

    public function test_stream_assembles_deltas_and_invokes_callback(): void
    {
        $this->enableAi();
        $this->fakeSse();

        $chunks = [];
        $result = app(AiManager::class)->stream('patient_assistant',
            [['role' => 'user', 'content' => 'hi']],
            function ($d) use (&$chunks) {
                $chunks[] = $d;
            });

        $this->assertSame('Hello world', $result->text);
        $this->assertSame(['Hel', 'lo ', 'world'], $chunks);
        $this->assertDatabaseHas('ai_request_logs', ['feature' => 'patient_assistant', 'status' => 'success']);
    }

    public function test_stream_blocked_when_disabled(): void
    {
        $this->expectException(AiUnavailableException::class);
        app(AiManager::class)->stream('patient_assistant', [['role' => 'user', 'content' => 'hi']], fn ($d) => null);
    }
}
