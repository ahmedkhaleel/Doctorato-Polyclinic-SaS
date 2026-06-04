<?php

namespace App\Http\Controllers\Doctor;

use App\Models\NeuropsychEncounter;
use App\Models\Patient;
use App\Models\ScaleResult;
use App\Services\AuditLogger;
use App\Services\NeuroPsych\ScaleEngine;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * NP2 — doctor records a measurement-based-care scale (PHQ-9, GAD-7, HIT-6, …)
 * for a patient. Score/severity/flag are computed by ScaleEngine via
 * ScaleResult::build. The scale must belong to the active module's track.
 */
class NeuropsychScaleController extends BaseDoctorController
{
    private function module(Request $request): string
    {
        $m = (string) $request->route('npModule');

        return in_array($m, NeuropsychEncounter::MODULES, true) ? $m : 'psychiatry';
    }

    public function store(Request $request): RedirectResponse
    {
        $module = $this->module($request);

        $data = $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'scale_key' => 'required|string',
            'answers' => 'required|array|min:1',
            'neuropsych_encounter_id' => 'nullable|exists:neuropsych_encounters,id',
        ]);

        // The scale must exist AND belong to this module's track.
        $def = ScaleEngine::definition($data['scale_key']);
        if (! $def || $def['module'] !== $module) {
            throw ValidationException::withMessages([
                'scale_key' => $this->msg('This scale is not available for this module.', 'هذا المقياس غير متاح لهذه الوحدة.'),
            ]);
        }

        $result = ScaleResult::build(
            (int) $data['patient_id'],
            $data['scale_key'],
            $data['answers'],
            'doctor',
            $data['neuropsych_encounter_id'] ?? null,
        );
        $result->save();

        AuditLogger::log('created', $result);

        return back()->with('success', $this->msg('Scale recorded', 'تم تسجيل المقياس'));
    }

    /** JSON: longitudinal results for a patient + scale (for trend charts). */
    public function trend(Request $request, Patient $patient)
    {
        $key = (string) $request->query('scale_key');

        $results = ScaleResult::where('patient_id', $patient->id)
            ->where('scale_key', $key)
            ->orderBy('taken_at')
            ->get(['score', 'severity', 'taken_at']);

        return response()->json(['scale_key' => $key, 'results' => $results]);
    }
}
