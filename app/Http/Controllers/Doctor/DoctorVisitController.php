<?php

namespace App\Http\Controllers\Doctor;

use App\Models\Visit;
use App\Services\AuditLogger;
use App\Services\VisitWorkflowService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DoctorVisitController extends BaseDoctorController
{
    public function __construct(
        protected VisitWorkflowService $workflowService,
    ) {}

    public function index(Request $request): Response
    {
        $doctorId = $this->doctorId($request);

        $query = Visit::with(['patient:id,full_name,file_number,phone', 'service:id,name_en'])
            ->where('doctor_id', $doctorId);

        // Validate filter inputs
        $filters = $request->validate([
            'search' => 'nullable|string|max:100',
            'status' => 'nullable|string|in:waiting,in_progress,completed,cancelled',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date',
            'module' => 'nullable|string|max:50',
        ]);

        if ($module = $filters['module'] ?? null) {
            $query->where('module', $module);
        }

        if ($search = $filters['search'] ?? null) {
            $query->whereHas('patient', function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('file_number', 'like', "%{$search}%");
            });
        }

        if ($status = $filters['status'] ?? null) {
            $query->where('status', $status);
        }

        if ($dateFrom = $filters['date_from'] ?? null) {
            $query->whereDate('visit_date', '>=', $dateFrom);
        }

        if ($dateTo = $filters['date_to'] ?? null) {
            $query->whereDate('visit_date', '<=', $dateTo);
        }

        $visits = $query->latest('visit_date')->paginate(15)->withQueryString();

        return Inertia::render('Doctor/Visits/Index', [
            'visits' => $visits,
            'filters' => $request->only(['search', 'status', 'date_from', 'date_to', 'module']),
        ]);
    }

    public function show(Request $request, Visit $visit): Response
    {
        $this->authorizeDoctor($request, $visit);

        $visit->load([
            'patient.activeInsurance.company:id,name_ar,name_en',
            'service',
            'prescriptions.items',
            'prescriptions.doctor',
            'invoice.payments.paymentMethod',
            'photos',
            'dentalTreatments',
        ]);

        $extra = [];

        // Load dental data for dental visits — use patient relationship to avoid N+1
        if ($visit->module === 'dental' && $visit->patient_id) {
            $patient = $visit->patient;
            $extra['dentalChart'] = $patient->dentalCharts()
                ->orderBy('tooth_number')
                ->get()
                ->keyBy('tooth_number');
            $extra['dentalXrays'] = $patient->dentalXrays()
                ->latest('taken_date')
                ->limit(10)
                ->get();
            $extra['dentalConditions'] = \App\Models\DentalChart::CONDITIONS;
            $extra['dentalSurfaces'] = \App\Models\DentalChart::SURFACES;
            $extra['allTeeth'] = \App\Models\DentalChart::ALL_TEETH;
            $extra['treatmentTypes'] = \App\Models\DentalTreatment::TYPES;

            // Dental risk flags for medical alerts
            $patient = $visit->patient;
            if ($patient) {
                $extra['dentalRiskFlags'] = $patient->getDentalRiskFlags();
                $extra['dentalMedicalInfo'] = $patient->only([
                    'has_dental_anxiety', 'dental_anxiety_level',
                    'is_pregnant', 'is_breastfeeding',
                    'has_bleeding_disorder', 'takes_blood_thinners', 'blood_thinner_name',
                    'has_heart_condition', 'has_diabetes', 'diabetes_type',
                    'has_hepatitis', 'hepatitis_type', 'has_hiv',
                    'latex_allergy', 'anesthesia_complications',
                    'is_smoker', 'smoking_frequency',
                    'allergies', 'chronic_conditions', 'current_medications', 'blood_type',
                ]);
            }
        }

        // ── Patient Vitals ──────────────────────────────────
        if ($visit->patient_id) {
            $latestVitals = $visit->patient->vitals()
                ->orderByDesc('recorded_at')
                ->first();
            $extra['latestVitals'] = $latestVitals;
            $extra['vitalsAlerts'] = $latestVitals ? $latestVitals->getAlerts() : [];

            // Active insurance — already eager-loaded with patient above
            $extra['activeInsurance'] = $visit->patient->activeInsurance;
        }

        return Inertia::render('Doctor/Visits/Show', array_merge([
            'visit' => $visit,
        ], $extra));
    }

    public function start(Request $request, Visit $visit): RedirectResponse
    {
        $this->authorizeDoctor($request, $visit);

        $this->workflowService->start($visit);

        AuditLogger::log('started', $visit);

        return redirect()->back()->with('success', $this->msg('Visit started.', 'تم بدء الزيارة.'));
    }

    public function complete(Request $request, Visit $visit): RedirectResponse
    {
        $this->authorizeDoctor($request, $visit);

        $results = $this->workflowService->complete($visit);

        AuditLogger::log('completed', $visit);

        $message = app()->getLocale() === 'ar' ? 'تم إكمال الزيارة.' : 'Visit completed.';
        if ($results['invoice']) {
            $message .= app()->getLocale() === 'ar'
                ? ' فاتورة رقم #'.$results['invoice']->invoice_number.' تم إنشاؤها.'
                : ' Invoice #'.$results['invoice']->invoice_number.' generated.';
        }

        return redirect()->back()->with('success', $message);
    }

    public function cancel(Request $request, Visit $visit): RedirectResponse
    {
        $this->authorizeDoctor($request, $visit);

        if (! in_array($visit->status, ['waiting', 'in_progress'])) {
            return redirect()->back()->with('error', $this->msg('Only waiting or in-progress visits can be cancelled.', 'يمكن إلغاء الزيارات في حالة الانتظار أو قيد التنفيذ فقط.'));
        }

        $visit->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
        ]);

        AuditLogger::log('cancelled', $visit);

        return redirect()->back()->with('success', $this->msg('Visit cancelled.', 'تم إلغاء الزيارة.'));
    }

    public function updateDiagnosis(Request $request, Visit $visit): RedirectResponse
    {
        $this->authorizeDoctor($request, $visit);

        $data = $request->validate([
            'diagnosis' => 'nullable|string|max:5000',
            'doctor_notes' => 'nullable|string|max:5000',
        ]);

        $visit->update($data);

        AuditLogger::log('updated_diagnosis', $visit);

        return redirect()->back()->with('success', $this->msg('Diagnosis updated.', 'تم تحديث التشخيص.'));
    }

    public function uploadPhoto(Request $request, Visit $visit): RedirectResponse
    {
        $this->authorizeDoctor($request, $visit);

        $request->validate([
            'photo' => 'required|image|mimes:jpg,jpeg,png,gif,webp|max:10240',
            'caption' => 'nullable|string|max:255',
            'type' => 'nullable|in:before,after,during',
        ]);

        $file = $request->file('photo');
        // Sanitize filename to prevent path traversal
        $safeName = preg_replace('/[^\w\s\-\.]/', '_', basename($file->getClientOriginalName()));
        $path = $file->store('visit-photos', 'public');

        $photo = $visit->photos()->create([
            'photo_path' => $path,
            'caption' => $request->input('caption'),
            'photo_type' => $request->input('type', 'during'),
        ]);

        AuditLogger::log('photo_uploaded', $visit, [
            'photo_id' => $photo->id,
            'photo_type' => $request->input('type', 'during'),
        ]);

        return redirect()->back()->with('success', $this->msg('Photo uploaded.', 'تم رفع الصورة.'));
    }
}
