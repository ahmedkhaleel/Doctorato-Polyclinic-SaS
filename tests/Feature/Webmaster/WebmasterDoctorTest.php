<?php

namespace Tests\Feature\Webmaster;

use App\Models\Doctor;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebmasterDoctorTest extends TestCase
{
    use RefreshDatabase;

    private User $webmaster;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::firstOrCreate(
            ['name' => 'webmaster'],
            ['display_name_en' => 'Webmaster', 'display_name_ar' => 'مدير الموقع', 'permissions' => [
                'doctors.view', 'doctors.create', 'doctors.update', 'doctors.delete',
                'services.view', 'gallery.view', 'gallery.create', 'gallery.update', 'gallery.delete',
                'faqs.view', 'faqs.create', 'faqs.update', 'faqs.delete',
                'settings.view', 'settings.update',
            ], 'is_system' => true]
        );

        $this->webmaster = User::create([
            'name' => 'Webmaster', 'email' => 'wm-doc@test.com',
            'password' => bcrypt('password'), 'role_id' => $role->id, 'is_active' => true,
        ]);
    }

    public function test_can_view_doctors_index(): void
    {
        $this->actingAs($this->webmaster)->get('/webmaster/doctors')->assertOk();
    }

    public function test_can_view_create_page(): void
    {
        $this->actingAs($this->webmaster)->get('/webmaster/doctors/create')->assertOk();
    }

    public function test_can_create_doctor(): void
    {
        $this->actingAs($this->webmaster)->post('/webmaster/doctors', [
            'name_ar' => 'دكتور جديد',
            'name_en' => 'New Doctor',
            'specialization_ar' => 'جلدية',
            'specialization_en' => 'Dermatology',
            'status' => 'active',
        ])->assertRedirect();

        $this->assertDatabaseHas('doctors', ['name_en' => 'New Doctor']);
    }

    public function test_doctor_requires_name(): void
    {
        $this->actingAs($this->webmaster)->post('/webmaster/doctors', [
            'specialization_ar' => 'جلدية',
            'specialization_en' => 'Dermatology',
            'status' => 'active',
        ])->assertSessionHasErrors(['name_ar', 'name_en']);
    }

    public function test_can_update_doctor(): void
    {
        $doctor = Doctor::create([
            'name_ar' => 'دكتور قديم', 'name_en' => 'Old Doctor',
            'specialization_ar' => 'أسنان', 'specialization_en' => 'Dental',
            'status' => 'active',
        ]);

        $this->actingAs($this->webmaster)->put("/webmaster/doctors/{$doctor->id}", [
            'name_ar' => 'دكتور محدث',
            'name_en' => 'Updated Doctor',
            'specialization_ar' => 'أسنان',
            'specialization_en' => 'Dental',
            'status' => 'active',
        ])->assertRedirect();

        $this->assertDatabaseHas('doctors', ['id' => $doctor->id, 'name_en' => 'Updated Doctor']);
    }

    public function test_can_delete_doctor(): void
    {
        $doctor = Doctor::create([
            'name_ar' => 'دكتور حذف', 'name_en' => 'Delete Doc',
            'specialization_ar' => 'جلدية', 'specialization_en' => 'Derma',
            'status' => 'active',
        ]);

        $this->actingAs($this->webmaster)->delete("/webmaster/doctors/{$doctor->id}")
            ->assertRedirect();

        $this->assertSoftDeleted('doctors', ['id' => $doctor->id]);
    }
}
