<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Patient;
use App\Models\PatientVital;
use App\Services\AuditLogger;
use Illuminate\Http\Request;

class PatientVitalController extends Controller
{
    /**
     * Get vitals history for a patient.
     */
    public function index(Patient $patient, Request $request)
    {
        $vitals = $patient->vitals()
            ->with('recorder:id,name')
            ->orderByDesc('recorded_at')
            ->paginate(20);

        return response()->json($vitals);
    }

    /**
     * Get the latest vitals snapshot (used in visit/appointment views).
     */
    public function latest(Patient $patient)
    {
        $vital = $patient->vitals()
            ->with('recorder:id,name')
            ->orderByDesc('recorded_at')
            ->first();

        return response()->json([
            'vital' => $vital,
            'alerts' => $vital ? $vital->getAlerts() : [],
        ]);
    }

    /**
     * Record new vitals.
     */
    public function store(Patient $patient, Request $request)
    {
        $validated = $request->validate([
            'visit_id' => 'nullable|exists:visits,id',
            'blood_pressure_systolic' => 'nullable|numeric|min:50|max:300',
            'blood_pressure_diastolic' => 'nullable|numeric|min:30|max:200',
            'heart_rate' => 'nullable|numeric|min:30|max:250',
            'temperature' => 'nullable|numeric|min:34|max:43',
            'respiratory_rate' => 'nullable|numeric|min:5|max:60',
            'oxygen_saturation' => 'nullable|numeric|min:50|max:100',
            'weight' => 'nullable|numeric|min:1|max:500',
            'height' => 'nullable|numeric|min:30|max:300',
            'blood_sugar' => 'nullable|numeric|min:20|max:800',
            'blood_sugar_type' => 'nullable|in:fasting,random,post_meal',
            'pain_level' => 'nullable|integer|min:0|max:10',
            'pain_location' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:1000',
            'source' => 'nullable|in:manual,device,pre_visit',
        ]);

        $validated['patient_id'] = $patient->id;
        $validated['recorded_by'] = auth()->id();
        $validated['recorded_at'] = now();

        $vital = PatientVital::create($validated);

        AuditLogger::log('vitals_recorded', $vital, [
            'patient_id' => $patient->id,
            'visit_id' => $validated['visit_id'] ?? null,
        ]);

        return response()->json([
            'vital' => $vital->fresh(['recorder:id,name']),
            'alerts' => $vital->getAlerts(),
            'message' => 'Vitals recorded successfully',
        ]);
    }

    /**
     * Get vitals trend data for charts (last 10 readings).
     */
    public function trends(Patient $patient)
    {
        $vitals = $patient->vitals()
            ->orderByDesc('recorded_at')
            ->limit(10)
            ->get()
            ->reverse()
            ->values();

        return response()->json([
            'readings' => $vitals->map(fn ($v) => [
                'date' => $v->recorded_at->format('Y-m-d'),
                'bp_sys' => $v->blood_pressure_systolic,
                'bp_dia' => $v->blood_pressure_diastolic,
                'hr' => $v->heart_rate,
                'temp' => $v->temperature,
                'weight' => $v->weight,
                'bmi' => $v->bmi,
                'sugar' => $v->blood_sugar,
                'spo2' => $v->oxygen_saturation,
            ]),
        ]);
    }
}
