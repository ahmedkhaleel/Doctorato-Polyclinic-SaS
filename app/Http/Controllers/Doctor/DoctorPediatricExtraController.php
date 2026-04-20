<?php

namespace App\Http\Controllers\Doctor;

use App\Models\Patient;
use App\Models\PediatricAllergy;
use App\Models\PediatricChronicCondition;
use App\Models\PediatricGrowthRecord;
use App\Models\PediatricVaccination;
use App\Models\PediatricWellChildVisit;
use App\Models\Prescription;
use App\Models\Setting;
use App\Models\Visit;
use Barryvdh\DomPDF\Facade\Pdf;
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
            'schedule' => PediatricWellChildVisit::SCHEDULE,
        ]);
    }

    /**
     * Well-child detail page for a specific patient.
     */
    public function wellChildPatient(Request $request, Patient $patient)
    {
        $doctorId = $this->doctorId($request);

        // Verify doctor has pediatric relationship
        $hasRelationship = Visit::where('doctor_id', $doctorId)
            ->where('patient_id', $patient->id)
            ->where('module', 'pediatric')
            ->exists();
        if (!$hasRelationship) abort(403);

        $visits = PediatricWellChildVisit::where('patient_id', $patient->id)
            ->orderBy('scheduled_age_months')
            ->get();

        $ageMonths = $patient->date_of_birth
            ? $patient->date_of_birth->diffInMonths(now())
            : null;

        return Inertia::render('Doctor/Pediatric/WellChild/Show', [
            'patient' => $patient,
            'wellChildVisits' => $visits,
            'schedule' => PediatricWellChildVisit::SCHEDULE,
            'ageMonths' => $ageMonths,
        ]);
    }

    /**
     * Initialize well-child schedule for a patient.
     */
    public function initializeWellChild(Request $request, Patient $patient)
    {
        $doctorId = $this->doctorId($request);

        $existing = PediatricWellChildVisit::where('patient_id', $patient->id)->count();
        if ($existing > 0) {
            return redirect()->back()->with('error', $this->msg('Well-child schedule already initialized', 'جدول فحوصات الطفل السليم مُفعل بالفعل'));
        }

        foreach (PediatricWellChildVisit::SCHEDULE as $item) {
            $scheduledDate = $patient->date_of_birth
                ? $patient->date_of_birth->copy()->addMonths((int) $item['age_months'])
                : null;

            PediatricWellChildVisit::create([
                'patient_id' => $patient->id,
                'doctor_id' => $doctorId,
                'schedule_key' => $item['key'],
                'scheduled_age_months' => $item['age_months'],
                'status' => ($scheduledDate && $scheduledDate->isPast()) ? 'missed' : 'scheduled',
            ]);
        }

        return redirect()->back()->with('success', $this->msg('Well-child schedule initialized', 'تم تفعيل جدول فحوصات الطفل السليم'));
    }

    /**
     * Update a well-child visit record (complete or edit).
     */
    public function updateWellChild(Request $request, Patient $patient, PediatricWellChildVisit $wellChild)
    {
        if ((int) $wellChild->patient_id !== (int) $patient->id) abort(403);

        $validated = $request->validate([
            'visit_date' => 'nullable|date',
            'status' => 'required|in:scheduled,completed,missed,skipped',
            'weight_kg' => 'nullable|numeric|min:0|max:200',
            'height_cm' => 'nullable|numeric|min:0|max:250',
            'head_circumference_cm' => 'nullable|numeric|min:0|max:80',
            'physical_exam_notes' => 'nullable|string|max:2000',
            'development_notes' => 'nullable|string|max:2000',
            'feeding_notes' => 'nullable|string|max:1000',
            'safety_guidance' => 'nullable|string|max:1000',
            'vaccinations_given' => 'nullable|array',
            'screening_tests_done' => 'nullable|array',
            'referrals' => 'nullable|array',
            'next_visit_date' => 'nullable|date',
            'notes' => 'nullable|string|max:2000',
        ]);

        $validated['doctor_id'] = $this->doctorId($request);

        $wellChild->update($validated);

        return redirect()->back()->with('success', $this->msg('Well-child visit updated', 'تم تحديث فحص الطفل السليم'));
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

    /**
     * Generate vaccination card PDF for a patient.
     */
    public function vaccinationCardPdf(Request $request, Patient $patient)
    {
        $vaccinations = PediatricVaccination::where('patient_id', $patient->id)
            ->orderBy('scheduled_date')
            ->get();

        $ageMonths = $patient->date_of_birth ? $patient->date_of_birth->diffInMonths(now()) : null;

        $pdf = Pdf::loadView('pdf.pediatric-vaccination-card', [
            'patient' => $patient,
            'vaccinations' => $vaccinations,
            'ageMonths' => $ageMonths,
            'clinicName' => \App\Models\Setting::get('clinic_name', 'Doctorato Polyclinic'),
            'clinicPhone' => \App\Models\Setting::get('clinic_phone', ''),
        ]);
        $pdf->setPaper('A4', 'landscape');

        $filename = 'VaccinationCard-' . ($patient->file_number ?? $patient->id) . '.pdf';
        return $pdf->stream($filename);
    }

    /**
     * Generate growth report PDF for a patient.
     */
    public function growthReportPdf(Request $request, Patient $patient)
    {
        $growthRecords = PediatricGrowthRecord::where('patient_id', $patient->id)
            ->orderBy('measurement_date')
            ->get();

        $ageMonths = $patient->date_of_birth ? $patient->date_of_birth->diffInMonths(now()) : null;

        $pdf = Pdf::loadView('pdf.pediatric-growth-report', [
            'patient' => $patient,
            'growthRecords' => $growthRecords,
            'ageMonths' => $ageMonths,
            'clinicName' => \App\Models\Setting::get('clinic_name', 'Doctorato Polyclinic'),
            'clinicPhone' => \App\Models\Setting::get('clinic_phone', ''),
        ]);
        $pdf->setPaper('A4');

        $filename = 'GrowthReport-' . ($patient->file_number ?? $patient->id) . '.pdf';
        return $pdf->stream($filename);
    }

    /**
     * Helper: get common PDF data for a patient.
     */
    private function getPdfData(Request $request, Patient $patient): array
    {
        $ageMonths = $patient->date_of_birth ? $patient->date_of_birth->diffInMonths(now()) : null;
        $years = $ageMonths ? intdiv($ageMonths, 12) : null;
        $months = $ageMonths ? $ageMonths % 12 : null;
        $ageDisplay = $ageMonths !== null
            ? ($ageMonths < 24 ? "{$ageMonths} months" : "{$years}y {$months}m")
            : '-';

        $doctor = auth('doctor')->user();

        return [
            'patient' => $patient,
            'ageMonths' => $ageMonths,
            'ageDisplay' => $ageDisplay,
            'doctorName' => $doctor ? ($doctor->name_en ?? $doctor->name_ar ?? '-') : '-',
            'clinicName' => Setting::get('clinic_name', 'Doctorato Polyclinic'),
            'clinicPhone' => Setting::get('clinic_phone', ''),
            'allergies' => PediatricAllergy::where('patient_id', $patient->id)->where('is_active', true)->get(),
            'chronicConditions' => PediatricChronicCondition::where('patient_id', $patient->id)->where('is_active', true)->get(),
            'latestGrowth' => PediatricGrowthRecord::where('patient_id', $patient->id)->latest('measurement_date')->first(),
        ];
    }

    /**
     * Generate general medical report PDF.
     */
    public function generalReportPdf(Request $request, Patient $patient)
    {
        $data = $this->getPdfData($request, $patient);

        $vaccinations = PediatricVaccination::where('patient_id', $patient->id)->get();
        $data['vaccinationStats'] = [
            'total' => $vaccinations->count(),
            'given' => $vaccinations->where('status', 'given')->count(),
            'scheduled' => $vaccinations->where('status', 'scheduled')->count(),
            'missed' => $vaccinations->where('status', 'missed')->count(),
        ];
        $data['reportDate'] = $request->input('date', now()->format('d M Y'));
        $data['notes'] = $request->input('notes', '');

        $pdf = Pdf::loadView('pdf.pediatric-general-report', $data);
        $pdf->setPaper('A4');

        return $pdf->stream('MedicalReport-' . ($patient->file_number ?? $patient->id) . '.pdf');
    }

    /**
     * Generate school health certificate PDF.
     */
    public function schoolCertificatePdf(Request $request, Patient $patient)
    {
        $data = $this->getPdfData($request, $patient);

        $vaccinations = PediatricVaccination::where('patient_id', $patient->id)->get();
        $missed = $vaccinations->where('status', 'missed')->count();
        $scheduled = $vaccinations->where('status', 'scheduled')->count();
        $data['vaccinationsComplete'] = $missed === 0 && $scheduled === 0;
        $data['reportDate'] = $request->input('date', now()->format('d M Y'));
        $data['notes'] = $request->input('notes', '');

        $pdf = Pdf::loadView('pdf.pediatric-school-certificate', $data);
        $pdf->setPaper('A4');

        return $pdf->stream('SchoolCertificate-' . ($patient->file_number ?? $patient->id) . '.pdf');
    }

    /**
     * Generate referral letter PDF.
     */
    public function referralLetterPdf(Request $request, Patient $patient)
    {
        $data = $this->getPdfData($request, $patient);
        $data['reportDate'] = $request->input('date', now()->format('d M Y'));
        $data['notes'] = $request->input('notes', '');
        $data['referredTo'] = $request->input('referred_to', 'Specialist');
        $data['reason'] = $request->input('reason', '');
        $data['clinicalSummary'] = $request->input('clinical_summary', '');
        $data['urgency'] = $request->input('urgency', 'routine');

        $pdf = Pdf::loadView('pdf.pediatric-referral-letter', $data);
        $pdf->setPaper('A4');

        return $pdf->stream('ReferralLetter-' . ($patient->file_number ?? $patient->id) . '.pdf');
    }

    /**
     * Generate medical leave certificate PDF.
     */
    public function medicalLeavePdf(Request $request, Patient $patient)
    {
        $data = $this->getPdfData($request, $patient);
        $data['reportDate'] = $request->input('date', now()->format('d M Y'));
        $data['notes'] = $request->input('notes', '');
        $data['diagnosis'] = $request->input('diagnosis', '');

        $leaveFrom = $request->input('leave_from', now()->format('d M Y'));
        $leaveTo = $request->input('leave_to', now()->addDays(2)->format('d M Y'));
        $data['leaveFrom'] = $leaveFrom;
        $data['leaveTo'] = $leaveTo;

        // Calculate days
        try {
            $from = \Carbon\Carbon::parse($leaveFrom);
            $to = \Carbon\Carbon::parse($leaveTo);
            $data['leaveDays'] = $from->diffInDays($to) + 1;
        } catch (\Exception $e) {
            $data['leaveDays'] = '-';
        }

        $pdf = Pdf::loadView('pdf.pediatric-medical-leave', $data);
        $pdf->setPaper('A4');

        return $pdf->stream('MedicalLeave-' . ($patient->file_number ?? $patient->id) . '.pdf');
    }
}
