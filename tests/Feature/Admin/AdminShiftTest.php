<?php

namespace Tests\Feature\Admin;

use App\Models\Role;
use App\Models\Shift;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminShiftTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::firstOrCreate(
            ['name' => 'admin'],
            ['display_name_en' => 'Admin', 'display_name_ar' => 'مدير', 'permissions' => ['*'], 'is_system' => true]
        );

        $this->admin = User::create([
            'name' => 'Admin', 'email' => 'admin-shift@test.com',
            'password' => bcrypt('password'), 'role_id' => $role->id, 'is_active' => true,
        ]);
    }

    public function test_can_view_shifts_index(): void
    {
        $this->actingAs($this->admin)->get('/admin/shifts')->assertOk();
    }

    public function test_can_create_shift(): void
    {
        $this->actingAs($this->admin)->post('/admin/shifts', [
            'name_ar' => 'صباحي',
            'name_en' => 'Morning',
            'start_time' => '08:00',
            'end_time' => '16:00',
            'is_active' => true,
        ])->assertRedirect();

        $this->assertDatabaseHas('shifts', [
            'name_en' => 'Morning',
            'start_time' => '08:00',
        ]);
    }

    public function test_shift_requires_name_and_times(): void
    {
        $this->actingAs($this->admin)->post('/admin/shifts', [])
            ->assertSessionHasErrors(['name_ar', 'name_en', 'start_time', 'end_time']);
    }

    public function test_can_update_shift(): void
    {
        $shift = Shift::create([
            'name_ar' => 'صباحي', 'name_en' => 'Morning',
            'start_time' => '08:00', 'end_time' => '16:00', 'is_active' => true,
        ]);

        $this->actingAs($this->admin)->post("/admin/shifts/{$shift->id}/update", [
            'name_ar' => 'مسائي',
            'name_en' => 'Evening',
            'start_time' => '16:00',
            'end_time' => '00:00',
            'is_active' => true,
        ])->assertRedirect();

        $this->assertDatabaseHas('shifts', ['id' => $shift->id, 'name_en' => 'Evening']);
    }

    public function test_can_delete_shift(): void
    {
        $shift = Shift::create([
            'name_ar' => 'ليلي', 'name_en' => 'Night',
            'start_time' => '00:00', 'end_time' => '08:00', 'is_active' => true,
        ]);

        $this->actingAs($this->admin)->post("/admin/shifts/{$shift->id}/delete")->assertRedirect();
        $this->assertDatabaseMissing('shifts', ['id' => $shift->id]);
    }

    public function test_unauthenticated_cannot_access(): void
    {
        $this->get('/admin/shifts')->assertRedirect();
    }
}
