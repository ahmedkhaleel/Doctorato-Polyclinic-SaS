<?php

namespace Tests\Feature\Ai;

use App\Models\AiFeatureFlag;
use App\Models\AiRequestLog;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AiOperationalTest extends TestCase
{
    use RefreshDatabase;

    private function enableAi(): void
    {
        Setting::set('ai_enabled', '1', 'ai');
        Setting::set('ai_openai_api_key', 'sk-test', 'ai');
        Setting::set('ai_monthly_budget_usd', '100', 'ai');
        Setting::set('ai_budget_alert_pct', '80', 'ai');
    }

    private function flag(): string
    {
        return 'ai_budget_alerted_'.now()->format('Ym');
    }

    // ─── Budget alert ────────────────────────────────────────
    public function test_no_alert_below_threshold(): void
    {
        $this->enableAi();
        AiRequestLog::create(['feature' => 'x', 'cost_usd' => 50, 'status' => 'success']); // 50% of 100

        $this->artisan('ai:budget-alert')->assertExitCode(0);
        $this->assertNull(Setting::get($this->flag()));
    }

    public function test_alert_fires_at_threshold(): void
    {
        $this->enableAi();
        AiRequestLog::create(['feature' => 'x', 'cost_usd' => 85, 'status' => 'success']); // 85% >= 80%

        $this->artisan('ai:budget-alert')->assertExitCode(0);
        $this->assertSame('1', Setting::get($this->flag()));
    }

    public function test_alert_fires_only_once_per_month(): void
    {
        $this->enableAi();
        Setting::set($this->flag(), '1', 'ai'); // already alerted
        AiRequestLog::create(['feature' => 'x', 'cost_usd' => 95, 'status' => 'success']);

        // Should not error and should remain '1' (no re-alert path).
        $this->artisan('ai:budget-alert')->assertExitCode(0);
        $this->assertSame('1', Setting::get($this->flag()));
    }

    public function test_no_alert_when_ai_disabled(): void
    {
        Setting::set('ai_monthly_budget_usd', '100', 'ai');
        AiRequestLog::create(['feature' => 'x', 'cost_usd' => 95, 'status' => 'success']);
        $this->artisan('ai:budget-alert')->assertExitCode(0);
        $this->assertNull(Setting::get($this->flag()));
    }

    // ─── Embeddings rebuild ──────────────────────────────────
    public function test_rebuild_skips_when_disabled(): void
    {
        $this->artisan('ai:rebuild-embeddings')->assertExitCode(0);
        $this->assertSame(0, \App\Models\AiEmbedding::count());
    }

    public function test_rebuild_indexes_when_enabled(): void
    {
        $this->enableAi();
        AiFeatureFlag::create(['key' => 'patient_assistant', 'enabled' => true, 'group' => 'patient']);
        \DB::table('faqs')->insert([
            'question_ar' => 'ما هي ساعات العمل؟', 'question_en' => 'What are your hours?',
            'answer_ar' => 'من 9 صباحاً', 'answer_en' => 'From 9am',
            'display_order' => 1, 'created_at' => now(), 'updated_at' => now(),
        ]);
        Http::fake(['*/embeddings' => Http::response(['data' => [['embedding' => [0.1, 0.2, 0.3]]]], 200)]);

        $this->artisan('ai:rebuild-embeddings')->assertExitCode(0);
        $this->assertGreaterThan(0, \App\Models\AiEmbedding::count());
    }
}
