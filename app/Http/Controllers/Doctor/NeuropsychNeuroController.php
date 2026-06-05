<?php

namespace App\Http\Controllers\Doctor;

use App\Models\HeadacheDiaryEntry;
use App\Models\NeuroExam;
use App\Models\NeuroProcedure;
use App\Models\NeuropsychEncounter;
use App\Models\Patient;
use App\Models\SeizureDiaryEntry;
use App\Services\AuditLogger;
use App\Services\NeuroPsychBillingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * NP5 — neurology tools: procedures (EMG/LP/EEG/botox/nerve block, with billing
 * + inventory) and the seizure/headache diaries (clinician view + entry).
 * Only mounted under the neurology module.
 */
class NeuropsychNeuroController extends BaseDoctorController
{
    public function __construct(private NeuroPsychBillingService $billing) {}

    public function index(Request $request): Response
    {
        $doctorId = $this->doctorId($request);

        return Inertia::render('Doctor/Neuropsych/Neuro', [
            'procedures' => NeuroProcedure::where('doctor_id', $doctorId)
                ->with('patient:id,full_name')->latest('performed_at')->paginate(15)->withQueryString(),
            'patients' => Patient::active()->orderBy('full_name')->limit(500)->get(['id', 'full_name']),
            'procedureTypes' => NeuroProcedure::TYPES,
            'supplies' => \App\Models\Supply::orderBy('name_ar')->get(['id', 'name_ar', 'name_en', 'unit', 'quantity']),
            'recentSeizures' => SeizureDiaryEntry::orderByDesc('occurred_at')->limit(20)->with('patient:id,full_name')->get(),
            'recentHeadaches' => HeadacheDiaryEntry::orderByDesc('date')->limit(20)->with('patient:id,full_name')->get(),
        ]);
    }

    public function storeProcedure(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'type' => 'required|in:'.implode(',', NeuroProcedure::TYPES),
            'performed_at' => 'required|date',
            'findings' => 'nullable|array',
            'cost' => 'nullable|numeric|min:0',
            'supply_id' => 'nullable|exists:supplies,id',
            'consumption_qty' => 'nullable|numeric|min:0',
            'completed_at' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);

        $proc = NeuroProcedure::create(array_merge($data, ['doctor_id' => $this->doctorId($request)]));

        if ($proc->completed_at) {
            $this->billing->billProcedure($proc->fresh());
        }

        AuditLogger::log('created', $proc);

        return back()->with('success', $this->msg('Procedure saved', 'تم حفظ الإجراء'));
    }

    public function destroyProcedure(Request $request, NeuroProcedure $neuroProcedure): RedirectResponse
    {
        $this->authorizeDoctor($request, $neuroProcedure);
        $neuroProcedure->delete();

        return back()->with('success', $this->msg('Deleted', 'تم الحذف'));
    }

    public function storeSeizure(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'occurred_at' => 'required|date',
            'seizure_type' => 'nullable|string|max:40',
            'duration_seconds' => 'nullable|integer|min:0',
            'triggers' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);
        SeizureDiaryEntry::create(array_merge($data, ['entered_by' => 'doctor']));

        return back()->with('success', $this->msg('Seizure logged', 'تم تسجيل النوبة'));
    }

    /** NP5 — record a structured neurological examination for an encounter. */
    public function storeExam(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'neuropsych_encounter_id' => 'required|exists:neuropsych_encounters,id',
            'cranial_nerves' => 'nullable|array',
            'motor' => 'nullable|array',
            'sensory' => 'nullable|array',
            'reflexes' => 'nullable|array',
            'coordination' => 'nullable|string|max:255',
            'gait' => 'nullable|string|max:255',
            'romberg' => 'nullable|in:negative,positive',
            'notes' => 'nullable|string',
        ]);

        $encounter = NeuropsychEncounter::findOrFail($data['neuropsych_encounter_id']);
        $this->authorizeDoctor($request, $encounter);

        NeuroExam::updateOrCreate(
            ['neuropsych_encounter_id' => $encounter->id],
            array_merge($data, ['patient_id' => $encounter->patient_id]),
        );

        return back()->with('success', $this->msg('Exam saved', 'تم حفظ الفحص'));
    }

    public function storeHeadache(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'date' => 'required|date',
            'intensity' => 'nullable|integer|min:0|max:10',
            'duration_hours' => 'nullable|integer|min:0',
            'ichd3_type' => 'nullable|string|max:40',
            'aura' => 'nullable|boolean',
            'meds_taken' => 'nullable|string|max:255',
            'triggers' => 'nullable|string|max:255',
        ]);
        HeadacheDiaryEntry::create(array_merge($data, ['entered_by' => 'doctor', 'aura' => (bool) ($data['aura'] ?? false)]));

        return back()->with('success', $this->msg('Headache logged', 'تم تسجيل الصداع'));
    }
}
