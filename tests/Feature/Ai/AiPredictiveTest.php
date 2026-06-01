<?php

namespace Tests\Feature\Ai;

use App\Models\AiFeatureFlag;
use App\Models\Role;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AiPredictiveTest extends TestCase
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

    private function fakeChat(string $reply): void
    {
        Http::fake(['*/chat/completions' => Http::response([
            'model' => 'gpt-4o-mini',
            'choices' => [['message' => ['content' => $reply]]],
            'usage' => ['prompt_tokens' => 30, 'completion_tokens' => 15],
        ], 200)]);
    }

    public function test_predictions_page_loads(): void
    {
        $this->actingAs($this->admin())->get('/admin/ai/predictions')->assertOk();
    }

    public function test_no_show_prediction(): void
    {
        $this->enableAi();
        AiFeatureFlag::create(['key' => 'no_show_prediction', 'enabled' => true, 'group' => 'predictive']);
        $this->fakeChat('Send extra reminders for tomorrow high-risk slots.');

        $this->actingAs($this->admin())->postJson('/admin/ai/predictions/no-show')
            ->assertOk()->assertJson(['ok' => true, 'text' => 'Send extra reminders for tomorrow high-risk slots.']);
        $this->assertDatabaseHas('ai_request_logs', ['feature' => 'no_show_prediction']);
    }

    public function test_inventory_reorder(): void
    {
        $this->enableAi();
        AiFeatureFlag::create(['key' => 'inventory_reorder', 'enabled' => true, 'group' => 'predictive']);
        $this->fakeChat('Reorder gloves x200, syringes x500.');

        $this->actingAs($this->admin())->postJson('/admin/ai/predictions/reorder')
            ->assertOk()->assertJson(['ok' => true, 'text' => 'Reorder gloves x200, syringes x500.']);
        $this->assertDatabaseHas('ai_request_logs', ['feature' => 'inventory_reorder']);
    }

    public function test_predictive_blocked_when_disabled(): void
    {
        $this->actingAs($this->admin())->postJson('/admin/ai/predictions/no-show')
            ->assertStatus(422)->assertJson(['reason' => 'disabled']);
    }

    public function test_predictions_require_ai_permission(): void
    {
        $this->actingAs($this->admin([]))->get('/admin/ai/predictions')->assertForbidden();
    }
}
