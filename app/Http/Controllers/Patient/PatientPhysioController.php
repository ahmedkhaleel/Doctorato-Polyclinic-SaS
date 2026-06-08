<?php

namespace App\Http\Controllers\Patient;

use App\Models\HepAdherenceLog;
use App\Models\PhysioExercisePrescription;
use App\Models\PhysioSession;
use App\Models\PhysioTreatmentPlan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The patient's own physiotherapy view: active plan progress, the prescribed
 * home-exercise-program (HEP), a 12-week adherence heatmap, and the ability to
 * self-log "done today". Patient-safe fields only; everything scoped to the
 * authenticated patient.
 */
class PatientPhysioController extends BasePatientController
{
    public function overview(Request $request): Response
    {
        $patientId = $this->patientId($request);

        $plan = PhysioTreatmentPlan::where('patient_id', $patientId)
            ->whereIn('status', ['planned', 'in_progress'])
            ->with('doctor:id,name_ar,name_en')
            ->latest('start_date')
            ->first();

        $planData = $plan ? array_merge(
            $plan->only(['id', 'title_ar', 'title_en', 'frequency', 'estimated_sessions', 'completed_sessions', 'status', 'start_date']),
            ['progress_percentage' => $plan->progress_percentage, 'sessions_remaining' => $plan->sessions_remaining, 'doctor' => $plan->doctor]
        ) : null;

        $prescriptions = PhysioExercisePrescription::where('patient_id', $patientId)
            ->where('status', 'active')
            ->with('exercise:id,name_ar,name_en,region,instructions,media_path,default_hold_sec')
            ->latest('prescribed_at')
            ->get()
            ->map(fn ($rx) => [
                'id' => $rx->id,
                'sets' => $rx->sets,
                'reps' => $rx->reps,
                'hold_sec' => $rx->hold_sec,
                'frequency' => $rx->frequency,
                'resistance' => $rx->resistance,
                'notes' => $rx->notes,
                'exercise' => $rx->exercise,
                'done_today' => HepAdherenceLog::where('prescription_id', $rx->id)
                    ->where('log_date', now()->toDateString())
                    ->where('done', true)
                    ->exists(),
            ]);

        // 12-week adherence heatmap: completed-log count per day.
        $since = now()->subDays(84)->toDateString();
        $adherence = HepAdherenceLog::where('patient_id', $patientId)
            ->where('done', true)
            ->where('log_date', '>=', $since)
            ->selectRaw('log_date, COUNT(*) as c')
            ->groupBy('log_date')
            ->pluck('c', 'log_date')
            ->map(fn ($c, $d) => ['date' => $d, 'count' => (int) $c])
            ->values();

        $recentSessions = PhysioSession::where('patient_id', $patientId)
            ->latest('session_date')
            ->limit(8)
            ->get(['id', 'session_number', 'session_date', 'pain_before', 'pain_after', 'attended']);

        $packages = \App\Models\PhysioPackagePurchase::where('patient_id', $patientId)
            ->where('status', 'active')
            ->with('package:id,name_ar,name_en')
            ->latest('purchased_at')->get()
            ->map(fn ($p) => [
                'name_ar' => $p->package?->name_ar,
                'name_en' => $p->package?->name_en,
                'total_sessions' => (int) $p->total_sessions,
                'sessions_used' => (int) $p->sessions_used,
                'sessions_remaining' => $p->sessions_remaining,
                'expires_at' => optional($p->expires_at)->toDateString(),
            ]);

        return Inertia::render('Patient/Physiotherapy/Overview', [
            'plan' => $planData,
            'prescriptions' => $prescriptions,
            'adherence' => $adherence,
            'recentSessions' => $recentSessions,
            'packages' => $packages,
        ]);
    }

    /** Self-log a prescribed exercise as done (or undone) for a given day. */
    public function logAdherence(Request $request): RedirectResponse
    {
        $patientId = $this->patientId($request);

        $data = $request->validate([
            'prescription_id' => 'required|integer|exists:physio_exercise_prescriptions,id',
            'log_date' => 'nullable|date',
            'done' => 'boolean',
            'pain_after' => 'nullable|integer|min:0|max:10',
            'note' => 'nullable|string|max:255',
        ]);

        // Ownership: the prescription must belong to this patient.
        $rx = PhysioExercisePrescription::where('id', $data['prescription_id'])
            ->where('patient_id', $patientId)
            ->firstOrFail();

        $date = $data['log_date'] ?? now()->toDateString();

        HepAdherenceLog::updateOrCreate(
            ['patient_id' => $patientId, 'prescription_id' => $rx->id, 'log_date' => $date],
            ['done' => $request->boolean('done', true), 'pain_after' => $data['pain_after'] ?? null, 'note' => $data['note'] ?? null]
        );

        return back()->with('success', app()->getLocale() === 'ar' ? 'تم تسجيل التمرين.' : 'Exercise logged.');
    }
}
