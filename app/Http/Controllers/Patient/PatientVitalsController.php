<?php

namespace App\Http\Controllers\Patient;

use App\Models\PatientVital;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Patient self-service vitals view. Read-only — vitals are recorded
 * by staff during visits, but the patient sees their own history and
 * trends here so they can track their progress (especially for
 * chronic conditions: hypertension, diabetes, weight management).
 */
class PatientVitalsController extends BasePatientController
{
    public function index(Request $request): Response
    {
        $patient = $this->patient($request);

        $vitals = PatientVital::where('patient_id', $patient->id)
            ->orderByDesc('recorded_at')
            ->limit(60) // last ~60 records, plenty for the trend charts
            ->get([
                'id', 'recorded_at', 'visit_id',
                'blood_pressure_systolic', 'blood_pressure_diastolic',
                'heart_rate', 'temperature', 'respiratory_rate', 'oxygen_saturation',
                'weight', 'height', 'bmi',
                'blood_sugar', 'blood_sugar_type',
                'pain_level', 'notes', 'source',
            ]);

        $latest = $vitals->first();

        // Series-friendly arrays for sparkline-style trend rendering
        // (oldest → newest so the chart reads left-to-right naturally).
        $series = $vitals->reverse()->values();

        return Inertia::render('Patient/Vitals/Index', [
            'latest' => $latest,
            'history' => $vitals->map(fn ($v) => [
                'id'                       => $v->id,
                'recorded_at'              => $v->recorded_at?->toDateTimeString(),
                'visit_id'                 => $v->visit_id,
                'bp_systolic'              => $v->blood_pressure_systolic,
                'bp_diastolic'             => $v->blood_pressure_diastolic,
                'heart_rate'               => $v->heart_rate,
                'temperature'              => $v->temperature,
                'respiratory_rate'         => $v->respiratory_rate,
                'oxygen_saturation'        => $v->oxygen_saturation,
                'weight'                   => $v->weight,
                'height'                   => $v->height,
                'bmi'                      => $v->bmi,
                'blood_sugar'              => $v->blood_sugar,
                'blood_sugar_type'         => $v->blood_sugar_type,
                'pain_level'               => $v->pain_level,
                'notes'                    => $v->notes,
            ]),
            'series' => [
                'labels' => $series->pluck('recorded_at')->map(fn ($d) => $d?->format('Y-m-d')),
                'weight' => $series->pluck('weight')->map(fn ($v) => $v !== null ? (float) $v : null),
                'bp'     => $series->map(fn ($v) => $v->blood_pressure_systolic !== null && $v->blood_pressure_diastolic !== null
                                ? [(float) $v->blood_pressure_systolic, (float) $v->blood_pressure_diastolic]
                                : null),
                'sugar'  => $series->pluck('blood_sugar')->map(fn ($v) => $v !== null ? (float) $v : null),
            ],
        ]);
    }
}
