<?php

namespace App\Http\Controllers\Patient;

use App\Models\ScaleResult;
use App\Services\ModuleManager;
use App\Services\NeuroPsych\ScaleEngine;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * NP2 — patient-portal measurement-based care. The patient fills validated
 * scales (PHQ-9, GAD-7, HIT-6, …) before/between visits; the result is stored
 * with entered_by=patient and flows into the clinician's longitudinal view.
 * Only scales from enabled psychiatry/neurology modules are offered.
 */
class PatientNeuropsychScaleController extends BasePatientController
{
    /** Scales offered to the patient = those from enabled NP modules. */
    private function availableScales(): array
    {
        $modules = array_values(array_filter(['psychiatry', 'neurology'], fn ($m) => ModuleManager::isEnabled($m)));

        $scales = [];
        foreach (ScaleEngine::registry() as $key => $def) {
            if (in_array($def['module'], $modules, true)) {
                $scales[] = [
                    'key' => $key,
                    'module' => $def['module'],
                    'name_en' => $def['name_en'],
                    'name_ar' => $def['name_ar'],
                    'items' => $def['items'],
                    'options' => $def['options'],
                ];
            }
        }

        return $scales;
    }

    public function index(Request $request, string $locale): Response
    {
        $patientId = $this->patientId($request);

        $recent = ScaleResult::where('patient_id', $patientId)
            ->orderByDesc('taken_at')
            ->limit(20)
            ->get(['scale_key', 'score', 'severity', 'taken_at', 'entered_by']);

        return Inertia::render('Patient/Neuropsych/Scales', [
            'scales' => $this->availableScales(),
            'recent' => $recent,
        ]);
    }

    public function store(Request $request, string $locale): RedirectResponse
    {
        $data = $request->validate([
            'scale_key' => 'required|string',
            'answers' => 'required|array|min:1',
        ]);

        // Must be an offered scale (exists + from an enabled NP module).
        $offered = collect($this->availableScales())->pluck('key')->all();
        if (! in_array($data['scale_key'], $offered, true)) {
            throw ValidationException::withMessages([
                'scale_key' => app()->getLocale() === 'ar' ? 'هذا المقياس غير متاح.' : 'This scale is not available.',
            ]);
        }

        ScaleResult::build($this->patientId($request), $data['scale_key'], $data['answers'], 'patient')->save();

        return back()->with('success', app()->getLocale() === 'ar' ? 'تم إرسال المقياس' : 'Scale submitted');
    }
}
