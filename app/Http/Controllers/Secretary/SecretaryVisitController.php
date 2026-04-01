<?php

namespace App\Http\Controllers\Secretary;

use App\Models\Visit;
use App\Services\AuditLogger;
use App\Services\VisitWorkflowService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SecretaryVisitController extends BaseSecretaryController
{
    public function __construct(
        protected VisitWorkflowService $workflowService,
    ) {}

    public function index(Request $request): Response
    {
        $query = Visit::with(['patient', 'doctor', 'service', 'booking']);

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

        if ($module = $request->input('module')) {
            $query->where('module', $module);
        }

        $visits = $query->latest('visit_date')->paginate(15)->withQueryString();

        return Inertia::render('Secretary/Visits/Index', [
            'visits' => $visits,
            'filters' => $request->only(['search', 'status', 'visit_type', 'date_from', 'date_to', 'module']),
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
            'dentalTreatments',
        ]);

        $extra = [];

        if ($visit->module === 'dental' && $visit->patient_id) {
            $extra['dentalChart'] = \App\Models\DentalChart::where('patient_id', $visit->patient_id)
                ->orderBy('tooth_number')->get()->keyBy('tooth_number');
            $extra['dentalConditions'] = \App\Models\DentalChart::CONDITIONS;
            $extra['allTeeth'] = \App\Models\DentalChart::ALL_TEETH;
            $extra['treatmentTypes'] = \App\Models\DentalTreatment::TYPES;
        }

        return Inertia::render('Secretary/Visits/Show', array_merge([
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

    public function cancel(Visit $visit): RedirectResponse
    {
        $this->workflowService->cancel($visit);

        AuditLogger::log('cancelled', $visit);

        return redirect()->back()->with('success', 'Visit cancelled.');
    }
}
