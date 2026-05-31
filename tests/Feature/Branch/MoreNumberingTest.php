<?php

namespace Tests\Feature\Branch;

use App\Models\Branch;
use App\Models\DoctorPayout;
use App\Models\SalarySlip;
use App\Services\Branch\BranchContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MoreNumberingTest extends TestCase
{
    use RefreshDatabase;

    private function ctx(): BranchContext
    {
        return app(BranchContext::class);
    }

    public function test_main_branch_keeps_legacy_format(): void
    {
        config(['branches.enabled' => true]);
        $payout = $this->ctx()->runForBranch(1, fn () => DoctorPayout::generatePayoutNumber());
        $slip = $this->ctx()->runForBranch(1, fn () => SalarySlip::generateSlipNumber(5, 2026));

        $this->assertStringStartsWith('PAY-'.now()->format('Ym').'-', $payout);
        $this->assertSame('SAL-202605-0001', $slip);
    }

    public function test_other_branch_gets_code_segment(): void
    {
        config(['branches.enabled' => true]);
        $b2 = Branch::create(['name_ar' => 'B2', 'name_en' => 'B2', 'code' => 'MAADI']);

        $payout = $this->ctx()->runForBranch($b2->id, fn () => DoctorPayout::generatePayoutNumber());
        $slip = $this->ctx()->runForBranch($b2->id, fn () => SalarySlip::generateSlipNumber(5, 2026));

        $this->assertStringStartsWith('PAY-MAADI-', $payout);
        $this->assertSame('SAL-MAADI-202605-0001', $slip);

        // Distinct from the main-branch sequence → no global-unique collision
        $mainSlip = $this->ctx()->runForBranch(1, fn () => SalarySlip::generateSlipNumber(5, 2026));
        $this->assertNotSame($mainSlip, $slip);
    }

    public function test_disabled_keeps_legacy(): void
    {
        config(['branches.enabled' => false]);
        $this->assertStringStartsWith('PAY-'.now()->format('Ym').'-', DoctorPayout::generatePayoutNumber());
    }
}
