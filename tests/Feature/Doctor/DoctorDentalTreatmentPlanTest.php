<?php

namespace Tests\Feature\Doctor;

use App\Models\DentalTreatment;
use App\Models\DentalTreatmentPlan;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DoctorDentalTreatmentPlanTest extends TestCase
{
    use RefreshDatabase;

    private User $doctorUser;
    private Doctor $doctor;
    private Patient $patient;

    protected function setUp(): void
    {
        parent::setUp();

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
            'name' => 'Plan Doctor', 'email' => 'doc-plan@test.com',
            'password' => bcrypt('password'), 'role_id' => $role->id, 'is_active' => true,
        ]);

        $this->doctor = Doctor::create([
            'name_ar' => 'دكتور خطة', 'name_en' => 'Plan Doctor',
            'user_id' => $this->doctorUser->id, 'status' => 'active',
        ]);

        $this->patient = new Patient(['full_name' => 'Plan Patient', 'phone' => '0500009444', 'gender' => 'female']);
        $this->patient->file_number = 'PAT-PL-001';
        $this->patient->is_active = true;
        $this->patient->save();

        Cache::flush();
        $reflection = new \ReflectionClass(\App\Services\ModuleManager::class);
        $prop = $reflection->getProperty('tableExists');
        $prop->setAccessible(true);
        $prop->setValue(null, null);
    }

    public function test_can_view_plans_index(): void
    {
        $this->actingAs($this->doctorUser)->get('/doctor/dental/treatment-plans')->assertOk();
    }

    public function test_can_create_plan(): void
    {
        $this->actingAs($this->doctorUser)->post('/doctor/dental/treatment-plans', [
            'patient_id' => $this->patient->id,
            'title_en' => 'Full Mouth Rehab',
            'title_ar' => 'إعادة تأهيل الفم الكامل',
            'estimated_cost' => 5000,
            'estimated_sessions' => 8,
            'priority' => 'high',
        ])->assertRedirect();

        $this->assertDatabaseHas('dental_treatment_plans', [
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'title_en' => 'Full Mouth Rehab',
            'status' => 'draft',
        ]);
    }

    public function test_can_create_plan_with_treatments(): void
    {
        $this->actingAs($this->doctorUser)->post('/doctor/dental/treatment-plans', [
            'patient_id' => $this->patient->id,
            'title_en' => 'Treatment Bundle',
            'treatments' => [
                ['treatment_type' => 'filling', 'tooth_number' => 11, 'cost' => 300],
                ['treatment_type' => 'crown', 'tooth_number' => 21, 'cost' => 1500],
            ],
        ])->assertRedirect();

        $plan = DentalTreatmentPlan::where('title_en', 'Treatment Bundle')->first();
        $this->assertNotNull($plan);
        $this->assertEquals(2, $plan->treatments()->count());
    }

    public function test_can_view_own_plan(): void
    {
        $plan = DentalTreatmentPlan::create([
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'title_en' => 'View Plan',
            'status' => 'draft',
        ]);

        $this->actingAs($this->doctorUser)
            ->get("/doctor/dental/treatment-plans/{$plan->id}")
            ->assertOk();
    }

    public function test_cannot_view_other_doctors_plan(): void
    {
        $otherDoc = Doctor::create([
            'name_ar' => 'آخر', 'name_en' => 'Other', 'status' => 'active',
        ]);

        $plan = DentalTreatmentPlan::create([
            'patient_id' => $this->patient->id,
            'doctor_id' => $otherDoc->id,
            'title_en' => 'Other Plan',
            'status' => 'draft',
        ]);

        $this->actingAs($this->doctorUser)
            ->get("/doctor/dental/treatment-plans/{$plan->id}")
            ->assertForbidden();
    }

    public function test_can_update_own_plan(): void
    {
        $plan = DentalTreatmentPlan::create([
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'title_en' => 'Old Title',
            'status' => 'draft',
        ]);

        $this->actingAs($this->doctorUser)
            ->post("/doctor/dental/treatment-plans/{$plan->id}/update", [
                'title_en' => 'Updated Title',
                'status' => 'approved',
            ])
            ->assertRedirect();

        $plan->refresh();
        $this->assertEquals('Updated Title', $plan->title_en);
    }

    public function test_cannot_update_other_doctors_plan(): void
    {
        $otherDoc = Doctor::create([
            'name_ar' => 'آخر', 'name_en' => 'Other', 'status' => 'active',
        ]);

        $plan = DentalTreatmentPlan::create([
            'patient_id' => $this->patient->id,
            'doctor_id' => $otherDoc->id,
            'title_en' => 'Other Plan',
            'status' => 'draft',
        ]);

        $this->actingAs($this->doctorUser)
            ->post("/doctor/dental/treatment-plans/{$plan->id}/update", [
                'title_en' => 'Hacked',
            ])
            ->assertForbidden();
    }

    public function test_plan_requires_patient(): void
    {
        $this->actingAs($this->doctorUser)->post('/doctor/dental/treatment-plans', [
            'title_en' => 'No Patient',
        ])->assertSessionHasErrors('patient_id');
    }
}
