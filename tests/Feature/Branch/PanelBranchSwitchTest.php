<?php

namespace Tests\Feature\Branch;

use App\Models\Branch;
use App\Models\Doctor;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PanelBranchSwitchTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role): User
    {
        $r = Role::firstOrCreate(['name' => $role],
            ['display_name_en' => $role, 'display_name_ar' => $role, 'permissions' => ['*'], 'is_system' => true]);

        return User::create(['name' => $role, 'email' => $role.'-'.uniqid().'@t.com',
            'password' => bcrypt('x'), 'role_id' => $r->id, 'is_active' => true]);
    }

    public function test_doctor_can_switch_to_assigned_branch(): void
    {
        $b2 = Branch::create(['name_ar' => 'B2', 'name_en' => 'B2', 'code' => 'B2']);
        $u = $this->user('doctor');
        $u->branches()->attach($b2->id);
        // doctor.auth requires a linked, active doctor profile
        Doctor::create(['name_ar' => 'د', 'name_en' => 'Dr', 'status' => 'active', 'user_id' => $u->id]);

        $this->actingAs($u)->post('/doctor/switch-branch', ['branch' => (string) $b2->id])->assertRedirect();
        $this->assertSame($b2->id, session(config('branches.session_key')));
    }

    public function test_secretary_cannot_switch_to_unassigned_branch(): void
    {
        $b2 = Branch::create(['name_ar' => 'B2', 'name_en' => 'B2', 'code' => 'B2']);
        $u = $this->user('secretary'); // only main branch

        $this->actingAs($u)->from('/secretary')->post('/secretary/switch-branch', ['branch' => (string) $b2->id])->assertRedirect();
        $this->assertNotSame($b2->id, session(config('branches.session_key')));
    }
}
