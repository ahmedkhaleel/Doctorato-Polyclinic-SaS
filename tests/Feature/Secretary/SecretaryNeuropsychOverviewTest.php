<?php

namespace Tests\Feature\Secretary;

use App\Models\Booking;
use App\Models\Doctor;
use App\Models\NeuropsychProfile;
use App\Models\Patient;
use App\Models\Role;
use App\Models\User;
use App\Services\ModuleManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * P1-1 — front-desk psychiatry/neurology overview. Administrative only:
 * appointments + roster + billing. Must NOT leak clinical content, and must be
 * gated by the module flag.
 */
class SecretaryNeuropsychOverviewTest extends TestCase
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
            'name' => 'Sec', 'email' => 'sec-np@test.com', 'password' => bcrypt('x'),
            'role_id' => $role->id, 'is_active' => true,
        ]);

        ModuleManager::enable('psychiatry');
        ModuleManager::enable('neurology');
        ModuleManager::flushStaticCache();
    }

    public function test_front_desk_shows_appointments_and_roster(): void
    {
        $doctor = Doctor::create(['name_ar' => 'د', 'name_en' => 'Dr', 'status' => 'active', 'module' => 'psychiatry']);
        $patient = Patient::create(['full_name' => 'NP Patient', 'phone' => '0500001111']);
        $patient->forceFill(['is_active' => true, 'file_number' => 'PAT-NP-1'])->save();

        Booking::create([
            'booking_number' => Booking::generateBookingNumber(), 'status' => 'confirmed', 'source' => 'secretary',
            'module' => 'psychiatry', 'booking_type' => 'psychiatry_consultation',
            'full_name' => 'NP Patient', 'phone' => '0500001111', 'patient_id' => $patient->id,
            'doctor_id' => $doctor->id, 'preferred_date' => now()->addDay()->toDateString(),
        ]);

        $this->actingAs($this->secretary)->get('/secretary/psychiatry/overview')
            ->assertOk()
            ->assertInertia(fn (Assert $p) => $p
                ->component('Secretary/Neuropsych/Index')
                ->where('module', 'psychiatry')
                ->has('appointments', 1)
                ->where('appointments.0.patient.full_name', 'NP Patient')
                ->has('roster', 1)
            );
    }

    public function test_overview_never_exposes_clinical_profile_fields(): void
    {
        $patient = Patient::create(['full_name' => 'Clin Patient', 'phone' => '0500002222']);
        $patient->forceFill(['is_active' => true, 'file_number' => 'PAT-NP-2'])->save();
        NeuropsychProfile::create([
            'patient_id' => $patient->id,
            'chief_complaint' => 'TOP SECRET COMPLAINT',
            'hpi' => 'sensitive history',
        ]);
        Booking::create([
            'booking_number' => Booking::generateBookingNumber(), 'status' => 'pending', 'source' => 'secretary',
            'module' => 'psychiatry', 'booking_type' => 'psychiatry_consultation',
            'full_name' => 'Clin Patient', 'phone' => '0500002222', 'patient_id' => $patient->id,
            'preferred_date' => now()->addDay()->toDateString(),
        ]);

        $resp = $this->actingAs($this->secretary)->get('/secretary/psychiatry/overview')->assertOk();

        // The clinical complaint must never appear in the front-desk payload.
        $this->assertStringNotContainsString('TOP SECRET COMPLAINT', $resp->getContent());
        $this->assertStringNotContainsString('sensitive history', $resp->getContent());
    }

    public function test_overview_is_gated_by_module_flag(): void
    {
        ModuleManager::disable('neurology');
        ModuleManager::flushStaticCache();

        $status = $this->actingAs($this->secretary)->get('/secretary/neurology/overview')->status();
        $this->assertContains($status, [403, 302, 404]);
    }
}
