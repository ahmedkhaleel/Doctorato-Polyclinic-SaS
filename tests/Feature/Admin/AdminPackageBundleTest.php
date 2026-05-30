<?php

namespace Tests\Feature\Admin;

use App\Models\PackageBundle;
use App\Models\Role;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPackageBundleTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Service $service;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::firstOrCreate(
            ['name' => 'admin'],
            ['display_name_en' => 'Admin', 'display_name_ar' => 'مدير', 'permissions' => ['*'], 'is_system' => true]
        );

        $this->admin = User::create([
            'name' => 'Admin', 'email' => 'admin-pkg@test.com',
            'password' => bcrypt('password'), 'role_id' => $role->id, 'is_active' => true,
        ]);

        $category = ServiceCategory::create([
            'name_ar' => 'تصنيف', 'name_en' => 'Category', 'slug' => 'test-cat',
        ]);

        $this->service = Service::create([
            'name_ar' => 'ليزر', 'name_en' => 'Laser',
            'slug' => 'laser', 'category_id' => $category->id,
            'status' => 'active', 'price' => 500,
        ]);
    }

    public function test_can_view_bundles_index(): void
    {
        $this->actingAs($this->admin)->get('/admin/package-bundles')->assertOk();
    }

    public function test_can_create_bundle(): void
    {
        $this->actingAs($this->admin)->post('/admin/package-bundles', [
            'name_ar' => 'باقة ليزر',
            'name_en' => 'Laser Package',
            'total_price' => 2000,
            'is_active' => true,
            'services' => [
                [
                    'service_id' => $this->service->id,
                    'sessions_count' => 5,
                    'discount_percentage' => 20,
                    'bundle_price' => 400,
                ],
            ],
        ])->assertRedirect();

        $this->assertDatabaseHas('package_bundles', [
            'name_en' => 'Laser Package',
            'total_price' => 2000,
        ]);
    }

    public function test_bundle_requires_fields(): void
    {
        $this->actingAs($this->admin)->post('/admin/package-bundles', [])
            ->assertSessionHasErrors(['name_ar', 'name_en', 'total_price', 'services']);
    }

    public function test_bundle_requires_at_least_one_service(): void
    {
        $this->actingAs($this->admin)->post('/admin/package-bundles', [
            'name_ar' => 'باقة',
            'name_en' => 'Package',
            'total_price' => 1000,
            'services' => [],
        ])->assertSessionHasErrors('services');
    }

    public function test_can_update_bundle(): void
    {
        $bundle = PackageBundle::create([
            'name_ar' => 'قديم', 'name_en' => 'Old',
            'total_price' => 1000, 'is_active' => true,
        ]);

        $this->actingAs($this->admin)->post("/admin/package-bundles/{$bundle->id}/update", [
            'name_ar' => 'جديد',
            'name_en' => 'Updated Package',
            'total_price' => 1500,
            'is_active' => true,
            'services' => [
                [
                    'service_id' => $this->service->id,
                    'sessions_count' => 3,
                    'discount_percentage' => 10,
                    'bundle_price' => 450,
                ],
            ],
        ])->assertRedirect();

        $this->assertDatabaseHas('package_bundles', [
            'id' => $bundle->id, 'name_en' => 'Updated Package',
        ]);
    }

    public function test_can_delete_bundle(): void
    {
        $bundle = PackageBundle::create([
            'name_ar' => 'حذف', 'name_en' => 'Delete',
            'total_price' => 500, 'is_active' => true,
        ]);

        $this->actingAs($this->admin)->post("/admin/package-bundles/{$bundle->id}/delete")->assertRedirect();
        $this->assertDatabaseMissing('package_bundles', ['id' => $bundle->id]);
    }
}
