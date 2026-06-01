<?php

namespace App\Http\Controllers\Patient;

use App\Models\Visit;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PatientVisitController extends BasePatientController
{
    public function index(Request $request): Response
    {
        $filters = $request->validate([
            'module' => 'nullable|string|in:derma,dental,pediatric',
        ]);

        $query = Visit::where('patient_id', $this->patientId($request))
            ->with(['doctor:id,name_en,name_ar', 'service:id,name_en,name_ar']);

        if ($module = $filters['module'] ?? null) {
            $query->where('module', $module);
        }

        $visits = $query->latest('visit_date')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Patient/Visits/Index', [
            'visits' => $visits,
            'filters' => [
                'module' => $request->input('module'),
            ],
        ]);
    }

    public function show(Request $request, string $locale, Visit $visit): Response
    {
        $this->authorizePatient($request, $visit);

        $relations = [
            'doctor:id,name_en,name_ar,specialization_en,specialization_ar',
            'service:id,name_en,name_ar',
            'photos',
            'prescription.medications',
            'invoice.items',
            'invoice.payments.paymentMethod:id,name_en,name_ar',
        ];

        if ($visit->module === 'dental') {
            $relations['dentalTreatments'] = fn ($q) => $q->with('labOrder:id,dental_treatment_id,status,item_type');
        }

        $visit->load($relations);

        // Show "Rate this visit" CTA only for completed visits the patient
        // hasn't already reviewed.
        $canReview = $visit->status === 'completed'
            && ! \App\Models\PatientSatisfaction::where('patient_id', $visit->patient_id)
                ->where('visit_id', $visit->id)
                ->whereNotNull('overall_rating')
                ->exists();

        return Inertia::render('Patient/Visits/Show', [
            'visit' => $visit,
            'canReview' => $canReview,
        ]);
    }
}
