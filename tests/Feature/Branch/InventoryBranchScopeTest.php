<?php

namespace Tests\Feature\Branch;

use App\Models\Branch;
use App\Models\Supply;
use App\Services\Branch\BranchContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryBranchScopeTest extends TestCase
{
    use RefreshDatabase;

    private function ctx(): BranchContext
    {
        return app(BranchContext::class);
    }

    private function supply(string $name = 'Gauze'): Supply
    {
        return Supply::create([
            'name_ar' => $name, 'name_en' => $name, 'unit' => 'box',
            'quantity' => 10, 'min_quantity' => 2, 'is_active' => true,
        ]);
    }

    public function test_disabled_stamps_main(): void
    {
        config(['branches.enabled' => false]);
        $this->assertSame(1, (int) $this->supply()->branch_id);
    }

    public function test_stock_isolated_per_branch(): void
    {
        config(['branches.enabled' => true]);
        Branch::create(['id' => 2, 'name_ar' => 'B2', 'name_en' => 'B2', 'code' => 'B2']);

        $this->ctx()->set(1);
        $this->supply('A');
        $this->ctx()->runForBranch(2, fn () => $this->supply('B'));

        $this->ctx()->set(1);
        $this->assertSame(1, Supply::count());
        $this->assertSame('A', Supply::first()->name_en);

        $this->ctx()->set(2);
        $this->assertSame('B', Supply::first()->name_en); // same SKU/name can exist per branch

        $this->ctx()->setAllBranches();
        $this->assertSame(2, Supply::count());
    }
}
