<?php

namespace Tests\Feature\Physio;

use App\Models\Doctor;
use App\Models\Invoice;
use App\Models\Patient;
use App\Models\PhysioPackage;
use App\Models\PhysioPackagePurchase;
use App\Models\PhysioSession;
use App\Models\Role;
use App\Models\User;
use App\Services\ModuleManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * F-D — prepaid session packages: catalog seeds, a doctor sells a package
 * (purchase + invoice), and a session drawn against it decrements the balance
 * and is NOT billed individually.
 */
class PhysiotherapyPackageTest extends TestCase
{
    use RefreshDatabase;

    private User $doctorUser;

    private Doctor $doctor;

    private Patient $patient;

    protected function setUp(): void
    {
        parent::setUp();
        ModuleManager::flushStaticCache();
        $role = Role::firstOrCreate(['name' => 'doctor'], ['display_name_en' => 'Doctor', 'display_name_ar' => 'طبيب', 'permissions' => ['*'], 'is_system' => true]);
        $role->update(['permissions' => ['*']]);
        $this->doctorUser = User::create(['name' => 'PT', 'email' => 'pkg-doc@test.com', 'password' => bcrypt('x'), 'role_id' => $role->id, 'is_active' => true]);
        $this->doctor = Doctor::create(['name_ar' => 'د', 'name_en' => 'PT', 'user_id' => $this->doctorUser->id, 'status' => 'active', 'module' => 'physiotherapy']);
        ModuleManager::enable('physiotherapy');
        ModuleManager::flushStaticCache();
        $this->patient = Patient::create(['full_name' => 'Pkg P', 'phone' => '0500005656']);
        $this->patient->forceFill(['is_active' => true, 'file_number' => 'PAT-PKG-1'])->save();
    }

    public function test_catalog_is_seeded(): void
    {
        $this->assertGreaterThanOrEqual(2, PhysioPackage::where('is_active', true)->count());
    }

    public function test_doctor_sells_a_package_and_session_draws_it_down(): void
    {
        $package = PhysioPackage::where('total_sessions', 10)->firstOrFail();

        // Sell.
        $this->actingAs($this->doctorUser)
            ->post("/doctor/physiotherapy/patients/{$this->patient->id}/packages", ['package_id' => $package->id])
            ->assertRedirect();

        $purchase = PhysioPackagePurchase::where('patient_id', $this->patient->id)->firstOrFail();
        $this->assertSame(10, (int) $purchase->total_sessions);
        $this->assertSame(0, (int) $purchase->sessions_used);
        // The package sale produced one invoice.
        $this->assertEquals($package->price, (float) Invoice::where('module', 'physiotherapy')->firstOrFail()->total);
        $invoiceCountAfterSale = Invoice::where('module', 'physiotherapy')->count();

        // Log a session drawn against the package.
        $this->actingAs($this->doctorUser)
            ->post("/doctor/physiotherapy/patients/{$this->patient->id}/sessions", [
                'session_date' => '2026-06-05', 'attended' => true, 'package_purchase_id' => $purchase->id, 'bill' => true, 'cost' => 200,
            ])->assertRedirect();

        $session = PhysioSession::where('patient_id', $this->patient->id)->firstOrFail();
        $this->assertSame($purchase->id, (int) $session->package_purchase_id);
        $this->assertEquals(0, (float) $session->cost);           // covered, not charged
        $this->assertNull($session->invoice_item_id);             // not billed
        $this->assertSame(1, (int) $purchase->fresh()->sessions_used); // drawn down
        // No new invoice was raised for the covered session.
        $this->assertSame($invoiceCountAfterSale, Invoice::where('module', 'physiotherapy')->count());
    }
}
