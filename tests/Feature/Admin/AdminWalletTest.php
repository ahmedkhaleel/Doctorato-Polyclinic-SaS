<?php

namespace Tests\Feature\Admin;

use App\Models\Patient;
use App\Models\PatientWallet;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminWalletTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Patient $patient;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::firstOrCreate(
            ['name' => 'admin'],
            [
                'display_name_en' => 'Admin',
                'display_name_ar' => 'مدير',
                'permissions' => ['*'],
                'is_system' => true,
            ]
        );

        $this->admin = User::create([
            'name' => 'Admin',
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
            'role_id' => $role->id,
            'is_active' => true,
        ]);

        $this->patient = new Patient(['full_name' => 'Test', 'phone' => '01000000010']);
        $this->patient->file_number = 'P-WALLET-001';
        $this->patient->is_active = true;
        $this->patient->save();
    }

    public function test_deposit_increases_wallet_balance(): void
    {
        $this->actingAs($this->admin);

        $response = $this->post("/admin/wallets/{$this->patient->id}/deposit", [
            'amount' => 500,
            'description' => 'Test deposit',
        ]);

        $response->assertRedirect();
        $wallet = PatientWallet::where('patient_id', $this->patient->id)->first();
        $this->assertEquals(500, (float) $wallet->balance);
    }

    public function test_withdrawal_fails_with_insufficient_balance(): void
    {
        PatientWallet::create([
            'patient_id' => $this->patient->id,
            'balance' => 100,
        ]);

        $this->actingAs($this->admin);

        $response = $this->post("/admin/wallets/{$this->patient->id}/withdraw", [
            'amount' => 200,
            'description' => 'Overdraft attempt',
            'type' => 'withdrawal',
        ]);

        $wallet = PatientWallet::where('patient_id', $this->patient->id)->first();
        $this->assertEquals(100, (float) $wallet->balance); // unchanged
    }

    public function test_valid_withdrawal_decreases_balance(): void
    {
        PatientWallet::create([
            'patient_id' => $this->patient->id,
            'balance' => 500,
        ]);

        $this->actingAs($this->admin);

        $response = $this->post("/admin/wallets/{$this->patient->id}/withdraw", [
            'amount' => 200,
            'description' => 'Valid withdrawal',
            'type' => 'withdrawal',
        ]);

        $response->assertRedirect();
        $wallet = PatientWallet::where('patient_id', $this->patient->id)->first();
        $this->assertEquals(300, (float) $wallet->balance);
    }

    public function test_deposit_validates_amount(): void
    {
        $this->actingAs($this->admin);

        $response = $this->post("/admin/wallets/{$this->patient->id}/deposit", [
            'amount' => 0,
            'description' => 'Invalid deposit',
        ]);

        $response->assertSessionHasErrors('amount');
    }

    public function test_adjustment_works_for_positive_and_negative(): void
    {
        PatientWallet::create([
            'patient_id' => $this->patient->id,
            'balance' => 500,
        ]);

        $this->actingAs($this->admin);

        // Negative adjustment
        $this->post("/admin/wallets/{$this->patient->id}/adjust", [
            'amount' => -200,
            'description' => 'Correction',
        ]);

        $wallet = PatientWallet::where('patient_id', $this->patient->id)->first();
        $this->assertEquals(300, (float) $wallet->balance);
    }
}
