<?php

namespace App\Http\Controllers\Doctor;

use App\Models\ControlledPrescription;
use App\Models\MedicalDataAccessLog;
use App\Models\NeuropsychEncounter;
use App\Models\Patient;
use App\Services\AuditLogger;
use App\Services\ControlledRx\ControlledRxManager;
use App\Services\ControlledRx\ControlledRxService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * NP-Future-1 (layer B) — controlled-substance e-prescribing. SENSITIVE: gated
 * by permission:{module}.view_sensitive and audited. Workflow:
 * draft → sign (MFA) → submit (gateway) → dispensed.
 */
class NeuropsychControlledRxController extends BaseDoctorController
{
    public function __construct(
        private ControlledRxService $rx,
        private ControlledRxManager $gateways,
    ) {}

    private function module(Request $request): string
    {
        $m = (string) $request->route('npModule');

        return in_array($m, NeuropsychEncounter::MODULES, true) ? $m : 'psychiatry';
    }

    public function index(Request $request): Response
    {
        $module = $this->module($request);

        $prescriptions = ControlledPrescription::where('module', $module)
            ->where('doctor_id', $this->doctorId($request))
            ->with('patient:id,full_name')->latest()->paginate(20)->withQueryString();

        // Audit: viewing controlled-substance prescriptions is sensitive access.
        foreach (collect($prescriptions->items())->pluck('patient_id')->filter()->unique()->all() as $pid) {
            MedicalDataAccessLog::record(
                (int) $pid,
                MedicalDataAccessLog::ACCESS_VIEW,
                MedicalDataAccessLog::CATEGORY_SENSITIVE,
                null,
                "Viewed {$module} controlled-substance prescriptions",
            );
        }

        return Inertia::render('Doctor/Neuropsych/ControlledRx', [
            'module' => $module,
            'prescriptions' => $prescriptions,
            'patients' => Patient::active()->orderBy('full_name')->limit(500)->get(['id', 'full_name']),
            'gateway' => $this->gateways->active()->code(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $module = $this->module($request);
        $data = $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'drug' => 'required|string|max:255',
            'schedule' => 'nullable|string|max:12',
            'dose' => 'nullable|string|max:100',
            'quantity' => 'required|string|max:100',
            'notes' => 'nullable|string',
        ]);

        $rx = ControlledPrescription::create(array_merge($data, [
            'module' => $module,
            'doctor_id' => $this->doctorId($request),
            'status' => 'draft',
        ]));
        AuditLogger::log('created', $rx);

        return back()->with('success', $this->msg('Controlled prescription drafted', 'تم إنشاء مسودة روشتة خاضعة'));
    }

    public function sign(Request $request, ControlledPrescription $controlledPrescription): RedirectResponse
    {
        $this->authorizeDoctor($request, $controlledPrescription);
        $request->validate(['mfa_token' => 'nullable|string']);

        // throws a validation error if MFA missing/invalid or not a draft
        $this->rx->sign($controlledPrescription, $this->doctorId($request), $request->input('mfa_token'));
        AuditLogger::log('signed', $controlledPrescription);

        return back()->with('success', $this->msg('Prescription signed', 'تم توقيع الروشتة'));
    }

    public function submit(Request $request, ControlledPrescription $controlledPrescription): RedirectResponse
    {
        $this->authorizeDoctor($request, $controlledPrescription);
        $result = $this->rx->submit($controlledPrescription); // throws on gateway failure
        AuditLogger::log('submitted', $result);

        return back()->with('success', $this->msg('Prescription authorized', 'تم اعتماد الروشتة'));
    }

    public function dispense(Request $request, ControlledPrescription $controlledPrescription): RedirectResponse
    {
        $this->authorizeDoctor($request, $controlledPrescription);
        $this->rx->markDispensed($controlledPrescription);

        return back()->with('success', $this->msg('Marked dispensed', 'تم وضع علامة مصروف'));
    }

    public function destroy(Request $request, ControlledPrescription $controlledPrescription): RedirectResponse
    {
        $this->authorizeDoctor($request, $controlledPrescription);
        $controlledPrescription->delete();

        return back()->with('success', $this->msg('Deleted', 'تم الحذف'));
    }
}
