<?php
namespace App\Http\Controllers\Doctor;

use App\Models\Visit;
use App\Models\Patient;
use App\Models\PediatricGrowthRecord;
use App\Models\PediatricAllergy;
use App\Services\WhoGrowthStandardsService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;

class DoctorPediatricVisitController extends BaseDoctorController
{
    public function index(Request $request)
    {
        $doctorId = $this->doctorId($request);
        $search = $request->input('search');
        $status = $request->input('status');
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        $visits = Visit::where('doctor_id', $doctorId)
            ->where('module', 'pediatric')
            ->with('patient:id,full_name,date_of_birth,gender,photo,file_number,guardian_name')
            ->when($search, function ($q) use ($search) {
                $q->whereHas('patient', function ($pq) use ($search) {
                    $pq->where('full_name', 'like', "%{$search}%")
                       ->orWhere('file_number', 'like', "%{$search}%");
                });
            })
            ->when($status, fn($q) => $q->where('status', $status))
            ->when($dateFrom, fn($q) => $q->whereDate('visit_date', '>=', $dateFrom))
            ->when($dateTo, fn($q) => $q->whereDate('visit_date', '<=', $dateTo))
            ->orderByDesc('visit_date')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Doctor/Pediatric/Visits/Index', [
            'visits' => $visits,
            'filters' => [
                'search' => $search,
                'status' => $status,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
            ],
        ]);
    }

    public function show(Request $request, Visit $visit)
    {
        $this->authorizeDoctor($request, $visit);

        $visit->load([
            'patient',
            'prescriptions.items',
            'photos',
        ]);

        $patient = $visit->patient;

        // Get allergies (for prescription safety alerts)
        $allergies = PediatricAllergy::where('patient_id', $patient->id)
            ->where('is_active', true)
            ->get();

        // Get latest growth record
        $latestGrowth = PediatricGrowthRecord::where('patient_id', $patient->id)
            ->orderByDesc('measurement_date')
            ->first();

        // Full growth history for chart
        $growthRecords = PediatricGrowthRecord::where('patient_id', $patient->id)
            ->orderBy('measurement_date')
            ->get();

        // Age at visit
        $ageMonths = $patient->date_of_birth
            ? $patient->date_of_birth->diffInMonths($visit->visit_date ?? now())
            : null;

        return Inertia::render('Doctor/Pediatric/Visits/Show', [
            'visit' => $visit,
            'allergies' => $allergies,
            'latestGrowth' => $latestGrowth,
            'growthRecords' => $growthRecords,
            'ageMonths' => $ageMonths,
        ]);
    }

    /**
     * Store a pediatric growth record from within a visit.
     */
    public function storeGrowthFromVisit(Request $request, Visit $visit)
    {
        // Verify doctor owns this visit
        $this->authorizeDoctor($request, $visit);

        $patient = $visit->patient;

        $isPediatricVisit = $visit->module === 'pediatric'
            || (method_exists($patient, 'isPediatric') && $patient->isPediatric());

        if (! $isPediatricVisit) {
            return redirect()->back()->with('error', $this->msg(
                'This visit is not a pediatric visit.',
                'هذه الزيارة ليست زيارة أطفال.'
            ));
        }

        $data = $request->validate([
            'measurement_date' => 'required|date',
            'weight_kg' => 'nullable|numeric|min:0|max:200',
            'height_cm' => 'nullable|numeric|min:0|max:250',
            'head_circumference_cm' => 'nullable|numeric|min:0|max:100',
            'notes' => 'nullable|string|max:1000',
        ]);

        // Calculate age in months from DOB
        $ageMonths = null;
        if ($patient->date_of_birth) {
            $dob = Carbon::parse($patient->date_of_birth);
            $measurementDate = Carbon::parse($data['measurement_date']);
            $ageMonths = round($dob->diffInMonths($measurementDate), 2);
        }

        // Calculate BMI
        $bmi = PediatricGrowthRecord::calculateBmi(
            $data['weight_kg'] ?? null,
            $data['height_cm'] ?? null
        );

        $record = PediatricGrowthRecord::create([
            'patient_id' => $patient->id,
            'visit_id' => $visit->id,
            'doctor_id' => $visit->doctor_id,
            'measurement_date' => $data['measurement_date'],
            'age_months' => $ageMonths,
            'weight_kg' => $data['weight_kg'] ?? null,
            'height_cm' => $data['height_cm'] ?? null,
            'head_circumference_cm' => $data['head_circumference_cm'] ?? null,
            'bmi' => $bmi,
            'notes' => $data['notes'] ?? null,
        ]);

        // Calculate WHO percentiles using existing service
        try {
            if ($ageMonths !== null && $patient->gender) {
                $who = WhoGrowthStandardsService::calculateAll(
                    (float) $ageMonths,
                    $patient->gender,
                    $data['weight_kg'] ?? null,
                    $data['height_cm'] ?? null,
                    $data['head_circumference_cm'] ?? null,
                    $bmi
                );

                $record->fill([
                    'weight_zscore' => $who['weight_zscore'],
                    'weight_percentile' => $who['weight_percentile'],
                    'height_zscore' => $who['height_zscore'],
                    'height_percentile' => $who['height_percentile'],
                    'head_zscore' => $who['head_zscore'],
                    'head_percentile' => $who['head_percentile'],
                    'bmi_zscore' => $who['bmi_zscore'],
                    'bmi_percentile' => $who['bmi_percentile'],
                ])->save();
            }
        } catch (\Throwable $e) {
            report($e);
        }

        return redirect()->back()->with(
            'success',
            $this->msg('Growth measurement saved successfully', 'تم تسجيل القياس بنجاح')
        );
    }
}
