<?php

namespace Tests\Feature\Admin;

use App\Models\Lead;
use App\Models\MarketerCommission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Marketer commissions are real cash-out (recorded in the expense ledger on
 * payment) and were previously untested. Covers create with fixed/percentage
 * calculation, the pending→approved→paid state machine with its guards, and the
 * delete guard (paid commissions are protected).
 */
class AdminMarketerCommissionTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $marketer;

    private Lead $lead;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::firstOrCreate(['name' => 'admin'],
            ['display_name_en' => 'Admin', 'display_name_ar' => 'مدير', 'permissions' => ['*'], 'is_system' => true]);
        $this->admin = User::create([
            'name' => 'Admin', 'email' => 'admin-comm@test.com',
            'password' => bcrypt('password'), 'role_id' => $role->id, 'is_active' => true,
        ]);

        $mRole = Role::firstOrCreate(['name' => 'marketer'],
            ['display_name_en' => 'Marketer', 'display_name_ar' => 'مسوّق', 'permissions' => [], 'is_system' => false]);
        $this->marketer = User::create([
            'name' => 'Marketer', 'email' => 'marketer-comm@test.com',
            'password' => bcrypt('password'), 'role_id' => $mRole->id, 'is_active' => true,
        ]);

        $this->lead = Lead::create([
            'full_name' => 'Lead One', 'phone' => '01055554444', 'status' => 'new',
        ]);
    }

    private function makeCommission(string $status = 'pending'): MarketerCommission
    {
        return MarketerCommission::create([
            'user_id' => $this->marketer->id,
            'lead_id' => $this->lead->id,
            'commission_type' => 'fixed',
            'rate' => 100, 'base_amount' => 1000, 'commission_amount' => 100,
            'status' => $status,
        ]);
    }

    public function test_can_view_commissions_index(): void
    {
        $this->actingAs($this->admin)->get('/admin/commissions')->assertOk();
    }

    public function test_can_create_percentage_commission_calculates_amount(): void
    {
        $this->actingAs($this->admin)->post('/admin/commissions', [
            'user_id' => $this->marketer->id,
            'lead_id' => $this->lead->id,
            'commission_type' => 'percentage',
            'rate' => 10,
            'base_amount' => 2000,
        ])->assertRedirect();

        // 10% of 2000 = 200
        $this->assertDatabaseHas('marketer_commissions', [
            'lead_id' => $this->lead->id,
            'commission_amount' => 200,
            'status' => 'pending',
        ]);
    }

    public function test_approve_then_mark_paid_workflow(): void
    {
        $c = $this->makeCommission('pending');
        $this->actingAs($this->admin);

        $this->post("/admin/commissions/{$c->id}/approve")->assertRedirect();
        $this->assertDatabaseHas('marketer_commissions', ['id' => $c->id, 'status' => 'approved']);

        $this->post("/admin/commissions/{$c->id}/mark-paid")->assertRedirect();
        $this->assertDatabaseHas('marketer_commissions', ['id' => $c->id, 'status' => 'paid']);
    }

    public function test_cannot_mark_paid_before_approval(): void
    {
        $c = $this->makeCommission('pending');
        $this->actingAs($this->admin)
            ->post("/admin/commissions/{$c->id}/mark-paid")
            ->assertSessionHas('error');

        $this->assertDatabaseHas('marketer_commissions', ['id' => $c->id, 'status' => 'pending']);
    }

    public function test_can_delete_pending_commission(): void
    {
        $c = $this->makeCommission('pending');
        $this->actingAs($this->admin)
            ->post("/admin/commissions/{$c->id}/delete")
            ->assertRedirect();

        $this->assertDatabaseMissing('marketer_commissions', ['id' => $c->id]);
    }

    public function test_cannot_delete_paid_commission(): void
    {
        $c = $this->makeCommission('paid');
        $this->actingAs($this->admin)
            ->post("/admin/commissions/{$c->id}/delete")
            ->assertSessionHas('error');

        $this->assertDatabaseHas('marketer_commissions', ['id' => $c->id]);
    }
}
