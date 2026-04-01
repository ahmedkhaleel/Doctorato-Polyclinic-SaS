<?php

namespace Tests\Feature\Admin;

use App\Models\Role;
use App\Models\Supply;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminSupplyTest extends TestCase
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

    public function test_can_view_supplies_index(): void
    {
        $this->actingAs($this->admin)->get('/admin/supplies')->assertOk();
    }

    public function test_can_create_supply(): void
    {
        $this->actingAs($this->admin)->post('/admin/supplies', [
            'name_ar' => 'قفازات طبية',
            'name_en' => 'Medical Gloves',
            'sku' => 'GLV-001',
            'quantity' => 100,
            'purchase_price' => 15.50,
            'unit' => 'box',
            'is_active' => true,
        ])->assertRedirect();

        $this->assertDatabaseHas('supplies', [
            'name_en' => 'Medical Gloves',
            'sku' => 'GLV-001',
        ]);
    }

    public function test_supply_requires_name_and_quantity(): void
    {
        $this->actingAs($this->admin)->post('/admin/supplies', [])
            ->assertSessionHasErrors(['name_ar', 'name_en', 'quantity']);
    }

    public function test_sku_must_be_unique(): void
    {
        Supply::create([
            'name_ar' => 'موجود', 'name_en' => 'Existing',
            'sku' => 'SKU-001', 'quantity' => 10, 'is_active' => true,
        ]);

        $this->actingAs($this->admin)->post('/admin/supplies', [
            'name_ar' => 'جديد', 'name_en' => 'New',
            'sku' => 'SKU-001', 'quantity' => 5,
        ])->assertSessionHasErrors('sku');
    }

    public function test_can_update_supply(): void
    {
        $supply = Supply::create([
            'name_ar' => 'قديم', 'name_en' => 'Old',
            'quantity' => 50, 'is_active' => true,
        ]);

        $this->actingAs($this->admin)->put("/admin/supplies/{$supply->id}", [
            'name_ar' => 'جديد',
            'name_en' => 'Updated',
            'min_quantity' => 10,
        ])->assertRedirect();

        $this->assertDatabaseHas('supplies', ['id' => $supply->id, 'name_en' => 'Updated']);
    }

    public function test_can_delete_supply(): void
    {
        $supply = Supply::create([
            'name_ar' => 'حذف', 'name_en' => 'Delete',
            'quantity' => 5, 'is_active' => true,
        ]);

        $this->actingAs($this->admin)->delete("/admin/supplies/{$supply->id}")->assertRedirect();
        $this->assertDatabaseMissing('supplies', ['id' => $supply->id]);
    }

    public function test_can_add_purchase_transaction(): void
    {
        $supply = Supply::create([
            'name_ar' => 'مستلزم', 'name_en' => 'Supply Item',
            'quantity' => 10, 'is_active' => true,
        ]);

        $this->actingAs($this->admin)->post("/admin/supplies/{$supply->id}/transactions", [
            'transaction_type' => 'purchase',
            'quantity' => 20,
            'unit_cost' => 5.00,
            'notes' => 'New stock arrival',
        ])->assertRedirect();

        $this->assertDatabaseHas('supply_transactions', [
            'supply_id' => $supply->id,
            'transaction_type' => 'purchase',
        ]);
    }

    public function test_invalid_transaction_type_rejected(): void
    {
        $supply = Supply::create([
            'name_ar' => 'مستلزم', 'name_en' => 'Supply',
            'quantity' => 10, 'is_active' => true,
        ]);

        $this->actingAs($this->admin)->post("/admin/supplies/{$supply->id}/transactions", [
            'transaction_type' => 'manual_deduction',
            'quantity' => 5,
        ])->assertSessionHasErrors('transaction_type');
    }
}
