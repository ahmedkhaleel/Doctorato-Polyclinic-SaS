<?php

namespace Tests\Feature\Admin;

use App\Models\Doctor;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDoctorsTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::firstOrCreate(
            ['name' => 'admin'],
            [
                'display_name_en' => 'Admin',
                'display_name_ar' => 'مدير',
                'permissions' => ['*'],
                'is_system' => true,
            ]
        );

        $this->admin = User::create([
            'name' => 'Admin',
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
            'role_id' => $role->id,
            'is_active' => true,
        ]);
    }

    public function test_admin_can_view_doctors_index(): void
    {
        $this->actingAs($this->admin);
        $response = $this->get('/admin/doctors');
        $response->assertStatus(200);
    }

    public function test_admin_can_view_create_doctor_page(): void
    {
        $this->actingAs($this->admin);
        $response = $this->get('/admin/doctors/create');
        $response->assertStatus(200);
    }

    public function test_admin_can_create_doctor(): void
    {
        $this->actingAs($this->admin);

        $response = $this->post('/admin/doctors', [
            'name_ar' => 'دكتور جديد',
            'name_en' => 'New Doctor',
            'specialization_ar' => 'جلدية',
            'specialization_en' => 'Dermatology',
            'status' => 'active',
            'dermatology_fee' => 200,
            'cosmetic_fee' => 300,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('doctors', [
            'name_en' => 'New Doctor',
            'dermatology_fee' => 200,
            'cosmetic_fee' => 300,
        ]);
    }

    public function test_admin_can_view_doctor_details(): void
    {
        $doctor = Doctor::create([
            'name_ar' => 'دكتور',
            'name_en' => 'Doctor',
            'status' => 'active',
        ]);

        $this->actingAs($this->admin);
        $response = $this->get("/admin/doctors/{$doctor->id}");
        $response->assertStatus(200);
    }

    public function test_admin_can_update_doctor(): void
    {
        $doctor = Doctor::create([
            'name_ar' => 'دكتور قديم',
            'name_en' => 'Old Doctor',
            'status' => 'active',
        ]);

        $this->actingAs($this->admin);

        $response = $this->post("/admin/doctors/{$doctor->id}", [
            'name_ar' => 'دكتور محدث',
            'name_en' => 'Updated Doctor',
            'specialization_ar' => 'جلدية',
            'specialization_en' => 'Dermatology',
            'status' => 'active',
            'dermatology_fee' => 250,
            'cosmetic_fee' => 350,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('doctors', [
            'id' => $doctor->id,
            'name_en' => 'Updated Doctor',
            'dermatology_fee' => 250,
        ]);
    }

    public function test_admin_can_delete_doctor(): void
    {
        $doctor = Doctor::create([
            'name_ar' => 'دكتور للحذف',
            'name_en' => 'Delete Doctor',
            'status' => 'active',
        ]);

        $this->actingAs($this->admin);

        $response = $this->post("/admin/doctors/{$doctor->id}/delete");

        $response->assertRedirect();
        $this->assertSoftDeleted('doctors', ['id' => $doctor->id]);
    }
}
