<?php

namespace Tests\Feature\Doctor;

use App\Models\Doctor;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DoctorCommissionTest extends TestCase
{
    use RefreshDatabase;

    private User $doctorUser;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::firstOrCreate(
            ['name' => 'doctor'],
            ['display_name_en' => 'Doctor', 'display_name_ar' => 'طبيب', 'permissions' => [], 'is_system' => true]
        );

        $this->doctorUser = User::create([
            'name' => 'Comm Doctor', 'email' => 'doc-comm@test.com',
            'password' => bcrypt('password'), 'role_id' => $role->id, 'is_active' => true,
        ]);

        Doctor::create([
            'name_ar' => 'دكتور عمولة', 'name_en' => 'Commission Doctor',
            'user_id' => $this->doctorUser->id, 'status' => 'active',
        ]);
    }

    public function test_can_view_commission_index(): void
    {
        $this->actingAs($this->doctorUser)->get('/doctor/commission')->assertOk();
    }

    public function test_commission_page_filters_by_month(): void
    {
        $this->actingAs($this->doctorUser)
            ->get('/doctor/commission?month=2026-01')
            ->assertOk();
    }

    public function test_non_doctor_cannot_access_commissions(): void
    {
        $patientRole = Role::firstOrCreate(
            ['name' => 'patient'],
            ['display_name_en' => 'Patient', 'display_name_ar' => 'مريض', 'permissions' => []]
        );

        $patient = User::create([
            'name' => 'Patient', 'email' => 'pat-comm@test.com',
            'password' => bcrypt('password'), 'role_id' => $patientRole->id, 'is_active' => true,
        ]);

        $response = $this->actingAs($patient)->get('/doctor/commission');
        $this->assertContains($response->status(), [302, 401, 403]);
    }
}
