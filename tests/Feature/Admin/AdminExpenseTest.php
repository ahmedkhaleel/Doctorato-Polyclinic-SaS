<?php

namespace Tests\Feature\Admin;

use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminExpenseTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private ExpenseCategory $category;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::firstOrCreate(
            ['name' => 'admin'],
            ['display_name_en' => 'Admin', 'display_name_ar' => 'مدير', 'permissions' => ['*'], 'is_system' => true]
        );

        $this->admin = User::create([
            'name' => 'Admin', 'email' => 'admin-exp@test.com',
            'password' => bcrypt('password'), 'role_id' => $role->id, 'is_active' => true,
        ]);

        $this->category = ExpenseCategory::create([
            'name_ar' => 'مستلزمات', 'name_en' => 'Supplies',
        ]);
    }

    public function test_can_view_expenses_index(): void
    {
        $this->actingAs($this->admin)->get('/admin/expenses')->assertOk();
    }

    public function test_can_view_create_page(): void
    {
        $this->actingAs($this->admin)->get('/admin/expenses/create')->assertOk();
    }

    public function test_can_create_expense(): void
    {
        $this->actingAs($this->admin)->post('/admin/expenses', [
            'expense_category_id' => $this->category->id,
            'amount' => 150.50,
            'expense_date' => now()->toDateString(),
            'description' => 'Office supplies purchase',
        ])->assertRedirect();

        $this->assertDatabaseHas('expenses', [
            'expense_category_id' => $this->category->id,
            'amount' => 150.50,
        ]);
    }

    public function test_expense_requires_category(): void
    {
        $this->actingAs($this->admin)->post('/admin/expenses', [
            'amount' => 100,
            'expense_date' => now()->toDateString(),
        ])->assertSessionHasErrors('expense_category_id');
    }

    public function test_expense_requires_amount(): void
    {
        $this->actingAs($this->admin)->post('/admin/expenses', [
            'expense_category_id' => $this->category->id,
            'expense_date' => now()->toDateString(),
        ])->assertSessionHasErrors('amount');
    }

    public function test_expense_requires_date(): void
    {
        $this->actingAs($this->admin)->post('/admin/expenses', [
            'expense_category_id' => $this->category->id,
            'amount' => 100,
        ])->assertSessionHasErrors('expense_date');
    }

    public function test_amount_must_be_positive(): void
    {
        $this->actingAs($this->admin)->post('/admin/expenses', [
            'expense_category_id' => $this->category->id,
            'amount' => -5,
            'expense_date' => now()->toDateString(),
        ])->assertSessionHasErrors('amount');
    }

    public function test_can_update_expense(): void
    {
        $expense = Expense::create([
            'expense_category_id' => $this->category->id,
            'amount' => 100,
            'expense_date' => now()->toDateString(),
            'description' => 'Old description',
            'created_by' => $this->admin->id,
        ]);

        $this->actingAs($this->admin)->put("/admin/expenses/{$expense->id}", [
            'expense_category_id' => $this->category->id,
            'amount' => 200,
            'expense_date' => now()->toDateString(),
            'description' => 'Updated description',
        ])->assertRedirect();

        $this->assertDatabaseHas('expenses', ['id' => $expense->id, 'amount' => 200]);
    }

    public function test_can_delete_expense(): void
    {
        $expense = Expense::create([
            'expense_category_id' => $this->category->id,
            'amount' => 50,
            'expense_date' => now()->toDateString(),
            'created_by' => $this->admin->id,
        ]);

        $this->actingAs($this->admin)->delete("/admin/expenses/{$expense->id}")
            ->assertRedirect();

        $this->assertDatabaseMissing('expenses', ['id' => $expense->id]);
    }

    public function test_non_admin_cannot_access_expenses(): void
    {
        $patientRole = Role::firstOrCreate(
            ['name' => 'patient'],
            ['display_name_en' => 'Patient', 'display_name_ar' => 'مريض', 'permissions' => []]
        );

        $patient = User::create([
            'name' => 'Patient', 'email' => 'pat-exp@test.com',
            'password' => bcrypt('password'), 'role_id' => $patientRole->id, 'is_active' => true,
        ]);

        $response = $this->actingAs($patient)->get('/admin/expenses');
        $this->assertContains($response->status(), [302, 401, 403]);
    }
}
