<?php

namespace Tests\Feature\Secretary;

use App\Models\Employee;
use App\Models\Leave;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecretaryLeaveTest extends TestCase
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
            'name' => 'Secretary', 'email' => 'sec-leave@test.com',
            'password' => bcrypt('password'), 'role_id' => $role->id, 'is_active' => true,
        ]);

        Employee::create([
            'user_id' => $this->secretary->id,
            'employee_number' => 'EMP-SEC-001',
            'contract_type' => 'full_time',
            'basic_salary' => 4000,
            'status' => 'active',
        ]);
    }

    public function test_can_view_leaves_index(): void
    {
        $this->actingAs($this->secretary)->get('/secretary/my-leaves')->assertOk();
    }

    public function test_can_submit_leave_request(): void
    {
        $this->actingAs($this->secretary)->post('/secretary/my-leaves', [
            'leave_type' => 'annual',
            'start_date' => now()->addDays(3)->toDateString(),
            'end_date' => now()->addDays(5)->toDateString(),
            'reason' => 'Family vacation',
        ])->assertRedirect();

        $this->assertDatabaseHas('leaves', [
            'user_id' => $this->secretary->id,
            'leave_type' => 'annual',
            'status' => 'pending',
        ]);
    }

    public function test_leave_requires_type(): void
    {
        $this->actingAs($this->secretary)->post('/secretary/my-leaves', [
            'start_date' => now()->addDays(3)->toDateString(),
            'end_date' => now()->addDays(5)->toDateString(),
        ])->assertSessionHasErrors('leave_type');
    }

    public function test_leave_requires_dates(): void
    {
        $this->actingAs($this->secretary)->post('/secretary/my-leaves', [
            'leave_type' => 'sick',
        ])->assertSessionHasErrors(['start_date', 'end_date']);
    }

    public function test_end_date_must_be_after_start(): void
    {
        $this->actingAs($this->secretary)->post('/secretary/my-leaves', [
            'leave_type' => 'annual',
            'start_date' => now()->addDays(5)->toDateString(),
            'end_date' => now()->addDays(3)->toDateString(),
        ])->assertSessionHasErrors('end_date');
    }

    public function test_invalid_leave_type_rejected(): void
    {
        $this->actingAs($this->secretary)->post('/secretary/my-leaves', [
            'leave_type' => 'invalid_type',
            'start_date' => now()->addDays(3)->toDateString(),
            'end_date' => now()->addDays(5)->toDateString(),
        ])->assertSessionHasErrors('leave_type');
    }
}
