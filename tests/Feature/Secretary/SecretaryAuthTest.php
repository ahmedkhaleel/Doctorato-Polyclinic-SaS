<?php

namespace Tests\Feature\Secretary;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecretaryAuthTest extends TestCase
{
    use RefreshDatabase;

    protected function createSecretaryUser(): User
    {
        $role = Role::firstOrCreate(
            ['name' => 'secretary'],
            [
                'display_name_en' => 'Secretary',
                'display_name_ar' => 'سكرتير',
                'permissions' => [],
                'is_system' => true,
            ]
        );

        return User::create([
            'name' => 'Secretary User',
            'email' => 'secretary@test.com',
            'password' => bcrypt('password'),
            'role_id' => $role->id,
            'is_active' => true,
        ]);
    }

    public function test_secretary_login_page_loads(): void
    {
        $response = $this->get('/secretary/login');
        $response->assertStatus(200);
    }

    public function test_secretary_can_login(): void
    {
        $secretary = $this->createSecretaryUser();

        $response = $this->post('/secretary/login', [
            'email' => 'secretary@test.com',
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
    }

    public function test_secretary_cannot_login_with_wrong_password(): void
    {
        $secretary = $this->createSecretaryUser();

        $response = $this->post('/secretary/login', [
            'email' => 'secretary@test.com',
            'password' => 'wrongpassword',
        ]);

        $this->assertGuest();
    }

    public function test_admin_cannot_access_secretary_panel(): void
    {
        $role = Role::firstOrCreate(
            ['name' => 'admin'],
            [
                'display_name_en' => 'Admin',
                'display_name_ar' => 'مدير',
                'permissions' => ['*'],
                'is_system' => true,
            ]
        );

        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
            'role_id' => $role->id,
            'is_active' => true,
        ]);

        $this->actingAs($admin);

        $response = $this->get('/secretary');
        $response->assertRedirect('/secretary/login');
    }

    public function test_unauthenticated_user_redirected_to_secretary_login(): void
    {
        $response = $this->get('/secretary');
        $response->assertRedirect('/secretary/login');
    }

    public function test_inactive_secretary_cannot_access(): void
    {
        $role = Role::firstOrCreate(
            ['name' => 'secretary'],
            [
                'display_name_en' => 'Secretary',
                'display_name_ar' => 'سكرتير',
                'permissions' => [],
                'is_system' => true,
            ]
        );

        $secretary = User::create([
            'name' => 'Inactive Secretary',
            'email' => 'inactive@test.com',
            'password' => bcrypt('password'),
            'role_id' => $role->id,
            'is_active' => false,
        ]);

        $this->actingAs($secretary);

        $response = $this->get('/secretary');
        $response->assertRedirect('/secretary/login');
    }

    public function test_secretary_can_logout(): void
    {
        $secretary = $this->createSecretaryUser();
        $this->actingAs($secretary);

        $response = $this->post('/secretary/logout');
        $this->assertGuest();
    }
}
