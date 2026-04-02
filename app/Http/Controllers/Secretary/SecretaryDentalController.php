<?php

namespace App\Http\Controllers\Secretary;

use App\Models\DentalChart;
use App\Models\DentalLabOrder;
use App\Models\DentalScheduledFollowup;
use App\Models\DentalTreatment;
use App\Models\DentalTreatmentPlan;
use App\Models\DentalXray;
use App\Models\Doctor;
use App\Models\Patient;
use App\Services\DentalInvoiceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SecretaryDentalController extends BaseSecretaryController
{
    /**
     * Treatment Plans list (read-only for scheduling & patient info).
     */
    public function treatmentPlans(Request $request): Response
    {
        $query = DentalTreatmentPlan::with([
            'patient:id,full_name,file_number,phone',
            'doctor:id,name_ar,name_en',
        ]);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('doctor_id')) {
            $query->where('doctor_id', $request->doctor_id);
        }
        if ($request->filled('search')) {
            $query->whereHas('patient', function ($q) use ($request) {
                $q->where('full_name', 'like', "%{$request->search}%")
                  ->orWhere('file_number', 'like', "%{$request->search}%");
            });
        }

        $plans = $query->withCount('treatments')
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Secretary/Dental/TreatmentPlans/Index', [
            'plans' => $plans,
            'filters' => $request->only(['status', 'doctor_id', 'search']),
            'doctors' => Doctor::dental()->active()->select('id', 'name_ar', 'name_en')->get(),
        ]);
    }

    /**
     * Show a single treatment plan.
     */
    public function treatmentPlanShow(DentalTreatmentPlan $treatmentPlan): Response
    {
        $treatmentPlan->load([
            'patient:id,full_name,file_number,phone,date_of_birth,gender',
            'doctor:id,name_ar,name_en',
            'treatments' => function ($q) {
                $q->with('doctor:id,name_ar,name_en', 'labOrder')->orderBy('tooth_number');
            },
        ]);

        return Inertia::render('Secretary/Dental/TreatmentPlans/Show', [
            'plan' => $treatmentPlan,
            'treatmentTypes' => DentalTreatment::TYPES,
        ]);
    }

    /**
     * Lab Orders list (secretary tracks orders for procurement/delivery).
     */
    public function labOrders(Request $request): Response
    {
        $query = DentalLabOrder::with([
            'patient:id,full_name,file_number',
            'doctor:id,name_ar,name_en',
        ]);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('search')) {
            $query->whereHas('patient', function ($q) use ($request) {
                $q->where('full_name', 'like', "%{$request->search}%")
                  ->orWhere('file_number', 'like', "%{$request->search}%");
            });
        }

        $orders = $query->latest()->paginate(20)->withQueryString();

        $stats = [
            'pending' => DentalLabOrder::where('status', 'pending')->count(),
            'overdue' => DentalLabOrder::where('status', 'pending')
                ->where('expected_date', '<', now()->toDateString())->count(),
            'in_lab' => DentalLabOrder::where('status', 'in_progress')->count(),
        ];

        return Inertia::render('Secretary/Dental/LabOrders/Index', [
            'orders' => $orders,
            'filters' => $request->only(['status', 'search']),
            'stats' => $stats,
        ]);
    }

    /**
     * Mark lab order as received.
     */
    public function markLabOrderReceived(DentalLabOrder $labOrder)
    {
        $labOrder->update([
            'status' => 'delivered',
            'delivered_date' => now()->toDateString(),
        ]);

        // Auto-add lab charge to invoice
        app(DentalInvoiceService::class)->addLabOrderToInvoice($labOrder);

        return redirect()->back()->with('success', $this->msg('Lab order marked as received.', 'تم تسجيل طلب المختبر كمستلم.'));
    }

    /**
     * Patient dental chart (read-only for secretary).
     */
    public function patientChart(Patient $patient): Response
    {
        $chart = DentalChart::where('patient_id', $patient->id)
            ->orderBy('tooth_number')
            ->get()
            ->keyBy('tooth_number');

        return Inertia::render('Secretary/Dental/PatientChart', [
            'patient' => $patient->only(['id', 'full_name', 'file_number', 'phone']),
            'chart' => $chart,
            'conditions' => DentalChart::CONDITIONS,
            'allTeeth' => DentalChart::ALL_TEETH,
        ]);
    }

    /**
     * Pending follow-ups list for secretary to schedule.
     */
    public function followups(Request $request): Response
    {
        $query = DentalScheduledFollowup::with([
            'patient:id,full_name,file_number,phone',
            'doctor:id,name_ar,name_en',
            'treatment:id,treatment_type,tooth_number',
        ]);

        // Filter by status
        $statusFilter = $request->input('status', 'pending');
        if ($statusFilter === 'pending') {
            $query->where('status', DentalScheduledFollowup::STATUS_PENDING)->whereNull('booking_id');
        } elseif ($statusFilter === 'overdue') {
            $query->where('status', DentalScheduledFollowup::STATUS_PENDING)
                ->whereNull('booking_id')
                ->where('scheduled_date', '<', today());
        } elseif ($statusFilter === 'booked') {
            $query->where('status', DentalScheduledFollowup::STATUS_BOOKING_CREATED);
        } elseif ($statusFilter !== 'all') {
            $query->where('status', $statusFilter);
        }

        // Search by patient
        if ($request->filled('search')) {
            $query->whereHas('patient', function ($q) use ($request) {
                $q->where('full_name', 'like', "%{$request->search}%")
                  ->orWhere('file_number', 'like', "%{$request->search}%")
                  ->orWhere('phone', 'like', "%{$request->search}%");
            });
        }

        $followups = $query->orderBy('scheduled_date')->paginate(20)->withQueryString();

        $stats = [
            'pending' => DentalScheduledFollowup::where('status', DentalScheduledFollowup::STATUS_PENDING)
                ->whereNull('booking_id')->count(),
            'overdue' => DentalScheduledFollowup::where('status', DentalScheduledFollowup::STATUS_PENDING)
                ->whereNull('booking_id')
                ->where('scheduled_date', '<', today())->count(),
            'booked' => DentalScheduledFollowup::where('status', DentalScheduledFollowup::STATUS_BOOKING_CREATED)->count(),
            'upcoming_week' => DentalScheduledFollowup::where('status', DentalScheduledFollowup::STATUS_PENDING)
                ->whereNull('booking_id')
                ->whereBetween('scheduled_date', [today(), today()->addDays(7)])->count(),
        ];

        return Inertia::render('Secretary/Dental/Followups/Index', [
            'followups' => $followups,
            'filters' => $request->only(['status', 'search']),
            'stats' => $stats,
        ]);
    }

    /**
     * Get medical risk flags for a patient (AJAX for check-in alerts).
     */
    public function patientRiskAlert(Patient $patient): JsonResponse
    {
        $riskFlags = $patient->getDentalRiskFlags();
        $hasHighRisk = !empty(array_filter($riskFlags, fn ($f) => $f['severity'] === 'high'));

        return response()->json([
            'patient_id' => $patient->id,
            'patient_name' => $patient->full_name,
            'risk_flags' => $riskFlags,
            'has_high_risk' => $hasHighRisk,
            'has_dental_risk' => $patient->hasDentalRiskFlags(),
        ]);
    }
}
