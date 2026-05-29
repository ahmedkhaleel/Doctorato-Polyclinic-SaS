<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\CosmeticProcedure;
use App\Models\CosmeticSession;
use App\Models\Patient;
use App\Services\ModuleManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class CosmeticController extends Controller
{
    public function index(Request $request)
    {
        // Cosmetic is a sub-feature of the `derma` module — there is no
        // `module = 'cosmetic'` on visits. The authoritative source of
        // "cosmetic patients" is who actually has cosmetic sessions.
        // "Visits" here means cosmetic-consultation bookings (demand),
        // while "sessions" below are the delivered procedures.
        $patientIds = CosmeticSession::distinct()->pluck('patient_id');
        $totalPatients = $patientIds->count();

        $cosmeticBookings = Booking::where('booking_type', 'cosmetic_consultation');
        $totalVisits = (clone $cosmeticBookings)->count();
        $thisMonthVisits = (clone $cosmeticBookings)
            ->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->count();

        $totalSessions = CosmeticSession::count();
        $thisMonthSessions = CosmeticSession::whereMonth('created_at', now()->month)->count();
        $totalRevenue = CosmeticSession::sum('cost');
        $monthRevenue = CosmeticSession::whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->sum('cost');
        $activeProcedures = CosmeticProcedure::where('is_active', true)->count();

        $monthlyTrend = [];
        for ($i = 11; $i >= 0; $i--) {
            $d = now()->subMonths($i);
            $monthlyTrend[] = [
                'month' => $d->format('M Y'),
                'monthShort' => $d->format('M'),
                'count' => CosmeticSession::whereMonth('created_at', $d->month)->whereYear('created_at', $d->year)->count(),
                'revenue' => (float) CosmeticSession::whereMonth('created_at', $d->month)->whereYear('created_at', $d->year)->sum('cost'),
            ];
        }

        $topProcedures = CosmeticSession::select('procedure_id', DB::raw('count(*) as total'), DB::raw('sum(cost) as revenue'))
            ->with('procedure:id,name_ar,name_en,category')
            ->groupBy('procedure_id')->orderByDesc('total')->limit(8)->get();

        $categoryBreakdown = CosmeticProcedure::select('category', DB::raw('count(*) as total'))
            ->groupBy('category')->get();

        $recentPatients = Patient::whereIn('id', $patientIds)
            ->latest('updated_at')->limit(10)->get(['id', 'full_name', 'phone', 'file_number', 'updated_at']);

        return Inertia::render('Admin/Cosmetic/Dashboard', [
            'stats' => [
                'totalPatients' => $totalPatients,
                'totalVisits' => $totalVisits,
                'thisMonthVisits' => $thisMonthVisits,
                'totalSessions' => $totalSessions,
                'thisMonthSessions' => $thisMonthSessions,
                'totalRevenue' => (float) $totalRevenue,
                'monthRevenue' => (float) $monthRevenue,
                'activeProcedures' => $activeProcedures,
            ],
            'monthlyTrend' => $monthlyTrend,
            'topProcedures' => $topProcedures,
            'categoryBreakdown' => $categoryBreakdown,
            'recentPatients' => $recentPatients,
        ]);
    }

    public function settings()
    {
        return Inertia::render('Admin/Cosmetic/Settings', [
            'settings' => [
                'consultation_fee' => ModuleManager::getSetting('cosmetic', 'consultation_fee', '0'),
                'followup_fee' => ModuleManager::getSetting('cosmetic', 'followup_fee', '0'),
                'name_ar' => ModuleManager::getSetting('cosmetic', 'name_ar', 'التجميل'),
                'name_en' => ModuleManager::getSetting('cosmetic', 'name_en', 'Cosmetic Medicine'),
            ],
        ]);
    }

    public function updateSettings(Request $request)
    {
        $data = $request->validate([
            'consultation_fee' => 'nullable|numeric|min:0',
            'followup_fee' => 'nullable|numeric|min:0',
            'name_ar' => 'nullable|string|max:100',
            'name_en' => 'nullable|string|max:100',
        ]);
        foreach ($data as $k => $v) {
            if ($v !== null) ModuleManager::setSetting('cosmetic', $k, (string) $v);
        }
        return back()->with('success', 'تم حفظ الإعدادات');
    }
}
