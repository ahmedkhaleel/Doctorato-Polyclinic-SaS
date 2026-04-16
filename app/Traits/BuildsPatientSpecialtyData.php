<?php

namespace App\Traits;

use App\Models\Patient;
use App\Models\Prescription;

/**
 * Shared helper for building unified patient specialty data bundles
 * (derma, dental, pediatric) used across Admin / Doctor / Secretary controllers.
 */
trait BuildsPatientSpecialtyData
{
    /**
     * Build the derma data bundle (visits, photos, prescriptions, diagnoses, stats).
     * Returns null if the patient has no derma records.
     */
    protected function buildDermaData(Patient $patient, ?int $doctorId = null): ?array
    {
        if (! $patient->hasDermaRecords()) {
            return null;
        }

        $visitsQuery = $patient->visits()
            ->where('module', 'derma')
            ->with([
                'doctor:id,name_ar,name_en',
                'service:id,name_ar,name_en',
                'photos',
            ])
            ->latest('visit_date')
            ->take(20);

        if ($doctorId !== null) {
            $visitsQuery->where('doctor_id', $doctorId);
        }

        $dermaVisits = $visitsQuery->get();

        // Extract all photos from derma visits
        $dermaPhotos = $dermaVisits->flatMap(fn ($v) => $v->photos ?? collect())
            ->sortByDesc('created_at')
            ->values();

        // Derma prescriptions linked to derma visits
        $dermaVisitIds = $dermaVisits->pluck('id');
        $dermaPrescriptions = Prescription::whereIn('visit_id', $dermaVisitIds)
            ->with(['doctor:id,name_ar,name_en', 'items'])
            ->latest()
            ->take(10)
            ->get();

        // Diagnoses summary (unique)
        $diagnoses = $dermaVisits->pluck('diagnosis')->filter()->unique()->take(10)->values();

        $baseCountQuery = $patient->visits()->where('module', 'derma');
        if ($doctorId !== null) {
            $baseCountQuery->where('doctor_id', $doctorId);
        }

        $completedQuery = (clone $baseCountQuery)->where('status', 'completed');

        return [
            'visits' => $dermaVisits,
            'photos' => $dermaPhotos,
            'prescriptions' => $dermaPrescriptions,
            'diagnoses' => $diagnoses,
            'stats' => [
                'total_visits' => $baseCountQuery->count(),
                'completed_visits' => $completedQuery->count(),
                'total_photos' => $dermaPhotos->count(),
                'before_after_sets' => $dermaPhotos->where('photo_type', 'before')->count(),
            ],
        ];
    }
}
