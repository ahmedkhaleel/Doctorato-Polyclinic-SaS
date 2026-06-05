<?php

namespace Tests\Feature\Neuropsych;

use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Role;
use App\Models\User;
use App\Services\ModuleManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Flow fixes — the module filter on patient Visits/Invoices and the doctor
 * Queue now accept psychiatry/neurology (previously a 422 / validation reject).
 */
class FlowFilterAcceptanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_patient_visit_and_invoice_filters_accept_neuropsych(): void
    {
        $role = Role::firstOrCreate(['name' => 'patient'], ['display_name_en' => 'Patient', 'display_name_ar' => 'مريض', 'permissions' => [], 'is_system' => true]);
        $u = User::create(['name' => 'Pat', 'email' => 'flow-pat@test.com', 'password' => bcrypt('x'), 'role_id' => $role->id, 'is_active' => true]);
        $p = Patient::create(['full_name' => 'Pat', 'phone' => '0500008888']);
        $p->forceFill(['user_id' => $u->id, 'is_active' => true, 'file_number' => 'PAT-FLOW-1'])->save();

        $this->actingAs($u)->get('/ar/patient/visits?module=psychiatry')->assertOk();
        $this->actingAs($u)->get('/ar/patient/visits?module=neurology')->assertOk();
        $this->actingAs($u)->get('/ar/patient/invoices?module=psychiatry')->assertOk();
        $this->actingAs($u)->get('/ar/patient/invoices?module=neurology')->assertOk();
    }

    public function test_doctor_queue_filter_accepts_neuropsych(): void
    {
        ModuleManager::flushStaticCache();
        $role = Role::firstOrCreate(['name' => 'doctor'], ['display_name_en' => 'Doctor', 'display_name_ar' => 'طبيب', 'permissions' => ['*'], 'is_system' => true]);
        $role->update(['permissions' => ['*']]);
        $u = User::create(['name' => 'Doc', 'email' => 'flow-doc@test.com', 'password' => bcrypt('x'), 'role_id' => $role->id, 'is_active' => true]);
        Doctor::create(['name_ar' => 'د', 'name_en' => 'Doc', 'user_id' => $u->id, 'status' => 'active', 'module' => 'psychiatry']);

        $this->actingAs($u)->get('/doctor/queue?module=psychiatry')->assertOk();
        $this->actingAs($u)->get('/doctor/queue?module=neurology')->assertOk();
    }
}
