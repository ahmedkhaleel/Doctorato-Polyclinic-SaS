<?php

namespace App\Http\Controllers\Patient;

use App\Models\PatientPhoto;
use App\Models\VisitPhoto;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PatientPhotoController extends BasePatientController
{
    public function index(Request $request): Response
    {
        $patient = $this->patient($request);

        // Get visit photos through visits
        $visitIds = $patient->visits()->pluck('id');
        $visitPhotos = VisitPhoto::whereIn('visit_id', $visitIds)
            ->with('visit:id,visit_date,service_id')
            ->with('visit.service:id,name_en,name_ar')
            ->latest('created_at')
            ->get();

        // Get patient profile photos
        $patientPhotos = $patient->photos()->latest('created_at')->get();

        return Inertia::render('Patient/Photos/Index', [
            'visitPhotos' => $visitPhotos,
            'patientPhotos' => $patientPhotos,
        ]);
    }
}
