<?php

namespace Tests\Feature\Doctor;

use App\Models\Doctor;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DoctorAuthTest extends TestCase
{
    use RefreshDatabase;

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

    public function test_doctor_login_page_loads(): void
    {
        $response = $this->get('/doctor/login');
        $response->assertStatus(200);
    }

    public function test_doctor_can_login(): void
    {
        $data = $this->createDoctorUser();

        $response = $this->post('/doctor/login', [
            'email' => 'doctor@test.com',
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
    }

    public function test_doctor_cannot_login_with_wrong_password(): void
    {
        $data = $this->createDoctorUser();

        $response = $this->post('/doctor/login', [
            'email' => 'doctor@test.com',
            'password' => 'wrongpassword',
        ]);

        $this->assertGuest();
    }

    public function test_user_without_doctor_profile_cannot_access(): void
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
            'name' => 'No Profile',
            'email' => 'noprofile@test.com',
            'password' => bcrypt('password'),
            'role_id' => $role->id,
            'is_active' => true,
        ]);

        // No doctor profile created
        $this->actingAs($user);

        $response = $this->get('/doctor');
        $response->assertRedirect('/doctor/login');
    }

    public function test_inactive_doctor_profile_cannot_access(): void
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
            'name' => 'Inactive Doctor',
            'email' => 'inactive@test.com',
            'password' => bcrypt('password'),
            'role_id' => $role->id,
            'is_active' => true,
        ]);

        Doctor::create([
            'name_ar' => 'دكتور غير نشط',
            'name_en' => 'Inactive Doctor',
            'user_id' => $user->id,
            'status' => 'inactive',
        ]);

        $this->actingAs($user);

        $response = $this->get('/doctor');
        $response->assertRedirect('/doctor/login');
    }

    public function test_doctor_can_access_dashboard(): void
    {
        $data = $this->createDoctorUser();
        $this->actingAs($data['user']);

        $response = $this->get('/doctor');
        $response->assertStatus(200);
    }

    public function test_doctor_can_logout(): void
    {
        $data = $this->createDoctorUser();
        $this->actingAs($data['user']);

        $response = $this->post('/doctor/logout');
        $this->assertGuest();
    }

    public function test_secretary_cannot_access_doctor_panel(): void
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

        $user = User::create([
            'name' => 'Secretary',
            'email' => 'secretary@test.com',
            'password' => bcrypt('password'),
            'role_id' => $role->id,
            'is_active' => true,
        ]);

        $this->actingAs($user);

        $response = $this->get('/doctor');
        $response->assertRedirect('/doctor/login');
    }
}
