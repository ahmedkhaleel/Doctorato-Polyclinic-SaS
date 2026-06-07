<?php

namespace Tests\Feature\Physio;

use App\Models\Doctor;
use App\Models\Invoice;
use App\Models\Patient;
use App\Models\PhysioAssessment;
use App\Models\PhysioSession;
use App\Models\PhysioTreatmentPlan;
use App\Models\Role;
use App\Models\User;
use App\Services\CommissionCalculator;
use App\Services\ModuleManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * PT-2 — doctor-side physiotherapy flow: open a plan, capture an assessment
 * (ROM + MMT + pain map), log a billable session, and confirm pricing/commission
 * + ownership guards hold. Mirrors ObgynDoctorFlowTest / Np6CourseTest.
 */
class PhysiotherapyDoctorFlowTest extends TestCase
{
    use RefreshDatabase;

    private User $doctorUser;

    private Doctor $doctor;

    private Patient $patient;

    protected function setUp(): void
    {
        parent::setUp();
        ModuleManager::flushStaticCache();

        $role = Role::firstOrCreate(['name' => 'doctor'], ['display_name_en' => 'Doctor', 'display_name_ar' => 'طبيب', 'permissions' => ['*'], 'is_system' => true]);
        $role->update(['permissions' => ['*']]);

        $this->doctorUser = User::create(['name' => 'PT Doc', 'email' => 'pt-doc@test.com', 'password' => bcrypt('x'), 'role_id' => $role->id, 'is_active' => true]);
        $this->doctor = Doctor::create(['name_ar' => 'د', 'name_en' => 'PT Doc', 'user_id' => $this->doctorUser->id, 'status' => 'active', 'module' => 'physiotherapy']);

        ModuleManager::enable('physiotherapy');
        ModuleManager::flushStaticCache();

        $this->patient = Patient::create(['full_name' => 'Karim', 'phone' => '0500001111']);
        $this->patient->forceFill(['is_active' => true, 'file_number' => 'PAT-PT-1'])->save();
    }

    public function test_dashboard_and_patient_pages_render(): void
    {
        $this->actingAs($this->doctorUser)->get('/doctor/physiotherapy')->assertOk();
        $this->actingAs($this->doctorUser)->get('/doctor/physiotherapy/patients')->assertOk();
        $this->actingAs($this->doctorUser)->get("/doctor/physiotherapy/patients/{$this->patient->id}")->assertOk();
        $this->actingAs($this->doctorUser)->get('/doctor/physiotherapy/treatment-plans')->assertOk();
    }

    public function test_module_gate_blocks_when_disabled(): void
    {
        ModuleManager::disable('physiotherapy'); // another medical module (derma) stays active
        ModuleManager::flushStaticCache();

        $this->actingAs($this->doctorUser)->get('/doctor/physiotherapy')->assertRedirect();
    }

    public function test_doctor_can_open_a_plan(): void
    {
        $this->actingAs($this->doctorUser)
            ->post("/doctor/physiotherapy/patients/{$this->patient->id}/plans", [
                'title_en' => 'Low back rehab', 'frequency' => '3x/week',
                'duration_weeks' => 4, 'estimated_sessions' => 12,
                'goals' => [['type' => 'pain', 'baseline' => 8, 'target' => 2]],
                'modalities' => ['tens', 'manual', 'exercise'],
            ])->assertRedirect();

        $plan = PhysioTreatmentPlan::where('patient_id', $this->patient->id)->firstOrFail();
        $this->assertSame($this->doctor->id, $plan->doctor_id);
        $this->assertSame('in_progress', $plan->status);
        $this->assertSame(12, $plan->estimated_sessions);
    }

    public function test_assessment_persists_rom_mmt_and_pain_points_with_normatives(): void
    {
        $this->actingAs($this->doctorUser)
            ->post("/doctor/physiotherapy/patients/{$this->patient->id}/assessments", [
                'assessment_date' => '2026-06-01',
                'subjective' => 'LBP 3 weeks', 'diagnosis' => 'Mechanical LBP',
                'rom' => [['joint' => 'knee', 'motion' => 'flexion', 'side' => 'right', 'arom' => 95, 'prom' => 110]],
                'strength' => [['muscle_group' => 'quadriceps', 'side' => 'right', 'grade' => 4]],
                'pain_points' => [['view' => 'back', 'x' => 50, 'y' => 55, 'intensity' => 7, 'pain_type' => 'aching']],
            ])->assertRedirect();

        $a = PhysioAssessment::where('patient_id', $this->patient->id)->with(['romMeasurements', 'strengthTests', 'painPoints'])->firstOrFail();
        $this->assertCount(1, $a->romMeasurements);
        $this->assertCount(1, $a->strengthTests);
        $this->assertCount(1, $a->painPoints);
        // normal_ref auto-filled from AAOS normatives (knee flexion = 135)
        $this->assertSame('135.0', (string) $a->romMeasurements->first()->normal_ref);
        $this->assertSame(4, (int) $a->strengthTests->first()->grade);
    }

