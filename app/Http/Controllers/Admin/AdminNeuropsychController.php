<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MedicationMonitoring;
use App\Models\NeuropsychEncounter;
use App\Models\RiskAssessment;
use App\Services\ModuleManager;
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
    private function module(Request $request): string
    {
        $m = (string) $request->route('npModule');

        return in_array($m, NeuropsychEncounter::MODULES, true) ? $m : 'psychiatry';
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
