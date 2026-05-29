<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DermaPhoto;
use App\Models\DermaSession;
use App\Models\Patient;
use App\Models\SkinCondition;
use App\Models\Visit;
use App\Services\ModuleManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class DermaController extends Controller
{
    public function index(Request $request)
    {
        // Count every patient with a derma footprint — visit, skin condition,
        // or session (sessions can exist without a visit).
        $patientIds = Visit::where('module', 'derma')->distinct()->pluck('patient_id')
            ->merge(SkinCondition::distinct()->pluck('patient_id'))
            ->merge(DermaSession::distinct()->pluck('patient_id'))
            ->filter()->unique()->values();
        $totalPatients = $patientIds->count();
        $totalVisits = Visit::where('module', 'derma')->count();
        $thisMonthVisits = Visit::where('module', 'derma')
            ->whereMonth('visit_date', now()->month)
            ->whereYear('visit_date', now()->year)
            ->count();

        $activeConditions = SkinCondition::where('status', 'active')->count();
        $totalSessions = DermaSession::count();
        $sessionsThisMonth = DermaSession::whereMonth('created_at', now()->month)->count();
        $totalPhotos = DermaPhoto::count();

        $monthlyTrend = [];
        for ($i = 11; $i >= 0; $i--) {
            $d = now()->subMonths($i);
            $monthlyTrend[] = [
                'month' => $d->format('M Y'),
                'monthShort' => $d->format('M'),
                'count' => Visit::where('module', 'derma')
                    ->whereMonth('visit_date', $d->month)
                    ->whereYear('visit_date', $d->year)
                    ->count(),
            ];
        }

        $topConditions = SkinCondition::select('category', DB::raw('count(*) as total'))
            ->groupBy('category')->orderByDesc('total')->limit(8)->get();

        $sessionTypes = DermaSession::select('session_type', DB::raw('count(*) as total'))
            ->groupBy('session_type')->orderByDesc('total')->get();

        $recentPatients = Patient::whereIn('id', $patientIds)
            ->latest('updated_at')->limit(10)->get(['id', 'full_name', 'phone', 'file_number', 'updated_at']);

        return Inertia::render('Admin/Derma/Dashboard', [
            'stats' => [
                'totalPatients' => $totalPatients,
                'totalVisits' => $totalVisits,
                'thisMonthVisits' => $thisMonthVisits,
                'activeConditions' => $activeConditions,
                'totalSessions' => $totalSessions,
                'sessionsThisMonth' => $sessionsThisMonth,
                'totalPhotos' => $totalPhotos,
            ],
            'monthlyTrend' => $monthlyTrend,
            'topConditions' => $topConditions,
            'sessionTypes' => $sessionTypes,
            'recentPatients' => $recentPatients,
        ]);
    }

    public function settings()
    {
        return Inertia::render('Admin/Derma/Settings', [
            'settings' => [
                'consultation_fee' => ModuleManager::getSetting('derma', 'consultation_fee', '0'),
                'followup_fee' => ModuleManager::getSetting('derma', 'followup_fee', '0'),
                'name_ar' => ModuleManager::getSetting('derma', 'name_ar', 'الجلدية'),
                'name_en' => ModuleManager::getSetting('derma', 'name_en', 'Dermatology'),
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
            if ($v !== null) ModuleManager::setSetting('derma', $k, (string) $v);
        }
        return back()->with('success', 'تم حفظ الإعدادات');
    }
}
