<?php

namespace Tests\Feature\Secretary;

use App\Models\Doctor;
use App\Models\OnlineConsultation;
use App\Models\Patient;
use App\Models\Role;
use App\Models\User;
use App\Services\ModuleManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * P1-3 — telemedicine front-desk overview: upcoming consultations + a
 * payment-chase queue. Administrative only; module-gated.
 */
class SecretaryTelemedicineOverviewTest extends TestCase
{
    use RefreshDatabase;

    private User $secretary;

    protected function setUp(): void
    {
        parent::setUp();
        ModuleManager::flushStaticCache();

        $role = Role::firstOrCreate(['name' => 'secretary'], [
            'display_name_en' => 'Secretary', 'display_name_ar' => 'سكرتير', 'permissions' => [], 'is_system' => true,
        ]);
        $this->secretary = User::create([
            'name' => 'Sec', 'email' => 'sec-tele@test.com', 'password' => bcrypt('x'),
            'role_id' => $role->id, 'is_active' => true,
        ]);

        ModuleManager::enable('telemedicine');
        ModuleManager::flushStaticCache();
    }

    private function makeConsultation(Patient $patient, Doctor $doctor, string $payment): OnlineConsultation
    {
        return OnlineConsultation::create([
            'consultation_number' => 'OC-'.uniqid(),
            'patient_id' => $patient->id, 'doctor_id' => $doctor->id,
            'module' => 'telemedicine',
            'scheduled_date' => now()->addDay()->toDateString(),
            'start_time' => '10:00', 'end_time' => '10:30',
            'status' => 'scheduled', 'fee' => 250, 'payment_status' => $payment,
        ]);
    }

    public function test_front_desk_shows_upcoming_and_unpaid(): void
    {
        $doctor = Doctor::create(['name_ar' => 'د', 'name_en' => 'Dr', 'status' => 'active', 'module' => 'psychiatry']);
        $paid = Patient::create(['full_name' => 'Paid Pt', 'phone' => '0500005555']);
        $paid->forceFill(['is_active' => true, 'file_number' => 'PAT-TM-1'])->save();
        $owing = Patient::create(['full_name' => 'Owing Pt', 'phone' => '0500006666']);
        $owing->forceFill(['is_active' => true, 'file_number' => 'PAT-TM-2'])->save();

        $this->makeConsultation($paid, $doctor, 'paid');
        $this->makeConsultation($owing, $doctor, 'pending');

        $this->actingAs($this->secretary)->get('/secretary/telemedicine/overview')
            ->assertOk()
            ->assertInertia(fn (Assert $p) => $p
                ->component('Secretary/Telemedicine/Index')
                ->has('upcoming', 2)
                ->has('unpaid', 1)
                ->where('unpaid.0.patient.full_name', 'Owing Pt')
            );
    }

    public function test_overview_is_gated_by_module_flag(): void
    {
        ModuleManager::disable('telemedicine');
        ModuleManager::flushStaticCache();

        $status = $this->actingAs($this->secretary)->get('/secretary/telemedicine/overview')->status();
        $this->assertContains($status, [403, 302, 404]);
    }
}
