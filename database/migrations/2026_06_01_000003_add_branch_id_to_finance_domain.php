<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** Multi-branch B1 — finance domain. Additive: nullable branch_id + backfill→Main + index. */
return new class extends Migration
{
    private array $tables = [
        'invoices', 'invoice_items', 'payments', 'payment_transactions',
        'credit_notes', 'discount_usage', 'marketer_commissions',
    ];

    public function up(): void
    {
        $default = (int) config('branches.default_id', 1);
        foreach ($this->tables as $t) {
            if (! Schema::hasTable($t) || Schema::hasColumn($t, 'branch_id')) {
                continue;
            }
            Schema::table($t, function (Blueprint $table) {
                $table->unsignedBigInteger('branch_id')->nullable()->after('id');
                $table->index(['branch_id', 'created_at']);
            });
            DB::table($t)->whereNull('branch_id')->update(['branch_id' => $default]);
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $t) {
            if (Schema::hasTable($t) && Schema::hasColumn($t, 'branch_id')) {
                Schema::table($t, function (Blueprint $table) {
                    $table->dropIndex(['branch_id', 'created_at']);
                    $table->dropColumn('branch_id');
                });
            }
        }
    }
};
