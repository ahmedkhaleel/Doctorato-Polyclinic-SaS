<?php

namespace Tests\Feature\Branch;

use App\Models\Branch;
use App\Models\Doctor;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BranchMemberAssignmentTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $r = Role::firstOrCreate(['name' => 'super_admin'],
            ['display_name_en' => 'Super', 'display_name_ar' => 'سوبر', 'permissions' => ['*'], 'is_system' => true]);

        return User::create(['name' => 'A', 'email' => 'a'.uniqid().'@t.com',
            'password' => bcrypt('x'), 'role_id' => $r->id, 'is_active' => true]);
    }

    public function test_sync_assigns_and_removes_members_preserving_primary(): void
    {
        $admin = $this->admin();
        $b2 = Branch::create(['name_ar' => 'B2', 'name_en' => 'B2', 'code' => 'B2']);

        $staff = $this->admin();          // some user to assign (primary on branch 1 via hook)
        $doc = Doctor::create(['name_ar' => 'د', 'name_en' => 'Dr', 'status' => 'active']);

        // Assign staff + doctor to branch 2
        $this->actingAs($admin)->post("/admin/branches/{$b2->id}/members", [
            'user_ids' => [$staff->id],
            'doctor_ids' => [$doc->id],
        ])->assertRedirect();

        $this->assertTrue($staff->fresh()->branches->contains($b2->id));
        $this->assertTrue($doc->fresh()->branches->contains($b2->id));

        // staff still primary on branch 1 (untouched pivot preserved)
        $this->assertTrue((bool) $staff->branches()->where('branches.id', 1)->first()->pivot->is_primary);

        // Remove them from branch 2
        $this->actingAs($admin)->post("/admin/branches/{$b2->id}/members", [
            'user_ids' => [],
            'doctor_ids' => [],
        ])->assertRedirect();

        $this->assertFalse($staff->fresh()->branches->contains($b2->id));
        $this->assertFalse($doc->fresh()->branches->contains($b2->id));
        // branch-1 assignment intact
        $this->assertTrue($staff->fresh()->branches->contains(1));
    }
}
