<?php

namespace App\Http\Controllers\Patient;

use App\Models\Booking;
use App\Models\HeadacheDiaryEntry;
use App\Models\ScaleResult;
use App\Models\SeizureDiaryEntry;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Patient-facing landing for the psychiatry & neurology modules — the
 * patient's own journey at a glance (upcoming appointments, recent
 * questionnaire scores, diary activity) with quick links into scales/diaries.
 * Only the patient's OWN data is shown.
 */
class PatientNeuropsychController extends BasePatientController
{
    public function overview(Request $request): Response
    {
        $patientId = $this->patientId($request);
        $since = now()->subDays(30);

        // Patient's own recent scale results (last 5).
        $recentScales = ScaleResult::where('patient_id', $patientId)
            ->latest('taken_at')
            ->limit(5)
            ->get(['scale_key', 'score', 'severity', 'taken_at'])
            ->map(fn ($r) => [
                'scale_key' => strtoupper($r->scale_key),
                'score' => $r->score,
                'severity' => $r->severity,
                'taken_at' => $r->taken_at?->toDateString(),
            ]);

        // Upcoming neuropsych appointments (patient's own bookings).
        $upcoming = Booking::where('patient_id', $patientId)
            ->whereIn('module', ['psychiatry', 'neurology'])
            ->whereIn('status', ['unconfirmed', 'confirmed'])
            ->whereNotNull('preferred_date')
            ->whereDate('preferred_date', '>=', now()->toDateString())
            ->with('doctor:id,name_ar,name_en')
            ->orderBy('preferred_date')
            ->limit(5)
            ->get()
            ->map(fn ($b) => [
                'id' => $b->id,
                'module' => $b->module,
                'date' => optional($b->preferred_date)->toDateString(),
                'time' => $b->preferred_time,
                'status' => $b->status,
                'doctor' => $b->doctor ? ['name_ar' => $b->doctor->name_ar, 'name_en' => $b->doctor->name_en] : null,
            ]);

        return Inertia::render('Patient/Neuropsych/Overview', [
            'stats' => [
                'scales_total' => ScaleResult::where('patient_id', $patientId)->count(),
                'seizure_30d' => SeizureDiaryEntry::where('patient_id', $patientId)->where('occurred_at', '>=', $since)->count(),
                'headache_30d' => HeadacheDiaryEntry::where('patient_id', $patientId)->where('date', '>=', $since->toDateString())->count(),
            ],
            'recentScales' => $recentScales,
            'upcoming' => $upcoming,
        ]);
    }
}
