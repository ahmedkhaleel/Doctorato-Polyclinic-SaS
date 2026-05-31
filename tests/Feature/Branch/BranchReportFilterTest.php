<?php

namespace Tests\Feature\Branch;

use App\Models\Branch;
use App\Services\Branch\BranchContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Raw DB::table report queries bypass the Eloquent global scope, so they must
 * use BranchContext::applyToBuilder() to avoid leaking another branch's data.
 */
class BranchReportFilterTest extends TestCase
{
    use RefreshDatabase;

    private function ctx(): BranchContext
    {
        return app(BranchContext::class);
    }

    private function seedPayments(): void
    {
        $p = new \App\Models\Patient(['full_name' => 'R', 'phone' => '0107'.random_int(1000000, 9999999)]);
        $p->file_number = 'P-RF-'.uniqid();
        $p->is_active = true;
        $p->save();

        $invId = DB::table('invoices')->insertGetId([
            'invoice_number' => 'INV-RF-'.uniqid(), 'patient_id' => $p->id, 'invoice_date' => now()->toDateString(),
            'status' => 'paid', 'branch_id' => 1, 'created_at' => now(), 'updated_at' => now(),
        ]);

        DB::table('payments')->insert([
            ['invoice_id' => $invId, 'patient_id' => $p->id, 'amount' => 100, 'payment_date' => now()->toDateString(), 'branch_id' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['invoice_id' => $invId, 'patient_id' => $p->id, 'amount' => 250, 'payment_date' => now()->toDateString(), 'branch_id' => 2, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function test_builder_filter_isolates_active_branch(): void
    {
        config(['branches.enabled' => true]);
        Branch::create(['id' => 2, 'name_ar' => 'B2', 'name_en' => 'B2', 'code' => 'B2']);
        $this->seedPayments();

        $this->ctx()->set(1);
        $this->assertSame(100.0, (float) $this->ctx()->applyToBuilder(DB::table('payments'), 'payments.branch_id')->sum('amount'));

        $this->ctx()->set(2);
        $this->assertSame(250.0, (float) $this->ctx()->applyToBuilder(DB::table('payments'), 'payments.branch_id')->sum('amount'));

        $this->ctx()->setAllBranches();
        $this->assertSame(350.0, (float) $this->ctx()->applyToBuilder(DB::table('payments'), 'payments.branch_id')->sum('amount'));
    }

    public function test_disabled_is_a_noop(): void
    {
        config(['branches.enabled' => false]);
        Branch::create(['id' => 2, 'name_ar' => 'B2', 'name_en' => 'B2', 'code' => 'B2']);
        $this->seedPayments();

        // Even pinned to branch 1, the kill-switch off means no filtering
        $this->ctx()->set(1);
        $this->assertSame(350.0, (float) $this->ctx()->applyToBuilder(DB::table('payments'), 'payments.branch_id')->sum('amount'));
    }
}
