<?php

namespace App\Http\Controllers\Patient;

use App\Models\PatientSatisfaction;
use App\Models\Visit;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Patient self-service feedback. Until now, satisfaction surveys only
 * reached patients via tokenized SMS links — patients without phones
 * or who missed the SMS had no way to leave feedback. This controller
 * lets logged-in patients leave feedback for any of their completed
 * visits directly from the portal.
 *
 * Reuses the existing PatientSatisfaction model + admin reports — no
 * schema changes needed. Source is set to 'patient_portal' so admins
 * can distinguish portal submissions from SMS submissions.
 */
class PatientFeedbackController extends BasePatientController
{
    public function index(Request $request): Response
    {
        $patient = $this->patient($request);

        // Visits eligible for feedback: completed AND patient hasn't
        // submitted feedback for them yet.
        $alreadyReviewed = PatientSatisfaction::where('patient_id', $patient->id)
            ->whereNotNull('overall_rating')
            ->pluck('visit_id')
            ->filter()
            ->all();

        $pendingVisits = Visit::where('patient_id', $patient->id)
            ->where('status', 'completed')
            ->whereNotIn('id', $alreadyReviewed)
            ->with(['doctor:id,name_ar,name_en,photo', 'service:id,name_ar,name_en'])
            ->latest('visit_date')
            ->limit(10)
            ->get(['id', 'visit_date', 'doctor_id', 'service_id', 'module']);

        $recentReviews = PatientSatisfaction::where('patient_id', $patient->id)
            ->whereNotNull('overall_rating')
            ->with(['doctor:id,name_ar,name_en', 'visit:id,visit_date'])
            ->latest()
            ->limit(5)
            ->get(['id', 'overall_rating', 'comments', 'created_at', 'doctor_id', 'visit_id']);

        return Inertia::render('Patient/Feedback/Index', [
            'pendingVisits' => $pendingVisits,
            'recentReviews' => $recentReviews,
        ]);
    }

    public function create(Request $request, string $locale, Visit $visit): Response
    {
        $patient = $this->patient($request);
        $this->ensureOwnership($patient, $visit);

        $existing = PatientSatisfaction::where('patient_id', $patient->id)
            ->where('visit_id', $visit->id)
            ->whereNotNull('overall_rating')
            ->exists();

        if ($existing) {
            return Inertia::render('Patient/Feedback/AlreadySubmitted', [
                'visit' => $visit->load(['doctor:id,name_ar,name_en', 'service:id,name_ar,name_en']),
            ]);
        }

        return Inertia::render('Patient/Feedback/Create', [
            'visit' => $visit->load(['doctor:id,name_ar,name_en', 'service:id,name_ar,name_en']),
            'improvementAreas' => PatientSatisfaction::IMPROVEMENT_AREAS,
        ]);
    }

    public function store(Request $request, string $locale, Visit $visit): RedirectResponse
    {
        $patient = $this->patient($request);
        $this->ensureOwnership($patient, $visit);

        $data = $request->validate([
            'overall_rating'        => 'required|integer|min:1|max:5',
            'doctor_rating'         => 'nullable|integer|min:1|max:5',
            'staff_rating'          => 'nullable|integer|min:1|max:5',
            'cleanliness_rating'    => 'nullable|integer|min:1|max:5',
            'waiting_time_rating'   => 'nullable|integer|min:1|max:5',
            'communication_rating'  => 'nullable|integer|min:1|max:5',
            'comments'              => 'nullable|string|max:2000',
            'would_recommend'       => 'nullable|boolean',
            'improvement_areas'     => 'nullable|array',
            'improvement_areas.*'   => 'string|max:50',
            'nps_score'             => 'nullable|integer|min:0|max:10',
        ]);

        // updateOrCreate guards against double-submission (e.g. clicking
        // the submit button twice from a slow connection).
        DB::transaction(function () use ($patient, $visit, $data) {
            PatientSatisfaction::updateOrCreate(
                [
                    'patient_id' => $patient->id,
                    'visit_id'   => $visit->id,
                ],
                array_merge($data, [
                    'doctor_id'   => $visit->doctor_id,
                    'booking_id'  => $visit->booking_id ?? null,
                    'source'      => 'patient_portal',
                    'token'       => Str::random(40), // schema-required even when not used as link
                    'is_anonymous' => false,
                ])
            );

            // Bust the admin-side stat caches so the new row shows up
            // in dashboards immediately.
            Cache::forget('satisfaction_stats');
            Cache::forget('satisfaction_improvement_areas');
            Cache::forget('satisfaction_doctor_rankings');
        });

        AuditLogger::log('patient_feedback_submitted', $visit, [
            'patient_id'     => $patient->id,
            'overall_rating' => $data['overall_rating'],
        ]);

        return redirect()
            ->route('patient.feedback.index', ['locale' => $locale])
            ->with('success', 'Thank you for your feedback!');
    }

    /**
     * Reject any cross-patient access via URL guessing.
     */
    private function ensureOwnership($patient, Visit $visit): void
    {
        if ($visit->patient_id !== $patient->id) abort(403);
        if ($visit->status !== 'completed') abort(403, 'Only completed visits can be reviewed.');
    }
}
