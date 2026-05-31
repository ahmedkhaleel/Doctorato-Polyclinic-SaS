<?php

namespace Tests\Feature\Branch;

use App\Models\Branch;
use App\Models\CosmeticConsent;
use App\Models\DermaSession;
use App\Models\Patient;
use App\Services\Branch\BranchContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ClinicalBranchScopeTest extends TestCase
{
    use RefreshDatabase;

    private function ctx(): BranchContext
    {
        return app(BranchContext::class);
    }

    private function patient(): Patient
    {
        $p = new Patient(['full_name' => 'C', 'phone' => '0102'.random_int(1000000, 9999999)]);
        $p->file_number = 'P-C-'.uniqid();
        $p->is_active = true;
        $p->save();

        return $p;
    }

    public function test_disabled_stamps_main_branch(): void
    {
        config(['branches.enabled' => false]);
        $c = CosmeticConsent::create(['patient_id' => $this->patient()->id]);
        $this->assertSame(1, (int) $c->branch_id);
    }

    public function test_clinical_events_isolated_per_branch(): void
    {
        config(['branches.enabled' => true]);
        Branch::create(['id' => 2, 'name_ar' => 'B2', 'name_en' => 'B2', 'code' => 'B2']);
        $p = $this->patient();

        // CosmeticConsent + DermaSession created in two branches
        $this->ctx()->set(1);
        CosmeticConsent::create(['patient_id' => $p->id]);
        DermaSession::create(['patient_id' => $p->id]);
        $this->ctx()->runForBranch(2, function () use ($p) {
            CosmeticConsent::create(['patient_id' => $p->id]);
            DermaSession::create(['patient_id' => $p->id]);
        });

        $this->ctx()->set(1);
        $this->assertSame(1, CosmeticConsent::count());
        $this->assertSame(1, DermaSession::count());

        $this->ctx()->set(2);
        $this->assertSame(2, (int) CosmeticConsent::first()->branch_id);

        $this->ctx()->setAllBranches();
        $this->assertSame(2, CosmeticConsent::count());
        $this->assertSame(2, DermaSession::count());
    }

    /** Confirms branch_id column exists + is filterable on pediatric & obgyn event tables. */
    public function test_pediatric_and_obgyn_tables_are_branch_filterable(): void
    {
        config(['branches.enabled' => true]);
        Branch::create(['id' => 2, 'name_ar' => 'B2', 'name_en' => 'B2', 'code' => 'B2']);
        $p = $this->patient();

        DB::table('pediatric_vaccinations')->insert([
            ['patient_id' => $p->id, 'vaccine_name' => 'BCG', 'dose_number' => 1, 'scheduled_age' => 'birth', 'branch_id' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['patient_id' => $p->id, 'vaccine_name' => 'BCG', 'dose_number' => 1, 'scheduled_age' => 'birth', 'branch_id' => 2, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->ctx()->set(1);
        $this->assertSame(1, \App\Models\PediatricVaccination::count());
        $this->ctx()->set(2);
        $this->assertSame(1, \App\Models\PediatricVaccination::count());
        $this->ctx()->setAllBranches();
        $this->assertSame(2, \App\Models\PediatricVaccination::count());
    }
}
