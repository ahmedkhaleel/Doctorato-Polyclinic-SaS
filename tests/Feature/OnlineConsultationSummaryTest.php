<?php

namespace Tests\Feature;

use App\Events\VisitCompleted;
use App\Models\Doctor;
use App\Models\OnlineConsultation;
use App\Models\Patient;
use App\Services\OnlineConsultationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

/**
 * P3-3 — completing a telemedicine consultation must route the resulting visit
 * through the standard post-visit pipeline (VisitCompleted → summary email/SMS/
 * loyalty), so the patient gets a consultation summary after the call.
 */
class OnlineConsultationSummaryTest extends TestCase
{
    use RefreshDatabase;

    public function test_completing_a_consultation_dispatches_visit_completed(): void
    {
        Event::fake([VisitCompleted::class]);

        $doctor = Doctor::create(['name_ar' => 'د', 'name_en' => 'Dr', 'status' => 'active', 'module' => 'psychiatry']);
        $patient = Patient::create(['full_name' => 'Tele Pt', 'phone' => '0500007777']);
        $patient->forceFill(['is_active' => true, 'file_number' => 'PAT-TS-1'])->save();

        $consultation = OnlineConsultation::create([
            'consultation_number' => 'OC-'.uniqid(),
            'patient_id' => $patient->id, 'doctor_id' => $doctor->id,
            'module' => 'psychiatry',
            'scheduled_date' => now()->toDateString(),
            'start_time' => '10:00', 'end_time' => '10:30',
            'status' => 'in_progress', 'fee' => 200, 'payment_status' => 'paid',
            'session_started_at' => now()->subMinutes(20),
        ]);

        $visit = app(OnlineConsultationService::class)->completeSession($consultation, [
            'diagnosis' => 'Stable', 'doctor_notes' => 'Continue plan',
        ]);

        $this->assertSame('completed', $visit->status);
        $this->assertSame('online', $visit->consultation_type);
        Event::assertDispatched(VisitCompleted::class, fn ($e) => $e->visit->id === $visit->id);
    }
}
