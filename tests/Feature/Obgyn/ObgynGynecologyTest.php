<?php

namespace Tests\Feature\Obgyn;

use App\Models\ContraceptionRecord;
use App\Models\Doctor;
use App\Models\Invoice;
use App\Models\PapSmearScreening;
use App\Models\Patient;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * P3: the gynecology side of the OB/GYN doctor workspace — pap smears
 * (billed) and contraception records — with the female-only guard.
 */
class ObgynGynecologyTest extends TestCase
{
    use RefreshDatabase;

    private User $doctorUser;

    protected function setUp(): void
    {
        parent::setUp();
        $role = Role::firstOrCreate(
            ['name' => 'doctor'],
            ['display_name_en' => 'Doctor', 'display_name_ar' => 'طبيب', 'permissions' => [], 'is_system' => true]
        );
        $this->doctorUser = User::create([
            'name' => 'OB', 'email' => 'gyn-doc@test.com',
            'password' => bcrypt('password'), 'role_id' => $role->id, 'is_active' => true,
        ]);
        Doctor::create(['name_ar' => 'د', 'name_en' => 'Dr', 'user_id' => $this->doctorUser->id, 'status' => 'active', 'module' => 'obgyn']);
    }

    #[Test]
    public function gynecology_page_renders(): void
    {
        $this->actingAs($this->doctorUser)->get('/doctor/obgyn/gynecology')->assertOk();
    }

    #[Test]
    public function recording_a_pap_smear_bills_a_module_invoice(): void
    {
        $patient = Patient::create(['full_name' => 'F', 'phone' => '0100', 'gender' => 'female']);

        $this->actingAs($this->doctorUser)
            ->post('/doctor/obgyn/gynecology/pap-smear', [
                'patient_id' => $patient->id, 'test_date' => '2026-05-01', 'result' => 'normal', 'hpv_status' => 'negative', 'bill' => true,
            ])->assertRedirect();

        $this->assertDatabaseHas('pap_smear_screenings', ['patient_id' => $patient->id, 'result' => 'normal']);
        $this->assertEquals(180, (float) Invoice::where('module', 'obgyn')->first()?->total); // seeded pap_smear_fee
    }

    #[Test]
    public function contraception_record_is_saved(): void
    {
        $patient = Patient::create(['full_name' => 'F2', 'phone' => '0101', 'gender' => 'female']);

        $this->actingAs($this->doctorUser)
            ->post('/doctor/obgyn/gynecology/contraception', [
                'patient_id' => $patient->id, 'method' => 'IUD', 'start_date' => '2026-05-01',
            ])->assertRedirect();

        $this->assertDatabaseHas('contraception_records', ['patient_id' => $patient->id, 'method' => 'IUD', 'status' => 'active']);
    }

    #[Test]
    public function gynecology_records_reject_male_patients(): void
    {
        $male = Patient::create(['full_name' => 'M', 'phone' => '0102', 'gender' => 'male']);

        $this->actingAs($this->doctorUser)
            ->post('/doctor/obgyn/gynecology/pap-smear', ['patient_id' => $male->id, 'test_date' => '2026-05-01'])
            ->assertSessionHasErrors('patient_id');

        $this->assertSame(0, PapSmearScreening::count());
        $this->assertSame(0, ContraceptionRecord::count());
    }
}
