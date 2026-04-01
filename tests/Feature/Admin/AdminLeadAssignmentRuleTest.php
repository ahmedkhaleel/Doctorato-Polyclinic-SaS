<?php

namespace Tests\Feature\Admin;

use App\Models\LeadAssignmentRule;
use App\Models\LeadSource;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminLeadAssignmentRuleTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::firstOrCreate(
            ['name' => 'admin'],
            ['display_name_en' => 'Admin', 'display_name_ar' => 'مدير', 'permissions' => ['*'], 'is_system' => true]
        );

        $this->admin = User::create([
            'name' => 'Admin', 'email' => 'admin-assign@test.com',
            'password' => bcrypt('password'), 'role_id' => $role->id, 'is_active' => true,
        ]);
    }

    public function test_can_view_assignment_rules_index(): void
    {
        $this->actingAs($this->admin)->get('/admin/assignment-rules')->assertOk();
    }

    public function test_can_create_assignment_rule(): void
    {
        $source = LeadSource::create([
            'name_en' => 'Instagram', 'name_ar' => 'انستقرام',
            'slug' => 'instagram', 'channel_type' => 'online', 'is_active' => true,
        ]);

        $this->actingAs($this->admin)->post('/admin/assignment-rules', [
            'name' => 'Instagram Leads to Admin',
            'rule_type' => 'source_based',
            'lead_source_id' => $source->id,
            'assign_to_user_id' => $this->admin->id,
            'priority' => 10,
            'is_active' => true,
        ])->assertRedirect();

        $this->assertDatabaseHas('lead_assignment_rules', [
            'name' => 'Instagram Leads to Admin',
            'rule_type' => 'source_based',
        ]);
    }

    public function test_assignment_rule_requires_fields(): void
    {
        $this->actingAs($this->admin)->post('/admin/assignment-rules', [])
            ->assertSessionHasErrors(['name', 'rule_type', 'assign_to_user_id', 'priority']);
    }

    public function test_invalid_rule_type_rejected(): void
    {
        $this->actingAs($this->admin)->post('/admin/assignment-rules', [
            'name' => 'Test',
            'rule_type' => 'random',
            'assign_to_user_id' => $this->admin->id,
            'priority' => 5,
        ])->assertSessionHasErrors('rule_type');
    }

    public function test_service_based_rule_type_accepted(): void
    {
        $this->actingAs($this->admin)->post('/admin/assignment-rules', [
            'name' => 'Service Based Rule',
            'rule_type' => 'service_based',
            'assign_to_user_id' => $this->admin->id,
            'priority' => 5,
            'is_active' => true,
        ])->assertRedirect();

        $this->assertDatabaseHas('lead_assignment_rules', [
            'rule_type' => 'service_based',
        ]);
    }

    public function test_can_update_assignment_rule(): void
    {
        $rule = LeadAssignmentRule::create([
            'name' => 'Old Rule', 'rule_type' => 'round_robin',
            'assign_to_user_id' => $this->admin->id, 'priority' => 5, 'is_active' => true,
        ]);

        $this->actingAs($this->admin)->post("/admin/assignment-rules/{$rule->id}", [
            'name' => 'Updated Rule',
            'rule_type' => 'manual',
            'assign_to_user_id' => $this->admin->id,
            'priority' => 20,
        ])->assertRedirect();

        $this->assertDatabaseHas('lead_assignment_rules', [
            'id' => $rule->id, 'name' => 'Updated Rule', 'priority' => 20,
        ]);
    }

    public function test_can_delete_assignment_rule(): void
    {
        $rule = LeadAssignmentRule::create([
            'name' => 'Delete Me', 'rule_type' => 'manual',
            'assign_to_user_id' => $this->admin->id, 'priority' => 1, 'is_active' => true,
        ]);

        $this->actingAs($this->admin)->delete("/admin/assignment-rules/{$rule->id}")->assertRedirect();
        $this->assertDatabaseMissing('lead_assignment_rules', ['id' => $rule->id]);
    }
}
