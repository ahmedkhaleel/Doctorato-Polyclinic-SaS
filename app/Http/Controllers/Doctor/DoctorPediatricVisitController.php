<?php
namespace App\Http\Controllers\Doctor;

use App\Models\Visit;
use App\Models\Patient;
use App\Models\PediatricGrowthRecord;
use App\Models\PediatricAllergy;
use Illuminate\Http\Request;
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

        // Age at visit
        $ageMonths = $patient->date_of_birth
            ? $patient->date_of_birth->diffInMonths($visit->visit_date ?? now())
            : null;

        return Inertia::render('Doctor/Pediatric/Visits/Show', [
            'visit' => $visit,
            'allergies' => $allergies,
            'latestGrowth' => $latestGrowth,
            'ageMonths' => $ageMonths,
        ]);
    }
}
