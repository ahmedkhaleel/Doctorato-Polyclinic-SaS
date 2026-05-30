<?php

namespace Tests\Feature\Admin;

use App\Models\PurchaseOrder;
use App\Models\Role;
use App\Models\Supplier;
use App\Models\Supply;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPurchaseOrderTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Supply $supply;

    private Supplier $supplier;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::firstOrCreate(
            ['name' => 'admin'],
            ['display_name_en' => 'Admin', 'display_name_ar' => 'مدير', 'permissions' => ['*'], 'is_system' => true]
        );

        $this->admin = User::create([
            'name' => 'Admin', 'email' => 'admin-po@test.com',
            'password' => bcrypt('password'), 'role_id' => $role->id, 'is_active' => true,
        ]);

        $this->supplier = Supplier::create([
            'name_ar' => 'مورد طبي',
            'name_en' => 'Medical Supplier',
            'code' => 'SUP-001',
            'is_active' => true,
        ]);

        $this->supply = Supply::create([
            'name_ar' => 'قفازات',
            'name_en' => 'Gloves',
            'sku' => 'SUP-GLV-001',
            'quantity' => 100,
            'unit' => 'box',
            'purchase_price' => 25,
            'is_active' => true,
        ]);
    }

    public function test_can_view_purchase_orders_index(): void
    {
        $this->actingAs($this->admin)->get('/admin/purchase-orders')->assertOk();
    }

    public function test_can_create_purchase_order(): void
    {
        $this->actingAs($this->admin)->post('/admin/purchase-orders', [
            'supplier_id' => $this->supplier->id,
            'items' => [
                [
                    'supply_id' => $this->supply->id,
                    'quantity_ordered' => 50,
                    'unit_price' => 20,
                ],
            ],
            'notes' => 'Monthly restock',
        ])->assertRedirect();

        $this->assertDatabaseHas('purchase_orders', [
            'supplier_id' => $this->supplier->id,
            'status' => 'draft',
        ]);
    }

    public function test_purchase_order_requires_items(): void
    {
        $this->actingAs($this->admin)->post('/admin/purchase-orders', [
            'supplier_id' => $this->supplier->id,
        ])->assertSessionHasErrors('items');
    }

    public function test_purchase_order_requires_supplier(): void
    {
        $this->actingAs($this->admin)->post('/admin/purchase-orders', [
            'items' => [
                [
                    'supply_id' => $this->supply->id,
                    'quantity_ordered' => 10,
                    'unit_price' => 25,
                ],
            ],
        ])->assertSessionHasErrors('supplier_id');
    }

    public function test_can_update_status(): void
    {
        $po = PurchaseOrder::create([
            'po_number' => 'PO-TEST-001',
            'supplier_id' => $this->supplier->id,
            'status' => 'draft',
            'order_date' => now(),
            'created_by' => $this->admin->id,
        ]);

        $this->actingAs($this->admin)->post("/admin/purchase-orders/{$po->id}/status", [
            'status' => 'ordered',
        ])->assertRedirect();

        $this->assertDatabaseHas('purchase_orders', [
            'id' => $po->id,
            'status' => 'ordered',
        ]);
    }
}
