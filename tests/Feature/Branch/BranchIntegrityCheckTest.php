<?php

namespace Tests\Feature\Branch;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class BranchIntegrityCheckTest extends TestCase
{
    use RefreshDatabase;

    public function test_detects_null_and_orphan_branch_ids(): void
    {
        // NULL branch_id (would vanish when scoped)
        DB::table('expenses')->insert([
            'amount' => 10, 'expense_date' => now()->toDateString(), 'branch_id' => null,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        // Orphan branch_id (no such branch)
        DB::table('expenses')->insert([
            'amount' => 20, 'expense_date' => now()->toDateString(), 'branch_id' => 999,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        Artisan::call('data:integrity-check', ['--json' => true]);
        $output = Artisan::output();

        $this->assertStringContainsString('branch_id_missing', $output);
        $this->assertStringContainsString('branch_id_orphan', $output);
    }

    public function test_clean_data_has_no_branch_findings(): void
    {
        DB::table('expenses')->insert([
            'amount' => 30, 'expense_date' => now()->toDateString(), 'branch_id' => 1,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        Artisan::call('data:integrity-check', ['--json' => true]);
        $output = Artisan::output();

        $this->assertStringNotContainsString('branch_id_missing', $output);
        $this->assertStringNotContainsString('branch_id_orphan', $output);
    }
}
