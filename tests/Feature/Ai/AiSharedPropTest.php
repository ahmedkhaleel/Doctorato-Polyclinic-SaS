<?php

namespace Tests\Feature\Ai;

use App\Models\AiFeatureFlag;
use App\Models\Role;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AiSharedPropTest extends TestCase
{
    use RefreshDatabase;

    private function admin(array $perms = ['ai.view', 'settings.view']): User
    {
        $r = Role::create(['name' => 'admin', 'display_name_en' => 'A', 'display_name_ar' => 'A',
            'permissions' => $perms, 'is_system' => false]);

        return User::create(['name' => 'U', 'email' => 'a'.uniqid().'@t.com',
            'password' => bcrypt('x'), 'role_id' => $r->id, 'is_active' => true]);
    }

    public function test_shared_ai_disabled_by_default(): void
    {
        $this->actingAs($this->admin())->get('/admin/ai/settings')
            ->assertOk()
            ->assertInertia(fn (Assert $p) => $p->where('ai.enabled', false)->where('ai.features', []));
    }

    public function test_shared_ai_lists_enabled_features_when_ready(): void
    {
        Setting::set('ai_enabled', '1', 'ai');
        Setting::set('ai_openai_api_key', 'sk-test', 'ai');
        AiFeatureFlag::create(['key' => 'seo_content', 'enabled' => true, 'group' => 'wave1']);
        AiFeatureFlag::create(['key' => 'translation', 'enabled' => false, 'group' => 'wave1']);

        $this->actingAs($this->admin())->get('/admin/ai/settings')
            ->assertOk()
            ->assertInertia(fn (Assert $p) => $p->where('ai.enabled', true)
                ->where('ai.features', ['seo_content'])); // only the enabled flag
    }

    public function test_shared_ai_off_without_ai_permission(): void
    {
        Setting::set('ai_enabled', '1', 'ai');
        Setting::set('ai_openai_api_key', 'sk-test', 'ai');
        // settings.view only (no ai.view/ai.doctor) → ai shared prop disabled
        $this->actingAs($this->admin(['settings.view']))->get('/admin/settings')
            ->assertOk()
            ->assertInertia(fn (Assert $p) => $p->where('ai.enabled', false));
    }
}
