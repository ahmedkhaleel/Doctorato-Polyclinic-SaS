<?php

namespace Tests\Feature\Admin;

use App\Models\Role;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::firstOrCreate(
            ['name' => 'admin'],
            [
                'display_name_en' => 'Admin',
                'display_name_ar' => 'مدير',
                'permissions' => ['*'],
                'is_system' => true,
            ]
        );

        $this->admin = User::create([
            'name' => 'Admin',
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
            'role_id' => $role->id,
            'is_active' => true,
        ]);
    }

    public function test_admin_can_view_settings_page(): void
    {
        $this->actingAs($this->admin);
        $response = $this->get('/admin/settings');
        $response->assertStatus(200);
    }

    public function test_admin_can_update_consultation_fees(): void
    {
        $this->actingAs($this->admin);

        $response = $this->post('/admin/settings', [
            'default_dermatology_fee' => 150,
            'default_cosmetic_fee' => 250,
            'clinic_name_ar' => 'عيادة',
            'clinic_name_en' => 'Clinic',
        ]);

        $response->assertRedirect();

        $this->assertEquals('150', Setting::get('default_dermatology_fee'));
        $this->assertEquals('250', Setting::get('default_cosmetic_fee'));
    }

    public function test_setting_model_get_with_default(): void
    {
        $value = Setting::get('nonexistent_key', 'default_value');
        $this->assertEquals('default_value', $value);
    }

    public function test_setting_model_set_creates_new(): void
    {
        Setting::set('test_key', 'test_value', 'test');
        $this->assertDatabaseHas('settings', [
            'key' => 'test_key',
            'value' => 'test_value',
            'group' => 'test',
        ]);
    }

    public function test_setting_model_set_updates_existing(): void
    {
        Setting::set('test_key', 'original', 'test');
        Setting::set('test_key', 'updated', 'test');

        $this->assertEquals('updated', Setting::get('test_key'));
        $this->assertEquals(1, Setting::where('key', 'test_key')->count());
    }
}
