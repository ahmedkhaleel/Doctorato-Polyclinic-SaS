<?php

namespace Tests\Feature\Admin;

use App\Models\Role;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminServiceTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private ServiceCategory $category;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::firstOrCreate(
            ['name' => 'admin'],
            ['display_name_en' => 'Admin', 'display_name_ar' => 'مدير', 'permissions' => ['*'], 'is_system' => true]
        );

        $this->admin = User::create([
            'name' => 'Admin', 'email' => 'admin-svc@test.com',
            'password' => bcrypt('password'), 'role_id' => $role->id, 'is_active' => true,
        ]);

        $this->category = ServiceCategory::create([
            'name_ar' => 'جلدية', 'name_en' => 'Dermatology', 'slug' => 'dermatology',
        ]);
    }

    public function test_can_view_services_index(): void
    {
        $this->actingAs($this->admin)->get('/admin/services')->assertOk();
    }

    public function test_can_view_create_page(): void
    {
        $this->actingAs($this->admin)->get('/admin/services/create')->assertOk();
    }

    public function test_can_create_service(): void
    {
        $this->actingAs($this->admin)->post('/admin/services', [
            'category_id' => $this->category->id,
            'name_ar' => 'تقشير كيميائي',
            'name_en' => 'Chemical Peel',
            'status' => 'active',
        ])->assertRedirect();

        $this->assertDatabaseHas('services', ['name_en' => 'Chemical Peel']);
    }

    public function test_service_requires_name(): void
    {
        $this->actingAs($this->admin)->post('/admin/services', [
            'category_id' => $this->category->id,
            'status' => 'active',
        ])->assertSessionHasErrors(['name_ar', 'name_en']);
    }

    public function test_service_requires_category(): void
    {
        $this->actingAs($this->admin)->post('/admin/services', [
            'name_ar' => 'خدمة',
            'name_en' => 'Service',
            'status' => 'active',
        ])->assertSessionHasErrors('category_id');
    }

    public function test_service_auto_generates_slug(): void
    {
        $this->actingAs($this->admin)->post('/admin/services', [
            'category_id' => $this->category->id,
            'name_ar' => 'ليزر',
            'name_en' => 'Laser Treatment',
            'status' => 'active',
        ])->assertRedirect();

        $service = Service::where('name_en', 'Laser Treatment')->first();
        $this->assertNotNull($service);
        $this->assertNotEmpty($service->slug);
    }

    public function test_can_view_service_show(): void
    {
        $service = Service::create([
            'category_id' => $this->category->id,
            'name_ar' => 'بوتوكس', 'name_en' => 'Botox',
            'slug' => 'botox', 'status' => 'active',
        ]);

        $this->actingAs($this->admin)->get("/admin/services/{$service->id}")->assertOk();
    }

    public function test_can_update_service(): void
    {
        $service = Service::create([
            'category_id' => $this->category->id,
            'name_ar' => 'قديم', 'name_en' => 'Old Service',
            'slug' => 'old-service', 'status' => 'active',
        ]);

        $this->actingAs($this->admin)->post("/admin/services/{$service->id}/update", [
            'category_id' => $this->category->id,
            'name_ar' => 'محدث',
            'name_en' => 'Updated Service',
            'status' => 'active',
        ])->assertRedirect();

        $this->assertDatabaseHas('services', ['id' => $service->id, 'name_en' => 'Updated Service']);
    }

    public function test_can_delete_service(): void
    {
        $service = Service::create([
            'category_id' => $this->category->id,
            'name_ar' => 'حذف', 'name_en' => 'Delete Me',
            'slug' => 'delete-me', 'status' => 'active',
        ]);

        $this->actingAs($this->admin)->post("/admin/services/{$service->id}/delete")
            ->assertRedirect();

        $this->assertDatabaseMissing('services', ['id' => $service->id]);
    }

    public function test_non_admin_cannot_access_services(): void
    {
        $patientRole = Role::firstOrCreate(
            ['name' => 'patient'],
            ['display_name_en' => 'Patient', 'display_name_ar' => 'مريض', 'permissions' => []]
        );

        $patient = User::create([
            'name' => 'Patient', 'email' => 'pat-svc@test.com',
            'password' => bcrypt('password'), 'role_id' => $patientRole->id, 'is_active' => true,
        ]);

        $response = $this->actingAs($patient)->get('/admin/services');
        $this->assertContains($response->status(), [302, 401, 403]);
    }
}
