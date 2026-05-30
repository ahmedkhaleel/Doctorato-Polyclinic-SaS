<?php

namespace Tests\Feature\Admin;

use App\Models\PaymentMethod;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPaymentMethodTest extends TestCase
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
            'name' => 'Admin', 'email' => 'admin-pm@test.com',
            'password' => bcrypt('password'), 'role_id' => $role->id, 'is_active' => true,
        ]);
    }

    public function test_can_view_payment_methods_index(): void
    {
        $this->actingAs($this->admin)->get('/admin/payment-methods')->assertOk();
    }

    public function test_can_create_payment_method(): void
    {
        $this->actingAs($this->admin)->post('/admin/payment-methods', [
            'name_ar' => 'نقدي',
            'name_en' => 'Cash',
            'is_active' => true,
            'sort_order' => 1,
        ])->assertRedirect();

        $this->assertDatabaseHas('payment_methods', ['name_en' => 'Cash', 'is_active' => true]);
    }

    public function test_payment_method_requires_names(): void
    {
        $this->actingAs($this->admin)->post('/admin/payment-methods', [])
            ->assertSessionHasErrors(['name_ar', 'name_en']);
    }

    public function test_can_update_payment_method(): void
    {
        $pm = PaymentMethod::create([
            'name_ar' => 'نقدي', 'name_en' => 'Cash', 'is_active' => true,
        ]);

        $this->actingAs($this->admin)->post("/admin/payment-methods/{$pm->id}/update", [
            'name_ar' => 'بطاقة',
            'name_en' => 'Card',
            'is_active' => true,
        ])->assertRedirect();

        $this->assertDatabaseHas('payment_methods', ['id' => $pm->id, 'name_en' => 'Card']);
    }

    public function test_can_deactivate_payment_method(): void
    {
        $pm = PaymentMethod::create([
            'name_ar' => 'شيك', 'name_en' => 'Check', 'is_active' => true,
        ]);

        $this->actingAs($this->admin)->post("/admin/payment-methods/{$pm->id}/update", [
            'name_ar' => 'شيك',
            'name_en' => 'Check',
            'is_active' => false,
        ])->assertRedirect();

        $this->assertDatabaseHas('payment_methods', ['id' => $pm->id, 'is_active' => false]);
    }
}
