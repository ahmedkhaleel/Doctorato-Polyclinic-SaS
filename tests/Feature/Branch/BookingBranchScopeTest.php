<?php

namespace Tests\Feature\Branch;

use App\Models\Booking;
use App\Models\Branch;
use App\Services\Branch\BranchContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingBranchScopeTest extends TestCase
{
    use RefreshDatabase;

    private function ctx(): BranchContext
    {
        return app(BranchContext::class);
    }

    private function makeBranch(int $id, string $code): Branch
    {
        return Branch::create(['id' => $id, 'name_ar' => $code, 'name_en' => $code, 'code' => $code]);
    }

    private function booking(array $attrs = []): Booking
    {
        return Booking::create(array_merge([
            'full_name' => 'B', 'phone' => '0101'.random_int(1000000, 9999999),
            'booking_type' => 'service', 'status' => 'unconfirmed', 'source' => 'website',
            'module' => 'dental', 'preferred_date' => now()->addDay()->toDateString(),
        ], $attrs));
    }

    // ── Kill-switch OFF (default) = single-clinic behaviour ──
    public function test_disabled_does_not_filter_but_still_stamps_default_branch(): void
    {
        config(['branches.enabled' => false]);
        $b = $this->booking();
        $this->assertSame(1, (int) $b->branch_id); // always stamped → Main Branch

        $this->booking(['branch_id' => 2]); // pretend another branch
        $this->assertSame(2, Booking::count()); // no filtering while disabled
    }

    // ── Kill-switch ON = real isolation ──
    public function test_enabled_isolates_by_active_branch(): void
    {
        config(['branches.enabled' => true]);
        $this->makeBranch(2, 'B2');

        $this->ctx()->set(1);
        $this->booking(); // → branch 1
        $this->ctx()->runForBranch(2, fn () => $this->booking()); // → branch 2

        $this->ctx()->set(1);
        $this->assertSame(1, Booking::count()); // sees branch 1 only
        $this->assertTrue(Booking::get()->every(fn ($b) => (int) $b->branch_id === 1));

        $this->ctx()->set(2);
        $this->assertSame(1, Booking::count()); // sees branch 2 only
    }

    public function test_create_stamps_active_branch(): void
    {
        config(['branches.enabled' => true]);
        $this->makeBranch(2, 'B2');
        $this->ctx()->set(2);

        $b = $this->booking();
        $this->assertSame(2, (int) $b->branch_id);
    }

    public function test_all_branches_mode_sees_everything(): void
    {
        config(['branches.enabled' => true]);
        $this->makeBranch(2, 'B2');
        $this->ctx()->set(1);
        $this->booking();
        $this->ctx()->runForBranch(2, fn () => $this->booking());

        $this->ctx()->setAllBranches();
        $this->assertSame(2, Booking::count()); // super-admin / reports view
    }

    public function test_without_global_scope_bypasses_filter(): void
    {
        config(['branches.enabled' => true]);
        $this->makeBranch(2, 'B2');
        $this->ctx()->set(1);
        $this->booking();
        $this->ctx()->runForBranch(2, fn () => $this->booking());

        $this->ctx()->set(1);
        $this->assertSame(2, Booking::withoutGlobalScope('branch')->count());
    }

    public function test_branch_relation_resolves(): void
    {
        config(['branches.enabled' => true]);
        $this->ctx()->set(1);
        $b = $this->booking();
        $this->assertSame('MAIN', $b->branch->code);
    }
}
