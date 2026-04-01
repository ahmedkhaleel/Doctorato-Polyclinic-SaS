<?php

namespace Tests\Feature\Admin;

use App\Models\Role;
use App\Models\ServiceCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminServiceCategoryTest extends TestCase
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
            'name' => 'Admin', 'email' => 'admin-svccat@test.com',
            'password' => bcrypt('password'), 'role_id' => $role->id, 'is_active' => true,
        ]);
    }

    public function test_can_view_service_categories_index(): void
    {
        $this->actingAs($this->admin)->get('/admin/service-categories')->assertOk();
    }

    public function test_can_view_create_form(): void
    {
        $this->actingAs($this->admin)->get('/admin/service-categories/create')->assertOk();
    }

    public function test_can_create_service_category(): void
    {
        $this->actingAs($this->admin)->post('/admin/service-categories', [
            'name_ar' => 'علاج الجلد',
            'name_en' => 'Skin Treatment',
            'module' => 'derma',
            'display_order' => 1,
        ])->assertRedirect();

        $this->assertDatabaseHas('service_categories', [
            'name_en' => 'Skin Treatment',
            'slug' => 'skin-treatment',
            'module' => 'derma',
        ]);
    }

    public function test_category_requires_names(): void
    {
        $this->actingAs($this->admin)->post('/admin/service-categories', [])
            ->assertSessionHasErrors(['name_ar', 'name_en']);
    }

    public function test_module_must_be_valid(): void
    {
        $this->actingAs($this->admin)->post('/admin/service-categories', [
            'name_ar' => 'تصنيف',
            'name_en' => 'Category',
            'module' => 'invalid_module',
        ])->assertSessionHasErrors('module');
    }

    public function test_can_update_service_category(): void
    {
        $cat = ServiceCategory::create([
            'name_ar' => 'قديم', 'name_en' => 'Old', 'slug' => 'old', 'module' => 'derma',
        ]);

        $this->actingAs($this->admin)->put("/admin/service-categories/{$cat->id}", [
            'name_ar' => 'جديد',
            'name_en' => 'New Category',
            'module' => 'dental',
        ])->assertRedirect();

        $this->assertDatabaseHas('service_categories', [
            'id' => $cat->id,
            'slug' => 'new-category',
            'module' => 'dental',
        ]);
    }

    public function test_can_delete_service_category(): void
    {
        $cat = ServiceCategory::create([
            'name_ar' => 'حذف', 'name_en' => 'Delete', 'slug' => 'delete', 'module' => 'derma',
        ]);

        $this->actingAs($this->admin)->delete("/admin/service-categories/{$cat->id}")->assertRedirect();
        $this->assertDatabaseMissing('service_categories', ['id' => $cat->id]);
    }
}
