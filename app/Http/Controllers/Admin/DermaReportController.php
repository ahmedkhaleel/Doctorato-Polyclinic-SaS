<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CosmeticPackagePurchase;
use App\Models\CosmeticSession;
use App\Models\DermaSession;
use App\Models\DermaTreatmentPlan;
use App\Models\SkinCondition;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Dermatology & Cosmetic analytics over a date range: session volume,
 * à-la-carte vs package revenue, breakdown by session type and by top
 * procedures, course/package status, and active-condition mix.
 */
class DermaReportController extends Controller
{
    public function index(Request $request): Response
    {
        $dateFrom = $request->input('date_from', now()->startOfMonth()->toDateString());
        $dateTo = $request->input('date_to', now()->toDateString());
        $from = Carbon::parse($dateFrom)->startOfDay();
        $to = Carbon::parse($dateTo)->endOfDay();

        $dermaDone = DermaSession::whereBetween('completed_at', [$from, $to]);
        $cosmeticDone = CosmeticSession::whereBetween('completed_at', [$from, $to]);

        // À-la-carte revenue (package-linked cosmetic sessions are prepaid → cost 0/excluded)
        $dermaRevenue = (float) (clone $dermaDone)->sum('cost');
        $cosmeticRevenue = (float) (clone $cosmeticDone)->whereNull('package_purchase_id')->sum('cost');
        $packageSales = (float) CosmeticPackagePurchase::whereBetween('purchased_at', [$from->toDateString(), $to->toDateString()])->sum('amount');

        // Sessions by derma type
        $byType = DermaSession::select('session_type', DB::raw('count(*) as total'), DB::raw('sum(cost) as revenue'))
            ->whereBetween('completed_at', [$from, $to])
            ->groupBy('session_type')->orderByDesc('total')->get();

        // Top cosmetic procedures (count + revenue)
        $topProcedures = CosmeticSession::select('procedure_id', DB::raw('count(*) as total'), DB::raw('sum(cost) as revenue'))
            ->whereBetween('completed_at', [$from, $to])
            ->whereNotNull('procedure_id')
            ->with('procedure:id,name_ar,name_en,category')
            ->groupBy('procedure_id')->orderByDesc('total')->limit(8)->get()
            ->map(fn ($r) => [
                'name_ar' => $r->procedure?->name_ar,
                'name_en' => $r->procedure?->name_en,
                'category' => $r->procedure?->category,
                'total' => (int) $r->total,
                'revenue' => round((float) $r->revenue, 2),
            ]);

        // Active conditions mix
        $conditionMix = SkinCondition::select('category', DB::raw('count(*) as total'))
            ->where('status', 'active')->groupBy('category')->orderByDesc('total')->get();

        return Inertia::render('Admin/Reports/Derma', [
            'filters' => ['date_from' => $dateFrom, 'date_to' => $dateTo],
            'summary' => [
                'derma_sessions'    => (clone $dermaDone)->count(),
                'cosmetic_sessions' => (clone $cosmeticDone)->count(),
                'derma_revenue'     => round($dermaRevenue, 2),
                'cosmetic_revenue'  => round($cosmeticRevenue, 2),
                'package_sales'     => round($packageSales, 2),
                'total_revenue'     => round($dermaRevenue + $cosmeticRevenue + $packageSales, 2),
            ],
            'byType'        => $byType,
            'topProcedures' => $topProcedures,
            'conditionMix'  => $conditionMix,
            'plans' => [
                'active'    => DermaTreatmentPlan::active()->count(),
                'completed' => DermaTreatmentPlan::where('status', 'completed')->count(),
            ],
            'packages' => [
                'active'    => CosmeticPackagePurchase::where('status', 'active')->count(),
                'completed' => CosmeticPackagePurchase::where('status', 'completed')->count(),
            ],
        ]);
    }
}
