<?php

namespace Tests\Feature\Branch;

use App\Models\Booking;
use App\Models\Branch;
use App\Models\Patient;
use App\Models\Visit;
use App\Services\Branch\BranchContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VisitBranchScopeTest extends TestCase
{
    use RefreshDatabase;

    private function ctx(): BranchContext
    {
        return app(BranchContext::class);
    }

    private function patient(): Patient
    {
        $p = new Patient(['full_name' => 'V', 'phone' => '0101'.random_int(1000000, 9999999)]);
        $p->file_number = 'P-V-'.uniqid();
        $p->is_active = true;
        $p->save();

        return $p;
    }

    private function visit(Patient $p): Visit
    {
        $booking = Booking::create([
            'patient_id' => $p->id, 'full_name' => $p->full_name, 'phone' => $p->phone,
            'booking_type' => 'service', 'status' => 'confirmed', 'source' => 'website',
            'module' => 'dental', 'preferred_date' => now()->toDateString(),
        ]);

        return Visit::create([
            'patient_id' => $p->id, 'booking_id' => $booking->id,
            'visit_type' => 'consultation', 'status' => 'completed', 'visit_date' => now()->toDateString(),
        ]);
    }

    public function test_disabled_stamps_main(): void
    {
        config(['branches.enabled' => false]);
        $v = $this->visit($this->patient());
        $this->assertSame(1, (int) $v->branch_id);
    }

    public function test_visits_isolated_and_inherit_active_branch(): void
    {
        config(['branches.enabled' => true]);
        Branch::create(['id' => 2, 'name_ar' => 'B2', 'name_en' => 'B2', 'code' => 'B2']);
        $p = $this->patient();

        $this->ctx()->set(1);
        $this->visit($p);
        $this->ctx()->runForBranch(2, fn () => $this->visit($p));

        $this->ctx()->set(1);
        $this->assertSame(1, Visit::count());
        $this->ctx()->set(2);
        $v = Visit::first();
        $this->assertSame(2, (int) $v->branch_id);
        $this->ctx()->setAllBranches();
        $this->assertSame(2, Visit::count());
    }
}
