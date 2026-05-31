<?php

namespace Tests\Feature\Branch;

use App\Models\Booking;
use App\Models\Branch;
use App\Models\Invoice;
use App\Services\Branch\BranchContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BranchNumberingTest extends TestCase
{
    use RefreshDatabase;

    private function ctx(): BranchContext
    {
        return app(BranchContext::class);
    }

    public function test_disabled_keeps_legacy_format(): void
    {
        config(['branches.enabled' => false]);
        $n = Booking::generateBookingNumber();
        $this->assertStringStartsWith('BK-'.now()->format('Ym').'-', $n);
    }

    public function test_main_branch_keeps_legacy_format_when_enabled(): void
    {
        config(['branches.enabled' => true]);
        $this->ctx()->set(1);
        $n = Invoice::generateInvoiceNumber();
        $this->assertStringStartsWith('INV-'.now()->format('Ym').'-', $n);
        $this->assertStringNotContainsString('MAIN', $n);
    }

    public function test_other_branch_gets_code_segment_and_no_collision(): void
    {
        config(['branches.enabled' => true]);
        $b2 = Branch::create(['name_ar' => 'المعادي', 'name_en' => 'Maadi', 'code' => 'MAADI']);

        // Sequence runs independently per branch
        $main1 = $this->ctx()->runForBranch(1, fn () => Booking::generateBookingNumber());
        $maadi1 = $this->ctx()->runForBranch($b2->id, fn () => Booking::generateBookingNumber());

        $this->assertStringStartsWith('BK-'.now()->format('Ym').'-', $main1);
        $this->assertStringStartsWith('BK-MAADI-'.now()->format('Ym').'-', $maadi1);
        $this->assertNotSame($main1, $maadi1);

        // Both first-of-month → both end in 0001 but globally distinct via prefix
        $this->assertStringEndsWith('0001', $main1);
        $this->assertStringEndsWith('0001', $maadi1);
    }

    public function test_sequence_increments_within_a_branch(): void
    {
        config(['branches.enabled' => true]);
        $b2 = Branch::create(['name_ar' => 'B2', 'name_en' => 'B2', 'code' => 'B2X']);

        $this->ctx()->set($b2->id);
        // Persist one so the next generate sees it (query is branch-scoped)
        Booking::create([
            'booking_number' => Booking::generateBookingNumber(),
            'full_name' => 'X', 'phone' => '0102'.random_int(1000000, 9999999),
            'booking_type' => 'service', 'status' => 'pending', 'source' => 'website',
            'module' => 'dental', 'preferred_date' => now()->toDateString(),
        ]);
        $next = Booking::generateBookingNumber();
        $this->assertStringEndsWith('0002', $next);
    }
}
