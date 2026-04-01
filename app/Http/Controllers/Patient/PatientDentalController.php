<?php

namespace App\Http\Controllers\Patient;

use App\Models\DentalChart;
use App\Models\DentalLabOrder;
use App\Models\DentalScheduledFollowup;
use App\Models\DentalTreatment;
use App\Models\DentalTreatmentPlan;
use App\Models\DentalXray;
use App\Models\Visit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class PatientDentalController extends BasePatientController
{
    /**
     * Dental overview — consolidated summary for the patient.
     */
    public function overview(Request $request): Response
    {
        $patientId = $this->patientId($request);

        // Treatment summary
        $treatmentSummary = DentalTreatment::where('patient_id', $patientId)
            ->selectRaw("
                COUNT(*) as total,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
                SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress,
                SUM(CASE WHEN status = 'planned' THEN 1 ELSE 0 END) as planned
            ")
            ->first();

        // Active treatment plans with progress
        $activePlans = DentalTreatmentPlan::where('patient_id', $patientId)
            ->whereIn('status', ['approved', 'in_progress'])
            ->with(['doctor:id,name_ar,name_en', 'consent:id,dental_treatment_plan_id,status'])
            ->withCount('treatments')
            ->latest()
            ->limit(5)
            ->get();

        // Upcoming followups
        $upcomingFollowups = DentalScheduledFollowup::where('patient_id', $patientId)
            ->whereIn('status', ['pending', 'sms_sent', 'booking_created'])
            ->with(['doctor:id,name_ar,name_en', 'treatment:id,treatment_type,tooth_number'])
            ->orderBy('scheduled_date')
            ->limit(5)
            ->get();

        // Pending lab orders
        $pendingLabOrders = DentalLabOrder::where('patient_id', $patientId)
            ->whereIn('status', ['ordered', 'in_production', 'ready', 'delivered'])
            ->with('doctor:id,name_ar,name_en')
            ->orderByRaw("FIELD(status, 'ready', 'delivered', 'in_production', 'ordered')")
            ->limit(5)
            ->get();

        // Pending consents needing signature
        $pendingConsents = DentalTreatmentPlan::where('patient_id', $patientId)
            ->whereHas('consent', fn ($q) => $q->where('status', 'pending'))
            ->with(['consent:id,dental_treatment_plan_id,status,expires_at', 'doctor:id,name_ar,name_en'])
            ->get();

        // Next appointment
        $nextAppointment = Visit::where('module', 'dental')
            ->where('patient_id', $patientId)
            ->where('visit_date', '>=', now()->toDateString())
            ->whereIn('status', ['scheduled', 'booked', 'confirmed'])
            ->with(['doctor:id,name_ar,name_en', 'service:id,name_ar,name_en'])
            ->orderBy('visit_date')
            ->orderBy('scheduled_time')
            ->first();

        // Recent X-rays (last 3)
        $recentXrays = DentalXray::where('patient_id', $patientId)
            ->with('doctor:id,name_ar,name_en')
            ->latest('taken_date')
            ->limit(3)
            ->get();

        // Dental chart summary (tooth conditions count)
        $chartSummary = DentalChart::where('patient_id', $patientId)
            ->select('condition', DB::raw('COUNT(*) as count'))
            ->groupBy('condition')
            ->get()
            ->pluck('count', 'condition')
            ->toArray();

        // Visit history count
        $visitCount = Visit::where('module', 'dental')
            ->where('patient_id', $patientId)
            ->count();

        return Inertia::render('Patient/Dental/Overview', [
            'treatmentSummary' => [
                'total' => (int) ($treatmentSummary->total ?? 0),
                'completed' => (int) ($treatmentSummary->completed ?? 0),
                'in_progress' => (int) ($treatmentSummary->in_progress ?? 0),
                'planned' => (int) ($treatmentSummary->planned ?? 0),
            ],
            'activePlans' => $activePlans,
            'upcomingFollowups' => $upcomingFollowups,
            'pendingLabOrders' => $pendingLabOrders,
            'pendingConsents' => $pendingConsents,
            'nextAppointment' => $nextAppointment,
            'recentXrays' => $recentXrays,
            'chartSummary' => $chartSummary,
            'visitCount' => $visitCount,
        ]);
    }

    public function chart(Request $request): Response
    {
        $patientId = $this->patientId($request);

        $chart = DentalChart::where('patient_id', $patientId)
            ->orderBy('tooth_number')
            ->get()
            ->keyBy('tooth_number');

        return Inertia::render('Patient/Dental/Chart', [
            'dentalChart' => $chart,
            'dentalConditions' => DentalChart::CONDITIONS,
            'allTeeth' => DentalChart::ALL_TEETH,
        ]);
    }

    public function treatments(Request $request): Response
    {
        $patientId = $this->patientId($request);

        $treatments = DentalTreatment::where('patient_id', $patientId)
            ->with([
                'doctor:id,name_ar,name_en',
                'visit:id,visit_date,status',
            ])
            ->latest('created_at')
            ->paginate(15);

        return Inertia::render('Patient/Dental/Treatments', [
            'treatments' => $treatments,
            'treatmentTypes' => DentalTreatment::TYPES,
        ]);
    }

    public function xrays(Request $request): Response
    {
        $patientId = $this->patientId($request);

        $xrays = DentalXray::where('patient_id', $patientId)
            ->with('doctor:id,name_ar,name_en')
            ->latest('taken_date')
            ->paginate(15);

        return Inertia::render('Patient/Dental/Xrays', [
            'xrays' => $xrays,
        ]);
    }

    public function treatmentPlans(Request $request): Response
    {
        $patientId = $this->patientId($request);

        $plans = DentalTreatmentPlan::where('patient_id', $patientId)
            ->with(['doctor:id,name_ar,name_en', 'consent'])
            ->withCount('treatments')
            ->latest('created_at')
            ->paginate(15);

        return Inertia::render('Patient/Dental/TreatmentPlans/Index', [
            'plans' => $plans,
        ]);
    }

    public function treatmentPlanShow(Request $request, string $locale, DentalTreatmentPlan $dentalTreatmentPlan): Response
    {
        $this->authorizePatient($request, $dentalTreatmentPlan);

        $dentalTreatmentPlan->load([
            'doctor:id,name_ar,name_en,specialization_ar,specialization_en',
            'treatments' => fn ($q) => $q->with('doctor:id,name_ar,name_en')->orderBy('created_at'),
            'consent',
        ]);

        return Inertia::render('Patient/Dental/TreatmentPlans/Show', [
            'plan' => $dentalTreatmentPlan,
        ]);
    }

    /**
     * Patient's dental lab orders with tracking status.
     */
    public function labOrders(Request $request): Response
    {
        $patientId = $this->patientId($request);

        $labOrders = DentalLabOrder::where('patient_id', $patientId)
            ->with(['doctor:id,name_ar,name_en'])
            ->latest('order_date')
            ->paginate(15);

        return Inertia::render('Patient/Dental/LabOrders', [
            'labOrders' => $labOrders,
        ]);
    }

    /**
     * Patient's scheduled follow-ups.
     */
    public function followups(Request $request): Response
    {
        $patientId = $this->patientId($request);

        $followups = DentalScheduledFollowup::where('patient_id', $patientId)
            ->with(['doctor:id,name_ar,name_en', 'treatment:id,treatment_type,tooth_number'])
            ->orderByRaw("FIELD(status, 'pending', 'sms_sent', 'booking_created', 'completed', 'cancelled')")
            ->orderBy('scheduled_date')
            ->paginate(15);

        return Inertia::render('Patient/Dental/Followups', [
            'followups' => $followups,
        ]);
    }
}
