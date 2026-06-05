<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Doctor;
use App\Models\MedicationMonitoring;
use App\Models\NeuropsychEncounter;
use App\Models\NeuropsychProfile;
use App\Models\Patient;
use App\Models\RiskAssessment;
use App\Models\ScaleResult;
use App\Services\ModuleManager;
use App\Services\NeuroPsych\RiskAssessmentService;
use App\Services\NeuroPsych\ScaleEngine;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * NP7 — admin dashboard + settings for the psychiatry & neurology modules. One
 * controller serves both; the module comes from the route default `npModule`.
 */
class AdminNeuropsychController extends Controller
{
    public function __construct(private RiskAssessmentService $risk) {}

    private function module(Request $request): string
    {
        $m = (string) $request->route('npModule');

        return in_array($m, NeuropsychEncounter::MODULES, true) ? $m : 'psychiatry';
    }

    private function canSeeSensitive(Request $request, string $module): bool
    {
        return (bool) $request->user()?->role?->hasPermission("{$module}.view_sensitive");
    }

    /**
     * N1 — Cases: every patient with at least one encounter in this module,
     * with encounter count, last visit, last diagnosis, and (for sensitive
     * users) an active-risk flag. Read-only oversight; drills into the patient.
     */
    public function cases(Request $request): Response
    {
        $module = $this->module($request);
        $search = trim((string) $request->input('search'));

        // Patients who have any encounter in this module (branch scope applies).
        $patientIds = NeuropsychEncounter::where('module', $module)->distinct()->pluck('patient_id');

        $cases = Patient::whereIn('id', $patientIds)
            ->when($search !== '', fn ($q) => $q->where(fn ($w) => $w->where('full_name', 'like', "%{$search}%")->orWhere('phone', 'like', "%{$search}%")->orWhere('file_number', 'like', "%{$search}%")))
            ->orderBy('full_name')
            ->paginate(20)->withQueryString();

        $pageIds = collect($cases->items())->pluck('id')->all();

        // Batch the per-patient aggregates (avoid N+1).
        $agg = NeuropsychEncounter::where('module', $module)
            ->whereIn('patient_id', $pageIds)
            ->selectRaw('patient_id, COUNT(*) as c, MAX(encounter_date) as last_date')
            ->groupBy('patient_id')->get()->keyBy('patient_id');

        $profiles = NeuropsychProfile::whereIn('patient_id', $pageIds)->get()->keyBy('patient_id');

        $sensitive = $this->canSeeSensitive($request, $module);

        $cases->getCollection()->transform(function (Patient $p) use ($agg, $profiles, $sensitive) {
            $a = $agg->get($p->id);
            $risk = $sensitive ? $this->risk->activeRiskFor((int) $p->id) : null;

            return [
                'id' => $p->id,
                'full_name' => $p->full_name,
                'phone' => $p->phone,
                'file_number' => $p->file_number,
                'encounters_count' => (int) ($a->c ?? 0),
                'last_encounter' => $a?->last_date,
                'chief_complaint' => $profiles->get($p->id)?->chief_complaint,
                'risk_level' => $risk['level'] ?? null,   // null when not sensitive or no active risk
            ];
        });

        return Inertia::render('Admin/Neuropsych/Cases', [
            'module' => $module,
            'cases' => $cases,
            'filters' => ['search' => $search],
            'canSeeSensitive' => $sensitive,
        ]);
    }

