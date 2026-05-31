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
 *  - branch_id is ALWAYS stamped on create from the context (default = Main
 *    Branch), so data stays consistent and ready for the day the switch flips.
 *    (Skipped only in all-branches mode with no current branch, e.g. cron that
 *    sets branch_id explicitly from a parent record.)
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
