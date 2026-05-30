<?php

namespace Tests\Feature\Admin;

use App\Models\DiscountCode;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDiscountCodeTest extends TestCase
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
            'name' => 'Admin', 'email' => 'admin-disc@test.com',
            'password' => bcrypt('password'), 'role_id' => $role->id, 'is_active' => true,
        ]);
    }

    public function test_can_view_discount_codes_index(): void
    {
        $this->actingAs($this->admin)->get('/admin/discount-codes')->assertOk();
    }

    public function test_can_create_discount_code(): void
    {
        $this->actingAs($this->admin)->post('/admin/discount-codes', [
            'code' => 'SUMMER25',
            'discount_type' => 'percentage',
            'discount_value' => 25,
            'is_active' => true,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addMonth()->toDateString(),
        ])->assertRedirect();

        $this->assertDatabaseHas('discount_codes', ['code' => 'SUMMER25', 'discount_value' => 25]);
    }

    public function test_discount_code_requires_code(): void
    {
        $this->actingAs($this->admin)->post('/admin/discount-codes', [
            'discount_type' => 'percentage',
            'discount_value' => 10,
        ])->assertSessionHasErrors('code');
    }

    public function test_discount_code_requires_type(): void
    {
        $this->actingAs($this->admin)->post('/admin/discount-codes', [
            'code' => 'TEST10',
            'discount_value' => 10,
        ])->assertSessionHasErrors('discount_type');
    }

    public function test_can_update_discount_code(): void
    {
        $code = DiscountCode::create([
            'code' => 'OLD10', 'discount_type' => 'percentage', 'discount_value' => 10,
            'is_active' => true,
        ]);

        $this->actingAs($this->admin)->post("/admin/discount-codes/{$code->id}", [
            'code' => 'NEW20',
            'discount_type' => 'percentage',
            'discount_value' => 20,
            'is_active' => true,
        ])->assertRedirect();

        $this->assertDatabaseHas('discount_codes', ['id' => $code->id, 'code' => 'NEW20', 'discount_value' => 20]);
    }

    public function test_can_delete_discount_code(): void
    {
        $code = DiscountCode::create([
            'code' => 'DELETE', 'discount_type' => 'fixed', 'discount_value' => 50,
            'is_active' => true,
        ]);

        $this->actingAs($this->admin)->post("/admin/discount-codes/{$code->id}/delete")
            ->assertRedirect();

        $this->assertDatabaseMissing('discount_codes', ['id' => $code->id]);
    }

    public function test_duplicate_code_rejected(): void
    {
        DiscountCode::create([
            'code' => 'UNIQUE', 'discount_type' => 'percentage', 'discount_value' => 10,
            'is_active' => true,
        ]);

        $this->actingAs($this->admin)->post('/admin/discount-codes', [
            'code' => 'UNIQUE',
            'discount_type' => 'percentage',
            'discount_value' => 20,
        ])->assertSessionHasErrors('code');
    }
}