    /**
     * N2 — Encounters log: clinic-wide encounters for billing/oversight.
     * Filters by doctor and date range; surfaces billed vs un-billed.
     */
    public function encounters(Request $request): Response
    {
        $module = $this->module($request);
        $doctorId = $request->integer('doctor_id') ?: null;
        $from = $request->input('from');
        $to = $request->input('to');

        $encounters = NeuropsychEncounter::where('module', $module)
            ->when($doctorId, fn ($q) => $q->where('doctor_id', $doctorId))
            ->when($from, fn ($q) => $q->whereDate('encounter_date', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('encounter_date', '<=', $to))
            ->with(['patient:id,full_name,phone', 'doctor:id,name_ar,name_en', 'diagnoses'])
            ->latest('encounter_date')
            ->paginate(25)->withQueryString()
            ->through(fn (NeuropsychEncounter $e) => [
                'id' => $e->id,
                'encounter_date' => $e->encounter_date?->toDateString(),
                'patient' => ['id' => $e->patient?->id, 'full_name' => $e->patient?->full_name, 'phone' => $e->patient?->phone],
                'doctor' => ['name_ar' => $e->doctor?->name_ar, 'name_en' => $e->doctor?->name_en],
                'note_format' => $e->note_format,
                'primary_dx' => optional($e->diagnoses->firstWhere('is_primary', true) ?? $e->diagnoses->first())->label,
                'dx_count' => $e->diagnoses->count(),
                'cost' => (float) $e->cost,
                'completed' => $e->completed_at !== null,
                'billed' => $e->invoice_id !== null,
            ]);

        // Doctors that actually have encounters in this module (filter dropdown).
        $docIds = NeuropsychEncounter::where('module', $module)->distinct()->pluck('doctor_id')->filter()->all();
        $doctors = Doctor::whereIn('id', $docIds)->get(['id', 'name_ar', 'name_en']);

        return Inertia::render('Admin/Neuropsych/Encounters', [
            'module' => $module,
            'encounters' => $encounters,
            'doctors' => $doctors,
            'filters' => ['doctor_id' => $doctorId, 'from' => $from, 'to' => $to],
        ]);
    }

    /**
     * N3 — Outcomes (measurement-based care): per-scale severity distribution,
     * flagged count, and a simple improvement rate (first vs latest score per
     * patient) for the scales that belong to this module.
     */
    public function outcomes(Request $request): Response
    {
        $module = $this->module($request);
        $defs = ScaleEngine::forModule($module);                 // keyed by scale_key
        $keys = array_keys($defs);

        $summary = [];
        foreach ($defs as $key => $def) {
            $rows = ScaleResult::where('scale_key', $key)->get(['patient_id', 'score', 'severity', 'flag', 'taken_at']);

            // Improvement: patients with >=2 results — latest score lower than first
            // counts as improved (lower = better on PHQ-9 / GAD-7 / HIT-6).
            $improved = 0;
            $comparable = 0;
            foreach ($rows->groupBy('patient_id') as $g) {
                if ($g->count() < 2) {
                    continue;
                }
                $comparable++;
                $sorted = $g->sortBy('taken_at')->values();
                if ((int) $sorted->last()->score < (int) $sorted->first()->score) {
                    $improved++;
                }
            }

            $summary[] = [
                'key' => $key,
                'name_en' => $def['name_en'],
                'name_ar' => $def['name_ar'],
                'total' => $rows->count(),
                'flagged' => $rows->where('flag', true)->count(),
                'by_severity' => $rows->groupBy('severity')->map->count(),
                'improved_pct' => $comparable > 0 ? (int) round($improved / $comparable * 100) : null,
            ];
        }

        $recent = ScaleResult::whereIn('scale_key', $keys)
            ->with('patient:id,full_name,phone')
            ->latest('taken_at')
            ->paginate(25)->withQueryString()
            ->through(fn (ScaleResult $r) => [
                'id' => $r->id,
                'scale_key' => strtoupper($r->scale_key),
                'patient' => ['id' => $r->patient?->id, 'full_name' => $r->patient?->full_name, 'phone' => $r->patient?->phone],
                'score' => $r->score,
                'severity' => $r->severity,
                'flag' => (bool) $r->flag,
                'taken_at' => $r->taken_at?->toDateString(),
            ]);

        return Inertia::render('Admin/Neuropsych/Outcomes', [
            'module' => $module,
            'summary' => $summary,
            'recent' => $recent,
        ]);
    }

    public function dashboard(Request $request): Response
    {
        $module = $this->module($request);

        return Inertia::render('Admin/Neuropsych/Dashboard', [
            'module' => $module,
            'stats' => [
                'encounters_this_month' => NeuropsychEncounter::where('module', $module)
                    ->whereMonth('encounter_date', now()->month)->whereYear('encounter_date', now()->year)->count(),
                'active_high_risk' => RiskAssessment::where('is_active', true)->where('risk_level', 'high')->count(),
                'monitoring_due' => MedicationMonitoring::where('status', 'due')->whereDate('due_at', '<=', now())->count(),
            ],
        ]);
    }

    public function reports(Request $request): Response
    {
        $module = $this->module($request);

        // Encounters per month (last 6 months) + revenue from module-tagged invoices.
        $since = now()->startOfMonth()->subMonths(5);
        $byMonth = NeuropsychEncounter::where('module', $module)
            ->where('encounter_date', '>=', $since->toDateString())
            ->get(['encounter_date'])
            ->groupBy(fn ($e) => $e->encounter_date->format('Y-m'))
            ->map(fn ($g) => $g->count());

        $months = [];
        for ($i = 5; $i >= 0; $i--) {
            $key = now()->startOfMonth()->subMonths($i)->format('Y-m');
            $months[] = ['month' => $key, 'encounters' => (int) ($byMonth[$key] ?? 0)];
        }

        $revenue = \App\Models\Invoice::where('module', $module)
            ->where('invoice_date', '>=', $since->toDateString())
            ->sum('total');

        return Inertia::render('Admin/Neuropsych/Reports', [
            'module' => $module,
            'byMonth' => $months,
            'totalEncounters' => array_sum(array_column($months, 'encounters')),
            'revenue' => (float) $revenue,
            'completedCourses' => \App\Models\TreatmentCourse::where('module', $module)->where('status', 'completed')->count(),
        ]);
    }

    public function settings(Request $request): Response
    {
        $module = $this->module($request);

        return Inertia::render('Admin/Neuropsych/Settings', [
            'module' => $module,
            'settings' => ModuleManager::getSettings($module),
            'enabled' => ModuleManager::isEnabled($module),
        ]);
    }

    public function updateSettings(Request $request): RedirectResponse
    {
        $module = $this->module($request);
        $data = $request->validate([
            'enabled' => 'nullable|boolean',
            'name_ar' => 'nullable|string|max:100',
            'name_en' => 'nullable|string|max:100',
        ]);

        if (array_key_exists('enabled', $data)) {
            $data['enabled'] ? ModuleManager::enable($module) : ModuleManager::disable($module);
        }
        foreach (['name_ar', 'name_en'] as $k) {
            if (! empty($data[$k])) {
                ModuleManager::setSetting($module, $k, $data[$k]);
            }
        }

        return back()->with('success', app()->getLocale() === 'ar' ? 'تم حفظ الإعدادات' : 'Settings saved');
    }
}