    public function test_session_bills_a_module_invoice_and_advances_the_plan(): void
    {
        $plan = PhysioTreatmentPlan::create([
            'patient_id' => $this->patient->id, 'doctor_id' => $this->doctor->id,
            'title_en' => 'Plan', 'estimated_sessions' => 12, 'completed_sessions' => 0,
            'status' => 'in_progress', 'start_date' => '2026-06-01',
        ]);

        $this->actingAs($this->doctorUser)
            ->post("/doctor/physiotherapy/patients/{$this->patient->id}/sessions", [
                'treatment_plan_id' => $plan->id, 'session_date' => '2026-06-02',
                'attended' => true, 'pain_before' => 7, 'pain_after' => 4, 'cost' => 200, 'bill' => true,
            ])->assertRedirect();

        $session = PhysioSession::where('patient_id', $this->patient->id)->firstOrFail();
        $this->assertSame(1, (int) $session->session_number);
        $this->assertNotNull($session->invoice_id);
        $this->assertNotNull($session->invoice_item_id);

        $invoice = Invoice::where('module', 'physiotherapy')->firstOrFail();
        $this->assertEquals(200, (float) $invoice->total);

        $this->assertSame(1, (int) $plan->fresh()->completed_sessions);
    }

    public function test_session_without_cost_falls_back_to_module_session_fee(): void
    {
        $this->actingAs($this->doctorUser)
            ->post("/doctor/physiotherapy/patients/{$this->patient->id}/sessions", [
                'session_date' => '2026-06-03', 'attended' => true, 'bill' => true,
            ])->assertRedirect();

        // seeded physiotherapy session_fee = 200
        $invoice = Invoice::where('module', 'physiotherapy')->firstOrFail();
        $this->assertEquals(200, (float) $invoice->total);
    }

    public function test_plan_status_update_is_ownership_guarded(): void
    {
        $otherDoctorUser = User::create(['name' => 'Other', 'email' => 'other-pt@test.com', 'password' => bcrypt('x'), 'role_id' => $this->doctorUser->role_id, 'is_active' => true]);
        $otherDoctor = Doctor::create(['name_ar' => 'x', 'name_en' => 'Other', 'user_id' => $otherDoctorUser->id, 'status' => 'active', 'module' => 'physiotherapy']);

        $plan = PhysioTreatmentPlan::create([
            'patient_id' => $this->patient->id, 'doctor_id' => $this->doctor->id,
            'title_en' => 'Plan', 'estimated_sessions' => 6, 'status' => 'in_progress', 'start_date' => '2026-06-01',
        ]);

        // Owner can update.
        $this->actingAs($this->doctorUser)->post("/doctor/physiotherapy/plans/{$plan->id}/status", ['status' => 'on_hold'])->assertRedirect();
        $this->assertSame('on_hold', $plan->fresh()->status);

        // A different doctor is forbidden.
        $this->actingAs($otherDoctorUser)->post("/doctor/physiotherapy/plans/{$plan->id}/status", ['status' => 'completed'])->assertForbidden();
        $this->assertSame('on_hold', $plan->fresh()->status);
    }

    public function test_commission_calculator_resolves_physiotherapy_fee_and_commission(): void
    {
        $calc = app(CommissionCalculator::class);

        // No doctor override → module_settings consultation_fee (seeded 250).
        $this->assertEquals(250, $calc->getConsultationFee($this->doctor, 'physiotherapy'));

        // Doctor override wins.
        $this->doctor->update(['physiotherapy_consultation_fee' => 320, 'physiotherapy_consultation_commission' => 55]);
        $this->assertEquals(320, $calc->getConsultationFee($this->doctor->fresh(), 'physiotherapy'));
        $this->assertEquals(55, $calc->getConsultationCommissionRate($this->doctor->fresh(), 'physiotherapy'));
    }
}
