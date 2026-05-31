<?php

namespace Tests\Feature\Branch;

use App\Models\Booking;
use App\Models\Branch;
use App\Models\Patient;
use App\Models\Role;
use App\Models\User;
use App\Services\Branch\BranchContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The crown-jewel multi-branch guarantee: implicit route-model binding resolves
 * through the BelongsToBranch global scope, so a branch admin CANNOT open another
 * branch's record by guessing its URL — it 404s. No per-controller code needed.
 */
class CrossBranchAccessTest extends TestCase
{
    use RefreshDatabase;

    private function adminForBranch(int $branchId): User
    {
        $r = Role::firstOrCreate(['name' => 'admin'],
            ['display_name_en' => 'Admin', 'display_name_ar' => 'مدير', 'permissions' => ['*'], 'is_system' => true]);
        $r->update(['permissions' => ['*']]);
        $u = User::create(['name' => 'A', 'email' => 'a'.uniqid().'@t.com',
            'password' => bcrypt('x'), 'role_id' => $r->id, 'is_active' => true]);
        // Pin to the given branch only (detach the auto-assigned default if different)
        $u->branches()->sync([$branchId]);

        return $u;
    }

    private function bookingInBranch(int $branchId): Booking
    {
        $p = new Patient(['full_name' => 'X', 'phone' => '0102'.random_int(1000000, 9999999)]);
        $p->file_number = 'P-'.uniqid();
        $p->is_active = true;
        $p->save();

        return app(BranchContext::class)->runForBranch($branchId, fn () => Booking::create([
            'patient_id' => $p->id, 'full_name' => $p->full_name, 'phone' => $p->phone,
            'booking_type' => 'service', 'status' => 'confirmed', 'source' => 'website',
            'module' => 'dental', 'preferred_date' => now()->toDateString(),
        ]));
    }

    public function test_branch_admin_cannot_open_another_branchs_booking(): void
    {
        config(['branches.enabled' => true]);
        $b2 = Branch::create(['name_ar' => 'B2', 'name_en' => 'B2', 'code' => 'B2']);

        $admin = $this->adminForBranch(1);                 // assigned to branch 1 only
        $foreign = $this->bookingInBranch($b2->id);        // booking in branch 2

        // Branch-1 admin tries to open the branch-2 booking by URL → not found.
        $this->actingAs($admin)
            ->get("/admin/bookings/{$foreign->id}")
            ->assertNotFound();
    }

    public function test_branch_admin_can_open_own_branch_booking(): void
    {
        config(['branches.enabled' => true]);
        Branch::create(['id' => 2, 'name_ar' => 'B2', 'name_en' => 'B2', 'code' => 'B2']);

        $admin = $this->adminForBranch(1);
        $own = $this->bookingInBranch(1);

        $this->actingAs($admin)
            ->get("/admin/bookings/{$own->id}")
            ->assertOk();
    }
}
