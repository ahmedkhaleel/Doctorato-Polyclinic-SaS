<?php

namespace Tests\Feature\Neuropsych;

use App\Models\AiFeatureFlag;
use App\Models\Role;
use App\Models\User;
use App\Services\ModuleManager;
use Database\Seeders\AiSettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * NP7 — admin dashboard/settings for both modules and the neuropsych AI
 * feature-flag registry.
 */
class Np7AdminAiTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        ModuleManager::flushStaticCache();

        $role = Role::firstOrCreate(['name' => 'admin'], ['display_name_en' => 'Admin', 'display_name_ar' => 'مدير', 'permissions' => ['*'], 'is_system' => true]);
        $role->update(['permissions' => ['*']]);
        $this->admin = User::create(['name' => 'Admin', 'email' => 'np7-admin@test.com', 'password' => bcrypt('x'), 'role_id' => $role->id, 'is_active' => true]);

        ModuleManager::enable('psychiatry');
        ModuleManager::enable('neurology');
        ModuleManager::flushStaticCache();
    }

    public function test_admin_dashboards_render_for_both_modules(): void
    {
        $this->actingAs($this->admin)->get('/admin/psychiatry')->assertOk();
        $this->actingAs($this->admin)->get('/admin/neurology')->assertOk();
    }

    public function test_admin_settings_render_and_toggle(): void
    {
        $this->actingAs($this->admin)->get('/admin/psychiatry/settings')->assertOk();

        $this->actingAs($this->admin)->post('/admin/psychiatry/settings', ['enabled' => false])->assertRedirect();
        ModuleManager::flushStaticCache();
        $this->assertFalse(ModuleManager::isEnabled('psychiatry'));
    }

    public function test_neuropsych_ai_feature_flags_are_registered(): void
    {
        $this->seed(AiSettingsSeeder::class);

        foreach (['np_note_assist', 'np_risk_flag', 'np_session_transcription'] as $key) {
            $this->assertDatabaseHas('ai_feature_flags', ['key' => $key]);
            // Disabled by default.
            $this->assertFalse((bool) AiFeatureFlag::where('key', $key)->value('enabled'));
        }
    }
}
