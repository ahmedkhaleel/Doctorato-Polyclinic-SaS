<?php

namespace Tests\Feature\Admin;

use App\Models\Doctor;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDoctorScheduleTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Doctor $doctor;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::firstOrCreate(
            ['name' => 'admin'],
            ['display_name_en' => 'Admin', 'display_name_ar' => 'مدير', 'permissions' => ['*'], 'is_system' => true]
        );

        $this->admin = User::create([
            'name' => 'Admin', 'email' => 'admin-sched@test.com',
            'password' => bcrypt('password'), 'role_id' => $role->id, 'is_active' => true,
        ]);

        $this->doctor = Doctor::create([
            'name_ar' => 'دكتور جدول',
            'name_en' => 'Schedule Doctor',
            'status' => 'active',
        ]);
    }

    public function test_can_view_schedules_index(): void
    {
        $this->actingAs($this->admin)->get('/admin/schedules')->assertOk();
    }

    public function test_can_update_doctor_schedule(): void
    {
        $this->actingAs($this->admin)->put("/admin/schedules/{$this->doctor->id}", [
            'schedules' => [
                ['day_of_week' => 1, 'start_time' => '09:00', 'end_time' => '17:00', 'is_active' => true],
                ['day_of_week' => 2, 'start_time' => '09:00', 'end_time' => '17:00', 'is_active' => true],
                ['day_of_week' => 3, 'start_time' => '09:00', 'end_time' => '13:00', 'is_active' => true],
            ],
        ])->assertRedirect();

        $this->assertDatabaseHas('doctor_schedules', [
            'doctor_id' => $this->doctor->id,
            'day_of_week' => 1,
            'is_active' => true,
        ]);

        $this->assertEquals(3, $this->doctor->schedules()->count());
    }

    public function test_schedule_requires_array(): void
    {
        $this->actingAs($this->admin)->put("/admin/schedules/{$this->doctor->id}", [])
            ->assertSessionHasErrors('schedules');
    }

    public function test_schedule_replaces_existing(): void
    {
        // First set 5 days
        $this->actingAs($this->admin)->put("/admin/schedules/{$this->doctor->id}", [
            'schedules' => [
                ['day_of_week' => 0, 'start_time' => '08:00', 'end_time' => '16:00', 'is_active' => true],
                ['day_of_week' => 1, 'start_time' => '08:00', 'end_time' => '16:00', 'is_active' => true],
                ['day_of_week' => 2, 'start_time' => '08:00', 'end_time' => '16:00', 'is_active' => true],
                ['day_of_week' => 3, 'start_time' => '08:00', 'end_time' => '16:00', 'is_active' => true],
                ['day_of_week' => 4, 'start_time' => '08:00', 'end_time' => '16:00', 'is_active' => true],
            ],
        ]);

        $this->assertEquals(5, $this->doctor->schedules()->count());

        // Now replace with 2 days
        $this->actingAs($this->admin)->put("/admin/schedules/{$this->doctor->id}", [
            'schedules' => [
                ['day_of_week' => 1, 'start_time' => '10:00', 'end_time' => '18:00', 'is_active' => true],
                ['day_of_week' => 3, 'start_time' => '10:00', 'end_time' => '18:00', 'is_active' => true],
            ],
        ]);

        $this->assertEquals(2, $this->doctor->schedules()->count());
    }

    public function test_day_of_week_must_be_valid(): void
    {
        $this->actingAs($this->admin)->put("/admin/schedules/{$this->doctor->id}", [
            'schedules' => [
                ['day_of_week' => 7, 'start_time' => '09:00', 'end_time' => '17:00', 'is_active' => true],
            ],
        ])->assertSessionHasErrors('schedules.0.day_of_week');
    }
}
