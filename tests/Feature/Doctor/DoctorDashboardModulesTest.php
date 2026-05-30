<?php

namespace Tests\Feature\Doctor;

use App\Models\Doctor;
use App\Models\Role;
use App\Models\User;
use App\Services\ModuleManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * P2: the main /doctor dashboard is module-aware — it exposes the matching
 * specialty summary for the logged-in doctor's module (derma / obgyn /
 * telemedicine), not just dental/pediatric.
 */
class DoctorDashboardModulesTest extends TestCase
{
    use RefreshDatabase;

    private function doctorOf(string $module, array $extra = []): User
    {
        ModuleManager::enable($module === 'telemedicine' ? 'telemedicine' : $module);

        $role = Role::firstOrCreate(
            ['name' => 'doctor'],
            ['display_name_en' => 'Doctor', 'display_name_ar' => 'طبيب', 'permissions' => [], 'is_system' => true]
        );
        $user = User::create([
            'name' => $module, 'email' => $module.'-dash@test.com',
            'password' => bcrypt('password'), 'role_id' => $role->id, 'is_active' => true,
        ]);
        Doctor::create(array_merge([
            'name_ar' => 'د', 'name_en' => 'Dr', 'user_id' => $user->id,
            'status' => 'active', 'module' => $module,
        ], $extra));

        return $user;
    }

    #[Test]
    public function derma_doctor_sees_a_derma_summary(): void
    {
        $user = $this->doctorOf('derma');
        $this->actingAs($user)->get('/doctor')
            ->assertOk()
            ->assertInertia(fn ($p) => $p->where('derma.visits_today', 0)->where('obgyn', null));
    }

    #[Test]
    public function obgyn_doctor_sees_an_obgyn_summary(): void
    {
        $user = $this->doctorOf('obgyn');
        $this->actingAs($user)->get('/doctor')
            ->assertOk()
            ->assertInertia(fn ($p) => $p->where('obgyn.active_pregnancies', 0)->where('derma', null));
    }

    #[Test]
    public function online_enabled_doctor_sees_a_telemedicine_summary(): void
    {
        $user = $this->doctorOf('derma', ['online_consultation_enabled' => true]);
        ModuleManager::enable('telemedicine');
        $this->actingAs($user)->get('/doctor')
            ->assertOk()
            ->assertInertia(fn ($p) => $p->where('telemedicine.today', 0)->where('telemedicine.upcoming', 0));
    }
}
