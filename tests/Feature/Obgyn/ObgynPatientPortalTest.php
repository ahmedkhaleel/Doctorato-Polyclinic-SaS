<?php

namespace Tests\Feature\Obgyn;

use App\Http\Controllers\Patient\PatientObgynController;
use App\Models\AntenatalVisit;
use App\Models\Patient;
use App\Models\Pregnancy;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Patient portal — a patient sees her own pregnancy follow-up.
 *
 * NOTE: the authenticated render is exercised by invoking the controller
 * directly. The patient.auth middleware + actingAs combination does not
 * authenticate in this local MySQL harness (a pre-existing quirk that also
 * affects the committed OnlineConsultationBookingTest), so a direct call is
 * the deterministic way to pin the controller's data-shaping here.
 */
class ObgynPatientPortalTest extends TestCase
{
    use RefreshDatabase;

    private User $patientUser;

    private Patient $patient;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::firstOrCreate(
            ['name' => 'patient'],
            ['display_name_en' => 'Patient', 'display_name_ar' => 'مريض', 'permissions' => [], 'is_system' => true]
        );
        $this->patientUser = User::create([
            'name' => 'Mona', 'email' => 'mona-ob@test.com',
            'password' => bcrypt('password'), 'role_id' => $role->id, 'is_active' => true,
        ]);
        $this->patient = Patient::create([
            'full_name' => 'Mona', 'phone' => '0100', 'gender' => 'female',
        ]);
        // user_id + is_active are guarded against mass assignment — set directly.
        $this->patient->forceFill(['user_id' => $this->patientUser->id, 'is_active' => true])->save();
    }

    private function renderOverview(): array
    {
        $user = User::with('patient')->find($this->patientUser->id);
        $request = Request::create('/ar/patient/obgyn', 'GET');
        $request->setUserResolver(fn () => $user);
        $request->headers->set('X-Inertia', 'true');

        $http = app(PatientObgynController::class)->overview($request)->toResponse($request);

        return json_decode($http->getContent(), true);
    }

    #[Test]
    public function overview_renders_active_pregnancy_with_timeline(): void
    {
        $pregnancy = Pregnancy::create([
            'patient_id' => $this->patient->id, 'lmp' => '2026-01-01', 'edd' => '2026-10-08', 'status' => 'active',
        ]);
        AntenatalVisit::create(['pregnancy_id' => $pregnancy->id, 'visit_date' => '2026-04-01', 'weight_kg' => 63]);

        $page = $this->renderOverview();

        $this->assertSame('Patient/Obgyn/Overview', $page['component']);
        $this->assertSame($pregnancy->id, $page['props']['pregnancy']['id']);
        $this->assertCount(1, $page['props']['pregnancy']['antenatal_visits']);
        $this->assertNotNull($page['props']['pregnancy']['edd']);
    }

    #[Test]
    public function overview_shows_empty_state_without_active_pregnancy(): void
    {
        $page = $this->renderOverview();

        $this->assertSame('Patient/Obgyn/Overview', $page['component']);
        $this->assertNull($page['props']['pregnancy']);
    }

    #[Test]
    public function only_the_patients_own_pregnancy_is_shown(): void
    {
        // Another patient's active pregnancy must not leak in.
        $other = Patient::create(['full_name' => 'Other', 'phone' => '0102', 'gender' => 'female']);
        Pregnancy::create(['patient_id' => $other->id, 'lmp' => '2026-02-01', 'status' => 'active']);

        $page = $this->renderOverview();

        $this->assertNull($page['props']['pregnancy']);
    }

    #[Test]
    public function guest_is_redirected_to_login(): void
    {
        $this->get('/ar/patient/obgyn')->assertRedirect();
    }
}
