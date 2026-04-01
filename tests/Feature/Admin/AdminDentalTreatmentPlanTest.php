<?php

namespace Tests\Feature\Admin;

use App\Models\DentalTreatmentPlan;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AdminDentalTreatmentPlanTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Patient $patient;
    private Doctor $doctor;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::firstOrCreate(
            ['name' => 'admin'],
            ['display_name_en' => 'Admin', 'display_name_ar' => 'مدير', 'permissions' => ['*'], 'is_system' => true]
        );

        $this->admin = User::create([
            'name' => 'Admin', 'email' => 'admin-dtp@test.com',
            'password' => bcrypt('password'), 'role_id' => $role->id, 'is_active' => true,
        ]);

        // Enable dental module
        DB::table('module_settings')->updateOrInsert(
            ['module' => 'dental', 'key' => 'enabled'],
            ['value' => '1', 'created_at' => now(), 'updated_at' => now()]
        );
        cache()->forget('module_dental_enabled');

        $this->patient = new Patient([
            'full_name' => 'Dental Patient', 'phone' => '0500002001', 'gender' => 'female',
        ]);
        $this->patient->file_number = 'PAT-DTP-001';
        $this->patient->is_active = true;
        $this->patient->save();

        $this->doctor = Doctor::create([
            'name_ar' => 'دكتور خطة', 'name_en' => 'Plan Doctor',
            'specialization_ar' => 'أسنان', 'specialization_en' => 'Dental',
            'department' => 'dental', 'status' => 'active',
        ]);
    }

    public function test_can_view_treatment_plans_index(): void
    {
        $this->actingAs($this->admin)->get('/admin/dental/treatment-plans')->assertOk();
    }

    public function test_can_create_treatment_plan(): void
    {
        $this->actingAs($this->admin)->post('/admin/dental/treatment-plans', [
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'title_en' => 'Full Restoration Plan',
            'title_ar' => 'خطة ترميم كاملة',
            'priority' => 'high',
            'estimated_sessions' => 5,
        ])->assertRedirect();

        $this->assertDatabaseHas('dental_treatment_plans', [
            'patient_id' => $this->patient->id,
            'title_en' => 'Full Restoration Plan',
            'status' => 'draft',
            'priority' => 'high',
        ]);
    }

    public function test_can_create_plan_with_treatments(): void
    {
        $this->actingAs($this->admin)->post('/admin/dental/treatment-plans', [
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'title_en' => 'With Treatments',
            'treatments' => [
                ['treatment_type' => 'filling', 'tooth_number' => 11, 'cost' => 200],
                ['treatment_type' => 'crown', 'tooth_number' => 21, 'cost' => 1500, 'lab_cost' => 500],
            ],
        ])->assertRedirect();

        $plan = DentalTreatmentPlan::where('title_en', 'With Treatments')->first();
        $this->assertNotNull($plan);
        $this->assertEquals(2, $plan->treatments()->count());
        $this->assertEquals(2200, (float) $plan->estimated_cost);
    }

    public function test_plan_requires_patient(): void
    {
        $this->actingAs($this->admin)->post('/admin/dental/treatment-plans', [
            'title_en' => 'No Patient',
        ])->assertSessionHasErrors('patient_id');
    }

    public function test_priority_must_be_valid(): void
    {
        $this->actingAs($this->admin)->post('/admin/dental/treatment-plans', [
            'patient_id' => $this->patient->id,
            'priority' => 'critical',
        ])->assertSessionHasErrors('priority');
    }

    public function test_can_update_treatment_plan(): void
    {
        $plan = DentalTreatmentPlan::create([
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'title_en' => 'Old Title',
            'status' => 'draft',
        ]);

        $this->actingAs($this->admin)->post("/admin/dental/treatment-plans/{$plan->id}", [
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'title_en' => 'Updated Title',
            'priority' => 'urgent',
        ])->assertRedirect();

        $this->assertDatabaseHas('dental_treatment_plans', [
            'id' => $plan->id,
            'title_en' => 'Updated Title',
        ]);
    }

    public function test_can_delete_treatment_plan(): void
    {
        $plan = DentalTreatmentPlan::create([
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'status' => 'draft',
        ]);

        $this->actingAs($this->admin)->delete("/admin/dental/treatment-plans/{$plan->id}")->assertRedirect();
        $this->assertDatabaseMissing('dental_treatment_plans', ['id' => $plan->id]);
    }

    public function test_dental_module_required(): void
    {
        // Disable dental module
        DB::table('module_settings')->where('module', 'dental')->update(['value' => '0']);
        cache()->forget('module_dental_enabled');

        $this->actingAs($this->admin)->get('/admin/dental/treatment-plans')->assertRedirect();
    }
}
