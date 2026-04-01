<?php

namespace App\Http\Controllers\Secretary;

use App\Models\Doctor;
use App\Models\Visit;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SecretaryQueueController extends BaseSecretaryController
{
    public function index(Request $request): Response
    {
        $visits = Visit::with(['patient', 'service', 'dentalTreatments:id,visit_id,treatment_type,tooth_number'])
            ->today()
            ->orderBy('created_at')
            ->get();

        // Build medical risk alerts for dental patients in today's queue
        $medicalAlerts = [];
        foreach ($visits as $visit) {
            if ($visit->module === 'dental' && $visit->patient) {
                $riskFlags = $visit->patient->getDentalRiskFlags();
                if (!empty($riskFlags)) {
                    $medicalAlerts[$visit->id] = $riskFlags;
                }
            }
        }

        $visitsByDoctor = $visits->groupBy('doctor_id');

        $doctors = Doctor::active()->get();

        return Inertia::render('Secretary/Queue/Index', [
            'visitsByDoctor' => $visitsByDoctor,
            'doctors' => $doctors,
            'medicalAlerts' => $medicalAlerts,
        ]);
    }
}
