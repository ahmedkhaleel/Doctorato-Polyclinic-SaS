<?php

namespace Tests\Feature\Obgyn;

use App\Models\Doctor;
use App\Models\Pregnancy;
use App\Models\Role;
use App\Models\Supply;
use App\Models\SupplyTransaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * P-fix #2: obstetric ultrasounds/deliveries deduct their consumables from
 * inventory via a SupplyTransaction (usage), mirroring cosmetic sessions.
 */
class ObgynInventoryTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function recording_an_ultrasound_with_a_supply_deducts_inventory(): void
    {
        $role = Role::firstOrCreate(['name' => 'doctor'], ['display_name_en' => 'D', 'display_name_ar' => 'د', 'permissions' => [], 'is_system' => true]);
        $user = User::create(['name' => 'D', 'email' => 'inv-ob@test.com', 'password' => bcrypt('x'), 'role_id' => $role->id, 'is_active' => true]);
        $doctor = Doctor::create(['name_ar' => 'د', 'name_en' => 'D', 'user_id' => $user->id, 'status' => 'active', 'module' => 'obgyn']);

        $supply = Supply::create([
            'name_ar' => 'جل سونار', 'name_en' => 'Ultrasound gel', 'unit' => 'bottle',
            'quantity' => 100, 'min_quantity' => 5, 'purchase_price' => 10, 'is_active' => true,
        ]);

        $patient = \App\Models\Patient::create(['full_name' => 'Mom', 'phone' => '0100', 'gender' => 'female']);
        $pregnancy = Pregnancy::create(['patient_id' => $patient->id, 'doctor_id' => $doctor->id, 'lmp' => '2026-01-01', 'status' => 'active']);

        $this->actingAs($user)
            ->post("/doctor/obgyn/pregnancies/{$pregnancy->id}/ultrasound", [
                'scan_date' => '2026-05-01', 'scan_type' => 'growth',
                'supply_id' => $supply->id, 'consumption_qty' => 2, 'bill' => false,
            ])->assertRedirect();

        // Stock decremented + a usage transaction recorded + linked.
        $this->assertEquals(98, (float) $supply->fresh()->quantity);
        $txn = SupplyTransaction::where('supply_id', $supply->id)->where('transaction_type', 'usage')->first();
        $this->assertNotNull($txn);
        $this->assertEquals(2, (float) $txn->quantity);
        $this->assertSame($txn->id, $pregnancy->ultrasounds()->first()->supply_transaction_id);
    }

    #[Test]
    public function ultrasound_without_a_supply_does_not_touch_inventory(): void
    {
        $role = Role::firstOrCreate(['name' => 'doctor'], ['display_name_en' => 'D', 'display_name_ar' => 'د', 'permissions' => [], 'is_system' => true]);
        $user = User::create(['name' => 'D2', 'email' => 'inv-ob2@test.com', 'password' => bcrypt('x'), 'role_id' => $role->id, 'is_active' => true]);
        $doctor = Doctor::create(['name_ar' => 'د', 'name_en' => 'D', 'user_id' => $user->id, 'status' => 'active', 'module' => 'obgyn']);
        $patient = \App\Models\Patient::create(['full_name' => 'Mom2', 'phone' => '0101', 'gender' => 'female']);
        $pregnancy = Pregnancy::create(['patient_id' => $patient->id, 'doctor_id' => $doctor->id, 'lmp' => '2026-01-01', 'status' => 'active']);

        $this->actingAs($user)
            ->post("/doctor/obgyn/pregnancies/{$pregnancy->id}/ultrasound", ['scan_date' => '2026-05-01', 'scan_type' => 'dating', 'bill' => false])
            ->assertRedirect();

        $this->assertSame(0, SupplyTransaction::count());
    }
}
