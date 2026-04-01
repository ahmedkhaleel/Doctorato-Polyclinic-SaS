<?php

namespace Tests\Feature\Secretary;

use App\Models\Employee;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecretaryAttendanceTest extends TestCase
{
    use RefreshDatabase;

    private User $secretary;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::firstOrCreate(
            ['name' => 'secretary'],
            ['display_name_en' => 'Secretary', 'display_name_ar' => 'سكرتيرة', 'permissions' => [], 'is_system' => true]
        );

        $this->secretary = User::create([
            'name' => 'Secretary', 'email' => 'sec-att@test.com',
            'password' => bcrypt('password'), 'role_id' => $role->id, 'is_active' => true,
        ]);

        Employee::create([
            'user_id' => $this->secretary->id,
            'employee_number' => 'EMP-SEC-002',
            'contract_type' => 'full_time',
            'basic_salary' => 4000,
            'status' => 'active',
        ]);
    }

    public function test_can_view_attendance_index(): void
    {
        $this->actingAs($this->secretary)->get('/secretary/my-attendance')->assertOk();
    }

    public function test_non_secretary_cannot_access(): void
    {
        $patientRole = Role::firstOrCreate(
            ['name' => 'patient'],
            ['display_name_en' => 'Patient', 'display_name_ar' => 'مريض', 'permissions' => []]
        );

        $patient = User::create([
            'name' => 'Patient', 'email' => 'pat-secatt@test.com',
            'password' => bcrypt('password'), 'role_id' => $patientRole->id, 'is_active' => true,
        ]);

        $response = $this->actingAs($patient)->get('/secretary/my-attendance');
        $this->assertContains($response->status(), [302, 401, 403]);
    }
}
