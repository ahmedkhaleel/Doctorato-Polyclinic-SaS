<?php

namespace Tests\Feature\Admin;

use App\Models\Role;
use App\Models\User;
use App\Services\ModuleManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AdminModuleToggleProtectionTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::firstOrCreate(
            ['name' => 'admin'],
            ['display_name_en' => 'Admin', 'display_name_ar' => 'مدير', 'permissions' => ['*'], 'is_system' => true]
        );

        $this->admin = User::create([
            'name' => 'Admin', 'email' => 'admin-toggle@test.com',
            'password' => bcrypt('password'), 'role_id' => $role->id, 'is_active' => true,
        ]);

        ModuleManager::clearCache();
    }

    private function disableAllModules(): void
    {
        DB::table('module_settings')
            ->where('key', 'enabled')
            ->update(['value' => '0']);
        ModuleManager::clearCache();
    }

    public function test_can_disable_derma_when_dental_is_active(): void
    {
        $this->disableAllModules();
        ModuleManager::enable('derma');
        ModuleManager::enable('dental');
        ModuleManager::clearCache();

        $response = $this->actingAs($this->admin)
            ->post('/admin/settings/modules/derma/toggle', ['enabled' => false]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
    }

    public function test_cannot_disable_last_medical_module_via_controller(): void
    {
        $this->disableAllModules();
        ModuleManager::enable('derma');
        ModuleManager::clearCache();

        $response = $this->actingAs($this->admin)
            ->post('/admin/settings/modules/derma/toggle', ['enabled' => false]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    public function test_can_enable_any_module(): void
    {
        $response = $this->actingAs($this->admin)
            ->post('/admin/settings/modules/pediatric/toggle', ['enabled' => true]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        ModuleManager::clearCache('pediatric');
        $this->assertTrue(ModuleManager::isEnabled('pediatric'));
    }

    public function test_dental_can_be_sole_active_module(): void
    {
        $this->disableAllModules();
        ModuleManager::enable('dental');
        ModuleManager::clearCache();

        $this->assertTrue(ModuleManager::isEnabled('dental'));
        $this->assertEquals('dental', ModuleManager::getDefaultModule());

        // Cannot disable it — it's the last medical
        $response = $this->actingAs($this->admin)
            ->post('/admin/settings/modules/dental/toggle', ['enabled' => false]);

        $response->assertSessionHas('error');
    }

    public function test_can_switch_from_derma_to_dental_only(): void
    {
        $this->disableAllModules();
        ModuleManager::enable('derma');
        ModuleManager::enable('dental');
        ModuleManager::clearCache();

        // Disable derma
        $this->actingAs($this->admin)
            ->post('/admin/settings/modules/derma/toggle', ['enabled' => false]);

        ModuleManager::clearCache();

        $this->assertFalse(ModuleManager::isEnabled('derma'));
        $this->assertTrue(ModuleManager::isEnabled('dental'));
        $this->assertEquals('dental', ModuleManager::getDefaultModule());
    }
}
