<?php

namespace Tests\Unit\Models;

use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleModelTest extends TestCase
{
    use RefreshDatabase;

    // ─── Wildcard Permission ───────────────────────────

    public function test_wildcard_permission_grants_all(): void
    {
        $role = Role::firstOrCreate(
            ['name' => 'superadmin'],
            [
                'display_name_en' => 'Super Admin',
                'display_name_ar' => 'مدير أعلى',
                'permissions' => ['*'],
                'is_system' => true,
            ]
        );

        $this->assertTrue($role->hasPermission('patients.view'));
        $this->assertTrue($role->hasPermission('invoices.create'));
        $this->assertTrue($role->hasPermission('anything.at.all'));
    }

    // ─── Specific Permission ───────────────────────────

    public function test_specific_permission_check(): void
    {
        $role = Role::firstOrCreate(
            ['name' => 'limited_role'],
            [
                'display_name_en' => 'Limited',
                'display_name_ar' => 'محدود',
                'permissions' => ['patients.view', 'patients.create'],
                'is_system' => false,
            ]
        );

        $this->assertTrue($role->hasPermission('patients.view'));
        $this->assertTrue($role->hasPermission('patients.create'));
    }

    // ─── Missing Permission ────────────────────────────

    public function test_missing_permission_returns_false(): void
    {
        $role = Role::firstOrCreate(
            ['name' => 'viewer_role'],
            [
                'display_name_en' => 'Viewer',
                'display_name_ar' => 'مشاهد',
                'permissions' => ['patients.view'],
                'is_system' => false,
            ]
        );

        $this->assertFalse($role->hasPermission('patients.delete'));
        $this->assertFalse($role->hasPermission('invoices.create'));
    }

    // ─── Empty Permissions ─────────────────────────────

    public function test_empty_permissions_denies_all(): void
    {
        $role = Role::firstOrCreate(
            ['name' => 'empty_role'],
            [
                'display_name_en' => 'Empty',
                'display_name_ar' => 'فارغ',
                'permissions' => [],
                'is_system' => false,
            ]
        );

        $this->assertFalse($role->hasPermission('patients.view'));
        $this->assertFalse($role->hasPermission('invoices.create'));
        $this->assertFalse($role->hasPermission('*'));
    }
}
