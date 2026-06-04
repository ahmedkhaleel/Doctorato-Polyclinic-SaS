<?php

namespace Tests\Feature\Neuropsych;

use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Role;
use App\Models\ScaleResult;
use App\Models\User;
use App\Services\ModuleManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * NP2b — recording scales end-to-end from both sides: clinician (doctor panel)
 * and patient (portal pre-visit fill), plus the track guard.
 */
class Np2ScaleFlowTest extends TestCase
{
    use RefreshDatabase;

    private User $doctorUser;

    private User $patientUser;

    private Patient $patient;

    protected function setUp(): void
    {
        parent::setUp();
        ModuleManager::flushStaticCache();

        $docRole = Role::firstOrCreate(['name' => 'doctor'], ['display_name_en' => 'Doctor', 'display_name_ar' => 'طبيب', 'permissions' => ['*'], 'is_system' => true]);
        $docRole->update(['permissions' => ['*']]);
        $this->doctorUser = User::create(['name' => 'Doc', 'email' => 'np2-doc@test.com', 'password' => bcrypt('x'), 'role_id' => $docRole->id, 'is_active' => true]);
        Doctor::create(['name_ar' => 'د', 'name_en' => 'Doc', 'user_id' => $this->doctorUser->id, 'status' => 'active', 'module' => 'psychiatry']);

        $patRole = Role::firstOrCreate(['name' => 'patient'], ['display_name_en' => 'Patient', 'display_name_ar' => 'مريض', 'permissions' => [], 'is_system' => true]);
        $this->patientUser = User::create(['name' => 'Pat', 'email' => 'np2-pat@test.com', 'password' => bcrypt('x'), 'role_id' => $patRole->id, 'is_active' => true]);
        $this->patient = Patient::create(['full_name' => 'Pat', 'phone' => '0500004444']);
        $this->patient->forceFill(['user_id' => $this->patientUser->id, 'is_active' => true, 'file_number' => 'PAT-NP2-1'])->save();

        ModuleManager::enable('psychiatry');
        ModuleManager::flushStaticCache();
    }

    public function test_doctor_records_a_scale_with_computed_score(): void
    {
        $this->actingAs($this->doctorUser)->post('/doctor/psychiatry/scales', [
            'patient_id' => $this->patient->id,
            'scale_key' => 'phq9',
            'answers' => array_fill(0, 9, 2), // 18 → moderately severe, item 9=2 → flag
        ])->assertRedirect();

        $r = ScaleResult::where('patient_id', $this->patient->id)->firstOrFail();
        $this->assertSame('doctor', $r->entered_by);
        $this->assertSame(18, $r->score);
        $this->assertSame('Moderately severe', $r->severity);
        $this->assertTrue($r->flag);
    }

    public function test_doctor_cannot_record_a_scale_from_another_track(): void
    {
        // hit6 is a neurology scale → rejected under the psychiatry route.
        $this->actingAs($this->doctorUser)->post('/doctor/psychiatry/scales', [
            'patient_id' => $this->patient->id,
            'scale_key' => 'hit6',
            'answers' => array_fill(0, 6, 10),
        ])->assertSessionHasErrors('scale_key');

        $this->assertDatabaseMissing('scale_results', ['scale_key' => 'hit6']);
    }

    public function test_patient_fills_a_scale_from_the_portal(): void
    {
        $this->actingAs($this->patientUser)->post('/ar/patient/neuropsych/scales', [
            'scale_key' => 'gad7',
            'answers' => [2, 2, 2, 2, 2, 1, 1], // 12 → moderate
        ])->assertRedirect();

        $r = ScaleResult::where('patient_id', $this->patient->id)->where('scale_key', 'gad7')->firstOrFail();
        $this->assertSame('patient', $r->entered_by);
        $this->assertSame(12, $r->score);
        $this->assertSame('Moderate', $r->severity);
    }

    public function test_patient_scales_index_renders(): void
    {
        $this->actingAs($this->patientUser)->get('/ar/patient/neuropsych/scales')->assertOk();
    }
}
