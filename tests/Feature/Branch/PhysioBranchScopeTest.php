<?php

namespace Tests\Feature\Branch;

use App\Models\Branch;
use App\Models\Patient;
use App\Models\PhysioSession;
use App\Services\Branch\BranchContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Branch isolation for the physiotherapy event tables (the "add a domain"
 * contract): sessions stamp a concrete branch on create, and the global scope
 * isolates by the active branch once BRANCHES_ENABLED is on. The shared
 * exercises catalog is intentionally NOT branch-scoped.
 */
class PhysioBranchScopeTest extends TestCase
{
    use RefreshDatabase;

    private function ctx(): BranchContext
    {
        return app(BranchContext::class);
    }

    private function patient(): Patient
    {
        $p = Patient::create(['full_name' => 'P', 'phone' => '0101'.random_int(1000000, 9999999)]);
        $p->forceFill(['is_active' => true])->save();

        return $p;
    }

    private function makeSession(Patient $p): PhysioSession
    {
        return PhysioSession::create([
            'patient_id' => $p->id, 'session_number' => 1, 'session_date' => now()->toDateString(), 'attended' => true,
        ]);
    }

    public function test_disabled_stamps_default_branch_and_does_not_filter(): void
    {
        config(['branches.enabled' => false]);
        $p = $this->patient();
        $s = $this->makeSession($p);
        $this->assertSame(1, (int) $s->branch_id); // always a concrete branch

        PhysioSession::create(['patient_id' => $p->id, 'session_number' => 2, 'session_date' => now()->toDateString(), 'attended' => true, 'branch_id' => 2]);
        $this->assertSame(2, PhysioSession::count()); // no filtering while disabled
    }

    public function test_enabled_isolates_by_active_branch(): void
    {
        config(['branches.enabled' => true]);
        Branch::create(['id' => 2, 'name_ar' => 'B2', 'name_en' => 'B2', 'code' => 'B2']);
        $p = $this->patient();

        $this->ctx()->set(1);
        $this->makeSession($p);
        $this->ctx()->runForBranch(2, fn () => $this->makeSession($p));

        $this->ctx()->set(1);
        $this->assertSame(1, PhysioSession::count());
        $this->assertTrue(PhysioSession::get()->every(fn ($s) => (int) $s->branch_id === 1));

        $this->ctx()->set(2);
        $this->assertSame(1, PhysioSession::count());
    }
}
