<?php

namespace Tests\Feature\Neuropsych;

use App\Models\Booking;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Visit;
use App\Services\BookingWorkflowService;
use App\Services\ModuleManager;
use App\Services\Pricing\PricingResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Unified specialty pricing & follow-up (P1–P3):
 * - P1: module_settings carry consultant/specialist/followup fees + window.
 * - P2: PricingResolver resolves by doctor_type, override, and follow-up.
 * - P3: follow-up eligibility is module-aware (works beyond derma).
 */
class SpecialtyPricingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        ModuleManager::clearCache();
    }

    public function test_p1_pricing_settings_seeded_for_thin_modules(): void
    {
        foreach (['obgyn', 'psychiatry', 'neurology'] as $m) {
            $this->assertNotNull(ModuleManager::getSetting($m, 'consultant_fee'), "$m consultant_fee");
            $this->assertNotNull(ModuleManager::getSetting($m, 'specialist_fee'), "$m specialist_fee");
            $this->assertNotNull(ModuleManager::getSetting($m, 'followup_fee'), "$m followup_fee");
            $this->assertNotNull(ModuleManager::getSetting($m, 'followup_window_days'), "$m followup_window_days");
        }
    }

    public function test_p2_resolver_by_doctor_type_override_and_followup(): void
    {
        $resolver = app(PricingResolver::class);

        $consultant = Doctor::create(['name_ar' => 'د', 'name_en' => 'C', 'status' => 'active', 'module' => 'psychiatry', 'doctor_type' => 'consultant']);
        $specialist = Doctor::create(['name_ar' => 'د', 'name_en' => 'S', 'status' => 'active', 'module' => 'psychiatry', 'doctor_type' => 'specialist']);

        $this->assertEqualsWithDelta(350, $resolver->consultationFee($consultant, 'psychiatry'), 0.01);
        $this->assertEqualsWithDelta(250, $resolver->consultationFee($specialist, 'psychiatry'), 0.01);

        // Follow-up beats grade.
        $this->assertEqualsWithDelta(150, $resolver->consultationFee($consultant, 'psychiatry', true), 0.01);

        // Per-doctor override beats grade.
        $override = Doctor::create(['name_ar' => 'د', 'name_en' => 'O', 'status' => 'active', 'module' => 'neurology', 'doctor_type' => 'consultant', 'neurology_consultation_fee' => 500]);
        $this->assertEqualsWithDelta(500, $resolver->consultationFee($override, 'neurology'), 0.01);
    }

    public function test_p3_followup_eligibility_is_module_aware(): void
    {
        $patient = Patient::create(['full_name' => 'FU', 'phone' => '0500000099']);
        $patient->forceFill(['is_active' => true, 'file_number' => 'PAT-FU-1'])->save();
        $doctor = Doctor::create(['name_ar' => 'د', 'name_en' => 'Doc', 'status' => 'active', 'module' => 'psychiatry']);
        $booking = Booking::create(['booking_number' => Booking::generateBookingNumber(), 'status' => 'completed', 'source' => 'secretary', 'module' => 'psychiatry', 'booking_type' => 'psychiatry_consultation', 'full_name' => 'FU', 'phone' => '0500000099', 'patient_id' => $patient->id]);

        // Recent completed psychiatry consultation → next one is a follow-up.
        Visit::create([
            'patient_id' => $patient->id, 'doctor_id' => $doctor->id, 'booking_id' => $booking->id,
            'visit_type' => 'consultation', 'consultation_type' => 'psychiatry', 'status' => 'completed',
            'visit_date' => now()->subDays(3)->toDateString(),
        ]);

        $svc = app(BookingWorkflowService::class);
        $elig = $svc->checkFollowUpEligibility($patient->id, 'psychiatry');
        $this->assertNotNull($elig);
        $this->assertTrue($elig['eligible']);
        $this->assertEqualsWithDelta(150, $elig['follow_up_fee'], 0.01);

        // A neurology patient with no neuro visit is NOT follow-up eligible.
        $this->assertNull($svc->checkFollowUpEligibility($patient->id, 'neurology'));
    }
}
