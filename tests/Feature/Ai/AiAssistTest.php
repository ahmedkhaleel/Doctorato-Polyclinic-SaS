<?php

namespace Tests\Feature\Ai;

use App\Models\AiFeatureFlag;
use App\Models\Role;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AiAssistTest extends TestCase
{
    use RefreshDatabase;

    private function admin(array $perms = ['ai.view', 'ai.manage']): User
    {
        $r = Role::create(['name' => 'admin', 'display_name_en' => 'A', 'display_name_ar' => 'A',
            'permissions' => $perms, 'is_system' => false]);

        return User::create(['name' => 'U', 'email' => 'a'.uniqid().'@t.com',
            'password' => bcrypt('x'), 'role_id' => $r->id, 'is_active' => true]);
    }

    private function enableAi(): void
    {
        Setting::set('ai_enabled', '1', 'ai');
        Setting::set('ai_openai_api_key', 'sk-test', 'ai');
        Setting::set('ai_phi_redaction', '0', 'ai');
    }

    public function test_workspace_loads(): void
    {
        $this->actingAs($this->admin())->get('/admin/ai/assistant')->assertOk();
    }

    public function test_assist_blocked_when_ai_disabled(): void
    {
        $res = $this->actingAs($this->admin())->postJson('/admin/ai/assist', [
            'feature' => 'translation', 'vars' => ['text' => 'hi', 'target' => 'English'],
        ]);
        $res->assertStatus(422)->assertJson(['ok' => false, 'reason' => 'disabled']);
    }

    public function test_assist_blocked_when_feature_off(): void
    {
        $this->enableAi(); // feature flag 'translation' not enabled
        $this->actingAs($this->admin())->postJson('/admin/ai/assist', [
            'feature' => 'translation', 'vars' => ['text' => 'hi', 'target' => 'English'],
        ])->assertStatus(422)->assertJson(['ok' => false, 'reason' => 'feature_off']);
    }

    public function test_assist_generates_when_enabled(): void
    {
        $this->enableAi();
        AiFeatureFlag::create(['key' => 'seo_content', 'enabled' => true, 'group' => 'wave1']);
        Http::fake(['*/chat/completions' => Http::response([
            'model' => 'gpt-4o-mini',
            'choices' => [['message' => ['content' => 'A great SEO article.']]],
            'usage' => ['prompt_tokens' => 8, 'completion_tokens' => 12],
        ], 200)]);

        $res = $this->actingAs($this->admin())->postJson('/admin/ai/assist', [
            'feature' => 'seo_content',
            'vars' => ['type' => 'article', 'topic' => 'Laser hair removal'],
            'locale' => 'en',
        ]);

        $res->assertOk()->assertJson(['ok' => true, 'text' => 'A great SEO article.', 'tokens' => 20]);
        $this->assertDatabaseHas('ai_request_logs', ['feature' => 'seo_content', 'status' => 'success']);
    }

    public function test_invalid_feature_is_rejected(): void
    {
        $this->actingAs($this->admin())->postJson('/admin/ai/assist', [
            'feature' => 'hacking', 'vars' => [],
        ])->assertStatus(422);
    }

    public function test_requires_ai_permission(): void
    {
        $u = $this->admin([]); // admin role, no ai.* perm
        $this->actingAs($u)->get('/admin/ai/assistant')->assertForbidden();
        $this->actingAs($u)->postJson('/admin/ai/assist', ['feature' => 'translation', 'vars' => []])->assertForbidden();
    }
}
