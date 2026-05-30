<?php

namespace Tests\Feature\Admin;

use App\Models\Patient;
use App\Models\Referral;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminReferralTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Patient $patient;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::firstOrCreate(
            ['name' => 'admin'],
            ['display_name_en' => 'Admin', 'display_name_ar' => 'مدير', 'permissions' => ['*'], 'is_system' => true]
        );

        $this->admin = User::create([
            'name' => 'Admin', 'email' => 'admin-ref@test.com',
            'password' => bcrypt('password'), 'role_id' => $role->id, 'is_active' => true,
        ]);

        $this->patient = new Patient([
            'full_name' => 'Test Patient',
            'phone' => '0199900099',
        ]);
        $this->patient->file_number = 'P-REF-001';
        $this->patient->is_active = true;
        $this->patient->save();
    }

    public function test_can_view_referrals_index(): void
    {
        $this->actingAs($this->admin)->get('/admin/referrals')->assertOk();
    }

    public function test_can_create_referral(): void
    {
        $this->actingAs($this->admin)->post('/admin/referrals', [
            'patient_id' => $this->patient->id,
            'referring_doctor_id' => $this->admin->id,
            'from_department' => 'dermatology',
            'to_department' => 'dental',
            'urgency' => 'routine',
            'reason' => 'Patient needs dental evaluation',
        ])->assertRedirect();

        $this->assertDatabaseHas('referrals', [
            'patient_id' => $this->patient->id,
            'from_department' => 'dermatology',
            'to_department' => 'dental',
            'status' => 'pending',
        ]);
    }

    public function test_referral_requires_fields(): void
    {
        $this->actingAs($this->admin)->post('/admin/referrals', [])
            ->assertSessionHasErrors(['patient_id', 'referring_doctor_id', 'from_department', 'to_department', 'urgency', 'reason']);
    }

    public function test_invalid_department_rejected(): void
    {
        $this->actingAs($this->admin)->post('/admin/referrals', [
            'patient_id' => $this->patient->id,
            'referring_doctor_id' => $this->admin->id,
            'from_department' => 'cardiology',
            'to_department' => 'dental',
            'urgency' => 'routine',
            'reason' => 'Test',
        ])->assertSessionHasErrors('from_department');
    }

    public function test_can_accept_referral(): void
    {
        $referral = Referral::create([
            'referral_number' => 'REF-TEST-001',
            'patient_id' => $this->patient->id,
            'referring_doctor_id' => $this->admin->id,
            'from_department' => 'dermatology',
            'to_department' => 'dental',
            'urgency' => 'routine',
            'reason' => 'Evaluation needed',
            'status' => 'pending',
        ]);

        $this->actingAs($this->admin)->post("/admin/referrals/{$referral->id}/status", [
            'status' => 'accepted',
        ])->assertRedirect();

        $this->assertDatabaseHas('referrals', [
            'id' => $referral->id,
            'status' => 'accepted',
        ]);
    }

    public function test_can_decline_referral(): void
    {
        $referral = Referral::create([
            'referral_number' => 'REF-TEST-002',
            'patient_id' => $this->patient->id,
            'referring_doctor_id' => $this->admin->id,
            'from_department' => 'dental',
            'to_department' => 'cosmetic',
            'urgency' => 'urgent',
            'reason' => 'Not applicable',
            'status' => 'pending',
        ]);

        $this->actingAs($this->admin)->post("/admin/referrals/{$referral->id}/status", [
            'status' => 'declined',
            'response_notes' => 'Patient does not require cosmetic referral',
        ])->assertRedirect();

        $this->assertDatabaseHas('referrals', [
            'id' => $referral->id,
            'status' => 'declined',
        ]);
    }
}
