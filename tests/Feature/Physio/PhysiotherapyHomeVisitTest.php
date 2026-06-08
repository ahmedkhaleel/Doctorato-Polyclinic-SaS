<?php

namespace Tests\Feature\Physio;

use App\Models\Doctor;
use App\Models\Invoice;
use App\Models\Patient;
use App\Models\Role;
use App\Models\User;
use App\Services\ModuleManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * F-B — a home-visit physio session adds the home_visit_surcharge module
 * setting (seeded 100) on top of the base session fee (seeded 200) → 300.
 */
class PhysiotherapyHomeVisitTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_visit_session_adds_the_surcharge(): void
    {
        ModuleManager::flushStaticCache();
        $role = Role::firstOrCreate(['name' => 'doctor'], ['display_name_en' => 'Doctor', 'display_name_ar' => 'طبيب', 'permissions' => ['*'], 'is_system' => true]);
        $role->update(['permissions' => ['*']]);
        $user = User::create(['name' => 'PT', 'email' => 'hv-doc@test.com', 'password' => bcrypt('x'), 'role_id' => $role->id, 'is_active' => true]);
        $doctor = Doctor::create(['name_ar' => 'د', 'name_en' => 'PT', 'user_id' => $user->id, 'status' => 'active', 'module' => 'physiotherapy']);
        ModuleManager::enable('physiotherapy');
        ModuleManager::flushStaticCache();

        $patient = Patient::create(['full_name' => 'HV', 'phone' => '0500001212']);
        $patient->forceFill(['is_active' => true, 'file_number' => 'PAT-HV-1'])->save();

        // No cost passed → falls back to session_fee (200) + home_visit_surcharge (100).
        $this->actingAs($user)
            ->post("/doctor/physiotherapy/patients/{$patient->id}/sessions", [
                'session_date' => '2026-06-05', 'attended' => true, 'home_visit' => true, 'bill' => true,
            ])->assertRedirect();

        $invoice = Invoice::where('module', 'physiotherapy')->firstOrFail();
        $this->assertEquals(300, (float) $invoice->total);
        $this->assertTrue(\App\Models\PhysioSession::where('patient_id', $patient->id)->value('home_visit'));
    }
}
