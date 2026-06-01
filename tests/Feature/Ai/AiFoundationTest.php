<?php

namespace Tests\Feature\Ai;

use App\Models\AiFeatureFlag;
use App\Models\AiRequestLog;
use App\Models\Role;
use App\Models\Setting;
use App\Models\User;
use App\Services\Ai\AiManager;
use App\Services\Ai\Exceptions\AiUnavailableException;
use App\Services\Ai\PhiRedactor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AiFoundationTest extends TestCase
{
    use RefreshDatabase;

    private function admin(array $perms = ['ai.view', 'ai.manage', 'ai.prompts', 'ai.logs']): User
    {
        $r = Role::create(['name' => 'admin', 'display_name_en' => 'A', 'display_name_ar' => 'A',
            'permissions' => $perms, 'is_system' => false]);

        return User::create(['name' => 'U', 'email' => 'a'.uniqid().'@t.com',
            'password' => bcrypt('x'), 'role_id' => $r->id, 'is_active' => true]);
    }

    private function enableAi(): void
    {
        Setting::set('ai_enabled', '1', 'ai');
        Setting::set('ai_openai_api_key', 'sk-test-key', 'ai');
        Setting::set('ai_default_model', 'gpt-4o-mini', 'ai');
        Setting::set('ai_phi_redaction', '0', 'ai');
    }

    // ─── Kill-switch / gate ──────────────────────────────────
    public function test_ai_is_disabled_by_default_and_uses_null_driver(): void
    {
        $ai = app(AiManager::class);
        $this->assertSame('null', $ai->driverName());
        $this->assertFalse($ai->isReady());
        $this->assertFalse($ai->testConnection()['ok']);
    }

    public function test_generate_is_blocked_when_disabled(): void
    {
        $this->expectException(AiUnavailableException::class);
        app(AiManager::class)->generate('translation', [['role' => 'user', 'content' => 'hi']]);
    }

    public function test_generate_blocked_when_feature_flag_off(): void
    {
        $this->enableAi();
        // feature flag 'translation' does not exist / disabled
        try {
            app(AiManager::class)->generate('translation', [['role' => 'user', 'content' => 'hi']]);
            $this->fail('Expected block');
        } catch (AiUnavailableException $e) {
            $this->assertSame('feature_off', $e->reason);
        }
    }

    // ─── Happy path with fake HTTP ───────────────────────────
    public function test_generate_succeeds_and_logs_cost_when_enabled(): void
    {
        $this->enableAi();
        AiFeatureFlag::create(['key' => 'translation', 'enabled' => true, 'group' => 'wave1']);

        Http::fake(['*/chat/completions' => Http::response([
            'model' => 'gpt-4o-mini',
            'choices' => [['message' => ['content' => 'Hello']]],
            'usage' => ['prompt_tokens' => 10, 'completion_tokens' => 5],
        ], 200)]);

        $result = app(AiManager::class)->generate('translation', [['role' => 'user', 'content' => 'مرحبا']]);

        $this->assertSame('Hello', $result->text);
        $this->assertSame(15, $result->totalTokens());
        $this->assertDatabaseHas('ai_request_logs', ['feature' => 'translation', 'status' => 'success']);
    }

    public function test_over_budget_blocks_generation(): void
    {
        $this->enableAi();
        Setting::set('ai_monthly_budget_usd', '0.001', 'ai');
        AiFeatureFlag::create(['key' => 'translation', 'enabled' => true, 'group' => 'wave1']);
        AiRequestLog::create(['feature' => 'translation', 'cost_usd' => 1.0, 'status' => 'success']);

        try {
            app(AiManager::class)->generate('translation', [['role' => 'user', 'content' => 'x']]);
            $this->fail('Expected over-budget block');
        } catch (AiUnavailableException $e) {
            $this->assertSame('over_budget', $e->reason);
        }
    }

    // ─── PHI redaction ───────────────────────────────────────
    public function test_phi_redactor_masks_and_restores(): void
    {
        Setting::set('ai_phi_redaction', '1', 'ai');
        $r = app(PhiRedactor::class);
        $text = 'اتصل على 01012345678 أو raed@mail.com';
        $redacted = $r->redact($text);
        $this->assertStringNotContainsString('01012345678', $redacted);
        $this->assertStringNotContainsString('raed@mail.com', $redacted);
        $this->assertSame($text, $r->restore($redacted));
    }

    // ─── Settings secret handling ────────────────────────────
    public function test_api_key_is_encrypted_and_never_exposed(): void
    {
        $this->assertTrue(Setting::isEncryptedKey('ai_openai_api_key'));
        Setting::set('ai_openai_api_key', 'sk-secret', 'ai');
        $raw = \DB::table('settings')->where('key', 'ai_openai_api_key')->value('value');
        $this->assertNotSame('sk-secret', $raw); // stored encrypted
        $this->assertSame('sk-secret', Setting::get('ai_openai_api_key'));
    }

    // ─── Admin screens ───────────────────────────────────────
    public function test_admin_can_view_ai_settings(): void
    {
        $this->actingAs($this->admin())->get('/admin/ai/settings')->assertOk();
    }

    public function test_admin_ai_screens_load(): void
    {
        $u = $this->admin();
        foreach (['/admin/ai/features', '/admin/ai/prompts', '/admin/ai/usage', '/admin/ai/logs'] as $url) {
            $this->actingAs($u)->get($url)->assertOk();
        }
    }

    public function test_update_settings_does_not_overwrite_key_when_blank(): void
    {
        Setting::set('ai_openai_api_key', 'sk-keep', 'ai');
        $this->actingAs($this->admin())->post('/admin/ai/settings', [
            'ai_enabled' => '1', 'ai_openai_api_key' => '', 'ai_default_model' => 'gpt-4o-mini',
        ])->assertRedirect();
        $this->assertSame('sk-keep', Setting::get('ai_openai_api_key'));
    }

    public function test_without_ai_permission_is_forbidden(): void
    {
        $u = $this->admin([]); // admin role but no ai.* permission
        $this->actingAs($u)->get('/admin/ai/settings')->assertForbidden();
    }
}
