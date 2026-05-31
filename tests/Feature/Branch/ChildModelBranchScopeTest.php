<?php

namespace Tests\Feature\Branch;

use App\Models\Branch;
use App\Models\InvoiceItem;
use App\Models\Patient;
use App\Services\Branch\BranchContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Child/line-item models now carry the BelongsToBranch scope so DIRECT queries
 * on them (reports, exports) are branch-filtered too — not just their parents.
 */
class ChildModelBranchScopeTest extends TestCase
{
    use RefreshDatabase;

    private function ctx(): BranchContext
    {
        return app(BranchContext::class);
    }

    private function patientId(): int
    {
        $p = new Patient(['full_name' => 'CI', 'phone' => '0106'.random_int(1000000, 9999999)]);
        $p->file_number = 'P-CI-'.uniqid();
        $p->is_active = true;
        $p->save();

        return $p->id;
    }

    public function test_invoice_items_are_branch_filtered_on_direct_query(): void
    {
        config(['branches.enabled' => true]);
        $b2 = Branch::create(['name_ar' => 'B2', 'name_en' => 'B2', 'code' => 'B2']);

        // Two line items, different branches (direct inserts to isolate the scope)
        $invId = DB::table('invoices')->insertGetId([
            'invoice_number' => 'INV-T-1', 'patient_id' => $this->patientId(), 'invoice_date' => now()->toDateString(),
            'status' => 'unpaid', 'branch_id' => 1, 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('invoice_items')->insert([
            ['invoice_id' => $invId, 'unit_price' => 10, 'total' => 10, 'branch_id' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['invoice_id' => $invId, 'unit_price' => 20, 'total' => 20, 'branch_id' => 2, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->ctx()->set(1);
        $this->assertSame(1, InvoiceItem::count());
        $this->ctx()->set(2);
        $this->assertSame(1, InvoiceItem::count());
        $this->ctx()->setAllBranches();
        $this->assertSame(2, InvoiceItem::count());
    }

    public function test_child_create_stamps_active_branch(): void
    {
        config(['branches.enabled' => true]);
        Branch::create(['id' => 2, 'name_ar' => 'B2', 'name_en' => 'B2', 'code' => 'B2']);

        $invId = DB::table('invoices')->insertGetId([
            'invoice_number' => 'INV-T-2', 'patient_id' => $this->patientId(), 'invoice_date' => now()->toDateString(),
            'status' => 'unpaid', 'branch_id' => 2, 'created_at' => now(), 'updated_at' => now(),
        ]);

        $item = $this->ctx()->runForBranch(2, fn () => InvoiceItem::create([
            'invoice_id' => $invId, 'unit_price' => 5, 'total' => 5,
        ]));
        $this->assertSame(2, (int) $item->branch_id);
    }

    public function test_disabled_is_a_noop_for_children(): void
    {
        config(['branches.enabled' => false]);
        $invId = DB::table('invoices')->insertGetId([
            'invoice_number' => 'INV-T-3', 'patient_id' => $this->patientId(), 'invoice_date' => now()->toDateString(),
            'status' => 'unpaid', 'branch_id' => 1, 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('invoice_items')->insert([
            ['invoice_id' => $invId, 'unit_price' => 1, 'total' => 1, 'branch_id' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['invoice_id' => $invId, 'unit_price' => 2, 'total' => 2, 'branch_id' => 2, 'created_at' => now(), 'updated_at' => now()],
        ]);
        // kill-switch off → no filtering even with a branch pinned
        $this->ctx()->set(1);
        $this->assertSame(2, InvoiceItem::count());
    }
}
