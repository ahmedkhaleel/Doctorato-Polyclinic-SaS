<?php

namespace Tests\Feature\Telemedicine;

use App\Models\Doctor;
use App\Models\DermaSession;
use App\Models\OnlineConsultation;
use App\Models\Patient;
use App\Models\Visit;
use App\Services\OnlineConsultationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Guards two linked regressions:
 *
 *  1. visits.consultation_type used to be enum('dermatology','cosmetic'),
 *     so completeSession() writing 'online' was truncated/rejected by MySQL
 *     — breaking EVERY online-consultation completion. The column is now a
 *     plain string; 'online' must persist.
 *
 *  2. Telemedicine ⟶ Derma link: completing a derma online consultation
 *     must also create a linked DermaSession (type 'other', cost 0), while a
 *     non-derma consultation must NOT.
 */
class CompleteSessionTest extends TestCase
{
    use RefreshDatabase;

    private function makeConsultation(string $module): OnlineConsultation
    {
        $doctor = Doctor::create([
            'name_ar' => 'د. اختبار', 'name_en' => 'Dr. Test',
            'doctor_type' => 'specialist', 'status' => 'active', 'module' => $module,
        ]);
        $patient = Patient::create([
            'full_name' => 'Test Patient', 'phone' => '+971500000001', 'is_active' => true,
        ]);

        return OnlineConsultation::create([
            'patient_id'         => $patient->id,
            'doctor_id'          => $doctor->id,
            'module'             => $module,
            'consultation_type'  => 'initial',
            'status'             => OnlineConsultation::STATUS_IN_PROGRESS,
            'scheduled_date'     => now()->toDateString(),
            'start_time'         => '10:00',
            'end_time'           => '10:30',
            'session_started_at' => now()->subMinutes(10),
            'diagnosis'          => 'Test diagnosis',
        ]);
    }

    public function test_completing_a_derma_consultation_creates_visit_and_derma_session(): void
    {
        $consultation = $this->makeConsultation('derma');

        $visit = app(OnlineConsultationService::class)->completeSession($consultation->fresh());

        // 1) The Visit persisted with the previously-rejected value.
        $this->assertDatabaseHas('visits', [
            'id'                => $visit->id,
            'module'            => 'derma',
            'consultation_type' => 'online',
            'status'            => 'completed',
        ]);

        // 2) The consultation is completed and linked to the visit.
        $this->assertSame(OnlineConsultation::STATUS_COMPLETED, $consultation->fresh()->status);
        $this->assertSame($visit->id, $consultation->fresh()->visit_id);

        // 3) A linked derma session was recorded (no extra charge).
        $session = DermaSession::where('visit_id', $visit->id)->first();
        $this->assertNotNull($session, 'A derma session should be created for a derma online consultation.');
        $this->assertSame('other', $session->session_type);
        $this->assertEquals(0, (float) $session->cost);
    }

    public function test_completing_a_non_derma_consultation_creates_no_derma_session(): void
    {
        $consultation = $this->makeConsultation('dental');

        $visit = app(OnlineConsultationService::class)->completeSession($consultation->fresh());

        $this->assertDatabaseHas('visits', ['id' => $visit->id, 'module' => 'dental']);
        $this->assertSame(0, DermaSession::where('visit_id', $visit->id)->count());
    }
}
