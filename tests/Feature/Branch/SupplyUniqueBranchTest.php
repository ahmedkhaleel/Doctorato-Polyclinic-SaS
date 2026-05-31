<?php

namespace Tests\Feature\Branch;

use App\Models\Branch;
use App\Models\Supply;
use App\Services\Branch\BranchContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SupplyUniqueBranchTest extends TestCase
{
    use RefreshDatabase;

    private function ctx(): BranchContext
    {
        return app(BranchContext::class);
    }

    private function makeSupply(string $sku): Supply
    {
        $s = new Supply([
            'module' => 'dental', 'name_ar' => 'مادة', 'name_en' => 'Item',
            'unit' => 'pcs', 'quantity' => 5, 'min_quantity' => 1, 'sku' => $sku, 'is_active' => true,
        ]);
        $s->save();

        return $s;
    }

    public function test_same_sku_allowed_in_two_branches(): void
    {
        config(['branches.enabled' => true]);
        $b2 = Branch::create(['name_ar' => 'B2', 'name_en' => 'B2', 'code' => 'B2']);

        $this->ctx()->runForBranch(1, fn () => $this->makeSupply('SKU-100'));
        $this->ctx()->runForBranch($b2->id, fn () => $this->makeSupply('SKU-100')); // must NOT collide

        $this->assertSame(2, Supply::withoutGlobalScope('branch')->where('sku', 'SKU-100')->count());
    }

    public function test_duplicate_sku_within_one_branch_is_rejected(): void
    {
        config(['branches.enabled' => true]);
        $this->ctx()->set(1);
        $this->makeSupply('SKU-200');

        $this->expectException(\Illuminate\Database\QueryException::class);
        $this->makeSupply('SKU-200'); // same branch + sku → unique violation
    }
}
