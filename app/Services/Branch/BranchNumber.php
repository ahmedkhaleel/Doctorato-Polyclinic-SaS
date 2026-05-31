<?php

namespace App\Services\Branch;

use App\Models\Branch;

/**
 * Branch-aware document numbering.
 *
 * Builds a date-stamped prefix for sequence generators (bookings, invoices,
 * credit notes, certificates, …). The MAIN/default branch keeps the legacy
 * format (`XX-YYYYMM-`) so existing sequences continue unbroken and behaviour
 * is identical while the kill-switch is off. Other branches get a code segment
 * (`XX-CODE-YYYYMM-`) so numbers never collide across branches under the
 * existing global unique constraint — no index surgery required.
 *
 * Generators still query `static::where($field,'like',$prefix.'%')`, which is
 * branch-scoped by BelongsToBranch when enabled, so the running sequence is
 * naturally per-branch.
 */
class BranchNumber
{
    public static function prefix(string $base): string
    {
        return $base.'-'.self::segment().now()->format('Ym').'-';
    }

    /**
     * The branch code segment ("CODE-") to inject into a number for non-default
     * branches, or "" for the main branch / when disabled. Use this for custom
     * formats that prefix() can't build (e.g. salary slips keyed by year+month).
     */
    public static function segment(): string
    {
        $ctx = app(BranchContext::class);
        $default = (int) config('branches.default_id', 1);
        $branchId = $ctx->currentId() ?? $default;

        if (config('branches.enabled') && $branchId !== $default) {
            $code = Branch::where('id', $branchId)->value('code');

            return ($code ?: 'B'.$branchId).'-';
        }

        return '';
    }
}
