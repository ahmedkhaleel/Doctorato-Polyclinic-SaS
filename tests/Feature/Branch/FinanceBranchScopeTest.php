<?php

namespace Tests\Feature\Branch;

use App\Models\Branch;
use App\Models\Invoice;
use App\Models\Patient;
use App\Models\Payment;
use App\Services\Branch\BranchContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinanceBranchScopeTest extends TestCase
{
    use RefreshDatabase;

    private function ctx(): BranchContext
    {
        return app(BranchContext::class);
    }

    private function patient(): Patient
    {
        $p = new Patient(['full_name' => 'Fin', 'phone' => '0101'.random_int(1000000, 9999999)]);
        $p->file_number = 'P-FIN-'.uniqid();
        $p->is_active = true;
        $p->save();

        return $p;
    }

    private function invoice(Patient $p, array $a = []): Invoice
    {
        return Invoice::create(array_merge([
            'patient_id' => $p->id, 'invoice_number' => 'INV-'.uniqid(),
            'total' => 100, 'invoice_date' => now()->toDateString(),
        ], $a));
    }

    public function test_disabled_stamps_main_no_filter(): void
    {
        config(['branches.enabled' => false]);
        $p = $this->patient();
        $inv = $this->invoice($p);
        $this->assertSame(1, (int) $inv->branch_id);
    }

    public function test_invoices_isolated_by_branch(): void
    {
        config(['branches.enabled' => true]);
        Branch::create(['id' => 2, 'name_ar' => 'B2', 'name_en' => 'B2', 'code' => 'B2']);
        $p = $this->patient();

        $this->ctx()->set(1);
        $this->invoice($p);
        $this->ctx()->runForBranch(2, fn () => $this->invoice($p));

        $this->ctx()->set(1);
        $this->assertSame(1, Invoice::count());
        $this->ctx()->set(2);
        $this->assertSame(1, Invoice::count());
        $this->ctx()->setAllBranches();
        $this->assertSame(2, Invoice::count());
    }

    public function test_payment_stamps_active_branch(): void
    {
        config(['branches.enabled' => true]);
        Branch::create(['id' => 2, 'name_ar' => 'B2', 'name_en' => 'B2', 'code' => 'B2']);
        $p = $this->patient();
        $this->ctx()->set(2);
        $inv = $this->invoice($p);

        $pay = Payment::create([
            'invoice_id' => $inv->id, 'patient_id' => $p->id,
            'amount' => 50, 'payment_date' => now()->toDateString(),
        ]);
        $this->assertSame(2, (int) $pay->branch_id);
    }
}
