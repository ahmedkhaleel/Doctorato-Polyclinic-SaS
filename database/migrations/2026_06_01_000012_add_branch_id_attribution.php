<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Multi-branch — ATTRIBUTION columns only (no global scope).
 *
 * notification_logs / scheduled_notifications: per-branch analytics for the
 * org-wide notification hub. Existing rows attributed to the default branch.
 *
 * leads: optional branch attribution for CRM reporting. Left NULL on existing
 * rows (a lead is central / unassigned until it converts at a branch).
 */
return new class extends Migration
{
    private array $backfill = ['notification_logs', 'scheduled_notifications'];

    private array $noBackfill = ['leads'];

    public function up(): void
    {
        $default = (int) config('branches.default_id', 1);
        foreach (array_merge($this->backfill, $this->noBackfill) as $t) {
            if (! Schema::hasTable($t) || Schema::hasColumn($t, 'branch_id')) {
                continue;
            }
            Schema::table($t, function (Blueprint $table) {
                $table->unsignedBigInteger('branch_id')->nullable()->after('id');
                $table->index(['branch_id', 'created_at']);
            });
        }
        foreach ($this->backfill as $t) {
            if (Schema::hasTable($t) && Schema::hasColumn($t, 'branch_id')) {
                DB::table($t)->whereNull('branch_id')->update(['branch_id' => $default]);
            }
        }
    }

    public function down(): void
    {
        foreach (array_merge($this->backfill, $this->noBackfill) as $t) {
            if (Schema::hasTable($t) && Schema::hasColumn($t, 'branch_id')) {
                Schema::table($t, function (Blueprint $table) {
                    $table->dropIndex(['branch_id', 'created_at']);
                    $table->dropColumn('branch_id');
                });
            }
        }
    }
};
