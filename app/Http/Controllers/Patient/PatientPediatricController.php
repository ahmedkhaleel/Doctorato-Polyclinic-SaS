<?php

namespace App\Http\Controllers\Patient;

use App\Models\PediatricGrowthRecord;
use App\Models\PediatricVaccination;
use App\Models\PediatricScreeningTest;
use App\Models\PediatricMilestone;
use App\Models\PediatricAllergy;
use App\Models\PediatricChronicCondition;
use App\Models\Setting;
use App\Models\Visit;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PatientPediatricController extends BasePatientController
{
    /**
     * Pediatric overview — consolidated summary for the patient's child.
     */
    public function overview(Request $request): Response
    {
        $patientId = $this->patientId($request);

        // Visit summary
        $visitSummary = Visit::where('patient_id', $patientId)
            ->where('module', 'pediatric')
            ->selectRaw("
                COUNT(*) as total,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
                SUM(CASE WHEN status = 'waiting' OR status = 'in_progress' THEN 1 ELSE 0 END) as upcoming
            ")
            ->first();

        // Vaccination summary
        $vaccinationSummary = PediatricVaccination::where('patient_id', $patientId)
            ->selectRaw("
                COUNT(*) as total,
                SUM(CASE WHEN status = 'given' THEN 1 ELSE 0 END) as given,
                SUM(CASE WHEN status = 'scheduled' THEN 1 ELSE 0 END) as scheduled,
                SUM(CASE WHEN status = 'missed' THEN 1 ELSE 0 END) as missed
            ")
            ->first();

        // Latest growth record
        $latestGrowth = PediatricGrowthRecord::where('patient_id', $patientId)
            ->latest('measurement_date')
            ->first();

        // Active allergies
        $allergies = PediatricAllergy::where('patient_id', $patientId)
            ->where('is_active', true)
            ->get();

        // Chronic conditions
        $chronicConditions = PediatricChronicCondition::where('patient_id', $patientId)
            ->where('is_active', true)
            ->get();

        // Upcoming vaccinations
        $upcomingVaccinations = PediatricVaccination::where('patient_id', $patientId)
            ->where('status', PediatricVaccination::STATUS_SCHEDULED)
            ->orderBy('scheduled_date')
            ->limit(5)
            ->get();

        return Inertia::render('Patient/Pediatric/Overview', [
            'visitSummary' => $visitSummary,
            'vaccinationSummary' => $vaccinationSummary,
            'latestGrowth' => $latestGrowth,
            'allergies' => $allergies,
            'chronicConditions' => $chronicConditions,
            'upcomingVaccinations' => $upcomingVaccinations,
        ]);
    }

    /**
     * Vaccination record.
     */
    public function vaccinations(Request $request): Response
    {
        $patientId = $this->patientId($request);

        $vaccinations = PediatricVaccination::where('patient_id', $patientId)
            ->with('doctor:id,name_ar,name_en')
            ->orderBy('scheduled_date')
            ->get();

        return Inertia::render('Patient/Pediatric/Vaccinations', [
            'vaccinations' => $vaccinations,
        ]);
    }

    /**
     * Growth chart data.
     */
    public function growth(Request $request): Response
    {
        $patientId = $this->patientId($request);

        $records = PediatricGrowthRecord::where('patient_id', $patientId)
            ->orderBy('measurement_date')
            ->get();

        return Inertia::render('Patient/Pediatric/Growth', [
            'records' => $records,
        ]);
    }

    /**
     * Visit history for pediatric module.
     */
    public function visits(Request $request): Response
    {
        $patientId = $this->patientId($request);

        $visits = Visit::where('patient_id', $patientId)
            ->where('module', 'pediatric')
            ->with(['doctor:id,name_ar,name_en', 'service:id,name_ar,name_en'])
            ->latest('visit_date')
            ->paginate(15);

        return Inertia::render('Patient/Pediatric/Visits', [
            'visits' => $visits,
        ]);
    }

    /**
     * Download vaccination card PDF.
     */
    public function vaccinationCard(Request $request)
    {
        $patientId = $this->patientId($request);
        $patient = \App\Models\Patient::findOrFail($patientId);

        $vaccinations = PediatricVaccination::where('patient_id', $patientId)
            ->orderBy('scheduled_date')
            ->get();

        $ageMonths = $patient->date_of_birth ? $patient->date_of_birth->diffInMonths(now()) : null;

        $pdf = Pdf::loadView('pdf.pediatric-vaccination-card', [
            'patient' => $patient,
            'vaccinations' => $vaccinations,
            'ageMonths' => $ageMonths,
            'clinicName' => Setting::get('clinic_name', 'Doctorato Polyclinic'),
            'clinicPhone' => Setting::get('clinic_phone', ''),
        ]);
        $pdf->setPaper('A4', 'landscape');

        $filename = 'VaccinationCard-' . ($patient->file_number ?? $patient->id) . '.pdf';
        return $pdf->stream($filename);
    }

    /**
     * Download growth report PDF.
     */
    public function growthReport(Request $request)
    {
        $patientId = $this->patientId($request);
        $patient = \App\Models\Patient::findOrFail($patientId);

        $growthRecords = PediatricGrowthRecord::where('patient_id', $patientId)
            ->orderBy('measurement_date')
            ->get();

        $ageMonths = $patient->date_of_birth ? $patient->date_of_birth->diffInMonths(now()) : null;

        $pdf = Pdf::loadView('pdf.pediatric-growth-report', [
            'patient' => $patient,
            'growthRecords' => $growthRecords,
            'ageMonths' => $ageMonths,
            'clinicName' => Setting::get('clinic_name', 'Doctorato Polyclinic'),
            'clinicPhone' => Setting::get('clinic_phone', ''),
        ]);
        $pdf->setPaper('A4');

        $filename = 'GrowthReport-' . ($patient->file_number ?? $patient->id) . '.pdf';
        return $pdf->stream($filename);
    }
}
