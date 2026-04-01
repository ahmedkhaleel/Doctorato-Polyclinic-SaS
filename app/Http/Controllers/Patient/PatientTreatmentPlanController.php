<?php

namespace App\Http\Controllers\Patient;

use App\Models\TreatmentPlan;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PatientTreatmentPlanController extends BasePatientController
{
    public function index(Request $request): Response
    {
        $plans = TreatmentPlan::where('patient_id', $this->patientId($request))
            ->with('doctor:id,name_en,name_ar')
            ->withCount('steps')
            ->latest('created_at')
            ->paginate(15);

        return Inertia::render('Patient/TreatmentPlans/Index', [
            'plans' => $plans,
        ]);
    }

    public function show(Request $request, string $locale, TreatmentPlan $treatmentPlan): Response
    {
        $this->authorizePatient($request, $treatmentPlan);

        $treatmentPlan->load([
            'doctor:id,name_en,name_ar,specialization_en,specialization_ar',
            'steps' => fn ($q) => $q->orderBy('step_number'),
        ]);

        return Inertia::render('Patient/TreatmentPlans/Show', [
            'plan' => $treatmentPlan,
        ]);
    }
}
