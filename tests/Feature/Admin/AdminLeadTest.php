<?php

namespace Tests\Feature\Admin;

use App\Models\Lead;
use App\Models\LeadSource;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminLeadTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private LeadSource $source;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::firstOrCreate(
            ['name' => 'admin'],
            ['display_name_en' => 'Admin', 'display_name_ar' => 'مدير', 'permissions' => ['*'], 'is_system' => true]
        );

        $this->admin = User::create([
            'name' => 'Admin', 'email' => 'admin-lead@test.com',
            'password' => bcrypt('password'), 'role_id' => $role->id, 'is_active' => true,
        ]);

        $this->source = LeadSource::create([
            'name_ar' => 'انستقرام', 'name_en' => 'Instagram', 'slug' => 'instagram', 'is_active' => true,
        ]);
    }

    public function test_can_view_leads_index(): void
    {
        $this->actingAs($this->admin)->get('/admin/leads')->assertOk();
    }

    public function test_can_create_lead(): void
    {
        $this->actingAs($this->admin)->post('/admin/leads', [
            'full_name' => 'Jane Doe',
            'phone' => '01000000001',
            'lead_source_id' => $this->source->id,
            'priority' => '2',
        ])->assertRedirect();

        $this->assertDatabaseHas('leads', ['full_name' => 'Jane Doe', 'phone' => '01000000001']);
    }

    public function test_lead_requires_name(): void
    {
        $this->actingAs($this->admin)->post('/admin/leads', [
            'phone' => '01000000001',
            'lead_source_id' => $this->source->id,
            'priority' => '2',
        ])->assertSessionHasErrors('full_name');
    }

    public function test_lead_requires_priority(): void
    {
        $this->actingAs($this->admin)->post('/admin/leads', [
            'full_name' => 'Test Lead',
            'lead_source_id' => $this->source->id,
        ])->assertSessionHasErrors('priority');
    }

    public function test_can_update_lead(): void
    {
        $lead = Lead::create([
            'full_name' => 'Old Lead', 'phone' => '01000000002',
            'lead_source_id' => $this->source->id, 'status' => 'new',
            'priority' => 2, 'created_by' => $this->admin->id,
        ]);

        $this->actingAs($this->admin)->post("/admin/leads/{$lead->id}", [
            'full_name' => 'Updated Lead',
            'phone' => '01000000002',
            'lead_source_id' => $this->source->id,
            'priority' => '1',
        ])->assertRedirect();

        $this->assertDatabaseHas('leads', ['id' => $lead->id, 'full_name' => 'Updated Lead']);
    }

    public function test_can_delete_lead(): void
    {
        $lead = Lead::create([
            'full_name' => 'Delete Me', 'phone' => '01000000003',
            'lead_source_id' => $this->source->id, 'status' => 'new',
            'priority' => 2, 'created_by' => $this->admin->id,
        ]);

        $this->actingAs($this->admin)->delete("/admin/leads/{$lead->id}")
            ->assertRedirect();

        $this->assertSoftDeleted('leads', ['id' => $lead->id]);
    }

    public function test_non_admin_cannot_access_leads(): void
    {
        $patientRole = Role::firstOrCreate(
            ['name' => 'patient'],
            ['display_name_en' => 'Patient', 'display_name_ar' => 'مريض', 'permissions' => []]
        );

        $patient = User::create([
            'name' => 'Patient', 'email' => 'pat-lead@test.com',
            'password' => bcrypt('password'), 'role_id' => $patientRole->id, 'is_active' => true,
        ]);

        $response = $this->actingAs($patient)->get('/admin/leads');
        $this->assertContains($response->status(), [302, 401, 403]);
    }
}
