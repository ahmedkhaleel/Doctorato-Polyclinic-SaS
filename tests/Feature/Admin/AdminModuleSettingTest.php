<?php

namespace Tests\Feature\Admin;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminModuleSettingTest extends TestCase
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
            'name' => 'Admin', 'email' => 'admin-mod@test.com',
            'password' => bcrypt('password'), 'role_id' => $role->id, 'is_active' => true,
        ]);
    }

    public function test_can_view_module_settings(): void
    {
        $this->actingAs($this->admin)->get('/admin/settings/modules')->assertOk();
    }

    public function test_can_update_module_settings(): void
    {
        $this->actingAs($this->admin)->post('/admin/settings/modules/dental', [
            'settings' => [
                'lab_name' => 'Test Lab',
            ],
        ])->assertRedirect();
    }

    public function test_can_enable_module(): void
    {
        $this->actingAs($this->admin)->post('/admin/settings/modules/dental/toggle', [
            'enabled' => true,
        ])->assertRedirect();
    }

    public function test_toggle_requires_enabled_field(): void
    {
        $this->actingAs($this->admin)->post('/admin/settings/modules/dental/toggle', [])
            ->assertSessionHasErrors('enabled');
    }
}
