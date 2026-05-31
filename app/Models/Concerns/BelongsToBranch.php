<?php

namespace App\Models\Concerns;

use App\Models\Branch;
use App\Services\Branch\BranchContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Scopes a model to the active branch (BranchContext).
 *
 * Safety design:
 *  - FILTERING is gated by config('branches.enabled') — a kill-switch. While off
 *    (default), the global scope is a complete no-op, so behaviour is identical
 *    to single-clinic even where the trait is applied.
 *  - branch_id is ALWAYS stamped on create from the context, falling back to
 *    the default (Main) Branch in all-branches mode (cron/jobs/super-admin), so
 *    scoped data is never branchless. An explicit branch_id or a runForBranch()
 *    wrapper overrides this.
 *
 * Bypass filtering for a query with: Model::query()->withoutGlobalScope('branch')
 * or via BranchContext::runWithoutScope() / setAllBranches().
 */
trait BelongsToBranch
{
    public static function bootBelongsToBranch(): void
    {
        static::addGlobalScope('branch', function (Builder $query) {
            if (! config('branches.enabled')) {
                return; // kill-switch off → no filtering
            }
            $ctx = app(BranchContext::class);
            if ($ctx->isAllBranches()) {
                return;
            }
            if ($id = $ctx->currentId()) {
                $query->where($query->getModel()->getTable().'.branch_id', $id);
            }
        });

        static::creating(function ($model) {
            if (! empty($model->branch_id)) {
                return;
            }
            // Always stamp a concrete branch so scoped data is never branchless,
            // even in all-branches mode (cron/jobs/super-admin) → falls back to
            // the default (Main) branch. A per-branch loop (runForBranch) or an
            // explicit branch_id on the model overrides this.
            $ctx = app(BranchContext::class);
            $model->branch_id = $ctx->currentId() ?? (int) config('branches.default_id', 1);
        });
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }
}
