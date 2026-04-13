<?php
namespace App\Http\Controllers\Doctor;

use App\Models\Patient;
use App\Models\PediatricGrowthRecord;
use App\Models\PediatricVaccination;
use App\Models\PediatricMilestone;
use App\Models\PediatricAllergy;
use App\Models\PediatricFamilyHistory;
use App\Models\PediatricNutritionRecord;
use App\Models\PediatricChronicCondition;
use App\Models\PediatricScreeningTest;
use App\Models\Visit;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DoctorPediatricPatientController extends BaseDoctorController
{
    public function index(Request $request)
    {
        $doctorId = $this->doctorId($request);
        $search = $request->input('search');

        $patientIds = Visit::where('doctor_id', $doctorId)
            ->where('module', 'pediatric')
            ->distinct()
            ->pluck('patient_id');

        $patients = Patient::whereIn('id', $patientIds)
            ->when($search, function ($q) use ($search) {
                $q->where(function ($sq) use ($search) {
                    $sq->where('full_name', 'like', "%{$search}%")
                       ->orWhere('phone', 'like', "%{$search}%")
                       ->orWhere('file_number', 'like', "%{$search}%")
                       ->orWhere('guardian_name', 'like', "%{$search}%")
                       ->orWhere('guardian_phone', 'like', "%{$search}%");
                });
            })
            ->withCount(['visits as pediatric_visits_count' => function ($q) use ($doctorId) {
                $q->where('doctor_id', $doctorId)->where('module', 'pediatric');
            }])
            ->orderByDesc('updated_at')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Doctor/Pediatric/Patients/Index', [
            'patients' => $patients,
            'filters' => ['search' => $search],
        ]);
    }

    public function show(Request $request, Patient $patient)
    {
        $doctorId = $this->doctorId($request);
        $tab = $request->input('tab', 'overview');

        // Growth records
        $growthRecords = PediatricGrowthRecord::where('patient_id', $patient->id)
            ->orderBy('measurement_date')
            ->get();

        // Vaccinations
        $vaccinations = PediatricVaccination::where('patient_id', $patient->id)
            ->orderBy('scheduled_date')
            ->get();

        // Milestones
        $milestones = PediatricMilestone::where('patient_id', $patient->id)
            ->orderBy('category')
            ->orderBy('expected_age')
            ->get();

        // Allergies
        $allergies = PediatricAllergy::where('patient_id', $patient->id)
            ->orderByDesc('is_active')
            ->get();

        // Family history
        $familyHistory = PediatricFamilyHistory::where('patient_id', $patient->id)->get();

        // Nutrition records
        $nutritionRecords = PediatricNutritionRecord::where('patient_id', $patient->id)
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        // Chronic conditions
        $chronicConditions = PediatricChronicCondition::where('patient_id', $patient->id)
            ->orderByDesc('is_active')
            ->get();

        // Screening tests
        $screeningTests = PediatricScreeningTest::where('patient_id', $patient->id)
            ->orderByDesc('test_date')
            ->get();

        // Recent visits
        $visits = Visit::where('patient_id', $patient->id)
            ->where('module', 'pediatric')
            ->with('doctor:id,name_en,name_ar')
            ->orderByDesc('visit_date')
            ->limit(20)
            ->get();

        // Calculate age in months
        $ageMonths = $patient->date_of_birth ? $patient->date_of_birth->diffInMonths(now()) : null;
        $ageDays = $patient->date_of_birth ? $patient->date_of_birth->diffInDays(now()) : null;

        // Vaccination stats
        $vaccineStats = [
            'total' => $vaccinations->count(),
            'given' => $vaccinations->where('status', 'given')->count(),
            'scheduled' => $vaccinations->where('status', 'scheduled')->count(),
            'missed' => $vaccinations->where('status', 'missed')->count(),
        ];

        // Milestone stats
        $milestoneStats = [
            'total' => $milestones->count(),
            'achieved' => $milestones->where('status', 'achieved')->count(),
            'emerging' => $milestones->where('status', 'emerging')->count(),
            'not_achieved' => $milestones->where('status', 'not_achieved')->count(),
        ];

        return Inertia::render('Doctor/Pediatric/Patients/Show', [
            'patient' => $patient,
            'ageMonths' => $ageMonths,
            'ageDays' => $ageDays,
            'growthRecords' => $growthRecords,
            'vaccinations' => $vaccinations,
            'milestones' => $milestones,
            'allergies' => $allergies,
            'familyHistory' => $familyHistory,
            'nutritionRecords' => $nutritionRecords,
            'chronicConditions' => $chronicConditions,
            'screeningTests' => $screeningTests,
            'visits' => $visits,
            'vaccineStats' => $vaccineStats,
            'milestoneStats' => $milestoneStats,
            'tab' => $tab,
            'milestoneDefinitions' => PediatricMilestone::MILESTONES,
            'vaccineSchedule' => PediatricVaccination::VACCINE_SCHEDULE,
        ]);
    }

    /**
     * Store growth record.
     */
    public function storeGrowth(Request $request, Patient $patient)
    {
        $validated = $request->validate([
            'measurement_date' => 'required|date',
            'weight_kg' => 'nullable|numeric|min:0|max:200',
            'height_cm' => 'nullable|numeric|min:0|max:250',
            'head_circumference_cm' => 'nullable|numeric|min:0|max:80',
            'notes' => 'nullable|string|max:500',
        ]);

        $ageMonths = $patient->date_of_birth
            ? $patient->date_of_birth->diffInMonths($validated['measurement_date'])
            : 0;

        $bmi = PediatricGrowthRecord::calculateBmi(
            $validated['weight_kg'] ?? null,
            $validated['height_cm'] ?? null
        );

        PediatricGrowthRecord::create([
            'patient_id' => $patient->id,
            'doctor_id' => $this->doctorId($request),
            'measurement_date' => $validated['measurement_date'],
            'age_months' => $ageMonths,
            'weight_kg' => $validated['weight_kg'],
            'height_cm' => $validated['height_cm'],
            'head_circumference_cm' => $validated['head_circumference_cm'],
            'bmi' => $bmi,
            'notes' => $validated['notes'],
        ]);

        return redirect()->back()->with('success', $this->msg('Growth record saved', 'تم حفظ قياسات النمو'));
    }

    /**
     * Store/update vaccination.
     */
    public function storeVaccination(Request $request, Patient $patient)
    {
        $validated = $request->validate([
            'id' => 'nullable|integer|exists:pediatric_vaccinations,id',
            'vaccine_name' => 'required|string|max:255',
            'vaccine_name_ar' => 'nullable|string|max:255',
            'dose_number' => 'required|string|max:100',
            'scheduled_age' => 'nullable|string|max:100',
            'given_date' => 'nullable|date',
            'batch_number' => 'nullable|string|max:100',
            'manufacturer' => 'nullable|string|max:255',
            'site_of_injection' => 'nullable|string|max:100',
            'status' => 'required|in:scheduled,given,missed,postponed,contraindicated',
            'side_effects' => 'nullable|string|max:500',
            'notes' => 'nullable|string|max:500',
        ]);

        $data = array_merge($validated, [
            'patient_id' => $patient->id,
            'doctor_id' => $this->doctorId($request),
        ]);
        unset($data['id']);

        if ($validated['id'] ?? null) {
            PediatricVaccination::where('id', $validated['id'])
                ->where('patient_id', $patient->id)
                ->update($data);
        } else {
            PediatricVaccination::create($data);
        }

        return redirect()->back()->with('success', $this->msg('Vaccination record saved', 'تم حفظ سجل التطعيم'));
    }

    /**
     * Update milestone status.
     */
    public function updateMilestone(Request $request, Patient $patient)
    {
        $validated = $request->validate([
            'milestone_key' => 'required|string',
            'status' => 'required|in:achieved,not_achieved,emerging',
            'achieved_date' => 'nullable|date',
            'notes' => 'nullable|string|max:500',
        ]);

        $milestone = PediatricMilestone::MILESTONES;
        $def = collect($milestone)->firstWhere('key', $validated['milestone_key']);
        if (!$def) return redirect()->back()->with('error', 'Invalid milestone');

        $ageMonths = $patient->date_of_birth ? $patient->date_of_birth->diffInMonths(now()) : 0;

        PediatricMilestone::updateOrCreate(
            ['patient_id' => $patient->id, 'milestone_key' => $validated['milestone_key']],
            [
                'doctor_id' => $this->doctorId($request),
                'assessment_date' => now()->toDateString(),
                'age_months' => $ageMonths,
                'category' => $def['category'],
                'milestone_name_en' => $def['name_en'],
                'milestone_name_ar' => $def['name_ar'],
                'expected_age' => $def['expected_months'] . ' months',
                'status' => $validated['status'],
                'achieved_date' => $validated['status'] === 'achieved' ? ($validated['achieved_date'] ?? now()->toDateString()) : null,
                'notes' => $validated['notes'],
            ]
        );

        return redirect()->back()->with('success', $this->msg('Milestone updated', 'تم تحديث المعلم التطوري'));
    }

    /**
     * Store allergy.
     */
    public function storeAllergy(Request $request, Patient $patient)
    {
        $validated = $request->validate([
            'allergy_type' => 'required|in:food,drug,environmental,insect,other',
            'allergen' => 'required|string|max:255',
            'severity' => 'required|in:mild,moderate,severe,anaphylaxis',
            'symptoms' => 'nullable|array',
            'treatment' => 'nullable|string|max:500',
            'notes' => 'nullable|string|max:500',
        ]);

        PediatricAllergy::create(array_merge($validated, [
            'patient_id' => $patient->id,
        ]));

        return redirect()->back()->with('success', $this->msg('Allergy recorded', 'تم تسجيل الحساسية'));
    }

    /**
     * Initialize vaccination schedule for a patient.
     */
    public function initializeVaccinations(Request $request, Patient $patient)
    {
        if (!$patient->date_of_birth) {
            return redirect()->back()->with('error', $this->msg('Patient date of birth is required', 'تاريخ ميلاد المريض مطلوب'));
        }

        $existing = PediatricVaccination::where('patient_id', $patient->id)->count();
        if ($existing > 0) {
            return redirect()->back()->with('error', $this->msg('Vaccination schedule already initialized', 'جدول التطعيمات مُفعل بالفعل'));
        }

        $dob = $patient->date_of_birth;
        foreach (PediatricVaccination::VACCINE_SCHEDULE as $vaccine) {
            PediatricVaccination::create([
                'patient_id' => $patient->id,
                'doctor_id' => $this->doctorId($request),
                'vaccine_name' => $vaccine['vaccine'],
                'vaccine_name_ar' => $vaccine['vaccine_ar'],
                'dose_number' => $vaccine['dose'],
                'scheduled_age' => $vaccine['age'],
                'scheduled_date' => $dob->copy()->addMonths($vaccine['months']),
                'status' => $dob->copy()->addMonths($vaccine['months'])->isPast() ? 'missed' : 'scheduled',
            ]);
        }

        return redirect()->back()->with('success', $this->msg('Vaccination schedule initialized', 'تم تفعيل جدول التطعيمات'));
    }
}
