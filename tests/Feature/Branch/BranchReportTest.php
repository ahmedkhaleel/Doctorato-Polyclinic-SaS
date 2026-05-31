<?php

namespace Tests\Feature\Branch;

use App\Models\Booking;
use App\Models\Branch;
use App\Models\Patient;
use App\Models\Visit;
use App\Services\Branch\BranchContext;
use App\Services\Branch\BranchReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BranchReportTest extends TestCase
{
    use RefreshDatabase;

    private function ctx(): BranchContext
    {
        return app(BranchContext::class);
    }

    private function bookingWithVisit(): void
    {
        $p = new Patient(['full_name' => 'R', 'phone' => '0104'.random_int(1000000, 9999999)]);
        $p->file_number = 'P-R-'.uniqid();
        $p->is_active = true;
        $p->save();

        $b = Booking::create([
            'patient_id' => $p->id, 'full_name' => $p->full_name, 'phone' => $p->phone,
            'booking_type' => 'service', 'status' => 'confirmed', 'source' => 'website',
            'module' => 'dental', 'preferred_date' => now()->toDateString(),
        ]);
        Visit::create([
            'patient_id' => $p->id, 'booking_id' => $b->id, 'visit_type' => 'consultation',
            'status' => 'completed', 'visit_date' => now()->toDateString(),
        ]);
    }

    public function test_comparison_breaks_down_by_branch(): void
    {
        config(['branches.enabled' => true]);
        $b2 = Branch::create(['name_ar' => 'فرع ٢', 'name_en' => 'Branch 2', 'code' => 'B2']);

        // 2 bookings+visits in branch 1, 1 in branch 2
        $this->ctx()->runForBranch(1, fn () => $this->bookingWithVisit());
        $this->ctx()->runForBranch(1, fn () => $this->bookingWithVisit());
        $this->ctx()->runForBranch($b2->id, fn () => $this->bookingWithVisit());

        $report = app(BranchReportService::class)->comparison();

        $byId = collect($report['rows'])->keyBy('branch_id');
        $this->assertSame(2, $byId[1]['bookings']);
        $this->assertSame(2, $byId[1]['visits']);
        $this->assertSame(1, $byId[$b2->id]['bookings']);
        $this->assertSame(1, $byId[$b2->id]['visits']);
        $this->assertSame(3, $report['totals']['bookings']);
        $this->assertSame(3, $report['totals']['visits']);
    }

    public function test_comparison_is_correct_even_when_caller_is_scoped_to_one_branch(): void
    {
        config(['branches.enabled' => true]);
        $b2 = Branch::create(['name_ar' => 'B2', 'name_en' => 'B2', 'code' => 'B2']);
        $this->ctx()->runForBranch(1, fn () => $this->bookingWithVisit());
        $this->ctx()->runForBranch($b2->id, fn () => $this->bookingWithVisit());

        // Caller pinned to branch 1 must still get BOTH branches in the comparison
        $this->ctx()->set(1);
        $report = app(BranchReportService::class)->comparison();
        $this->assertSame(2, $report['totals']['bookings']);
    }
}
