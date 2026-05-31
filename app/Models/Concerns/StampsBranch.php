<?php

namespace App\Models\Concerns;

use App\Models\Branch;
use App\Services\Branch\BranchContext;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Schema;

/**
 * Attribution-only branch behaviour: stamps branch_id from the active context
 * on create (so reports can attribute the row to a branch) but applies NO global
 * scope — the model stays CENTRALLY VISIBLE across all branches.
 *
 * Used for org-wide subsystems that must not be partitioned by branch:
 *  - the notification hub (single org-wide sender — locked decision)
 *  - CRM leads/pipeline (central — locked decision; branch_id is optional
 *    attribution for reporting only)
 *
 * Contrast with BelongsToBranch, which also filters (global scope).
 */
trait StampsBranch
{
    /** Memoized table => has branch_id column (deploy-before-migrate safety). */
    protected static array $stampBranchColumnCache = [];

    public static function bootStampsBranch(): void
    {
        static::creating(function ($model) {
            if (! empty($model->branch_id)) {
                return;
            }
            $table = $model->getTable();
            if (! array_key_exists($table, static::$stampBranchColumnCache)) {
                try {
                    static::$stampBranchColumnCache[$table] = Schema::hasColumn($table, 'branch_id');
                } catch (\Throwable $e) {
                    static::$stampBranchColumnCache[$table] = false;
                }
            }
            if (! static::$stampBranchColumnCache[$table]) {
                return; // column not migrated yet
            }
            $ctx = app(BranchContext::class);
            if (! $ctx->isAllBranches() && ($id = $ctx->currentId())) {
                $model->branch_id = $id;
            }
        });
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }
}
