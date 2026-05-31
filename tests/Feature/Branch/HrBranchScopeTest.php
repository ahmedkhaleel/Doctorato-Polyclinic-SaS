<?php

namespace Tests\Feature\Branch;

use App\Models\Branch;
use App\Models\Employee;
use App\Models\Role;
use App\Models\User;
use App\Services\Branch\BranchContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HrBranchScopeTest extends TestCase
{
    use RefreshDatabase;

    private function ctx(): BranchContext
    {
        return app(BranchContext::class);
    }

    private function employee(string $num): Employee
    {
        $role = Role::firstOrCreate(['name' => 'employee'],
            ['display_name_en' => 'E', 'display_name_ar' => 'م', 'permissions' => [], 'is_system' => true]);
        $user = User::create(['name' => $num, 'email' => $num.'-'.uniqid().'@t.com',
            'password' => bcrypt('x'), 'role_id' => $role->id, 'is_active' => true]);

        return Employee::create([
            'user_id' => $user->id, 'employee_number' => $num, 'job_title_ar' => 'موظف', 'job_title_en' => 'Staff',
            'hire_date' => now()->toDateString(), 'basic_salary' => 5000, 'status' => 'active',
        ]);
    }

    public function test_disabled_stamps_main(): void
    {
        config(['branches.enabled' => false]);
        $this->assertSame(1, (int) $this->employee('E-'.uniqid())->branch_id);
    }

    public function test_staff_isolated_per_branch(): void
    {
        config(['branches.enabled' => true]);
        Branch::create(['id' => 2, 'name_ar' => 'B2', 'name_en' => 'B2', 'code' => 'B2']);

        $this->ctx()->set(1);
        $this->employee('E-A');
        $this->ctx()->runForBranch(2, fn () => $this->employee('E-B'));

        $this->ctx()->set(1);
        $this->assertSame(1, Employee::count());
        $this->ctx()->set(2);
        $this->assertSame('E-B', Employee::first()->employee_number);
        $this->ctx()->setAllBranches();
        $this->assertSame(2, Employee::count());
    }
}
