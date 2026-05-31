<?php

namespace Tests\Feature\Branch;

use App\Models\Booking;
use App\Models\Branch;
use App\Services\Branch\BranchContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Guards the most dangerous multi-branch failure mode: a scheduled command or
 * queued job silently scoping to one branch. In console contexts BranchContext
 * must resolve to all-branches so jobs process EVERY branch.
 */
class CronBranchAwarenessTest extends TestCase
{
    use RefreshDatabase;

    private function ctx(): BranchContext
    {
        return app(BranchContext::class);
    }

    private function booking(): Booking
    {
        return Booking::create([
            'full_name' => 'X', 'phone' => '0102'.random_int(1000000, 9999999),
            'booking_type' => 'service', 'status' => 'confirmed', 'source' => 'website',
            'module' => 'dental', 'preferred_date' => now()->toDateString(),
        ]);
    }

    public function test_console_context_sees_all_branches(): void
    {
        config(['branches.enabled' => true]);
        $b2 = Branch::create(['name_ar' => 'B2', 'name_en' => 'B2', 'code' => 'B2']);

        $this->ctx()->runForBranch(1, fn () => $this->booking());
        $this->ctx()->runForBranch($b2->id, fn () => $this->booking());

        // Simulate a fresh cron/queue boot: drop the singleton so it re-resolves.
        // Tests run under the CLI SAPI, so runningInConsole() is true → all-branches.
        app()->forgetInstance(BranchContext::class);

        $this->assertTrue(app(BranchContext::class)->isAllBranches());
        $this->assertSame(2, Booking::count(), 'A cron/job must process every branch, not just branch 1');
    }

    public function test_create_in_console_still_stamps_a_concrete_branch(): void
    {
        config(['branches.enabled' => true]);
        app()->forgetInstance(BranchContext::class); // all-branches in console

        $b = $this->booking();
        $this->assertSame(1, (int) $b->branch_id, 'all-branches create falls back to the default branch');
    }
}
