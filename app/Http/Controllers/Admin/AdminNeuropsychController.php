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
