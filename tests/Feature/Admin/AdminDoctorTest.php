<?php

namespace Tests\Feature\Admin;

use App\Models\Doctor;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDoctorTest extends TestCase
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
            'name' => 'Admin', 'email' => 'admin-doc@test.com',
            'password' => bcrypt('password'), 'role_id' => $role->id, 'is_active' => true,
        ]);
    }

    public function test_can_view_doctors_index(): void
    {
        $this->actingAs($this->admin)->get('/admin/doctors')->assertOk();
    }

    public function test_can_create_doctor(): void
    {
        $this->actingAs($this->admin)->post('/admin/doctors', [
            'name_ar' => 'دكتور اختبار',
            'name_en' => 'Test Doctor',
            'specialization_ar' => 'جلدية',
            'specialization_en' => 'Dermatology',
            'status' => 'active',
        ])->assertRedirect();

        $this->assertDatabaseHas('doctors', [
            'name_en' => 'Test Doctor',
            'status' => 'active',
        ]);
    }

    public function test_doctor_requires_name_and_specialization(): void
    {
        $this->actingAs($this->admin)->post('/admin/doctors', [])
            ->assertSessionHasErrors(['name_ar', 'name_en', 'specialization_ar', 'specialization_en', 'status']);
    }

    public function test_can_update_doctor(): void
    {
        $doctor = Doctor::create([
            'name_ar' => 'قديم', 'name_en' => 'Old',
            'specialization_ar' => 'جلدية', 'specialization_en' => 'Derm',
            'status' => 'active',
        ]);

        $this->actingAs($this->admin)->post("/admin/doctors/{$doctor->id}", [
            'name_ar' => 'جديد',
            'name_en' => 'Updated Doctor',
            'specialization_ar' => 'تجميل',
            'specialization_en' => 'Cosmetic',
            'status' => 'active',
        ])->assertRedirect();

        $this->assertDatabaseHas('doctors', [
            'id' => $doctor->id,
            'name_en' => 'Updated Doctor',
        ]);
    }

    public function test_can_delete_doctor(): void
    {
        $doctor = Doctor::create([
            'name_ar' => 'حذف', 'name_en' => 'Delete',
            'specialization_ar' => 'جلدية', 'specialization_en' => 'Derm',
            'status' => 'active',
        ]);

        $this->actingAs($this->admin)->delete("/admin/doctors/{$doctor->id}")->assertRedirect();
    }

    public function test_status_must_be_valid(): void
    {
        $this->actingAs($this->admin)->post('/admin/doctors', [
            'name_ar' => 'دكتور',
            'name_en' => 'Doctor',
            'specialization_ar' => 'جلدية',
            'specialization_en' => 'Derm',
            'status' => 'invalid_status',
        ])->assertSessionHasErrors('status');
    }

    public function test_can_create_doctor_with_schedules(): void
    {
        $this->actingAs($this->admin)->post('/admin/doctors', [
            'name_ar' => 'دكتور جدول',
            'name_en' => 'Schedule Doctor',
            'specialization_ar' => 'جلدية',
            'specialization_en' => 'Dermatology',
            'status' => 'active',
            'schedules' => [
                ['day_of_week' => 0, 'start_time' => '09:00', 'end_time' => '17:00', 'is_active' => true],
                ['day_of_week' => 1, 'start_time' => '09:00', 'end_time' => '17:00', 'is_active' => true],
            ],
        ])->assertRedirect();

        $doctor = Doctor::where('name_en', 'Schedule Doctor')->first();
        $this->assertNotNull($doctor);
        $this->assertEquals(2, $doctor->schedules()->count());
    }
}
