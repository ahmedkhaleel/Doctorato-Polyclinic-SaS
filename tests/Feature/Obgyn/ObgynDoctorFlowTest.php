<?php

namespace Tests\Feature\Obgyn;

use App\Models\Doctor;
use App\Models\Invoice;
use App\Models\Patient;
use App\Models\Pregnancy;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * End-to-end doctor flow for the OB/GYN module: open a pregnancy, record an
 * antenatal visit, confirm it bills into a module-tagged invoice, and that
 * the female-only rule + non-doctor lockout hold.
 */
class ObgynDoctorFlowTest extends TestCase
{
    use RefreshDatabase;

    private User $doctorUser;

    private Doctor $doctor;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::firstOrCreate(
            ['name' => 'doctor'],
            ['display_name_en' => 'Doctor', 'display_name_ar' => 'طبيب', 'permissions' => [], 'is_system' => true]
        );

        $this->doctorUser = User::create([
            'name' => 'OB Doctor', 'email' => 'ob-doc@test.com',
            'password' => bcrypt('password'), 'role_id' => $role->id, 'is_active' => true,
        ]);

        $this->doctor = Doctor::create([
            'name_ar' => 'دكتورة نساء', 'name_en' => 'OB Doctor',
            'user_id' => $this->doctorUser->id, 'status' => 'active', 'module' => 'obgyn',
        ]);
    }

    private function female(): Patient
    {
        return Patient::create(['full_name' => 'Mona', 'phone' => '01000', 'gender' => 'female']);
    }

    #[Test]
    public function doctor_can_open_a_pregnancy_and_edd_is_computed(): void
    {
        $patient = $this->female();

        $this->actingAs($this->doctorUser)
            ->post('/doctor/obgyn/pregnancies', ['patient_id' => $patient->id, 'lmp' => '2026-01-01'])
            ->assertRedirect();

        $pregnancy = Pregnancy::first();
        $this->assertNotNull($pregnancy);
        $this->assertSame('2026-10-08', $pregnancy->edd->toDateString()); // Naegele
        $this->assertSame($this->doctor->id, $pregnancy->doctor_id);
    }

    #[Test]
    public function recording_an_antenatal_visit_bills_a_module_tagged_invoice(): void
    {
        $patient = $this->female();
        $pregnancy = Pregnancy::create([
            'patient_id' => $patient->id, 'doctor_id' => $this->doctor->id,
            'lmp' => '2026-01-01', 'edd' => '2026-10-08', 'status' => 'active',
        ]);

        $this->actingAs($this->doctorUser)
            ->post("/doctor/obgyn/pregnancies/{$pregnancy->id}/antenatal", [
                'visit_date' => '2026-05-01', 'weight_kg' => 64, 'bp_systolic' => 118, 'bp_diastolic' => 76, 'bill' => true,
            ])
            ->assertRedirect();

        $this->assertCount(1, $pregnancy->antenatalVisits);
        $invoice = Invoice::where('module', 'obgyn')->first();
        $this->assertNotNull($invoice);
        $this->assertEquals(150, (float) $invoice->total); // seeded anc_fee
    }

    #[Test]
    public function pregnancy_cannot_be_opened_for_a_male_patient(): void
    {
        $male = Patient::create(['full_name' => 'Sami', 'phone' => '01001', 'gender' => 'male']);

        $this->actingAs($this->doctorUser)
            ->post('/doctor/obgyn/pregnancies', ['patient_id' => $male->id, 'lmp' => '2026-01-01'])
            ->assertSessionHasErrors('patient_id');

        $this->assertSame(0, Pregnancy::count());
    }

    #[Test]
    public function a_patient_cannot_have_two_active_pregnancies(): void
    {
        $patient = $this->female();
        Pregnancy::create(['patient_id' => $patient->id, 'doctor_id' => $this->doctor->id, 'status' => 'active']);

        $this->actingAs($this->doctorUser)
            ->post('/doctor/obgyn/pregnancies', ['patient_id' => $patient->id, 'lmp' => '2026-02-01'])
            ->assertSessionHasErrors('patient_id');

        $this->assertSame(1, Pregnancy::count());
    }

    #[Test]
    public function dashboard_and_index_pages_render(): void
    {
        $this->actingAs($this->doctorUser)->get('/doctor/obgyn')->assertOk();
        $this->actingAs($this->doctorUser)->get('/doctor/obgyn/pregnancies')->assertOk();
    }

    #[Test]
    public function antenatal_card_pdf_renders(): void
    {
        $patient = $this->female();
        $pregnancy = Pregnancy::create([
            'patient_id' => $patient->id, 'doctor_id' => $this->doctor->id,
            'lmp' => '2026-01-01', 'edd' => '2026-10-08', 'status' => 'active',
        ]);
        \App\Models\AntenatalVisit::create(['pregnancy_id' => $pregnancy->id, 'visit_date' => '2026-04-01', 'weight_kg' => 64]);

        $response = $this->actingAs($this->doctorUser)
            ->get("/doctor/obgyn/pregnancies/{$pregnancy->id}/antenatal-card");

        $response->assertOk();
        $this->assertSame('application/pdf', $response->headers->get('content-type'));
    }

    #[Test]
    public function non_doctor_cannot_access_the_module(): void
    {
        $patientRole = Role::firstOrCreate(
            ['name' => 'patient'],
            ['display_name_en' => 'Patient', 'display_name_ar' => 'مريض', 'permissions' => []]
        );
        $user = User::create([
            'name' => 'P', 'email' => 'p-ob@test.com',
            'password' => bcrypt('password'), 'role_id' => $patientRole->id, 'is_active' => true,
        ]);

        $this->actingAs($user)->get('/doctor/obgyn')->assertRedirect();
    }
}
