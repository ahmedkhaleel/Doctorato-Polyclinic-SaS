<?php

namespace Tests\Feature\Inventory;

use App\Models\CosmeticSession;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\ServiceSupply;
use App\Models\Supply;
use App\Models\SupplyTransaction;
use App\Models\User;
use App\Models\Visit;
use App\Services\VisitWorkflowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * I1 — a completed session visit consumes its service supplies exactly ONCE
 * (the redundant second deduction path was removed).
 * I2 — clinical event records carrying supply_id+consumption_qty (cosmetic
 * sessions, obgyn ultrasounds/deliveries) now actually draw + restore stock.
 */
class InventoryConsumptionTest extends TestCase
{
    use RefreshDatabase;

    private function actor(): User
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        return $user;
    }

    public function test_session_visit_deducts_service_supply_once(): void
    {
        $this->actor();

        $supply = Supply::create(['name_ar' => 'مستلزم', 'name_en' => 'Gauze', 'unit' => 'pcs', 'quantity' => 10, 'min_quantity' => 2, 'purchase_price' => 5]);
        $cat = ServiceCategory::create(['name_ar' => 'ت', 'name_en' => 'Cat', 'slug' => 'cat-inv']);
        $service = Service::create(['category_id' => $cat->id, 'name_ar' => 'خ', 'name_en' => 'Svc', 'slug' => 'svc-inv', 'status' => 'active', 'price' => 100, 'default_sessions' => 1, 'session_duration_minutes' => 30]);
        ServiceSupply::create(['service_id' => $service->id, 'supply_id' => $supply->id, 'quantity_per_session' => 2]);

        $doctor = Doctor::create(['name_ar' => 'د', 'name_en' => 'Doc', 'status' => 'active']);
        $patient = Patient::create(['full_name' => 'P', 'phone' => '0500000010']);
        $patient->forceFill(['is_active' => true, 'file_number' => 'PAT-INV-1'])->save();

        // Visits must be linked to a booking (model invariant).
        $booking = \App\Models\Booking::create([
            'booking_number' => \App\Models\Booking::generateBookingNumber(),
            'status' => 'confirmed', 'source' => 'secretary', 'module' => 'derma', 'booking_type' => 'service',
            'full_name' => 'P', 'phone' => '0500000010', 'patient_id' => $patient->id,
        ]);

        $visit = Visit::create([
            'patient_id' => $patient->id, 'doctor_id' => $doctor->id, 'service_id' => $service->id, 'booking_id' => $booking->id,
            'visit_type' => 'session', 'status' => 'in_progress', 'visit_date' => now()->toDateString(),
        ]);

        app(VisitWorkflowService::class)->complete($visit);

        // Deducted exactly once: 10 - 2 = 8 (NOT 6 from a double-deduction).
        $this->assertEqualsWithDelta(8, (float) $supply->fresh()->quantity, 0.001);
        $this->assertSame(1, SupplyTransaction::where('visit_id', $visit->id)->where('transaction_type', 'usage')->count());
    }

    public function test_cosmetic_session_draws_and_restores_stock(): void
    {
        $this->actor();

        $supply = Supply::create(['name_ar' => 'ب', 'name_en' => 'Botox vial', 'unit' => 'vial', 'quantity' => 10, 'min_quantity' => 1, 'purchase_price' => 50]);
        $patient = Patient::create(['full_name' => 'P2', 'phone' => '0500000011']);
        $patient->forceFill(['is_active' => true, 'file_number' => 'PAT-INV-2'])->save();

        $session = CosmeticSession::create([
            'patient_id' => $patient->id, 'supply_id' => $supply->id, 'consumption_qty' => 3, 'session_number' => 1,
        ]);

        $this->assertEqualsWithDelta(7, (float) $supply->fresh()->quantity, 0.001);
        $this->assertNotNull($session->fresh()->supply_transaction_id);

        // Idempotent: re-saving doesn't draw again.
        $session->update(['notes' => 'touch']);
        $this->assertEqualsWithDelta(7, (float) $supply->fresh()->quantity, 0.001);

        // Delete restores stock.
        $session->delete();
        $this->assertEqualsWithDelta(10, (float) $supply->fresh()->quantity, 0.001);
    }
}
