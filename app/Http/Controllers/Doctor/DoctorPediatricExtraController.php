<?php

namespace App\Http\Controllers\Doctor;

use App\Models\Patient;
use App\Models\Prescription;
use App\Models\Visit;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DoctorPediatricExtraController extends BaseDoctorController
{
    /**
     * Pediatric prescriptions list with weight-based presets.
     */
    public function prescriptions(Request $request)
    {
        $doctorId = $this->doctorId($request);

        $query = Prescription::with(['patient:id,full_name,file_number,phone,date_of_birth,gender,guardian_name', 'items'])
            ->where('doctor_id', $doctorId)
            ->whereHas('visit', function ($q) {
                $q->where('module', 'pediatric');
            });

        if ($search = $request->input('search')) {
            $query->whereHas('patient', function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('file_number', 'like', "%{$search}%");
            });
        }

        $prescriptions = $query->latest()->paginate(15)->withQueryString();

        // Common pediatric medication presets with weight-based dosing
        $presets = [
            ['name' => 'Amoxicillin', 'name_ar' => 'أموكسيسيلين', 'dose_per_kg' => 25, 'unit' => 'mg', 'frequency' => '3x daily', 'max_dose' => 500, 'form' => 'suspension'],
            ['name' => 'Ibuprofen', 'name_ar' => 'ايبوبروفين', 'dose_per_kg' => 10, 'unit' => 'mg', 'frequency' => '3x daily', 'max_dose' => 400, 'form' => 'suspension'],
            ['name' => 'Paracetamol', 'name_ar' => 'باراسيتامول', 'dose_per_kg' => 15, 'unit' => 'mg', 'frequency' => '4x daily', 'max_dose' => 1000, 'form' => 'suspension'],
            ['name' => 'Azithromycin', 'name_ar' => 'أزيثروميسين', 'dose_per_kg' => 10, 'unit' => 'mg', 'frequency' => 'once daily', 'max_dose' => 500, 'form' => 'suspension'],
            ['name' => 'Cefalexin', 'name_ar' => 'سيفاليكسين', 'dose_per_kg' => 25, 'unit' => 'mg', 'frequency' => '2x daily', 'max_dose' => 500, 'form' => 'suspension'],
            ['name' => 'Prednisolone', 'name_ar' => 'بريدنيزولون', 'dose_per_kg' => 1, 'unit' => 'mg', 'frequency' => 'once daily', 'max_dose' => 60, 'form' => 'syrup'],
            ['name' => 'Salbutamol Nebulizer', 'name_ar' => 'سالبيوتامول بخاخ', 'dose_per_kg' => 0.15, 'unit' => 'mg', 'frequency' => 'as needed', 'max_dose' => 5, 'form' => 'nebulizer'],
            ['name' => 'Montelukast', 'name_ar' => 'مونتيلوكاست', 'dose_per_kg' => 0, 'unit' => 'mg', 'frequency' => 'once daily', 'max_dose' => 10, 'form' => 'chewable', 'fixed_doses' => ['2-5y' => 4, '6-14y' => 5, '>14y' => 10]],
        ];

        return Inertia::render('Doctor/Pediatric/Prescriptions/Index', [
            'prescriptions' => $prescriptions,
            'filters' => ['search' => $request->input('search')],
            'presets' => $presets,
        ]);
    }

    /**
     * Well-child visit schedule.
     */
    public function wellChild(Request $request)
    {
        $doctorId = $this->doctorId($request);

        $patientIds = Visit::where('doctor_id', $doctorId)
            ->where('module', 'pediatric')
            ->distinct()
            ->pluck('patient_id');

        $patients = Patient::whereIn('id', $patientIds)
            ->select('id', 'full_name', 'date_of_birth', 'gender', 'guardian_name')
            ->withCount(['visits as pediatric_visits_count' => function ($q) use ($doctorId) {
                $q->where('doctor_id', $doctorId)->where('module', 'pediatric');
            }])
            ->orderByDesc('updated_at')
            ->limit(50)
            ->get();

        return Inertia::render('Doctor/Pediatric/WellChild/Index', [
            'patients' => $patients,
        ]);
    }

    /**
     * Reports page.
     */
    public function reports(Request $request)
    {
        $doctorId = $this->doctorId($request);

        $patientIds = Visit::where('doctor_id', $doctorId)
            ->where('module', 'pediatric')
            ->distinct()
            ->pluck('patient_id');

        $patients = Patient::whereIn('id', $patientIds)
            ->select('id', 'full_name', 'date_of_birth', 'gender', 'guardian_name')
            ->orderBy('full_name')
            ->get();

        return Inertia::render('Doctor/Pediatric/Reports/Index', [
            'patients' => $patients,
        ]);
    }
}
