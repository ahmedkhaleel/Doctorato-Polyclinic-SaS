<?php

namespace Tests\Feature\Doctor;

use App\Models\Doctor;
use App\Models\Employee;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DoctorAttendanceTest extends TestCase
{
    use RefreshDatabase;

    private User $doctorUser;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::firstOrCreate(
            ['name' => 'doctor'],
            ['display_name_en' => 'Doctor', 'display_name_ar' => 'طبيب', 'permissions' => [], 'is_system' => true]
        );

        $this->doctorUser = User::create([
            'name' => 'Att Doctor', 'email' => 'doc-att@test.com',
            'password' => bcrypt('password'), 'role_id' => $role->id, 'is_active' => true,
        ]);

        Doctor::create([
            'name_ar' => 'دكتور حضور', 'name_en' => 'Attendance Doctor',
            'user_id' => $this->doctorUser->id, 'status' => 'active',
        ]);

        Employee::create([
            'user_id' => $this->doctorUser->id,
            'employee_number' => 'EMP-DOC-ATT',
            'contract_type' => 'full_time',
            'basic_salary' => 8000,
            'status' => 'active',
        ]);
    }

    public function test_can_view_attendance_index(): void
    {
        $this->actingAs($this->doctorUser)->get('/doctor/my-attendance')->assertOk();
    }

    public function test_non_doctor_cannot_access(): void
    {
        $patientRole = Role::firstOrCreate(
            ['name' => 'patient'],
            ['display_name_en' => 'Patient', 'display_name_ar' => 'مريض', 'permissions' => []]
        );

        $patient = User::create([
            'name' => 'Not Doc', 'email' => 'not-doc-att@test.com',
            'password' => bcrypt('password'), 'role_id' => $patientRole->id, 'is_active' => true,
        ]);

        $response = $this->actingAs($patient)->get('/doctor/my-attendance');
        $this->assertContains($response->status(), [302, 401, 403]);
    }
}
