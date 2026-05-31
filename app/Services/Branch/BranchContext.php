<?php

namespace App\Services\Branch;

/**
 * Holds the active branch for the current request/process. The single source of
 * truth the BelongsToBranch global scope reads from.
 *
 * Resolution order (lazy, on first read):
 *   1. an explicitly set() branch (e.g. branch switcher, runForBranch)
 *   2. the staff member's session branch (config('branches.session_key'))
 *   3. config('branches.default_id') — the Main Branch
 *
 * "All branches" mode disables filtering (super-admin views, reports, cron).
 */
class BranchContext
{
    private ?int $currentId = null;

    private bool $allBranches = false;

    private bool $resolved = false;

    /** Active branch id, or null in all-branches mode (scope then does not filter). */
    public function currentId(): ?int
    {
        if (! $this->resolved) {
            $this->resolve();
        }

        return $this->allBranches ? null : $this->currentId;
    }

    public function isAllBranches(): bool
    {
        if (! $this->resolved) {
            $this->resolve();
        }

        return $this->allBranches;
    }

    /** Pin the context to a single branch. */
    public function set(int $branchId): void
    {
        $this->currentId = $branchId;
        $this->allBranches = false;
        $this->resolved = true;
    }

    /** Disable branch filtering (see everything). */
    public function setAllBranches(bool $on = true): void
    {
        $this->allBranches = $on;
        $this->resolved = true;
    }

    /** Run a callback pinned to a branch, restoring the previous state after. */
    public function runForBranch(int $branchId, callable $callback)
    {
        [$id, $all, $res] = [$this->currentId, $this->allBranches, $this->resolved];
        $this->set($branchId);
        try {
            return $callback();
        } finally {
            [$this->currentId, $this->allBranches, $this->resolved] = [$id, $all, $res];
        }
    }

    /**
     * Apply the active-branch filter to a raw query builder (DB::table reports
     * that bypass the Eloquent global scope). No-op when the kill-switch is off
     * or in all-branches mode, so single-clinic and super-admin views are
     * unchanged. Pass a qualified column ('payments.branch_id') when joining.
     */
    public function applyToBuilder($query, string $column = 'branch_id')
    {
        if (config('branches.enabled') && ! $this->isAllBranches() && ($id = $this->currentId())) {
            $query->where($column, $id);
        }

        return $query;
    }

    /** Run a callback with branch filtering disabled (cross-branch reports / cron). */
    public function runWithoutScope(callable $callback)
    {
        [$id, $all, $res] = [$this->currentId, $this->allBranches, $this->resolved];
        $this->setAllBranches(true);
        try {
            return $callback();
        } finally {
            [$this->currentId, $this->allBranches, $this->resolved] = [$id, $all, $res];
        }
    }

    private function resolve(): void
    {
        $this->resolved = true;
        $default = (int) config('branches.default_id', 1);

        // Console contexts (scheduled commands, queue:work, CLI) have no session.
        // They must operate across ALL branches so a cron/job never silently
        // scopes to a single branch. Commands that must act per-branch can still
        // wrap their work in runForBranch(). Creates still stamp a concrete branch
        // (see BelongsToBranch — currentId() ?? default), so data stays consistent.
        try {
            if (app()->runningInConsole()) {
                $this->allBranches = true;

                return;
            }
        } catch (\Throwable $e) {
            // container not ready — fall through
        }

        try {
            $sessionKey = config('branches.session_key', 'current_branch_id');
            if (function_exists('session') && app()->bound('session') && session()->isStarted() && session()->has($sessionKey)) {
                $this->currentId = (int) session($sessionKey);

                return;
            }
        } catch (\Throwable $e) {
            // no session — fall through to default
        }

        $this->currentId = $default;
    }
}
