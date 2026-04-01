<?php

namespace Tests\Feature\Secretary;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecretaryDashboardTest extends TestCase
{
    use RefreshDatabase;

    private User $secretary;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::firstOrCreate(
            ['name' => 'secretary'],
            ['display_name_en' => 'Secretary', 'display_name_ar' => 'سكرتارية', 'permissions' => [
                'patients.view', 'visits.view', 'bookings.view',
            ], 'is_system' => true]
        );

        $this->secretary = User::create([
            'name' => 'Secretary', 'email' => 'sec-dash@test.com',
            'password' => bcrypt('password'), 'role_id' => $role->id, 'is_active' => true,
        ]);
    }

    public function test_dashboard_loads(): void
    {
        $this->actingAs($this->secretary)->get('/secretary/')->assertOk();
    }

    public function test_dashboard_has_stats(): void
    {
        $response = $this->actingAs($this->secretary)->get('/secretary/');
        $response->assertOk();

        $props = $response->original->getData()['page']['props'] ?? [];
        $this->assertArrayHasKey('stats', $props);
    }

    public function test_unauthenticated_redirected(): void
    {
        $response = $this->get('/secretary/');
        $this->assertContains($response->status(), [302, 401, 403]);
    }

    public function test_non_secretary_cannot_access(): void
    {
        $patientRole = Role::firstOrCreate(
            ['name' => 'patient'],
            ['display_name_en' => 'Patient', 'display_name_ar' => 'مريض', 'permissions' => []]
        );

        $patient = User::create([
            'name' => 'Patient', 'email' => 'not-sec@test.com',
            'password' => bcrypt('password'), 'role_id' => $patientRole->id, 'is_active' => true,
        ]);

        $response = $this->actingAs($patient)->get('/secretary/');
        $this->assertContains($response->status(), [302, 401, 403]);
    }
}
