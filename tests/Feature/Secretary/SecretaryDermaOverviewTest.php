<?php

namespace Tests\Feature\Secretary;

use App\Models\Booking;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Role;
use App\Models\User;
use App\Services\ModuleManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * P1-2 — dermatology/cosmetic front-desk overview: appointments + roster +
 * active package balances. Administrative only; module-gated.
 */
class SecretaryDermaOverviewTest extends TestCase
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
            'name' => 'Sec', 'email' => 'sec-derma@test.com', 'password' => bcrypt('x'),
            'role_id' => $role->id, 'is_active' => true,
        ]);

        ModuleManager::enable('derma');
        ModuleManager::flushStaticCache();
    }

    public function test_front_desk_shows_derma_appointments(): void
    {
        $doctor = Doctor::create(['name_ar' => 'د', 'name_en' => 'Dr', 'status' => 'active', 'module' => 'derma']);
        $patient = Patient::create(['full_name' => 'Derma Patient', 'phone' => '0500004444']);
        $patient->forceFill(['is_active' => true, 'file_number' => 'PAT-DR-1'])->save();

        Booking::create([
            'booking_number' => Booking::generateBookingNumber(), 'status' => 'confirmed', 'source' => 'secretary',
            'module' => 'derma', 'booking_type' => 'derma_consultation',
            'full_name' => 'Derma Patient', 'phone' => '0500004444', 'patient_id' => $patient->id,
            'doctor_id' => $doctor->id, 'preferred_date' => now()->addDay()->toDateString(),
        ]);

        $this->actingAs($this->secretary)->get('/secretary/derma/overview')
            ->assertOk()
            ->assertInertia(fn (Assert $p) => $p
                ->component('Secretary/Derma/Index')
                ->has('appointments', 1)
                ->where('appointments.0.patient.full_name', 'Derma Patient')
                ->has('roster', 1)
                ->has('packages')
            );
    }

    public function test_overview_is_gated_by_module_flag(): void
    {
        ModuleManager::disable('derma');
        ModuleManager::flushStaticCache();

        $status = $this->actingAs($this->secretary)->get('/secretary/derma/overview')->status();
        $this->assertContains($status, [403, 302, 404]);
    }
}
