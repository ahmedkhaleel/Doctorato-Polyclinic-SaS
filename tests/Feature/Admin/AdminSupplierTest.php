<?php

namespace Tests\Feature\Admin;

use App\Models\Role;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminSupplierTest extends TestCase
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
            'name' => 'Admin', 'email' => 'admin-sup@test.com',
            'password' => bcrypt('password'), 'role_id' => $role->id, 'is_active' => true,
        ]);
    }

    public function test_can_view_suppliers_index(): void
    {
        $this->actingAs($this->admin)->get('/admin/suppliers')->assertOk();
    }

    public function test_can_create_supplier(): void
    {
        $this->actingAs($this->admin)->post('/admin/suppliers', [
            'name_ar' => 'شركة أدوية',
            'name_en' => 'Pharma Co',
            'phone' => '01234567890',
            'is_active' => true,
        ])->assertRedirect();

        $this->assertDatabaseHas('suppliers', ['name_en' => 'Pharma Co']);
    }

    public function test_supplier_requires_names(): void
    {
        $this->actingAs($this->admin)->post('/admin/suppliers', [
            'is_active' => true,
        ])->assertSessionHasErrors(['name_ar', 'name_en']);
    }

    public function test_can_update_supplier(): void
    {
        $supplier = Supplier::create(['name_ar' => 'قديم', 'name_en' => 'Old', 'is_active' => true]);

        $this->actingAs($this->admin)->put("/admin/suppliers/{$supplier->id}", [
            'name_ar' => 'محدث',
            'name_en' => 'Updated Supplier',
            'is_active' => true,
        ])->assertRedirect();

        $this->assertDatabaseHas('suppliers', ['id' => $supplier->id, 'name_en' => 'Updated Supplier']);
    }

    public function test_can_delete_supplier(): void
    {
        $supplier = Supplier::create(['name_ar' => 'حذف', 'name_en' => 'Delete', 'is_active' => true]);

        $this->actingAs($this->admin)->delete("/admin/suppliers/{$supplier->id}")
            ->assertRedirect();

        $this->assertDatabaseMissing('suppliers', ['id' => $supplier->id]);
    }

    public function test_supplier_code_must_be_unique(): void
    {
        Supplier::create(['name_ar' => 'أول', 'name_en' => 'First', 'code' => 'SUP001', 'is_active' => true]);

        $this->actingAs($this->admin)->post('/admin/suppliers', [
            'name_ar' => 'ثاني',
            'name_en' => 'Second',
            'code' => 'SUP001',
            'is_active' => true,
        ])->assertSessionHasErrors('code');
    }
}
