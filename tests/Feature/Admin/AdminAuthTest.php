<?php

namespace Tests\Feature\Admin;

use App\Models\Doctor;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAuthTest extends TestCase
{
    use RefreshDatabase;

    protected function createAdminUser(): User
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

        return User::create([
            'name' => 'Admin User',
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
            'role_id' => $role->id,
            'is_active' => true,
        ]);
    }

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

    protected function createDoctorUser(): array
    {
        $role = Role::firstOrCreate(
            ['name' => 'doctor'],
            [
                'display_name_en' => 'Doctor',
                'display_name_ar' => 'طبيب',
                'permissions' => [],
                'is_system' => true,
            ]
        );

        $user = User::create([
            'name' => 'Doctor User',
            'email' => 'doctor@test.com',
            'password' => bcrypt('password'),
            'role_id' => $role->id,
            'is_active' => true,
        ]);

        $doctor = Doctor::create([
            'name_ar' => 'دكتور تست',
            'name_en' => 'Doctor Test',
            'user_id' => $user->id,
            'status' => 'active',
        ]);

        return ['user' => $user, 'doctor' => $doctor];
    }

    // ─── Login Page ────────────────────────────────────
    public function test_admin_login_page_loads(): void
    {
        $response = $this->get('/admin/login');
        $response->assertStatus(200);
    }

    public function test_admin_can_login_with_valid_credentials(): void
    {
        $admin = $this->createAdminUser();

        $response = $this->post('/admin/login', [
            'login' => 'admin@test.com',
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect();
    }

    public function test_admin_cannot_login_with_wrong_password(): void
    {
        $admin = $this->createAdminUser();

        $response = $this->post('/admin/login', [
            'email' => 'admin@test.com',
            'password' => 'wrongpassword',
        ]);

        $this->assertGuest();
    }

    public function test_secretary_cannot_access_admin_panel(): void
    {
        $secretary = $this->createSecretaryUser();

        $this->actingAs($secretary);

        $response = $this->get('/admin');
        $response->assertRedirect('/admin/login');
    }

    public function test_doctor_cannot_access_admin_panel(): void
    {
        $data = $this->createDoctorUser();

        $this->actingAs($data['user']);

        $response = $this->get('/admin');
        $response->assertRedirect('/admin/login');
    }

    public function test_unauthenticated_user_redirected_to_login(): void
    {
        $response = $this->get('/admin');
        $response->assertRedirect('/admin/login');
    }

    public function test_inactive_admin_cannot_login(): void
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

        $user = User::create([
            'name' => 'Inactive Admin',
            'email' => 'inactive@test.com',
            'password' => bcrypt('password'),
            'role_id' => $role->id,
            'is_active' => false,
        ]);

        $this->actingAs($user);

        $response = $this->get('/admin');
        $response->assertRedirect('/admin/login');
    }

    public function test_admin_can_logout(): void
    {
        $admin = $this->createAdminUser();
        $this->actingAs($admin);

        $response = $this->post('/admin/logout');

        $this->assertGuest();
    }
}
