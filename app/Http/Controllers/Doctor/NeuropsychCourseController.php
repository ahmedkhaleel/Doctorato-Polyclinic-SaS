<?php

namespace App\Http\Controllers\Doctor;

use App\Models\NeuropsychEncounter;
use App\Models\Patient;
use App\Models\TreatmentCourse;
use App\Services\AuditLogger;
use App\Services\NeuroPsychBillingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * NP6 — ECT/rTMS/ketamine treatment courses delivered as session series. A
 * session cannot be added to a consent-required course until consent is signed
 * (gate); completed sessions are billed.
 */
class NeuropsychCourseController extends BaseDoctorController
{
    public function __construct(private NeuroPsychBillingService $billing) {}

    private function module(Request $request): string
    {
        $m = (string) $request->route('npModule');

        return in_array($m, NeuropsychEncounter::MODULES, true) ? $m : 'psychiatry';
    }

    public function index(Request $request): Response
    {
        $module = $this->module($request);

        return Inertia::render('Doctor/Neuropsych/Courses', [
            'module' => $module,
            'courses' => TreatmentCourse::where('module', $module)
                ->where('doctor_id', $this->doctorId($request))
                ->with(['patient:id,full_name', 'sessions'])
                ->latest()->paginate(15)->withQueryString(),
            'patients' => Patient::active()->orderBy('full_name')->limit(500)->get(['id', 'full_name']),
            'courseTypes' => TreatmentCourse::TYPES,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $module = $this->module($request);
        $data = $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'type' => 'required|in:'.implode(',', TreatmentCourse::TYPES),
            'planned_sessions' => 'required|integer|min:1|max:60',
            'session_fee' => 'nullable|numeric|min:0',
            'consent_required' => 'nullable|boolean',
            'notes' => 'nullable|string',
        ]);

        $course = TreatmentCourse::create(array_merge($data, [
            'module' => $module,
            'doctor_id' => $this->doctorId($request),
            'session_fee' => $data['session_fee'] ?? 0,
            'consent_required' => (bool) ($data['consent_required'] ?? true),
        ]));

        AuditLogger::log('created', $course);

        return back()->with('success', $this->msg('Course created', 'تم إنشاء الدورة'));
    }

    public function signConsent(Request $request, TreatmentCourse $treatmentCourse): RedirectResponse
    {
        $this->authorizeDoctor($request, $treatmentCourse);
        $treatmentCourse->update([
            'consent_signed_at' => now(),
            'consent_signed_by' => $request->input('signed_by', 'patient'),
        ]);

        return back()->with('success', $this->msg('Consent recorded', 'تم توثيق الموافقة'));
    }

    public function addSession(Request $request, TreatmentCourse $treatmentCourse): RedirectResponse
    {
        $this->authorizeDoctor($request, $treatmentCourse);

        if (! $treatmentCourse->consentOk()) {
            throw ValidationException::withMessages([
                'consent' => $this->msg('Signed consent is required before performing a session.', 'يلزم توثيق موافقة موقّعة قبل تنفيذ جلسة.'),
            ]);
        }

        $data = $request->validate([
            'performed_at' => 'required|date',
            'parameters' => 'nullable|array',
            'cost' => 'nullable|numeric|min:0',
            'completed_at' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);

        $next = ((int) $treatmentCourse->sessions()->max('session_number')) + 1;

        $session = $treatmentCourse->sessions()->create([
            'patient_id' => $treatmentCourse->patient_id,
            'session_number' => $next,
            'performed_at' => $data['performed_at'],
            'parameters' => $data['parameters'] ?? null,
            'cost' => $data['cost'] ?? $treatmentCourse->session_fee,
            'completed_at' => $data['completed_at'] ?? null,
            'notes' => $data['notes'] ?? null,
        ]);

        if ($session->completed_at) {
            $this->billing->billCourseSession($session->fresh(), $treatmentCourse->module);
        }

        return back()->with('success', $this->msg('Session added', 'تمت إضافة الجلسة'));
    }

    public function destroy(Request $request, TreatmentCourse $treatmentCourse): RedirectResponse
    {
        $this->authorizeDoctor($request, $treatmentCourse);
        $treatmentCourse->delete();

        return back()->with('success', $this->msg('Deleted', 'تم الحذف'));
    }
}
