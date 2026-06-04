<?php

namespace Tests\Feature\Neuropsych;

use App\Models\Branch;
use App\Models\NeuropsychEncounter;
use App\Models\NeuropsychTreatmentPlan;
use App\Models\Patient;
use App\Services\Branch\BranchContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * NP1 data layer: encounter (with structured MSE) + diagnoses + treatment plan
 * persist correctly, and the branch-scoped clinical events isolate per branch
 * while the shared patient profile does not (covered in NP0).
 */
class Np1EncounterDataTest extends TestCase
{
    use RefreshDatabase;

    private function ctx(): BranchContext
    {
        return app(BranchContext::class);
    }

    private function patient(): Patient
    {
        $p = new Patient(['full_name' => 'NP1', 'phone' => '0102'.random_int(1000000, 9999999)]);
        $p->file_number = 'P-NP1-'.uniqid();
        $p->is_active = true;
        $p->save();

        return $p;
    }

    public function test_encounter_persists_mse_and_diagnoses(): void
    {
        $p = $this->patient();

        $enc = NeuropsychEncounter::create([
            'patient_id' => $p->id,
            'module' => 'psychiatry',
            'encounter_date' => now()->toDateString(),
            'note_format' => 'soap',
            'subjective' => 'Reports 3 weeks of low mood',
            'mse' => ['mood' => 'depressed', 'affect' => 'constricted', 'insight' => 'fair'],
        ]);

        $enc->diagnoses()->create([
            'code_system' => 'dsm5', 'code' => 'F32.1', 'label' => 'Major depressive disorder, moderate', 'is_primary' => true,
        ]);

        $fresh = $enc->fresh('diagnoses');
        $this->assertSame('depressed', $fresh->mse['mood']);
        $this->assertCount(1, $fresh->diagnoses);
        $this->assertTrue($fresh->diagnoses->first()->is_primary);
        $this->assertSame('dsm5', $fresh->diagnoses->first()->code_system);
    }

    public function test_treatment_plan_persists_items(): void
    {
        $p = $this->patient();
        $plan = NeuropsychTreatmentPlan::create([
            'patient_id' => $p->id,
            'module' => 'neurology',
            'title' => 'Migraine management',
            'items' => [['problem' => 'Chronic migraine', 'goal' => '<4 days/mo', 'intervention' => 'Start topiramate']],
            'status' => 'active',
        ]);

        $this->assertSame('Start topiramate', $plan->fresh()->items[0]['intervention']);
    }

    public function test_encounters_isolate_per_branch(): void
    {
        config(['branches.enabled' => true]);
        Branch::create(['id' => 2, 'name_ar' => 'B2', 'name_en' => 'B2', 'code' => 'B2']);
        $p = $this->patient();

        $this->ctx()->set(1);
        NeuropsychEncounter::create(['patient_id' => $p->id, 'module' => 'psychiatry', 'encounter_date' => now()->toDateString()]);
        $this->ctx()->runForBranch(2, fn () => NeuropsychEncounter::create([
            'patient_id' => $p->id, 'module' => 'neurology', 'encounter_date' => now()->toDateString(),
        ]));

        $this->ctx()->set(1);
        $this->assertSame(1, NeuropsychEncounter::count());
        $this->ctx()->set(2);
        $this->assertSame(2, (int) NeuropsychEncounter::first()->branch_id);
        $this->ctx()->setAllBranches();
        $this->assertSame(2, NeuropsychEncounter::count());
    }

    public function test_disabled_branches_stamps_main(): void
    {
        config(['branches.enabled' => false]);
        $enc = NeuropsychEncounter::create([
            'patient_id' => $this->patient()->id, 'module' => 'psychiatry', 'encounter_date' => now()->toDateString(),
        ]);
        $this->assertSame(1, (int) $enc->branch_id);
    }
}
