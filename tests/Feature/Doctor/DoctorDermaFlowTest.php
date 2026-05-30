<?php

namespace Tests\Feature\Doctor;

use App\Models\Doctor;
use App\Models\Invoice;
use App\Models\Patient;
use App\Models\Role;
use App\Models\User;
use App\Services\ModuleManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Doctor dermatology/cosmetic workspace (P1): the treating doctor can open
 * the derma panel, log a session, and have it billed into a derma-tagged
 * invoice — closing the gap where derma had no doctor-side workspace.
 */
class DoctorDermaFlowTest extends TestCase
{
    use RefreshDatabase;

    private User $doctorUser;

    private Doctor $doctor;

    protected function setUp(): void
    {
        parent::setUp();
        ModuleManager::enable('derma');

        $role = Role::firstOrCreate(
            ['name' => 'doctor'],
            ['display_name_en' => 'Doctor', 'display_name_ar' => 'طبيب', 'permissions' => [], 'is_system' => true]
        );
        $this->doctorUser = User::create([
            'name' => 'Derma Doc', 'email' => 'derma-doc@test.com',
            'password' => bcrypt('password'), 'role_id' => $role->id, 'is_active' => true,
        ]);
        $this->doctor = Doctor::create([
            'name_ar' => 'د. جلدية', 'name_en' => 'Derma Doctor',
            'user_id' => $this->doctorUser->id, 'status' => 'active', 'module' => 'derma',
        ]);
    }

    #[Test]
    public function dashboard_and_patients_pages_render(): void
    {
        $this->actingAs($this->doctorUser)->get('/doctor/derma')->assertOk();
        $this->actingAs($this->doctorUser)->get('/doctor/derma/patients')->assertOk();
        $this->actingAs($this->doctorUser)->get('/doctor/derma/treatment-plans')->assertOk();
    }

    #[Test]
    public function logging_a_session_with_cost_bills_a_derma_tagged_invoice(): void
    {
        $patient = Patient::create(['full_name' => 'Derma Patient', 'phone' => '0100']);

        $this->actingAs($this->doctorUser)
            ->post("/doctor/derma/patients/{$patient->id}/sessions", [
                'session_type' => 'laser', 'area_treated' => 'face', 'cost' => 400,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('derma_sessions', [
            'patient_id' => $patient->id, 'doctor_id' => $this->doctor->id, 'session_type' => 'laser',
        ]);
        $invoice = Invoice::where('module', 'derma')->first();
        $this->assertNotNull($invoice);
        $this->assertEquals(400, (float) $invoice->total);
    }

    #[Test]
    public function zero_cost_session_is_logged_but_not_billed(): void
    {
        $patient = Patient::create(['full_name' => 'NoBill', 'phone' => '0101']);

        $this->actingAs($this->doctorUser)
            ->post("/doctor/derma/patients/{$patient->id}/sessions", ['session_type' => 'peel'])
            ->assertRedirect();

        $this->assertDatabaseHas('derma_sessions', ['patient_id' => $patient->id, 'session_type' => 'peel']);
        $this->assertSame(0, Invoice::where('module', 'derma')->count());
    }

    #[Test]
    public function non_doctor_cannot_access_the_derma_workspace(): void
    {
        $patientRole = Role::firstOrCreate(
            ['name' => 'patient'],
            ['display_name_en' => 'Patient', 'display_name_ar' => 'مريض', 'permissions' => []]
        );
        $user = User::create([
            'name' => 'P', 'email' => 'p-derma@test.com',
            'password' => bcrypt('password'), 'role_id' => $patientRole->id, 'is_active' => true,
        ]);

        $this->actingAs($user)->get('/doctor/derma')->assertRedirect();
    }
}
