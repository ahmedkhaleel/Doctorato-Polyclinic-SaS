<?php

namespace App\Services\Crm;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * CRM-1 — real revenue attribution (replaces the placeholder ROI).
 *
 * A converted lead carries patient_id; every invoice that patient generates is
 * revenue attributable to the lead's source / campaign. Read-only aggregation
 * over existing tables (leads ⋈ invoices on patient_id) — no schema change.
 */
class CrmRevenueService
{
    /**
     * Revenue + converted count per lead source since $start.
     *
     * @return array<int, array{lead_source_id:int|null, converted:int, revenue:float}>
     */
    public function bySource(?Carbon $start = null): array
    {
        return $this->aggregate('lead_source_id', $start);
    }

    /** Revenue + converted count per campaign since $start. */
    public function byCampaign(?Carbon $start = null): array
    {
        return $this->aggregate('campaign_id', $start);
    }

    /** Total attributed revenue for converted leads since $start. */
    public function total(?Carbon $start = null): float
    {
        $q = DB::table('leads')
            ->join('invoices', 'invoices.patient_id', '=', 'leads.patient_id')
            ->where('leads.status', 'converted')
            ->whereNotNull('leads.patient_id')
            ->whereNull('leads.deleted_at')
            ->whereNull('invoices.deleted_at');
        if ($start) {
            $q->where('invoices.invoice_date', '>=', $start->toDateString());
        }

        return round((float) $q->sum('invoices.total'), 2);
    }

    /**
     * ROI summary for one campaign: revenue, cost, roi% (null when no cost).
     *
     * @return array{revenue: float, cost: float, roi: float|null}
     */
    public function campaignRoi(int $campaignId): array
    {
        $revenue = round((float) DB::table('leads')
            ->join('invoices', 'invoices.patient_id', '=', 'leads.patient_id')
            ->where('leads.campaign_id', $campaignId)
            ->where('leads.status', 'converted')
            ->whereNotNull('leads.patient_id')
            ->whereNull('leads.deleted_at')
            ->whereNull('invoices.deleted_at')
            ->sum('invoices.total'), 2);

        $cost = (float) (DB::table('crm_campaigns')->where('id', $campaignId)->value('actual_cost') ?? 0);

        return [
            'revenue' => $revenue,
            'cost' => $cost,
            'roi' => $cost > 0 ? round((($revenue - $cost) / $cost) * 100, 1) : null,
        ];
    }

    /** @return array<int, array{lead_source_id?:int|null, campaign_id?:int|null, converted:int, revenue:float}> */
    private function aggregate(string $groupColumn, ?Carbon $start): array
    {
        $q = DB::table('leads')
            ->join('invoices', 'invoices.patient_id', '=', 'leads.patient_id')
            ->where('leads.status', 'converted')
            ->whereNotNull('leads.patient_id')
            ->whereNull('leads.deleted_at')
            ->whereNull('invoices.deleted_at')
            ->selectRaw("leads.{$groupColumn} as grp, COUNT(DISTINCT leads.id) as converted, SUM(invoices.total) as revenue")
            ->groupBy('grp');
        if ($start) {
            $q->where('invoices.invoice_date', '>=', $start->toDateString());
        }

        return $q->get()
            ->map(fn ($r) => [$groupColumn => $r->grp, 'converted' => (int) $r->converted, 'revenue' => round((float) $r->revenue, 2)])
            ->all();
    }
}
