<?php

namespace Tests\Feature\Admin;

use App\Models\LeadScoringRule;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminLeadScoringRuleTest extends TestCase
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
            'name' => 'Admin', 'email' => 'admin-score@test.com',
            'password' => bcrypt('password'), 'role_id' => $role->id, 'is_active' => true,
        ]);
    }

    public function test_can_view_scoring_rules_index(): void
    {
        $this->actingAs($this->admin)->get('/admin/scoring-rules')->assertOk();
    }

    public function test_can_create_scoring_rule(): void
    {
        $this->actingAs($this->admin)->post('/admin/scoring-rules', [
            'name_en' => 'Phone Provided',
            'name_ar' => 'رقم الهاتف',
            'event' => 'phone_provided',
            'points' => 10,
            'description' => 'Points for providing phone number',
            'is_active' => true,
        ])->assertRedirect();

        $this->assertDatabaseHas('lead_scoring_rules', [
            'event' => 'phone_provided',
            'points' => 10,
        ]);
    }

    public function test_scoring_rule_requires_fields(): void
    {
        $this->actingAs($this->admin)->post('/admin/scoring-rules', [])
            ->assertSessionHasErrors(['name_en', 'name_ar', 'event', 'points']);
    }

    public function test_points_must_be_within_range(): void
    {
        $this->actingAs($this->admin)->post('/admin/scoring-rules', [
            'name_en' => 'Test', 'name_ar' => 'تست',
            'event' => 'lead_created',
            'points' => 200,
        ])->assertSessionHasErrors('points');
    }

    public function test_negative_points_allowed(): void
    {
        $this->actingAs($this->admin)->post('/admin/scoring-rules', [
            'name_en' => 'No Response',
            'name_ar' => 'بدون رد',
            'event' => 'no_response_7_days',
            'points' => -15,
            'is_active' => true,
        ])->assertRedirect();

        $this->assertDatabaseHas('lead_scoring_rules', [
            'event' => 'no_response_7_days',
            'points' => -15,
        ]);
    }

    public function test_can_update_scoring_rule(): void
    {
        $rule = LeadScoringRule::create([
            'name_en' => 'Old', 'name_ar' => 'قديم',
            'event' => 'lead_created', 'points' => 5, 'is_active' => true,
        ]);

        $this->actingAs($this->admin)->post("/admin/scoring-rules/{$rule->id}", [
            'name_en' => 'Updated',
            'name_ar' => 'محدث',
            'points' => 20,
        ])->assertRedirect();

        $this->assertDatabaseHas('lead_scoring_rules', ['id' => $rule->id, 'points' => 20]);
    }

    public function test_can_delete_scoring_rule(): void
    {
        $rule = LeadScoringRule::create([
            'name_en' => 'Delete', 'name_ar' => 'حذف',
            'event' => 'referral_provided', 'points' => 25, 'is_active' => true,
        ]);

        $this->actingAs($this->admin)->post("/admin/scoring-rules/{$rule->id}/delete")->assertRedirect();
        $this->assertDatabaseMissing('lead_scoring_rules', ['id' => $rule->id]);
    }
}
