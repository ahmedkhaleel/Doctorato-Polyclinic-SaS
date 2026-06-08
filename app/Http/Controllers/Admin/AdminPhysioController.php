<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Exercise;
use App\Models\Invoice;
use App\Models\Patient;
use App\Models\PhysioAssessment;
use App\Models\PhysioSession;
use App\Models\PhysioTreatmentPlan;
use App\Services\AuditLogger;
use App\Services\ModuleManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Admin oversight for physiotherapy: KPIs + revenue, the patient caseload, the
 * shared exercise catalog (CRUD), and the module pricing/commission settings.
 * Read-mostly management view; clinical capture stays in the doctor portal.
 */
class AdminPhysioController extends Controller
{
    public function dashboard(): Response
    {
        $revenueMonth = Invoice::where('module', 'physiotherapy')
            ->whereMonth('invoice_date', now()->month)
            ->whereYear('invoice_date', now()->year)
            ->sum('total');

        $statusCounts = PhysioTreatmentPlan::select('status', DB::raw('COUNT(*) as c'))
            ->groupBy('status')->pluck('c', 'status')->toArray();

        $recentSessions = PhysioSession::with('patient:id,full_name', 'doctor:id,name_ar,name_en')
            ->latest('session_date')->limit(10)
            ->get(['id', 'patient_id', 'doctor_id', 'session_date', 'session_number', 'pain_before', 'pain_after', 'cost']);

        return Inertia::render('Admin/Physiotherapy/Dashboard', [
            'stats' => [
                'active_plans' => PhysioTreatmentPlan::whereIn('status', ['planned', 'in_progress'])->count(),
                'sessions_this_month' => PhysioSession::whereMonth('session_date', now()->month)->whereYear('session_date', now()->year)->count(),
                'assessments_this_month' => PhysioAssessment::whereMonth('assessment_date', now()->month)->whereYear('assessment_date', now()->year)->count(),
                'revenue_this_month' => (float) $revenueMonth,
            ],
            'statusCounts' => $statusCounts,
            'recentSessions' => $recentSessions,
        ]);
    }

    public function patients(Request $request): Response
    {
        $search = trim((string) $request->input('search'));

        $ids = collect()
            ->merge(PhysioTreatmentPlan::pluck('patient_id'))
            ->merge(PhysioSession::pluck('patient_id'))
            ->merge(PhysioAssessment::pluck('patient_id'))
            ->unique()->values();

        $patients = Patient::whereIn('id', $ids)
            ->when($search, fn ($q) => $q->where(fn ($w) => $w
                ->where('full_name', 'like', "%{$search}%")
                ->orWhere('phone', 'like', "%{$search}%")
                ->orWhere('file_number', 'like', "%{$search}%")))
            ->orderBy('full_name')
            ->paginate(20)
            ->withQueryString()
            ->through(fn ($p) => [
                'id' => $p->id,
                'full_name' => $p->full_name,
                'phone' => $p->phone,
                'file_number' => $p->file_number,
                'active_plans' => PhysioTreatmentPlan::where('patient_id', $p->id)->whereIn('status', ['planned', 'in_progress'])->count(),
                'sessions' => PhysioSession::where('patient_id', $p->id)->count(),
            ]);

        return Inertia::render('Admin/Physiotherapy/Patients', [
            'patients' => $patients,
            'filters' => ['search' => $search],
        ]);
    }

    public function exercises(Request $request): Response
    {
        $region = $request->input('region');
        $exercises = Exercise::query()
            ->when($region, fn ($q) => $q->where('region', $region))
            ->orderBy('region')->orderBy('name_en')
            ->get(['id', 'name_ar', 'name_en', 'region', 'category', 'default_sets', 'default_reps', 'default_hold_sec', 'is_active', 'instructions']);

        return Inertia::render('Admin/Physiotherapy/Exercises', [
            'exercises' => $exercises,
            'regions' => Exercise::REGIONS,
            'filters' => ['region' => $region],
        ]);
    }

    public function storeExercise(Request $request): RedirectResponse
    {
        $data = $this->validateExercise($request);
        $ex = Exercise::create(array_merge($data, ['is_active' => $request->boolean('is_active', true)]));
        AuditLogger::log('created', $ex, null, 'Created exercise');

        return back()->with('success', 'Exercise added.');
    }

