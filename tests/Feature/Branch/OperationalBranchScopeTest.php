<?php

namespace Tests\Feature\Branch;

use App\Models\Branch;
use App\Models\Expense;
use App\Models\Patient;
use App\Models\PatientRecallReminder;
use App\Services\Branch\BranchContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OperationalBranchScopeTest extends TestCase
{
    use RefreshDatabase;

    private function ctx(): BranchContext
    {
        return app(BranchContext::class);
    }

    private function patient(): Patient
    {
        $p = new Patient(['full_name' => 'O', 'phone' => '0103'.random_int(1000000, 9999999)]);
        $p->file_number = 'P-O-'.uniqid();
        $p->is_active = true;
        $p->save();

        return $p;
    }

    public function test_disabled_stamps_main(): void
    {
        config(['branches.enabled' => false]);
        $e = Expense::create(['amount' => 100, 'expense_date' => now()->toDateString()]);
        $this->assertSame(1, (int) $e->branch_id);
    }

    public function test_operational_records_isolated_per_branch(): void
    {
        config(['branches.enabled' => true]);
        Branch::create(['id' => 2, 'name_ar' => 'B2', 'name_en' => 'B2', 'code' => 'B2']);
        $p = $this->patient();

        $this->ctx()->set(1);
        Expense::create(['amount' => 50, 'expense_date' => now()->toDateString()]);
        PatientRecallReminder::create(['patient_id' => $p->id]);
        $this->ctx()->runForBranch(2, function () use ($p) {
            Expense::create(['amount' => 75, 'expense_date' => now()->toDateString()]);
            PatientRecallReminder::create(['patient_id' => $p->id]);
        });

        $this->ctx()->set(1);
        $this->assertSame(1, Expense::count());
        $this->assertSame(1, PatientRecallReminder::count());

        $this->ctx()->set(2);
        $this->assertSame(2, (int) Expense::first()->branch_id);

        $this->ctx()->setAllBranches();
        $this->assertSame(2, Expense::count());
        $this->assertSame(2, PatientRecallReminder::count());
    }
}
