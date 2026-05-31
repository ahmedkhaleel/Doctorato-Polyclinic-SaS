<?php

namespace App\Services\Branch;

use App\Models\Booking;
use App\Models\Branch;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Visit;
use Illuminate\Support\Carbon;

/**
 * Cross-branch comparison KPIs for the reporting dashboard.
 *
 * Each metric filters EXPLICITLY by branch_id with the branch global scope
 * removed, so totals are correct regardless of the caller's active branch
 * (e.g. a super-admin in all-branches mode) and never leak across branches.
 */
class BranchReportService
{
    public function comparison(?string $from = null, ?string $to = null): array
    {
        $from = $from ? Carbon::parse($from)->startOfDay() : Carbon::now()->startOfMonth();
        $to = $to ? Carbon::parse($to)->endOfDay() : Carbon::now()->endOfDay();

        $rows = Branch::where('is_active', true)->orderBy('id')->get()->map(function (Branch $b) use ($from, $to) {
            $bookings = Booking::withoutGlobalScope('branch')
                ->where('branch_id', $b->id)
                ->whereBetween('created_at', [$from, $to])->count();

            $visits = Visit::withoutGlobalScope('branch')
                ->where('branch_id', $b->id)
                ->whereBetween('created_at', [$from, $to])->count();

            $invoiced = (float) Invoice::withoutGlobalScope('branch')
                ->where('branch_id', $b->id)
                ->whereBetween('invoice_date', [$from->toDateString(), $to->toDateString()])
                ->sum('total');

            $collected = (float) Payment::withoutGlobalScope('branch')
                ->where('branch_id', $b->id)
                ->whereBetween('payment_date', [$from->toDateString(), $to->toDateString()])
                ->sum('amount');

            $outstanding = (float) Invoice::withoutGlobalScope('branch')
                ->where('branch_id', $b->id)
                ->whereNotIn('status', ['paid', 'cancelled'])
                ->sum('total') - (float) Invoice::withoutGlobalScope('branch')
                ->where('branch_id', $b->id)
                ->whereNotIn('status', ['paid', 'cancelled'])
                ->sum('paid_amount');

            return [
                'branch_id' => $b->id,
                'name_ar' => $b->name_ar,
                'name_en' => $b->name_en,
                'code' => $b->code,
                'bookings' => $bookings,
                'visits' => $visits,
                'invoiced' => round($invoiced, 2),
                'collected' => round($collected, 2),
                'outstanding' => round(max($outstanding, 0), 2),
            ];
        })->values()->all();

        return [
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'rows' => $rows,
            'totals' => [
                'bookings' => array_sum(array_column($rows, 'bookings')),
                'visits' => array_sum(array_column($rows, 'visits')),
                'invoiced' => round(array_sum(array_column($rows, 'invoiced')), 2),
                'collected' => round(array_sum(array_column($rows, 'collected')), 2),
                'outstanding' => round(array_sum(array_column($rows, 'outstanding')), 2),
            ],
        ];
    }
}
