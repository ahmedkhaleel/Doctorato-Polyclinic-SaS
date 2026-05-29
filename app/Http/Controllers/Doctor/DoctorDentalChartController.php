<?php

namespace App\Http\Controllers\Doctor;

use App\Models\DentalChart;
use App\Models\DentalChartEntry;
use App\Models\DentalTreatment;
use App\Models\DentalXray;
use App\Models\Doctor;
use App\Models\Patient;
use App\Http\Requests\Dental\UpdateDentalChartRequest;
use App\Services\AuditLogger;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DoctorDentalChartController extends BaseDoctorController
{
    /**
     * Search patients to open their dental chart.
     */
    public function search(Request $request): Response
    {
        $doctorId = $this->doctorId($request);
        $search = $request->input('search', '');
        $patients = [];

        if (strlen($search) >= 2) {
            $patients = Patient::whereHas('visits', fn ($q) => $q->where('doctor_id', $doctorId))
                ->where(function ($q) use ($search) {
                    $q->where('full_name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('file_number', 'like', "%{$search}%");
                })
                ->select('id', 'full_name', 'phone', 'file_number', 'photo')
                ->take(10)
                ->get();
        }

        // Recent patients who have dental charts (for quick access)
        $recentCharts = Patient::whereHas('dentalCharts')
            ->whereHas('visits', fn ($q) => $q->where('doctor_id', $doctorId))
            ->select('id', 'full_name', 'phone', 'file_number')
            ->withCount('dentalCharts as teeth_count')
            ->latest('updated_at')
            ->take(6)
            ->get();

        return Inertia::render('Doctor/Dental/DentalChart/Search', [
            'patients' => $patients,
            'recentCharts' => $recentCharts,
            'search' => $search,
        ]);
    }

    public function show(Request $request, Patient $patient): Response
    {
        $chart = DentalChart::where('patient_id', $patient->id)
            ->orderBy('tooth_number')
            ->get()
            ->keyBy('tooth_number');

        $treatments = DentalTreatment::where('patient_id', $patient->id)
            ->where('doctor_id', $this->doctorId($request))
            ->with('doctor:id,name_ar,name_en')
            ->orderByDesc('created_at')
            ->get()
            ->groupBy('tooth_number');

        $entries = DentalChartEntry::where('patient_id', $patient->id)
            ->with('doctor:id,name_ar,name_en')
            ->orderByDesc('entry_date')
            ->orderByDesc('created_at')
            ->get()
            ->groupBy('tooth_number');

        $doctors = Doctor::select('id', 'name_en', 'name_ar')
            ->where('status', 'active')
            ->orderBy('name_en')
            ->get();

        $isChild = false;
        if ($patient->date_of_birth) {
            $isChild = Carbon::parse($patient->date_of_birth)->age < 13;
        }

        // X-rays grouped by tooth for quick reference
        $xrays = DentalXray::where('patient_id', $patient->id)
            ->select('id', 'type', 'tooth_number', 'image_path', 'taken_date')
            ->orderByDesc('taken_date')
            ->get()
            ->groupBy('tooth_number');

        return Inertia::render('Doctor/Dental/DentalChart/Show', [
            'patient' => $patient->only(['id', 'full_name', 'file_number', 'phone', 'date_of_birth', 'gender', 'allergies', 'chronic_conditions', 'current_medications', 'blood_type', 'medical_notes',
                'has_dental_anxiety', 'dental_anxiety_level', 'latex_allergy', 'anesthesia_complications',
                'is_pregnant', 'is_breastfeeding', 'has_bleeding_disorder', 'takes_blood_thinners', 'blood_thinner_name',
                'has_heart_condition', 'has_diabetes', 'diabetes_type', 'has_hepatitis', 'hepatitis_type', 'has_hiv',
                'is_smoker', 'smoking_frequency',
            ]),
            'dentalRiskFlags' => $patient->getDentalRiskFlags(),
            'chart' => $chart,
            'treatments' => $treatments,
            'entries' => $entries,
            'xrays' => $xrays,
            'doctors' => $doctors,
            'conditions' => DentalChart::CONDITIONS,
            'surfaces' => DentalChart::SURFACES,
            'allTeeth' => DentalChart::ALL_TEETH,
            'deciduousTeeth' => DentalChart::ALL_DECIDUOUS_TEETH,
            'treatmentTypes' => DentalTreatment::TYPES,
            'isChild' => $isChild,
            'supplies' => \App\Models\Supply::orderBy('name_ar')->get(['id', 'name_ar', 'name_en', 'unit', 'quantity']),
        ]);
    }

    public function updateTooth(UpdateDentalChartRequest $request, Patient $patient, int $toothNumber)
    {
        $data = $request->validated();

        $chart = DentalChart::updateOrCreate(
            ['patient_id' => $patient->id, 'tooth_number' => $toothNumber],
            $data
        );

        AuditLogger::log($chart->wasRecentlyCreated ? 'created' : 'updated', $chart);

        return redirect()->back()->with('success', $this->msg('Tooth updated successfully.', 'تم تحديث السن بنجاح.'));
    }

    public function initializeChart(Request $request, Patient $patient)
    {
        $mode = $request->input('mode', 'adult');
        $teethList = $mode === 'deciduous' ? DentalChart::ALL_DECIDUOUS_TEETH : DentalChart::ALL_TEETH;

        $existing = DentalChart::where('patient_id', $patient->id)->pluck('tooth_number')->toArray();
        $toCreate = array_diff($teethList, $existing);

        $records = [];
        $now = now();
        foreach ($toCreate as $tooth) {
            $records[] = [
                'patient_id' => $patient->id,
                'tooth_number' => $tooth,
                'condition' => 'healthy',
                'status' => 'present',
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if (!empty($records)) {
            DentalChart::insert($records);

            $label = $mode === 'deciduous' ? 'deciduous' : 'adult';
            AuditLogger::log('created', null, [
                'new' => ['patient_id' => $patient->id, 'teeth_count' => count($records), 'mode' => $label],
            ], "Initialized {$label} dental chart for patient \"{$patient->full_name}\" (" . count($records) . ' teeth)');
        }

        return redirect()->back()->with('success', $this->msg('Dental chart initialized.', 'تم تهيئة خريطة الأسنان.'));
    }
}
