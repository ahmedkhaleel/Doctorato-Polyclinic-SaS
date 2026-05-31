<?php

namespace Tests\Feature\Branch;

use App\Models\Branch;
use App\Models\Patient;
use App\Models\PatientSatisfaction;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\Supply;
use App\Models\Visit;
use App\Services\Branch\BranchContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

/**
 * Cron commands that CREATE scoped records must attribute them to the right
 * branch, not the default — even though cron runs in all-branches mode.
 */
class CronCreateInheritsBranchTest extends TestCase
{
    use RefreshDatabase;

    private function ctx(): BranchContext
    {
        return app(BranchContext::class);
    }

    private function patient(): Patient
    {
        $p = new Patient(['full_name' => 'X', 'phone' => '0105'.random_int(1000000, 9999999)]);
        $p->file_number = 'P-'.uniqid();
        $p->is_active = true;
        $p->save();

        return $p;
    }

    public function test_satisfaction_survey_inherits_visit_branch(): void
    {
        config(['branches.enabled' => true]);
        $b2 = Branch::create(['name_ar' => 'B2', 'name_en' => 'B2', 'code' => 'B2']);
        $p = $this->patient();

        // Completed visit YESTERDAY in branch 2 (visits must link a booking)
        $this->ctx()->runForBranch($b2->id, function () use ($p) {
            $booking = \App\Models\Booking::create([
                'patient_id' => $p->id, 'full_name' => $p->full_name, 'phone' => $p->phone,
                'booking_type' => 'service', 'status' => 'confirmed', 'source' => 'website',
                'module' => 'dental', 'preferred_date' => now()->toDateString(),
            ]);
            Visit::create([
                'patient_id' => $p->id, 'booking_id' => $booking->id, 'visit_type' => 'consultation',
                'status' => 'completed', 'visit_date' => now()->subDay()->toDateString(),
            ]);
        });

        Artisan::call('satisfaction:send-surveys');

        $survey = PatientSatisfaction::withoutGlobalScope('branch')->first();
        $this->assertNotNull($survey);
        $this->assertSame($b2->id, (int) $survey->branch_id);
    }

    public function test_low_stock_purchase_orders_are_per_branch(): void
    {
        config(['branches.enabled' => true]);
        $b2 = Branch::create(['name_ar' => 'B2', 'name_en' => 'B2', 'code' => 'B2']);
        $supplier = Supplier::create(['name_ar' => 'أكمي', 'name_en' => 'Acme', 'is_active' => true]);

        $makeLowSupply = function () use ($supplier) {
            // auto_reorder / reorder_quantity / supplier_id are guarded — set directly
            $s = new Supply([
                'module' => 'dental', 'name_ar' => 'مادة', 'name_en' => 'Item',
                'unit' => 'pcs', 'quantity' => 1, 'min_quantity' => 10,
                'purchase_price' => 5, 'is_active' => true,
            ]);
            $s->auto_reorder = true;
            $s->reorder_quantity = 20;
            $s->supplier_id = $supplier->id;
            $s->save();
        };
        $this->ctx()->runForBranch(1, $makeLowSupply);
        $this->ctx()->runForBranch($b2->id, $makeLowSupply);

        Artisan::call('inventory:check-low-stock');

        $pos = PurchaseOrder::withoutGlobalScope('branch')->get();
        $this->assertSame(2, $pos->count(), 'one PO per branch');
        $this->assertEqualsCanonicalizing([1, $b2->id], $pos->pluck('branch_id')->map(fn ($x) => (int) $x)->all());
        // Branch-coded numbering prevents a global po_number collision
        $this->assertSame(2, $pos->pluck('po_number')->unique()->count());
    }
}
