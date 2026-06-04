<?php

namespace App\Http\Controllers\Patient;

use App\Models\HeadacheDiaryEntry;
use App\Models\SeizureDiaryEntry;
use App\Services\ModuleManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * NP5 — patient-portal seizure & headache diaries. The patient logs events
 * between visits; entries flow into the neurologist's longitudinal view.
 * Gated on the neurology module being enabled.
 */
class PatientNeuroDiaryController extends BasePatientController
{
    public function index(Request $request, string $locale): Response
    {
        $pid = $this->patientId($request);

        return Inertia::render('Patient/Neuropsych/Diaries', [
            'seizures' => SeizureDiaryEntry::where('patient_id', $pid)->orderByDesc('occurred_at')->limit(50)->get(),
            'headaches' => HeadacheDiaryEntry::where('patient_id', $pid)->orderByDesc('date')->limit(50)->get(),
        ]);
    }

    public function storeSeizure(Request $request, string $locale): RedirectResponse
    {
        abort_unless(ModuleManager::isEnabled('neurology'), 404);
        $data = $request->validate([
            'occurred_at' => 'required|date',
            'seizure_type' => 'nullable|string|max:40',
            'duration_seconds' => 'nullable|integer|min:0',
            'triggers' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);
        SeizureDiaryEntry::create(array_merge($data, ['patient_id' => $this->patientId($request), 'entered_by' => 'patient']));

        return back()->with('success', app()->getLocale() === 'ar' ? 'تم تسجيل النوبة' : 'Seizure logged');
    }

    public function storeHeadache(Request $request, string $locale): RedirectResponse
    {
        abort_unless(ModuleManager::isEnabled('neurology'), 404);
        $data = $request->validate([
            'date' => 'required|date',
            'intensity' => 'nullable|integer|min:0|max:10',
            'duration_hours' => 'nullable|integer|min:0',
            'ichd3_type' => 'nullable|string|max:40',
            'aura' => 'nullable|boolean',
            'meds_taken' => 'nullable|string|max:255',
            'triggers' => 'nullable|string|max:255',
        ]);
        HeadacheDiaryEntry::create(array_merge($data, [
            'patient_id' => $this->patientId($request), 'entered_by' => 'patient', 'aura' => (bool) ($data['aura'] ?? false),
        ]));

        return back()->with('success', app()->getLocale() === 'ar' ? 'تم تسجيل الصداع' : 'Headache logged');
    }
}