    public function updateExercise(Request $request, Exercise $exercise): RedirectResponse
    {
        $exercise->update($this->validateExercise($request));
        AuditLogger::log('updated', $exercise, null, 'Updated exercise');

        return back()->with('success', 'Exercise updated.');
    }

    public function toggleExercise(Exercise $exercise): RedirectResponse
    {
        $exercise->update(['is_active' => ! $exercise->is_active]);

        return back()->with('success', 'Exercise updated.');
    }

    public function packages(): Response
    {
        $packages = \App\Models\PhysioPackage::orderBy('total_sessions')
            ->get(['id', 'name_ar', 'name_en', 'total_sessions', 'price', 'validity_days', 'is_active'])
            ->map(fn ($p) => array_merge($p->toArray(), [
                'active_purchases' => \App\Models\PhysioPackagePurchase::where('package_id', $p->id)->where('status', 'active')->count(),
            ]));

        return Inertia::render('Admin/Physiotherapy/Packages', ['packages' => $packages]);
    }

    public function storePackage(Request $request): RedirectResponse
    {
        $p = \App\Models\PhysioPackage::create($this->validatePackage($request) + ['is_active' => $request->boolean('is_active', true)]);
        AuditLogger::log('created', $p, null, 'Created physio package');

        return back()->with('success', 'Package added.');
    }

    public function updatePackage(Request $request, \App\Models\PhysioPackage $package): RedirectResponse
    {
        $package->update($this->validatePackage($request));
        AuditLogger::log('updated', $package, null, 'Updated physio package');

        return back()->with('success', 'Package updated.');
    }

    public function togglePackage(\App\Models\PhysioPackage $package): RedirectResponse
    {
        $package->update(['is_active' => ! $package->is_active]);

        return back()->with('success', 'Package updated.');
    }

    private function validatePackage(Request $request): array
    {
        return $request->validate([
            'name_ar' => 'required|string|max:255',
            'name_en' => 'required|string|max:255',
            'total_sessions' => 'required|integer|min:1|max:200',
            'price' => 'required|numeric|min:0',
            'validity_days' => 'nullable|integer|min:0|max:1095',
        ]);
    }

    public function settings(): Response
    {
        return Inertia::render('Admin/Physiotherapy/Settings', [
            'settings' => DB::table('module_settings')
                ->where('module', 'physiotherapy')
                ->whereIn('group', ['pricing', 'commission', 'duration'])
                ->pluck('value', 'key')->toArray(),
        ]);
    }

    public function updateSettings(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'consultation_fee' => 'nullable|numeric|min:0',
            'followup_fee' => 'nullable|numeric|min:0',
            'session_fee' => 'nullable|numeric|min:0',
            'home_visit_surcharge' => 'nullable|numeric|min:0',
            'default_commission' => 'nullable|numeric|min:0|max:100',
            'consultation_commission' => 'nullable|numeric|min:0|max:100',
            'session_commission' => 'nullable|numeric|min:0|max:100',
            'consultation_duration' => 'nullable|integer|min:0',
            'session_duration' => 'nullable|integer|min:0',
        ]);

        $groups = [
            'consultation_fee' => 'pricing', 'followup_fee' => 'pricing', 'session_fee' => 'pricing', 'home_visit_surcharge' => 'pricing',
            'default_commission' => 'commission', 'consultation_commission' => 'commission', 'session_commission' => 'commission',
            'consultation_duration' => 'duration', 'session_duration' => 'duration',
        ];

        $now = now();
        foreach ($data as $key => $value) {
            if ($value === null) {
                continue;
            }
            DB::table('module_settings')->updateOrInsert(
                ['module' => 'physiotherapy', 'key' => $key],
                ['value' => (string) $value, 'group' => $groups[$key], 'type' => 'number', 'updated_at' => $now, 'created_at' => $now]
            );
        }

        ModuleManager::clearCache();

        return back()->with('success', 'Settings updated.');
    }

    private function validateExercise(Request $request): array
    {
        return $request->validate([
            'name_ar' => 'required|string|max:255',
            'name_en' => 'required|string|max:255',
            'region' => 'nullable|string|max:40',
            'category' => 'nullable|string|max:40',
            'instructions' => 'nullable|string|max:2000',
            'default_sets' => 'nullable|integer|min:0|max:50',
            'default_reps' => 'nullable|integer|min:0|max:200',
            'default_hold_sec' => 'nullable|integer|min:0|max:600',
        ]);
    }
}
