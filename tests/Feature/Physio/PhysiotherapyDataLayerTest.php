<?php

namespace Tests\Feature\Physio;

use App\Models\Patient;
use App\Models\PhysioSession;
use App\Models\PhysioTreatmentPlan;
use App\Services\Physio\RomNormatives;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * PT-1 — the physiotherapy data layer: tables exist, models persist with their
 * relations/casts, plan progress computes, and ROM normatives resolve.
 */
class PhysiotherapyDataLayerTest extends TestCase
{
    use RefreshDatabase;

    public function test_models_persist_with_relations_and_casts(): void
    {
        $patient = Patient::create(['full_name' => 'Physio P', 'phone' => '01222000111']);

        $plan = PhysioTreatmentPlan::create([
            'patient_id' => $patient->id,
            'title_ar' => 'خطة ظهر', 'title_en' => 'Back plan',
            'goals' => [['type' => 'pain', 'baseline' => 8, 'target' => 2]],
            'modalities' => ['tens', 'manual', 'exercise'],
            'frequency' => '3x/week', 'duration_weeks' => 4,
            'estimated_sessions' => 12, 'completed_sessions' => 3,
            'status' => PhysioTreatmentPlan::STATUS_IN_PROGRESS, 'start_date' => now()->toDateString(),
        ]);

        $this->assertIsArray($plan->goals);
        $this->assertIsArray($plan->modalities);
        $this->assertSame(25, $plan->progress_percentage);     // 3/12
        $this->assertSame(9, $plan->sessions_remaining);
        $this->assertTrue(PhysioTreatmentPlan::active()->whereKey($plan->id)->exists());

        \App\Models\PhysioRomMeasurement::create([
            'patient_id' => $patient->id, 'joint' => 'knee', 'motion' => 'flexion', 'side' => 'right',
            'arom' => 95, 'prom' => 110, 'normal_ref' => RomNormatives::for('knee', 'flexion'),
            'recorded_at' => now()->toDateString(),
        ]);
        \App\Models\PhysioStrengthTest::create([
            'patient_id' => $patient->id, 'muscle_group' => 'quadriceps', 'side' => 'right', 'grade' => 4,
            'recorded_at' => now()->toDateString(),
        ]);
        \App\Models\PhysioPainPoint::create([
            'patient_id' => $patient->id, 'view' => 'back', 'x' => 50, 'y' => 55, 'intensity' => 7, 'pain_type' => 'aching',
        ]);
        $session = PhysioSession::create([
            'patient_id' => $patient->id, 'treatment_plan_id' => $plan->id, 'session_number' => 1,
            'session_date' => now()->toDateString(), 'modalities' => [['type' => 'tens', 'params' => '80Hz/20min']],
            'attended' => true, 'pain_before' => 7, 'pain_after' => 4, 'cost' => 200,
        ]);

        $this->assertSame($plan->id, $session->treatmentPlan->id);
        $this->assertIsArray($session->modalities);
        $this->assertSame(1, $patient->fresh()->id ? PhysioSession::where('patient_id', $patient->id)->count() : 0);
    }

    public function test_rom_normatives_resolve(): void
    {
        $this->assertSame(135.0, RomNormatives::for('knee', 'flexion'));
        $this->assertSame(180.0, RomNormatives::for('shoulder', 'abduction'));
        $this->assertNull(RomNormatives::for('unknown', 'motion'));
        $this->assertContains('lumbar', RomNormatives::joints());
    }

    public function test_exercise_catalog_and_hep_prescription(): void
    {
        $patient = Patient::create(['full_name' => 'HEP P', 'phone' => '01222000222']);
        $ex = \App\Models\Exercise::create([
            'name_ar' => 'تمدد أوتار الركبة', 'name_en' => 'Hamstring stretch', 'region' => 'knee',
            'default_sets' => 3, 'default_reps' => 10, 'default_hold_sec' => 20, 'is_active' => true,
        ]);
        $rx = \App\Models\PhysioExercisePrescription::create([
            'patient_id' => $patient->id, 'exercise_id' => $ex->id, 'sets' => 3, 'reps' => 12,
            'hold_sec' => 30, 'frequency' => 'daily', 'status' => 'active', 'prescribed_at' => now()->toDateString(),
        ]);
        \App\Models\HepAdherenceLog::create([
            'patient_id' => $patient->id, 'prescription_id' => $rx->id, 'log_date' => now()->toDateString(),
            'done' => true, 'pain_after' => 3,
        ]);

        $this->assertSame('Hamstring stretch', $rx->exercise->name_en);
        $this->assertSame(1, \App\Models\HepAdherenceLog::where('patient_id', $patient->id)->where('done', true)->count());
    }
}
