<?php

namespace App\Http\Controllers\Secretary;

use App\Http\Controllers\Controller;
use App\Models\Patient;
use App\Models\PediatricFamilyHistory;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SecretaryPediatricPatientController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');

        $patients = Patient::query()
            ->whereHas('visits', fn($q) => $q->where('module', 'pediatric'))
            ->when($search, function ($q) use ($search) {
                $q->where(function ($sq) use ($search) {
                    $sq->where('full_name', 'like', "%{$search}%")
                       ->orWhere('phone', 'like', "%{$search}%")
                       ->orWhere('file_number', 'like', "%{$search}%")
                       ->orWhere('guardian_name', 'like', "%{$search}%")
                       ->orWhere('guardian_phone', 'like', "%{$search}%");
                });
            })
            ->withCount(['visits as pediatric_visits_count' => fn($q) => $q->where('module', 'pediatric')])
            ->orderByDesc('updated_at')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Secretary/Pediatric/Patients/Index', [
            'patients' => $patients,
            'filters' => ['search' => $search],
        ]);
    }

    public function create()
    {
        return Inertia::render('Secretary/Pediatric/Patients/Create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'full_name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'gender' => 'required|in:male,female',
            'date_of_birth' => 'required|date',
            'blood_type' => 'nullable|in:A+,A-,B+,B-,AB+,AB-,O+,O-',
            'nationality' => 'nullable|string|max:100',
            'address' => 'nullable|string|max:500',
            // Guardian
            'guardian_name' => 'required|string|max:255',
            'guardian_relation' => 'required|string|max:100',
            'guardian_phone' => 'required|string|max:20',
            'guardian_phone2' => 'nullable|string|max:20',
            'guardian_email' => 'nullable|email|max:255',
            'guardian_occupation' => 'nullable|string|max:255',
            // Birth history
            'birth_type' => 'nullable|in:normal,cesarean,vacuum,forceps',
            'birth_place' => 'nullable|string|max:255',
            'gestational_age_weeks' => 'nullable|numeric|min:20|max:45',
            'birth_weight_kg' => 'nullable|numeric|min:0.3|max:7',
            'birth_length_cm' => 'nullable|numeric|min:20|max:65',
            'birth_head_circumference_cm' => 'nullable|numeric|min:20|max:50',
            'apgar_1min' => 'nullable|integer|min:0|max:10',
            'apgar_5min' => 'nullable|integer|min:0|max:10',
            'birth_complications' => 'nullable|array',
            'nicu_days' => 'nullable|integer|min:0',
            'newborn_screening' => 'nullable|array',
            'feeding_type' => 'nullable|in:breastfed,formula,mixed',
            'pregnancy_complications' => 'nullable|string|max:1000',
            // Family history
            'family_history' => 'nullable|array',
        ]);

        // Generate file number
        $lastFileNumber = Patient::max('id') + 1;
        $fileNumber = 'PED-' . str_pad($lastFileNumber, 5, '0', STR_PAD_LEFT);

        $patient = new Patient();
        $patient->forceFill(['file_number' => $fileNumber, 'is_active' => true]);
        $patient->fill(collect($validated)->except(['family_history'])->toArray());
        $patient->save();

        // Save family history
        if (!empty($validated['family_history'])) {
            foreach ($validated['family_history'] as $fh) {
                if (!empty($fh['condition']) && !empty($fh['affected_members'])) {
                    PediatricFamilyHistory::create([
                        'patient_id' => $patient->id,
                        'condition' => $fh['condition'],
                        'condition_ar' => $fh['condition_ar'] ?? null,
                        'affected_members' => $fh['affected_members'],
                        'details' => $fh['details'] ?? null,
                    ]);
                }
            }
        }

        $locale = app()->getLocale();
        $msg = $locale === 'ar' ? 'تم تسجيل المريض بنجاح' : 'Patient registered successfully';

        return redirect('/secretary/pediatric/patients/' . $patient->id)
            ->with('success', $msg);
    }

    public function show(Patient $patient)
    {
        $patient->load([
            'visits' => fn($q) => $q->where('module', 'pediatric')->with('doctor:id,name_en,name_ar')->orderByDesc('visit_date')->limit(10),
        ]);

        $familyHistory = PediatricFamilyHistory::where('patient_id', $patient->id)->get();

        return Inertia::render('Secretary/Pediatric/Patients/Show', [
            'patient' => $patient,
            'familyHistory' => $familyHistory,
        ]);
    }

    public function storeFamilyHistory(Request $request, Patient $patient)
    {
        $validated = $request->validate([
            'condition' => 'required|string|max:255',
            'relation' => 'required|string|max:255',
            'notes' => 'nullable|string|max:500',
        ]);

        PediatricFamilyHistory::create([
            'patient_id' => $patient->id,
            'condition' => $validated['condition'],
            'affected_members' => [$validated['relation']],
            'details' => $validated['notes'],
        ]);

        return redirect()->back()->with('success', 'Family history added');
    }

    public function destroyFamilyHistory(Request $request, PediatricFamilyHistory $familyHistory)
    {
        $familyHistory->delete();

        return redirect()->back()->with('success', 'Family history removed');
    }

    public function edit(Patient $patient)
    {
        $familyHistory = PediatricFamilyHistory::where('patient_id', $patient->id)->get();

        return Inertia::render('Secretary/Pediatric/Patients/Edit', [
            'patient' => $patient,
            'familyHistory' => $familyHistory,
        ]);
    }

    public function update(Request $request, Patient $patient)
    {
        $validated = $request->validate([
            'full_name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'gender' => 'required|in:male,female',
            'date_of_birth' => 'required|date',
            'blood_type' => 'nullable|in:A+,A-,B+,B-,AB+,AB-,O+,O-',
            'nationality' => 'nullable|string|max:100',
            'address' => 'nullable|string|max:500',
            'guardian_name' => 'required|string|max:255',
            'guardian_relation' => 'required|string|max:100',
            'guardian_phone' => 'required|string|max:20',
            'guardian_phone2' => 'nullable|string|max:20',
            'guardian_email' => 'nullable|email|max:255',
            'guardian_occupation' => 'nullable|string|max:255',
            'birth_type' => 'nullable|in:normal,cesarean,vacuum,forceps',
            'birth_place' => 'nullable|string|max:255',
            'gestational_age_weeks' => 'nullable|numeric|min:20|max:45',
            'birth_weight_kg' => 'nullable|numeric|min:0.3|max:7',
            'birth_length_cm' => 'nullable|numeric|min:20|max:65',
            'birth_head_circumference_cm' => 'nullable|numeric|min:20|max:50',
            'apgar_1min' => 'nullable|integer|min:0|max:10',
            'apgar_5min' => 'nullable|integer|min:0|max:10',
            'birth_complications' => 'nullable|array',
            'nicu_days' => 'nullable|integer|min:0',
            'newborn_screening' => 'nullable|array',
            'feeding_type' => 'nullable|in:breastfed,formula,mixed',
            'pregnancy_complications' => 'nullable|string|max:1000',
        ]);

        $patient->fill($validated);
        $patient->save();

        $locale = app()->getLocale();
        $msg = $locale === 'ar' ? 'تم تحديث بيانات المريض' : 'Patient updated successfully';

        return redirect()->back()->with('success', $msg);
    }
}
