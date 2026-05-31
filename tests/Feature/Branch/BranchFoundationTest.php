<?php

namespace Tests\Feature\Branch;

use App\Models\Branch;
use App\Services\Branch\BranchContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BranchFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_main_branch_is_seeded(): void
    {
        $main = Branch::find(1);
        $this->assertNotNull($main);
        $this->assertSame('MAIN', $main->code);
        $this->assertTrue($main->is_default);
        $this->assertTrue($main->is_active);
    }

    public function test_console_context_defaults_to_all_branches(): void
    {
        // Tests run under the CLI SAPI (like cron/queue), so the context must
        // default to all-branches — a scheduled job must never silently scope
        // to one branch. HTTP requests resolve a concrete branch instead.
        $ctx = app(BranchContext::class);
        $this->assertTrue($ctx->isAllBranches());
        $this->assertNull($ctx->currentId());
    }

    public function test_set_and_all_branches_toggle(): void
    {
        $ctx = app(BranchContext::class);
        $ctx->set(5);
        $this->assertSame(5, $ctx->currentId());

        $ctx->setAllBranches();
        $this->assertTrue($ctx->isAllBranches());
        $this->assertNull($ctx->currentId());
    }

    public function test_run_for_branch_restores_previous_state(): void
    {
        $ctx = app(BranchContext::class);
        $ctx->set(2);

        $inside = $ctx->runForBranch(9, fn () => $ctx->currentId());

        $this->assertSame(9, $inside);
        $this->assertSame(2, $ctx->currentId()); // restored
    }

    public function test_run_without_scope_restores_previous_state(): void
    {
        $ctx = app(BranchContext::class);
        $ctx->set(3);

        $inside = $ctx->runWithoutScope(fn () => $ctx->isAllBranches());

        $this->assertTrue($inside);
        $this->assertFalse($ctx->isAllBranches()); // restored
        $this->assertSame(3, $ctx->currentId());
    }

    public function test_context_is_singleton(): void
    {
        $this->assertSame(app(BranchContext::class), app(BranchContext::class));
    }

    public function test_branch_localised_name(): void
    {
        $b = new Branch(['name_ar' => 'فرع', 'name_en' => 'Branch']);
        $this->assertSame('فرع', $b->name('ar'));
        $this->assertSame('Branch', $b->name('en'));
    }
}
