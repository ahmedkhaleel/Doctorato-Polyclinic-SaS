<?php

namespace Tests\Feature\Neuropsych;

use App\Models\CourseSession;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Role;
use App\Models\TreatmentCourse;
use App\Models\User;
use App\Services\ModuleManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * NP6 — treatment courses (ECT/rTMS/ketamine): the consent gate blocks sessions
 * until consent is signed, and completed sessions are billed.
 */
class Np6CourseTest extends TestCase
{
    use RefreshDatabase;

    private User $doctorUser;

    private Patient $patient;

    protected function setUp(): void
    {
        parent::setUp();
        ModuleManager::flushStaticCache();

        $role = Role::firstOrCreate(['name' => 'doctor'], ['display_name_en' => 'Doctor', 'display_name_ar' => 'طبيب', 'permissions' => ['*'], 'is_system' => true]);
        $role->update(['permissions' => ['*']]);
        $this->doctorUser = User::create(['name' => 'Doc', 'email' => 'np6-doc@test.com', 'password' => bcrypt('x'), 'role_id' => $role->id, 'is_active' => true]);
        Doctor::create(['name_ar' => 'د', 'name_en' => 'Doc', 'user_id' => $this->doctorUser->id, 'status' => 'active', 'module' => 'psychiatry']);

        ModuleManager::enable('psychiatry');
        ModuleManager::flushStaticCache();

        $this->patient = Patient::create(['full_name' => 'Pat', 'phone' => '0500008888']);
        $this->patient->forceFill(['is_active' => true, 'file_number' => 'PAT-NP6-1'])->save();
    }

    private function makeCourse(bool $consentRequired = true): TreatmentCourse
    {
        $this->actingAs($this->doctorUser)->post('/doctor/psychiatry/courses', [
            'patient_id' => $this->patient->id, 'type' => 'rtms', 'planned_sessions' => 20,
            'session_fee' => 400, 'consent_required' => $consentRequired,
        ])->assertRedirect();

        return TreatmentCourse::where('patient_id', $this->patient->id)->latest('id')->firstOrFail();
    }

    public function test_index_renders(): void
    {
        $this->actingAs($this->doctorUser)->get('/doctor/psychiatry/courses')->assertOk();
    }

    public function test_session_blocked_until_consent_signed(): void
    {
        $course = $this->makeCourse(true);

        $this->actingAs($this->doctorUser)
            ->from('/doctor/psychiatry/courses')
            ->post("/doctor/psychiatry/courses/{$course->id}/sessions", [
                'performed_at' => now()->toDateString(), 'cost' => 400, 'completed_at' => now()->toDateString(),
            ])->assertSessionHasErrors('consent');

        $this->assertSame(0, $course->sessions()->count());
    }

    public function test_after_consent_session_is_added_and_billed(): void
    {
        $course = $this->makeCourse(true);

        $this->actingAs($this->doctorUser)->post("/doctor/psychiatry/courses/{$course->id}/consent", ['signed_by' => 'patient'])->assertRedirect();
        $this->assertNotNull($course->fresh()->consent_signed_at);

        $this->actingAs($this->doctorUser)->post("/doctor/psychiatry/courses/{$course->id}/sessions", [
            'performed_at' => now()->toDateString(), 'cost' => 400, 'completed_at' => now()->toDateString(),
        ])->assertRedirect();

        $session = CourseSession::where('treatment_course_id', $course->id)->firstOrFail();
        $this->assertSame(1, $session->session_number);
        $this->assertNotNull($session->invoice_item_id);
        $this->assertDatabaseHas('invoice_items', ['id' => $session->invoice_item_id, 'total' => 400]);
    }

    public function test_session_numbers_increment(): void
    {
        $course = $this->makeCourse(false); // no consent required
        foreach ([1, 2] as $_) {
            $this->actingAs($this->doctorUser)->post("/doctor/psychiatry/courses/{$course->id}/sessions", [
                'performed_at' => now()->toDateString(),
            ])->assertRedirect();
        }
        $this->assertSame([1, 2], $course->sessions()->orderBy('session_number')->pluck('session_number')->all());
    }
}
