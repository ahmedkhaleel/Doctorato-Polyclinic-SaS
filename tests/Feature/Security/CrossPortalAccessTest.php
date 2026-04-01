<?php

namespace Tests\Feature\Security;

use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CrossPortalAccessTest extends TestCase
{
    use RefreshDatabase;

    private function createUserWithRole(string $roleName): User
    {
        $role = Role::firstOrCreate(
            ['name' => $roleName],
            [
                'display_name_en' => ucfirst($roleName),
                'display_name_ar' => $roleName,
                'permissions' => $roleName === 'admin' ? ['*'] : [],
                'is_system' => true,
            ]
        );

        return User::create([
            'name' => "{$roleName} User",
            'email' => "{$roleName}@test.com",
            'password' => bcrypt('password'),
            'role_id' => $role->id,
            'is_active' => true,
        ]);
    }

    public function test_patient_cannot_access_admin_patients(): void
    {
        $user = $this->createUserWithRole('patient');

        $this->actingAs($user);
        // Admin patients page should block non-admins (redirect or 403)
        $response = $this->get('/admin/patients');
        $this->assertContains($response->getStatusCode(), [302, 403]);
    }

    public function test_patient_cannot_access_secretary_portal(): void
    {
        $user = $this->createUserWithRole('patient');

        $this->actingAs($user);
        $response = $this->get('/secretary');
        $response->assertRedirect('/secretary/login');
    }

    public function test_secretary_cannot_access_admin_portal(): void
    {
        $user = $this->createUserWithRole('secretary');

        $this->actingAs($user);
        $response = $this->get('/admin');
        $response->assertRedirect('/admin/login');
    }

    public function test_admin_cannot_access_doctor_portal(): void
    {
        $user = $this->createUserWithRole('admin');

        $this->actingAs($user);
        $response = $this->get('/doctor');
        $response->assertRedirect('/doctor/login');
    }

    public function test_doctor_role_check_in_middleware(): void
    {
        // Create a user with secretary role but try accessing doctor portal
        $user = $this->createUserWithRole('secretary');

        $this->actingAs($user);
        $response = $this->get('/doctor');
        $response->assertRedirect('/doctor/login');
    }

    public function test_admin_cannot_access_patient_portal(): void
    {
        // Create admin user trying to access patient portal
        $user = $this->createUserWithRole('admin');

        $this->actingAs($user);
        $response = $this->get('/en/patient');
        // Should redirect non-patients away from the patient portal
        $this->assertContains($response->getStatusCode(), [302, 403, 404]);
    }

    public function test_inactive_user_cannot_access_admin(): void
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
}
