<?php

namespace Tests\Feature\Branch;

use App\Models\Branch;
use App\Models\Lead;
use App\Services\Branch\BranchContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttributionBranchTest extends TestCase
{
    use RefreshDatabase;

    private function ctx(): BranchContext
    {
        return app(BranchContext::class);
    }

    public function test_lead_branch_is_stamped_but_not_scoped(): void
    {
        config(['branches.enabled' => true]);
        Branch::create(['id' => 2, 'name_ar' => 'B2', 'name_en' => 'B2', 'code' => 'B2']);

        // Created while branch 2 is active → attributed to branch 2
        $lead = $this->ctx()->runForBranch(2, fn () => Lead::create([
            'full_name' => 'Central Lead', 'phone' => '01099'.random_int(100000, 999999), 'status' => 'new',
        ]));
        $this->assertSame(2, (int) $lead->branch_id);

        // CRM stays central: a branch-1 user still sees the branch-2 lead (NO global scope)
        $this->ctx()->set(1);
        $this->assertSame(1, Lead::count());
        $this->assertNotNull(Lead::find($lead->id));
    }
}
