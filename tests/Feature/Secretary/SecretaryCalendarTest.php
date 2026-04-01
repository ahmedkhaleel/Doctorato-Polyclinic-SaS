<?php

namespace Tests\Feature\Secretary;

use App\Models\Doctor;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecretaryCalendarTest extends TestCase
{
    use RefreshDatabase;

    private User $secretary;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::firstOrCreate(
            ['name' => 'secretary'],
            ['display_name_en' => 'Secretary', 'display_name_ar' => 'سكرتير', 'permissions' => [], 'is_system' => true]
        );

        $this->secretary = User::create([
            'name' => 'Calendar Secretary', 'email' => 'sec-cal@test.com',
            'password' => bcrypt('password'), 'role_id' => $role->id, 'is_active' => true,
        ]);
    }

    public function test_can_view_calendar(): void
    {
        $this->actingAs($this->secretary)->get('/secretary/calendar')->assertOk();
    }

    public function test_calendar_accepts_month_filter(): void
    {
        $this->actingAs($this->secretary)
            ->get('/secretary/calendar?month=2025-06')
            ->assertOk();
    }

    public function test_calendar_accepts_doctor_filter(): void
    {
        $doctor = Doctor::create([
            'name_ar' => 'دكتور تقويم', 'name_en' => 'Calendar Doctor',
            'status' => 'active',
        ]);

        $this->actingAs($this->secretary)
            ->get("/secretary/calendar?doctor_id={$doctor->id}")
            ->assertOk();
    }
}
