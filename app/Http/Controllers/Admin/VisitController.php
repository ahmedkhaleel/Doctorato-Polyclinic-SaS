<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Doctor;
use App\Models\Visit;
use App\Services\AuditLogger;
use App\Services\VisitWorkflowService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class VisitController extends Controller
{
    public function __construct(
        protected VisitWorkflowService $workflowService,
    ) {}

    public function index(Request $request): Response
    {
        $query = Visit::with(['patient', 'doctor', 'service', 'booking']);

        if ($module = $request->input('module')) {
            $query->where('module', $module);
        }

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('patient', function ($pq) use ($search) {
                    $pq->where('full_name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                })->orWhereHas('doctor', function ($dq) use ($search) {
                    $dq->where('name_ar', 'like', "%{$search}%")
                        ->orWhere('name_en', 'like', "%{$search}%");
                });
            });
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($visitType = $request->input('visit_type')) {
            $query->where('visit_type', $visitType);
        }

        if ($dateFrom = $request->input('date_from')) {
            $query->whereDate('visit_date', '>=', $dateFrom);
        }

        if ($dateTo = $request->input('date_to')) {
            $query->whereDate('visit_date', '<=', $dateTo);
        }

        $visits = $query->latest('visit_date')->paginate(15)->withQueryString();

        return Inertia::render('Admin/Visits/Index', [
            'visits' => $visits,
            'filters' => $request->only(['search', 'status', 'visit_type', 'date_from', 'date_to', 'module']),
        ]);
    }

    public function todayQueue(Request $request): Response
    {
        $visits = Visit::with(['patient', 'service', 'dentalTreatments:id,visit_id,treatment_type,tooth_number'])
            ->today()
            ->orderBy('created_at')
            ->get()
            ->groupBy('doctor_id');

        $doctors = Doctor::active()->get();

        return Inertia::render('Admin/Visits/TodayQueue', [
            'visitsByDoctor' => $visits,
            'doctors' => $doctors,
        ]);
    }

    public function show(Visit $visit): Response
    {
        $visit->load([
            'patient',
            'doctor',
            'service',
            'receptionist',
            'booking',
            'bookingAppointment',
            'prescriptions.items',
            'prescriptions.doctor',
            'invoice.payments.paymentMethod',
            'invoice.items',
            'photos',
            'supplyTransactions.supply',
            'dentalTreatments',
        ]);

        $extra = [];

        // Load dental data for dental visits
        if ($visit->module === 'dental' && $visit->patient_id) {
            $extra['dentalChart'] = \App\Models\DentalChart::where('patient_id', $visit->patient_id)
                ->orderBy('tooth_number')->get()->keyBy('tooth_number');
            $extra['dentalXrays'] = \App\Models\DentalXray::where('patient_id', $visit->patient_id)
                ->latest('taken_date')->limit(10)->get();
            $extra['dentalConditions'] = \App\Models\DentalChart::CONDITIONS;
            $extra['allTeeth'] = \App\Models\DentalChart::ALL_TEETH;
            $extra['treatmentTypes'] = \App\Models\DentalTreatment::TYPES;
            $extra['dentalPlans'] = \App\Models\DentalTreatmentPlan::where('patient_id', $visit->patient_id)
                ->whereIn('status', ['draft', 'approved', 'in_progress'])
                ->with('doctor:id,name_ar,name_en')
                ->withCount('treatments')
                ->get();
        }

        return Inertia::render('Admin/Visits/Show', array_merge([
            'visit' => $visit,
        ], $extra));
    }

    public function start(Visit $visit): RedirectResponse
    {
        $this->workflowService->start($visit);

        AuditLogger::log('started', $visit);

        return redirect()->back()->with('success', 'Visit started.');
    }

    public function complete(Visit $visit): RedirectResponse
    {
        $results = $this->workflowService->complete($visit);

        AuditLogger::log('completed', $visit);

        $message = 'Visit completed.';
        if ($results['invoice']) {
            $message .= ' Invoice #' . $results['invoice']->invoice_number . ' generated.';
        }

        return redirect()->back()->with('success', $message);
    }

    public function updateDiagnosis(Request $request, Visit $visit): RedirectResponse
    {
        $data = $request->validate([
            'diagnosis' => 'nullable|string',
            'doctor_notes' => 'nullable|string',
        ]);

        $visit->update($data);

        AuditLogger::log('updated_diagnosis', $visit);

        return redirect()->back()->with('success', 'Diagnosis updated successfully.');
    }

    public function uploadPhoto(Request $request, Visit $visit): RedirectResponse
    {
        $request->validate([
            'photo' => 'required|image|mimes:jpg,jpeg,png,gif,webp|max:5120',
            'caption' => 'nullable|string|max:255',
            'photo_type' => 'nullable|string|in:before,after,during',
        ]);

        $path = $request->file('photo')->store('visit-photos/' . $visit->id, 'public');

        $visit->photos()->create([
            'photo_path' => $path,
            'caption' => $request->input('caption'),
            'photo_type' => $request->input('photo_type', 'during'),
            'taken_at' => now(),
        ]);

        return redirect()->back()->with('success', 'Photo uploaded successfully.');
    }

    public function updateDetails(Request $request, Visit $visit): RedirectResponse
    {
        $data = $request->validate([
            'visit_date' => 'required|date',
        ]);

        $visit->update($data);

        AuditLogger::log('updated_details', $visit);

        return redirect()->back()->with('success', 'Visit date updated successfully.');
    }

    public function cancel(Visit $visit): RedirectResponse
    {
        $this->workflowService->cancel($visit);

        AuditLogger::log('cancelled', $visit);

        return redirect()->back()->with('success', 'Visit cancelled.');
    }
}
