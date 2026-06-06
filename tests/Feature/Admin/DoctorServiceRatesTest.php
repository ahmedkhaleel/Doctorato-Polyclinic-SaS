<?php

namespace Tests\Feature\Admin;

use App\Models\Doctor;
use App\Models\DoctorServiceRate;
use App\Models\Role;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * P5-3 — per-doctor service commission rates: a screen to set/clear per-service
 * rates and bulk-apply one rate. CommissionCalculator already prefers these
 * over the doctor default.
 */
class DoctorServiceRatesTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Doctor $doctor;

    private Service $service;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::firstOrCreate(['name' => 'admin'], [
            'display_name_en' => 'Admin', 'display_name_ar' => 'مدير', 'permissions' => ['*'], 'is_system' => true,
        ]);
        $role->update(['permissions' => ['*']]);
        $this->admin = User::create([
            'name' => 'Admin', 'email' => 'rates-admin@test.com', 'password' => bcrypt('x'),
            'role_id' => $role->id, 'is_active' => true,
        ]);

        $this->doctor = Doctor::create(['name_ar' => 'د', 'name_en' => 'Dr', 'status' => 'active', 'module' => 'dental', 'default_commission_percentage' => 20]);
        $category = ServiceCategory::create(['name_ar' => 'تصنيف', 'name_en' => 'Category', 'slug' => 'cat-'.uniqid()]);
        $this->service = $this->makeService($category->id, 'svc');
    }

    private function makeService(int $categoryId, string $slugPrefix): Service
    {
        return Service::create([
            'category_id' => $categoryId,
            'name_ar' => 'خدمة', 'name_en' => 'Service', 'slug' => $slugPrefix.'-'.uniqid(),
            'module' => 'dental', 'status' => 'active', 'bookable' => true,
            'price' => 500, 'default_sessions' => 1, 'session_duration_minutes' => 30,
        ]);
    }

    public function test_index_lists_services_for_the_doctor_module(): void
    {
        $this->actingAs($this->admin)->get("/admin/doctors/{$this->doctor->id}/service-rates")
            ->assertOk()
            ->assertInertia(fn (Assert $p) => $p
                ->component('Admin/Doctors/ServiceRates')
                ->where('doctor.id', $this->doctor->id)
                ->has('services', 1)
            );
    }

    public function test_update_sets_and_clears_a_rate(): void
    {
        // Set a rate.
        $this->actingAs($this->admin)->post("/admin/doctors/{$this->doctor->id}/service-rates", [
            'rates' => [$this->service->id => 35],
        ])->assertRedirect();

        $this->assertDatabaseHas('doctor_service_rates', [
            'doctor_id' => $this->doctor->id, 'service_id' => $this->service->id, 'commission_percentage' => 35,
        ]);

        // Clearing it (blank) removes the override.
        $this->actingAs($this->admin)->post("/admin/doctors/{$this->doctor->id}/service-rates", [
            'rates' => [$this->service->id => ''],
        ])->assertRedirect();

        $this->assertDatabaseMissing('doctor_service_rates', [
            'doctor_id' => $this->doctor->id, 'service_id' => $this->service->id,
        ]);
    }

    public function test_bulk_apply_sets_every_service(): void
    {
        $this->makeService($this->service->category_id, 'svc2');

        $this->actingAs($this->admin)->post("/admin/doctors/{$this->doctor->id}/service-rates/bulk", [
            'percentage' => 15,
        ])->assertRedirect();

        $this->assertSame(2, DoctorServiceRate::where('doctor_id', $this->doctor->id)->where('commission_percentage', 15)->count());
    }
}
