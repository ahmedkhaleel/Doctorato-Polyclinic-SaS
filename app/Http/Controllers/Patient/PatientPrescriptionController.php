<?php

namespace App\Http\Controllers\Patient;

use App\Models\Prescription;
use App\Services\Ai\AiManager;
use App\Services\Ai\Exceptions\AiUnavailableException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PatientPrescriptionController extends BasePatientController
{
    public function index(Request $request): Response
    {
        $prescriptions = Prescription::where('patient_id', $this->patientId($request))
            ->with(['doctor:id,name_en,name_ar', 'visit:id,visit_date'])
            ->latest('created_at')
            ->paginate(15);

        return Inertia::render('Patient/Prescriptions/Index', [
            'prescriptions' => $prescriptions,
        ]);
    }

    public function show(Request $request, string $locale, Prescription $prescription): Response
    {
        $this->authorizePatient($request, $prescription);

        $prescription->load([
            'doctor:id,name_en,name_ar,specialization_en,specialization_ar',
            'visit:id,visit_date',
            // medication is a string column (medication_name) on each item —
            // there is no related model to eager-load.
            'medications',
        ]);

        return Inertia::render('Patient/Prescriptions/Show', [
            'prescription' => $prescription,
        ]);
    }

    /**
     * Plain-language, non-diagnostic explanation of the patient's OWN prescription
     * (AI gap #4). Gated by the patient_explain feature flag; never changes data.
     * The model is told to explain only, never to diagnose or alter doses.
     */
    public function explain(Request $request, string $locale, Prescription $prescription, AiManager $ai): JsonResponse
    {
        $this->authorizePatient($request, $prescription);
        $prescription->load('medications');

        $lang = app()->getLocale();

        $meds = $prescription->medications
            ->map(fn ($m) => trim(implode(' · ', array_filter([
                $m->medication_name, $m->dosage, $m->frequency, $m->duration, $m->instructions,
            ]))))
            ->filter()
            ->values()
            ->all();

        if (empty($meds)) {
            return response()->json([
                'ok' => false,
                'message' => $lang === 'ar' ? 'لا توجد أدوية في هذه الوصفة لشرحها.' : 'This prescription has no medicines to explain.',
            ], 422);
        }

        $system = $lang === 'ar'
            ? 'أنت مساعد يشرح الوصفة الطبية للمريض بلغة بسيطة ومطمئنة. لكل دواء اذكر باختصار: لماذا يُوصف عادةً، وكيف يُؤخذ حسب التعليمات المكتوبة فقط. ممنوع تقديم تشخيص أو اقتراح تغيير الجرعة أو إيقاف الدواء. اختم بتذكير المريض بسؤال الطبيب أو الصيدلي عند أي شك. أجب بالعربية بنقاط موجزة.'
            : 'You explain a prescription to the patient in plain, reassuring language. For each medicine briefly state: what it is generally prescribed for, and how to take it per the written instructions ONLY. Never diagnose, never suggest changing or stopping a dose. End by reminding the patient to ask their doctor or pharmacist if unsure. Answer concisely with bullet points.';

        $user = ($lang === 'ar' ? 'التشخيص: ' : 'Diagnosis: ').($prescription->diagnosis ?: '—')."\n"
            .($lang === 'ar' ? "الأدوية:\n" : "Medicines:\n").implode("\n", $meds);

        try {
            $result = $ai->generate('patient_explain', [
                ['role' => 'system', 'content' => $system],
                ['role' => 'user', 'content' => $user],
            ], [
                'locale' => $lang,
                'rate_key' => 'patient-explain:'.$this->patientId($request),
                'actor' => ['type' => 'patient', 'id' => $this->patientId($request)],
            ]);
        } catch (AiUnavailableException) {
            return response()->json([
                'ok' => false,
                'message' => $lang === 'ar'
                    ? 'الخدمة غير متاحة حاليًا. يرجى سؤال الطبيب أو الصيدلي.'
                    : 'This service is currently unavailable. Please ask your doctor or pharmacist.',
            ], 422);
        }

        return response()->json(['ok' => true, 'text' => $result->text]);
    }
}
