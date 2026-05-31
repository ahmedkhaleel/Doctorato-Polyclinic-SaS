<?php

namespace Tests\Feature\Admin;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Financial reports (P&L, revenue analytics) require the dedicated
 * `reports.financial` permission, so a role can hold general `reports.view`
 * without seeing revenue figures.
 */
class FinancialReportPermissionTest extends TestCase
{
    use RefreshDatabase;

    private function userWith(array $perms): User
    {
        $r = Role::create([
            'name' => 'r'.uniqid(), 'display_name_en' => 'R', 'display_name_ar' => 'R',
            'permissions' => $perms, 'is_system' => false,
        ]);
        // AdminAuth whitelists admin/super_admin role names — alias to admin for access.
        $r->update(['name' => 'admin']);
        Role::where('id', '!=', $r->id)->where('name', 'admin')->delete();

        return User::create(['name' => 'U', 'email' => 'u'.uniqid().'@t.com',
            'password' => bcrypt('x'), 'role_id' => $r->id, 'is_active' => true]);
    }

    public function test_reports_view_without_financial_is_blocked_from_financial_report(): void
    {
        $user = $this->userWith(['reports.view']); // operational reports only
        $this->actingAs($user)->get('/admin/reports/financial')->assertForbidden();
        $this->actingAs($user)->get('/admin/reports/revenue-analytics')->assertForbidden();
        // but general reports still work
        $this->actingAs($user)->get('/admin/reports')->assertOk();
    }

    public function test_reports_financial_grants_access(): void
    {
        $user = $this->userWith(['reports.view', 'reports.financial']);
        $this->actingAs($user)->get('/admin/reports/financial')->assertOk();
    }
}
