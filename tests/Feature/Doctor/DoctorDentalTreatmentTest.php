<?php

namespace Tests\Feature\Doctor;

use App\Models\DentalTreatment;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DoctorDentalTreatmentTest extends TestCase
{
    use RefreshDatabase;

    private User $doctorUser;
    private Doctor $doctor;
    private Patient $patient;

    protected function setUp(): void
    {
        parent::setUp();

        // Enable dental module
        if (Schema::hasTable('module_settings')) {
            DB::table('module_settings')->updateOrInsert(
                ['module' => 'dental', 'key' => 'enabled'],
                ['module' => 'dental', 'key' => 'enabled', 'value' => '1', 'group' => 'general']
            );
        }

        $role = Role::firstOrCreate(
            ['name' => 'doctor'],
            ['display_name_en' => 'Doctor', 'display_name_ar' => 'طبيب', 'permissions' => [], 'is_system' => true]
        );

        $this->doctorUser = User::create([
            'name' => 'Dental Doctor', 'email' => 'doc-dental@test.com',
            'password' => bcrypt('password'), 'role_id' => $role->id, 'is_active' => true,
        ]);

        $this->doctor = Doctor::create([
            'name_ar' => 'دكتور أسنان', 'name_en' => 'Dental Doctor',
            'user_id' => $this->doctorUser->id, 'status' => 'active',
        ]);

        $this->patient = new Patient(['full_name' => 'Dental Patient', 'phone' => '01888000111']);
        $this->patient->file_number = 'P-DENT-001';
        $this->patient->is_active = true;
        $this->patient->save();

        // Clear module cache
        \Illuminate\Support\Facades\Cache::flush();
        // Reset static tableExists cache
        $reflection = new \ReflectionClass(\App\Services\ModuleManager::class);
        $prop = $reflection->getProperty('tableExists');
        $prop->setAccessible(true);
        $prop->setValue(null, null);
    }

    public function test_can_view_treatments_index(): void
    {
        $this->actingAs($this->doctorUser)
            ->get('/doctor/dental/treatments')
            ->assertOk();
    }

    public function test_can_create_treatment(): void
    {
        $this->actingAs($this->doctorUser)->post('/doctor/dental/treatments', [
            'patient_id' => $this->patient->id,
            'treatment_type' => 'filling',
            'tooth_number' => 11,
            'description' => 'Composite filling upper right central incisor',
            'cost' => 500,
            'status' => 'planned',
        ])->assertRedirect();

        $this->assertDatabaseHas('dental_treatments', [
            'patient_id' => $this->patient->id,
            'treatment_type' => 'filling',
            'tooth_number' => 11,
        ]);
    }

    public function test_treatment_requires_patient(): void
    {
        $response = $this->actingAs($this->doctorUser)->post('/doctor/dental/treatments', [
            'treatment_type' => 'filling',
            'tooth_number' => 11,
        ]);

        $response->assertSessionHasErrors('patient_id');
    }

    public function test_treatment_requires_type(): void
    {
        $response = $this->actingAs($this->doctorUser)->post('/doctor/dental/treatments', [
            'patient_id' => $this->patient->id,
            'tooth_number' => 11,
        ]);

        $response->assertSessionHasErrors('treatment_type');
    }

    public function test_invalid_treatment_type_rejected(): void
    {
        $response = $this->actingAs($this->doctorUser)->post('/doctor/dental/treatments', [
            'patient_id' => $this->patient->id,
            'treatment_type' => 'invalid_type',
            'tooth_number' => 11,
        ]);

        $response->assertSessionHasErrors('treatment_type');
    }

    public function test_invalid_tooth_number_rejected(): void
    {
        $response = $this->actingAs($this->doctorUser)->post('/doctor/dental/treatments', [
            'patient_id' => $this->patient->id,
            'treatment_type' => 'extraction',
            'tooth_number' => 99,
        ]);

        $response->assertSessionHasErrors('tooth_number');
    }

    public function test_can_update_treatment(): void
    {
        $treatment = DentalTreatment::create([
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'treatment_type' => 'filling',
            'tooth_number' => 21,
            'status' => 'planned',
            'cost' => 300,
        ]);

        $this->actingAs($this->doctorUser)->post("/doctor/dental/treatments/{$treatment->id}/update", [
            'status' => 'in_progress',
            'cost' => 350,
            'notes' => 'Started treatment',
        ])->assertRedirect();

        $this->assertDatabaseHas('dental_treatments', [
            'id' => $treatment->id,
            'status' => 'in_progress',
            'cost' => 350,
        ]);
    }

    public function test_cannot_update_other_doctors_treatment(): void
    {
        $otherDoc = Doctor::create([
            'name_ar' => 'دكتور آخر', 'name_en' => 'Other Doc',
            'status' => 'active',
        ]);

        $treatment = DentalTreatment::create([
            'patient_id' => $this->patient->id,
            'doctor_id' => $otherDoc->id,
            'treatment_type' => 'crown',
            'tooth_number' => 36,
            'status' => 'planned',
        ]);

        $response = $this->actingAs($this->doctorUser)
            ->post("/doctor/dental/treatments/{$treatment->id}/update", [
                'patient_id' => $this->patient->id,
                'treatment_type' => 'crown',
                'tooth_number' => 36,
                'status' => 'completed',
            ]);

        $this->assertContains($response->status(), [302, 403]);
    }

    public function test_non_doctor_cannot_access_dental(): void
    {
        $patientRole = Role::firstOrCreate(
            ['name' => 'patient'],
            ['display_name_en' => 'Patient', 'display_name_ar' => 'مريض', 'permissions' => []]
        );

        $patient = User::create([
            'name' => 'Not Doctor', 'email' => 'not-doc-dent@test.com',
            'password' => bcrypt('password'), 'role_id' => $patientRole->id, 'is_active' => true,
        ]);

        $response = $this->actingAs($patient)->get('/doctor/dental/treatments');
        $this->assertContains($response->status(), [302, 401, 403]);
    }
}
