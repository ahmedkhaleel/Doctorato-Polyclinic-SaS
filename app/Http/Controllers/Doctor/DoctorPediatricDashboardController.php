<?php
namespace App\Http\Controllers\Doctor;

use App\Models\Patient;
use App\Models\Visit;
use App\Models\PediatricVaccination;
use App\Models\PediatricGrowthRecord;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DoctorPediatricDashboardController extends BaseDoctorController
{
    public function index(Request $request)
    {
        $doctorId = $this->doctorId($request);

        // Today's pediatric visits
        $todayVisits = Visit::where('doctor_id', $doctorId)
            ->where('module', 'pediatric')
            ->whereDate('visit_date', today())
            ->with('patient:id,full_name,date_of_birth,gender,photo,guardian_name')
            ->orderBy('scheduled_time')
            ->get();

        // Stats
        $totalPatients = Visit::where('doctor_id', $doctorId)
            ->where('module', 'pediatric')
            ->distinct('patient_id')
            ->count('patient_id');

        $monthlyVisits = Visit::where('doctor_id', $doctorId)
            ->where('module', 'pediatric')
            ->whereMonth('visit_date', now()->month)
            ->whereYear('visit_date', now()->year)
            ->count();

        $completedToday = Visit::where('doctor_id', $doctorId)
            ->where('module', 'pediatric')
            ->whereDate('visit_date', today())
            ->where('status', 'completed')
            ->count();

        // Overdue vaccinations (patients of this doctor)
        $overdueVaccinations = PediatricVaccination::whereHas('patient', function ($q) use ($doctorId) {
                $q->whereHas('visits', function ($vq) use ($doctorId) {
                    $vq->where('doctor_id', $doctorId)->where('module', 'pediatric');
                });
            })
            ->where('status', 'scheduled')
            ->where('scheduled_date', '<', today())
            ->with('patient:id,full_name,date_of_birth,phone,guardian_name,guardian_phone')
            ->orderBy('scheduled_date')
            ->limit(10)
            ->get();

        // Recent growth alerts (underweight/overweight)
        $growthAlerts = PediatricGrowthRecord::whereHas('patient', function ($q) use ($doctorId) {
                $q->whereHas('visits', function ($vq) use ($doctorId) {
                    $vq->where('doctor_id', $doctorId)->where('module', 'pediatric');
                });
            })
            ->where(function ($q) {
                $q->where('weight_percentile', '<', 3)
                  ->orWhere('weight_percentile', '>', 97)
                  ->orWhere('height_percentile', '<', 3);
            })
            ->with('patient:id,full_name,date_of_birth,gender')
            ->orderByDesc('measurement_date')
            ->limit(5)
            ->get();

        // Upcoming vaccinations (next 7 days)
        $upcomingVaccinations = PediatricVaccination::whereHas('patient', function ($q) use ($doctorId) {
                $q->whereHas('visits', function ($vq) use ($doctorId) {
                    $vq->where('doctor_id', $doctorId)->where('module', 'pediatric');
                });
            })
            ->where('status', 'scheduled')
            ->whereBetween('scheduled_date', [today(), today()->addDays(7)])
            ->with('patient:id,full_name,date_of_birth,guardian_phone')
            ->orderBy('scheduled_date')
            ->limit(10)
            ->get();

        // Visit trend (last 7 days)
        $visitTrend = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $visitTrend[] = [
                'date' => $date->format('D'),
                'value' => Visit::where('doctor_id', $doctorId)
                    ->where('module', 'pediatric')
                    ->whereDate('visit_date', $date)
                    ->count(),
            ];
        }

        // Age distribution of patients
        $ageDistribution = [
            'newborn' => 0,  // 0-1 month
            'infant' => 0,   // 1-12 months
            'toddler' => 0,  // 1-3 years
            'preschool' => 0, // 3-6 years
            'school' => 0,   // 6-12 years
            'adolescent' => 0, // 12-18 years
        ];

        $patientIds = Visit::where('doctor_id', $doctorId)
            ->where('module', 'pediatric')
            ->distinct()
            ->pluck('patient_id');

        $patients = Patient::whereIn('id', $patientIds)
            ->whereNotNull('date_of_birth')
            ->get(['id', 'date_of_birth']);

        foreach ($patients as $p) {
            $ageMonths = $p->date_of_birth->diffInMonths(now());
            if ($ageMonths <= 1) $ageDistribution['newborn']++;
            elseif ($ageMonths <= 12) $ageDistribution['infant']++;
            elseif ($ageMonths <= 36) $ageDistribution['toddler']++;
            elseif ($ageMonths <= 72) $ageDistribution['preschool']++;
            elseif ($ageMonths <= 144) $ageDistribution['school']++;
            else $ageDistribution['adolescent']++;
        }

        // Recent growth records for dashboard chart
        $recentGrowthRecords = PediatricGrowthRecord::whereHas('patient', function ($q) use ($doctorId) {
                $q->whereHas('visits', function ($vq) use ($doctorId) {
                    $vq->where('doctor_id', $doctorId)->where('module', 'pediatric');
                });
            })
            ->orderByDesc('measurement_date')
            ->limit(30)
            ->get();

        return Inertia::render('Doctor/Pediatric/Dashboard', [
            'todayVisits' => $todayVisits,
            'stats' => [
                'totalPatients' => $totalPatients,
                'monthlyVisits' => $monthlyVisits,
                'completedToday' => $completedToday,
                'todayTotal' => $todayVisits->count(),
                'waitingCount' => $todayVisits->where('status', 'waiting')->count(),
            ],
            'overdueVaccinations' => $overdueVaccinations,
            'growthAlerts' => $growthAlerts,
            'upcomingVaccinations' => $upcomingVaccinations,
            'visitTrend' => $visitTrend,
            'ageDistribution' => $ageDistribution,
            'recentGrowthRecords' => $recentGrowthRecords,
        ]);
    }
}
