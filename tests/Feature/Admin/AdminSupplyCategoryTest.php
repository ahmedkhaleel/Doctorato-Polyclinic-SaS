<?php

namespace Tests\Feature\Admin;

use App\Models\Role;
use App\Models\SupplyCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminSupplyCategoryTest extends TestCase
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
            'name' => 'Admin', 'email' => 'admin-supcat@test.com',
            'password' => bcrypt('password'), 'role_id' => $role->id, 'is_active' => true,
        ]);
    }

    public function test_can_view_supply_categories(): void
    {
        $this->actingAs($this->admin)->get('/admin/supply-categories')->assertOk();
    }

    public function test_can_create_supply_category(): void
    {
        $this->actingAs($this->admin)->post('/admin/supply-categories', [
            'name_ar' => 'أدوية',
            'name_en' => 'Medications',
            'is_active' => true,
        ])->assertRedirect();

        $this->assertDatabaseHas('supply_categories', ['name_en' => 'Medications']);
    }

    public function test_supply_category_requires_names(): void
    {
        $this->actingAs($this->admin)->post('/admin/supply-categories', [])
            ->assertSessionHasErrors(['name_ar', 'name_en']);
    }

    public function test_can_update_supply_category(): void
    {
        $cat = SupplyCategory::create(['name_ar' => 'قديم', 'name_en' => 'Old', 'is_active' => true]);

        $this->actingAs($this->admin)->post("/admin/supply-categories/{$cat->id}/update", [
            'name_ar' => 'محدث',
            'name_en' => 'Updated',
            'is_active' => true,
        ])->assertRedirect();

        $this->assertDatabaseHas('supply_categories', ['id' => $cat->id, 'name_en' => 'Updated']);
    }

    public function test_can_delete_supply_category(): void
    {
        $cat = SupplyCategory::create(['name_ar' => 'حذف', 'name_en' => 'Delete', 'is_active' => true]);

        $this->actingAs($this->admin)->post("/admin/supply-categories/{$cat->id}/delete")
            ->assertRedirect();

        $this->assertDatabaseMissing('supply_categories', ['id' => $cat->id]);
    }
}
