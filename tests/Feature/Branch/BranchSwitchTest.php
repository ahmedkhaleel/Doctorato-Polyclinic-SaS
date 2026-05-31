<?php

namespace Tests\Feature\Branch;

use App\Models\Branch;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BranchSwitchTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role, array $perms = ['*']): User
    {
        $r = Role::firstOrCreate(['name' => $role],
            ['display_name_en' => $role, 'display_name_ar' => $role, 'permissions' => $perms, 'is_system' => true]);
        $r->update(['permissions' => $perms]);

        return User::create(['name' => $role, 'email' => $role.'-'.uniqid().'@t.com',
            'password' => bcrypt('x'), 'role_id' => $r->id, 'is_active' => true]);
    }

    public function test_users_backfilled_to_main_branch(): void
    {
        $u = $this->user('admin');
        $this->assertTrue($u->belongsToBranch(1));
        $this->assertSame(1, $u->primaryBranchId());
    }

    public function test_switch_to_assigned_branch(): void
    {
        $b2 = Branch::create(['name_ar' => 'B2', 'name_en' => 'B2', 'code' => 'B2']);
        $u = $this->user('admin');
        $u->branches()->attach($b2->id);

        $this->actingAs($u)->post('/admin/switch-branch', ['branch' => (string) $b2->id])->assertRedirect();
        $this->assertSame($b2->id, session(config('branches.session_key')));
    }

    public function test_cannot_switch_to_unassigned_branch(): void
    {
        $b2 = Branch::create(['name_ar' => 'B2', 'name_en' => 'B2', 'code' => 'B2']);
        $u = $this->user('admin'); // only branch 1

        $this->actingAs($u)->from('/admin')->post('/admin/switch-branch', ['branch' => (string) $b2->id])->assertRedirect();
        $this->assertNotSame($b2->id, session(config('branches.session_key')));
    }

    public function test_super_admin_can_switch_all(): void
    {
        $u = $this->user('super_admin');
        $this->assertTrue($u->canSwitchAllBranches());

        $this->actingAs($u)->post('/admin/switch-branch', ['branch' => 'all'])->assertRedirect();
        $this->assertSame('all', session(config('branches.session_key')));
    }

    public function test_non_super_admin_cannot_switch_all(): void
    {
        $u = $this->user('admin');
        $this->actingAs($u)->from('/admin')->post('/admin/switch-branch', ['branch' => 'all'])->assertRedirect();
        $this->assertNotSame('all', session(config('branches.session_key')));
    }

    public function test_branches_crud(): void
    {
        $u = $this->user('super_admin');

        $this->actingAs($u)->post('/admin/branches', [
            'name_ar' => 'المعادي', 'name_en' => 'Maadi', 'code' => 'MAADI',
        ])->assertRedirect();
        $this->assertDatabaseHas('branches', ['code' => 'MAADI']);

        $b = Branch::where('code', 'MAADI')->first();
        $this->actingAs($u)->post("/admin/branches/{$b->id}/delete")->assertRedirect();
        $this->assertFalse((bool) $b->fresh()->is_active);
    }

    public function test_default_branch_cannot_be_deactivated(): void
    {
        $u = $this->user('super_admin');
        $this->actingAs($u)->from('/admin/branches')->post('/admin/branches/1/delete')->assertRedirect();
        $this->assertTrue((bool) Branch::find(1)->is_active);
    }
}
