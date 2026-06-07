<?php

namespace Tests\Feature\Physio;

use App\Http\Controllers\Patient\PatientPhysioController;
use App\Models\Doctor;
use App\Models\Exercise;
use App\Models\Patient;
use App\Models\PhysioExercisePrescription;
use App\Models\Role;
use App\Models\User;
use App\Services\ModuleManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

/**
 * PT-4 — the home-exercise-program loop: the catalog seeds, a doctor prescribes
 * an exercise, the patient sees it on their portal and self-logs adherence.
 *
 * Patient-side rendering/POST is invoked on the controller directly — the
 * patient.auth + actingAs combo doesn't authenticate in this local MySQL harness
 * (documented in ObgynPatientPortalTest); the doctor side uses real HTTP.
 */
class PhysiotherapyHepTest extends TestCase
{
    use RefreshDatabase;

    private User $doctorUser;

    private Doctor $doctor;

    private User $patientUser;

    private Patient $patient;

    protected function setUp(): void
    {
        parent::setUp();
        ModuleManager::flushStaticCache();

        $role = Role::firstOrCreate(['name' => 'doctor'], ['display_name_en' => 'Doctor', 'display_name_ar' => 'طبيب', 'permissions' => ['*'], 'is_system' => true]);
        $role->update(['permissions' => ['*']]);
        $this->doctorUser = User::create(['name' => 'PT Doc', 'email' => 'hep-doc@test.com', 'password' => bcrypt('x'), 'role_id' => $role->id, 'is_active' => true]);
        $this->doctor = Doctor::create(['name_ar' => 'د', 'name_en' => 'PT Doc', 'user_id' => $this->doctorUser->id, 'status' => 'active', 'module' => 'physiotherapy']);

        ModuleManager::enable('physiotherapy');
        ModuleManager::flushStaticCache();

        $patientRole = Role::firstOrCreate(['name' => 'patient'], ['display_name_en' => 'Patient', 'display_name_ar' => 'مريض', 'permissions' => [], 'is_system' => true]);
        $this->patientUser = User::create(['name' => 'Karim', 'email' => 'hep-pat@test.com', 'password' => bcrypt('x'), 'role_id' => $patientRole->id, 'is_active' => true]);
        $this->patient = Patient::create(['full_name' => 'Karim', 'phone' => '0500004444']);
        $this->patient->forceFill(['user_id' => $this->patientUser->id, 'is_active' => true, 'file_number' => 'PAT-HEP-1'])->save();
    }

    public function test_exercise_catalog_is_seeded(): void
    {
        $this->assertGreaterThanOrEqual(10, Exercise::where('is_active', true)->count());
        $this->assertTrue(Exercise::where('name_en', 'Hamstring stretch')->exists());
    }

    public function test_doctor_prescribes_an_exercise(): void
    {
        $ex = Exercise::where('name_en', 'Glute bridge')->firstOrFail();

        $this->actingAs($this->doctorUser)
            ->post("/doctor/physiotherapy/patients/{$this->patient->id}/exercises", [
                'exercise_id' => $ex->id, 'sets' => 3, 'reps' => 12, 'hold_sec' => 3, 'frequency' => 'daily',
            ])->assertRedirect();

        $rx = PhysioExercisePrescription::where('patient_id', $this->patient->id)->firstOrFail();
        $this->assertSame($ex->id, $rx->exercise_id);
        $this->assertSame('active', $rx->status);
        $this->assertSame($this->doctor->id, $rx->doctor_id);
    }

    public function test_doctor_can_stop_a_prescription(): void
    {
        $rx = PhysioExercisePrescription::create([
            'patient_id' => $this->patient->id, 'doctor_id' => $this->doctor->id,
            'exercise_id' => Exercise::first()->id, 'sets' => 3, 'reps' => 10, 'status' => 'active', 'prescribed_at' => now()->toDateString(),
        ]);

        $this->actingAs($this->doctorUser)
            ->post("/doctor/physiotherapy/exercises/{$rx->id}/stop")
            ->assertRedirect();

        $this->assertSame('stopped', $rx->fresh()->status);
    }

    public function test_patient_sees_hep_and_logs_adherence(): void
    {
        $rx = PhysioExercisePrescription::create([
            'patient_id' => $this->patient->id, 'doctor_id' => $this->doctor->id,
            'exercise_id' => Exercise::where('name_en', 'Clamshell')->first()->id,
            'sets' => 3, 'reps' => 15, 'status' => 'active', 'prescribed_at' => now()->toDateString(),
        ]);

        $controller = app(PatientPhysioController::class);

        // Log adherence (done today).
        $logReq = $this->patientRequest('POST', ['prescription_id' => $rx->id, 'done' => true, 'pain_after' => 2]);
        $controller->logAdherence($logReq);

        $this->assertDatabaseHas('hep_adherence_logs', [
            'patient_id' => $this->patient->id, 'prescription_id' => $rx->id,
            'log_date' => now()->toDateString(), 'done' => true,
        ]);

        // Overview reflects done_today + adherence.
        $req = $this->patientRequest('GET');
        $page = json_decode($controller->overview($req)->toResponse($req)->getContent(), true);
        $this->assertSame('Patient/Physiotherapy/Overview', $page['component']);
        $this->assertCount(1, $page['props']['prescriptions']);
        $this->assertTrue($page['props']['prescriptions'][0]['done_today']);
        $this->assertCount(1, $page['props']['adherence']);
    }

    public function test_adherence_is_ownership_scoped(): void
    {
        // A prescription owned by a different patient.
        $other = Patient::create(['full_name' => 'Other', 'phone' => '0500009999']);
        $other->forceFill(['is_active' => true, 'file_number' => 'PAT-HEP-2'])->save();
        $rx = PhysioExercisePrescription::create([
            'patient_id' => $other->id, 'doctor_id' => $this->doctor->id,
            'exercise_id' => Exercise::first()->id, 'sets' => 3, 'reps' => 10, 'status' => 'active', 'prescribed_at' => now()->toDateString(),
        ]);

        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
        app(PatientPhysioController::class)->logAdherence($this->patientRequest('POST', ['prescription_id' => $rx->id, 'done' => true]));
    }

    /** Build a request authenticated as the patient user (controller-direct). */
    private function patientRequest(string $method, array $data = []): Request
    {
        $user = User::with('patient')->find($this->patientUser->id);
        $request = Request::create('/ar/patient/physiotherapy', $method, $data);
        $request->setUserResolver(fn () => $user);
        $request->headers->set('X-Inertia', 'true');

        return $request;
    }
}
