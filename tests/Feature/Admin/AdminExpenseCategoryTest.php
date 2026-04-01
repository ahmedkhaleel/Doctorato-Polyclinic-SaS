<?php

namespace Tests\Feature\Admin;

use App\Models\ExpenseCategory;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminExpenseCategoryTest extends TestCase
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
            'name' => 'Admin', 'email' => 'admin-expcat@test.com',
            'password' => bcrypt('password'), 'role_id' => $role->id, 'is_active' => true,
        ]);
    }

    public function test_can_view_expense_categories(): void
    {
        $this->actingAs($this->admin)->get('/admin/expense-categories')->assertOk();
    }

    public function test_can_create_expense_category(): void
    {
        $this->actingAs($this->admin)->post('/admin/expense-categories', [
            'name_ar' => 'إيجار',
            'name_en' => 'Rent',
        ])->assertRedirect();

        $this->assertDatabaseHas('expense_categories', ['name_en' => 'Rent']);
    }

    public function test_category_requires_name(): void
    {
        $this->actingAs($this->admin)->post('/admin/expense-categories', [])
            ->assertSessionHasErrors(['name_ar', 'name_en']);
    }

    public function test_can_update_expense_category(): void
    {
        $cat = ExpenseCategory::create(['name_ar' => 'قديم', 'name_en' => 'Old']);

        $this->actingAs($this->admin)->put("/admin/expense-categories/{$cat->id}", [
            'name_ar' => 'محدث',
            'name_en' => 'Updated',
        ])->assertRedirect();

        $this->assertDatabaseHas('expense_categories', ['id' => $cat->id, 'name_en' => 'Updated']);
    }

    public function test_can_delete_expense_category(): void
    {
        $cat = ExpenseCategory::create(['name_ar' => 'حذف', 'name_en' => 'Delete Me']);

        $this->actingAs($this->admin)->delete("/admin/expense-categories/{$cat->id}")
            ->assertRedirect();

        $this->assertDatabaseMissing('expense_categories', ['id' => $cat->id]);
    }
}
