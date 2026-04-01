<?php

namespace Tests\Feature\Doctor;

use App\Models\Doctor;
use App\Models\Employee;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DoctorLeaveTest extends TestCase
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
            'name' => 'Leave Doctor', 'email' => 'doc-leave@test.com',
            'password' => bcrypt('password'), 'role_id' => $role->id, 'is_active' => true,
        ]);

        Doctor::create([
            'name_ar' => 'دكتور إجازة', 'name_en' => 'Leave Doctor',
            'user_id' => $this->doctorUser->id, 'status' => 'active',
        ]);

        Employee::create([
            'user_id' => $this->doctorUser->id,
            'employee_number' => 'EMP-DOC-001',
            'contract_type' => 'full_time',
            'basic_salary' => 8000,
            'status' => 'active',
        ]);
    }

    public function test_can_view_leaves_index(): void
    {
        $this->actingAs($this->doctorUser)->get('/doctor/my-leaves')->assertOk();
    }

    public function test_can_submit_leave_request(): void
    {
        $this->actingAs($this->doctorUser)->post('/doctor/my-leaves', [
            'leave_type' => 'sick',
            'start_date' => now()->addDay()->toDateString(),
            'end_date' => now()->addDays(2)->toDateString(),
            'reason' => 'Medical appointment',
        ])->assertRedirect();

        $this->assertDatabaseHas('leaves', [
            'user_id' => $this->doctorUser->id,
            'leave_type' => 'sick',
            'status' => 'pending',
        ]);
    }

    public function test_leave_requires_type_and_dates(): void
    {
        $this->actingAs($this->doctorUser)->post('/doctor/my-leaves', [])
            ->assertSessionHasErrors(['leave_type', 'start_date', 'end_date']);
    }

    public function test_invalid_leave_type_rejected(): void
    {
        $this->actingAs($this->doctorUser)->post('/doctor/my-leaves', [
            'leave_type' => 'invalid',
            'start_date' => now()->addDay()->toDateString(),
            'end_date' => now()->addDays(2)->toDateString(),
        ])->assertSessionHasErrors('leave_type');
    }
}
