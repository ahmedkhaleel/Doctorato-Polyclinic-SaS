<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\Setting;
use App\Models\Visit;
use App\Models\Doctor;
use App\Models\PediatricGrowthRecord;
use App\Models\PediatricVaccination;
use App\Models\PediatricMilestone;
use App\Models\PediatricAllergy;
use App\Models\PediatricChronicCondition;
use App\Models\PediatricScreeningTest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class AdminPediatricController extends Controller
{
    /**
     * Pediatric Dashboard / Statistics
     */
    public function dashboard(Request $request)
    {
        // Patient IDs with pediatric visits
        $pediatricPatientIds = Visit::where('module', 'pediatric')
            ->distinct()
            ->pluck('patient_id');

        $totalPatients = $pediatricPatientIds->count();

        // Age distribution
        $ageDistribution = [
            'newborn' => 0, 'infant' => 0, 'toddler' => 0,
            'preschool' => 0, 'school' => 0, 'adolescent' => 0,
        ];

        $patients = Patient::whereIn('id', $pediatricPatientIds)
            ->whereNotNull('date_of_birth')
            ->get(['id', 'date_of_birth', 'gender']);

        foreach ($patients as $p) {
            $months = $p->date_of_birth->diffInMonths(now());
            if ($months <= 1) $ageDistribution['newborn']++;
            elseif ($months <= 12) $ageDistribution['infant']++;
            elseif ($months <= 36) $ageDistribution['toddler']++;
            elseif ($months <= 72) $ageDistribution['preschool']++;
            elseif ($months <= 144) $ageDistribution['school']++;
            else $ageDistribution['adolescent']++;
        }

        // Gender distribution
        $genderDistribution = [
            'male' => $patients->where('gender', 'male')->count(),
            'female' => $patients->where('gender', 'female')->count(),
        ];

        // Visit stats
        $totalVisits = Visit::where('module', 'pediatric')->count();
        $thisMonthVisits = Visit::where('module', 'pediatric')
            ->whereMonth('visit_date', now()->month)
            ->whereYear('visit_date', now()->year)
            ->count();
        $lastMonthVisits = Visit::where('module', 'pediatric')
            ->whereMonth('visit_date', now()->subMonth()->month)
            ->whereYear('visit_date', now()->subMonth()->year)
            ->count();

        // Monthly visits trend (last 12 months)
        $monthlyTrend = [];
        for ($i = 11; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $monthlyTrend[] = [
                'month' => $date->format('M Y'),
                'monthShort' => $date->format('M'),
                'count' => Visit::where('module', 'pediatric')
                    ->whereMonth('visit_date', $date->month)
                    ->whereYear('visit_date', $date->year)
                    ->count(),
            ];
        }

        // Vaccination coverage
        $totalVaccinations = PediatricVaccination::count();
        $givenVaccinations = PediatricVaccination::where('status', 'given')->count();
        $overdueVaccinations = PediatricVaccination::where('status', 'scheduled')
            ->where('scheduled_date', '<', today())->count();
        $vaccineCoverage = $totalVaccinations > 0
            ? round(($givenVaccinations / $totalVaccinations) * 100, 1)
            : 0;

        // Top vaccines by administration
        $topVaccines = PediatricVaccination::select('vaccine_name', DB::raw('count(*) as total'),
                DB::raw('sum(case when status = "given" then 1 else 0 end) as given_count'))
            ->groupBy('vaccine_name')
            ->orderByDesc('total')
            ->limit(10)
            ->get();

        // Growth alerts
        $growthAlerts = [
            'underweight' => PediatricGrowthRecord::where('weight_percentile', '<', 3)->distinct('patient_id')->count('patient_id'),
            'overweight' => PediatricGrowthRecord::where('weight_percentile', '>', 97)->distinct('patient_id')->count('patient_id'),
            'stunted' => PediatricGrowthRecord::where('height_percentile', '<', 3)->distinct('patient_id')->count('patient_id'),
        ];

        // Milestone statistics
        $milestoneStats = [
            'total' => PediatricMilestone::count(),
            'achieved' => PediatricMilestone::where('status', 'achieved')->count(),
            'emerging' => PediatricMilestone::where('status', 'emerging')->count(),
            'not_achieved' => PediatricMilestone::where('status', 'not_achieved')->count(),
        ];

        // Allergy statistics
        $allergyTypes = PediatricAllergy::where('is_active', true)
            ->select('allergy_type', DB::raw('count(*) as count'))
            ->groupBy('allergy_type')
            ->get();

        // Common conditions
        $chronicStats = PediatricChronicCondition::where('is_active', true)
            ->select('condition_type', DB::raw('count(*) as count'))
            ->groupBy('condition_type')
            ->orderByDesc('count')
            ->limit(8)
            ->get();

        // Screening test stats
        $screeningStats = PediatricScreeningTest::select('test_type', 'result', DB::raw('count(*) as count'))
            ->groupBy('test_type', 'result')
            ->get()
            ->groupBy('test_type');

        // Top doctors by pediatric visits
        $topDoctors = Visit::where('module', 'pediatric')
            ->select('doctor_id', DB::raw('count(*) as visit_count'))
            ->groupBy('doctor_id')
            ->orderByDesc('visit_count')
            ->limit(5)
            ->with('doctor:id,name_en,name_ar')
            ->get();

        // Recent registrations (last 10)
        $recentPatients = Patient::whereIn('id', $pediatricPatientIds)
            ->orderByDesc('created_at')
            ->limit(10)
            ->get(['id', 'full_name', 'date_of_birth', 'gender', 'file_number', 'guardian_name', 'created_at']);

        // ── Revenue stats ──
        $revenueThisMonth = Invoice::where('module', 'pediatric')
            ->whereMonth('invoice_date', now()->month)
            ->whereYear('invoice_date', now()->year)
            ->sum('total');
        $revenueLastMonth = Invoice::where('module', 'pediatric')
            ->whereMonth('invoice_date', now()->subMonth()->month)
            ->whereYear('invoice_date', now()->subMonth()->year)
            ->sum('total');
        $collectedThisMonth = Invoice::where('module', 'pediatric')
            ->whereMonth('invoice_date', now()->month)
            ->whereYear('invoice_date', now()->year)
            ->sum('paid_amount');

        // Monthly revenue trend (last 12 months)
        $revenueTrend = [];
        for ($i = 11; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $monthTotal = Invoice::where('module', 'pediatric')
                ->whereMonth('invoice_date', $date->month)
                ->whereYear('invoice_date', $date->year)
                ->sum('total');
            $monthCollected = Invoice::where('module', 'pediatric')
                ->whereMonth('invoice_date', $date->month)
                ->whereYear('invoice_date', $date->year)
                ->sum('paid_amount');
            $revenueTrend[] = [
                'month' => $date->format('M Y'),
                'monthShort' => $date->format('M'),
                'total' => round($monthTotal, 2),
                'collected' => round($monthCollected, 2),
            ];
        }

        // Revenue by doctor
        $revenueByDoctor = Invoice::where('invoices.module', 'pediatric')
            ->whereMonth('invoice_date', now()->month)
            ->whereYear('invoice_date', now()->year)
            ->join('visits', 'invoices.visit_id', '=', 'visits.id')
            ->join('doctors', 'visits.doctor_id', '=', 'doctors.id')
            ->select(
                'doctors.id as doctor_id',
                'doctors.name_en',
                'doctors.name_ar',
                DB::raw('SUM(invoices.total) as total'),
                DB::raw('SUM(invoices.paid_amount) as collected'),
                DB::raw('COUNT(invoices.id) as invoice_count')
            )
            ->groupBy('doctors.id', 'doctors.name_en', 'doctors.name_ar')
            ->orderByDesc('total')
            ->get();

        return Inertia::render('Admin/Pediatric/Dashboard', [
            'stats' => [
                'totalPatients' => $totalPatients,
                'totalVisits' => $totalVisits,
                'thisMonthVisits' => $thisMonthVisits,
                'lastMonthVisits' => $lastMonthVisits,
                'vaccineCoverage' => $vaccineCoverage,
                'totalVaccinations' => $totalVaccinations,
                'givenVaccinations' => $givenVaccinations,
                'overdueVaccinations' => $overdueVaccinations,
                'revenueThisMonth' => round($revenueThisMonth, 2),
                'revenueLastMonth' => round($revenueLastMonth, 2),
                'collectedThisMonth' => round($collectedThisMonth, 2),
            ],
            'ageDistribution' => $ageDistribution,
            'genderDistribution' => $genderDistribution,
            'monthlyTrend' => $monthlyTrend,
            'revenueTrend' => $revenueTrend,
            'revenueByDoctor' => $revenueByDoctor,
            'topVaccines' => $topVaccines,
            'growthAlerts' => $growthAlerts,
            'milestoneStats' => $milestoneStats,
            'allergyTypes' => $allergyTypes,
            'chronicStats' => $chronicStats,
            'screeningStats' => $screeningStats,
            'topDoctors' => $topDoctors,
            'recentPatients' => $recentPatients,
        ]);
    }

    /**
     * Patients list for admin
     */
    public function patients(Request $request)
    {
        $search = $request->input('search');

        $pediatricPatientIds = Visit::where('module', 'pediatric')
            ->distinct()
            ->pluck('patient_id');

        $patients = Patient::whereIn('id', $pediatricPatientIds)
            ->when($search, function ($q) use ($search) {
                $q->where(function ($sq) use ($search) {
                    $sq->where('full_name', 'like', "%{$search}%")
                       ->orWhere('phone', 'like', "%{$search}%")
                       ->orWhere('file_number', 'like', "%{$search}%")
                       ->orWhere('guardian_name', 'like', "%{$search}%");
                });
            })
            ->withCount(['visits as pediatric_visits_count' => fn($q) => $q->where('module', 'pediatric')])
            ->withCount(['pediatricVaccinations as vaccines_given' => fn($q) => $q->where('status', 'given')])
            ->withCount('pediatricVaccinations as vaccines_total')
            ->withCount('pediatricAllergies as active_allergies')
            ->orderByDesc('updated_at')
            ->paginate(25)
            ->withQueryString();

        return Inertia::render('Admin/Pediatric/Patients', [
            'patients' => $patients,
            'filters' => ['search' => $search],
        ]);
    }

    /**
     * Vaccinations overview
     */
    public function vaccinations(Request $request)
    {
        $search = $request->input('search');
        $status = $request->input('status');

        $vaccinations = PediatricVaccination::with('patient:id,full_name,date_of_birth,file_number,guardian_phone')
            ->when($search, function ($q) use ($search) {
                $q->where(function ($sq) use ($search) {
                    $sq->where('vaccine_name', 'like', "%{$search}%")
                       ->orWhereHas('patient', function ($pq) use ($search) {
                            $pq->where('full_name', 'like', "%{$search}%")
                               ->orWhere('file_number', 'like', "%{$search}%");
                        });
                });
            })
            ->when($status, fn($q) => $q->where('status', $status))
            ->orderBy('scheduled_date')
            ->paginate(30)
            ->withQueryString();

        $stats = [
            'total' => PediatricVaccination::count(),
            'given' => PediatricVaccination::where('status', 'given')->count(),
            'scheduled' => PediatricVaccination::where('status', 'scheduled')->where('scheduled_date', '>=', today())->count(),
            'overdue' => PediatricVaccination::where('status', 'scheduled')->where('scheduled_date', '<', today())->count(),
            'missed' => PediatricVaccination::where('status', 'missed')->count(),
        ];

        return Inertia::render('Admin/Pediatric/Vaccinations', [
            'vaccinations' => $vaccinations,
            'stats' => $stats,
            'filters' => ['search' => $search, 'status' => $status],
        ]);
    }

    /**
     * Visits overview
     */
    public function visits(Request $request)
    {
        $search = $request->input('search');
        $status = $request->input('status');
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        $visits = Visit::where('module', 'pediatric')
            ->with(['patient:id,full_name,date_of_birth,gender,file_number,guardian_name', 'doctor:id,name_en,name_ar'])
            ->when($search, function ($q) use ($search) {
                $q->where(function ($sq) use ($search) {
                    $sq->whereHas('patient', function ($pq) use ($search) {
                        $pq->where('full_name', 'like', "%{$search}%")
                           ->orWhere('file_number', 'like', "%{$search}%")
                           ->orWhere('guardian_name', 'like', "%{$search}%");
                    })->orWhereHas('doctor', function ($dq) use ($search) {
                        $dq->where('name_en', 'like', "%{$search}%")
                           ->orWhere('name_ar', 'like', "%{$search}%");
                    });
                });
            })
            ->when($status, fn($q) => $q->where('status', $status))
            ->when($dateFrom, fn($q) => $q->whereDate('visit_date', '>=', $dateFrom))
            ->when($dateTo, fn($q) => $q->whereDate('visit_date', '<=', $dateTo))
            ->orderByDesc('visit_date')
            ->paginate(25)
            ->withQueryString();

        $stats = [
            'total' => Visit::where('module', 'pediatric')->count(),
            'today' => Visit::where('module', 'pediatric')->whereDate('visit_date', today())->count(),
            'completed' => Visit::where('module', 'pediatric')->where('status', 'completed')->count(),
            'waiting' => Visit::where('module', 'pediatric')->whereDate('visit_date', today())->where('status', 'waiting')->count(),
        ];

        return Inertia::render('Admin/Pediatric/Visits', [
            'visits' => $visits,
            'stats' => $stats,
            'filters' => ['search' => $search, 'status' => $status, 'date_from' => $dateFrom, 'date_to' => $dateTo],
        ]);
    }

    /**
     * Growth monitoring overview
     */
    public function growth(Request $request)
    {
        $records = PediatricGrowthRecord::with('patient:id,full_name,date_of_birth,gender,file_number')
            ->orderByDesc('measurement_date')
            ->paginate(30)
            ->withQueryString();

        $alerts = [
            'underweight' => PediatricGrowthRecord::where('weight_percentile', '<', 3)
                ->with('patient:id,full_name,date_of_birth,file_number')
                ->orderByDesc('measurement_date')
                ->limit(10)->get(),
            'overweight' => PediatricGrowthRecord::where('weight_percentile', '>', 97)
                ->with('patient:id,full_name,date_of_birth,file_number')
                ->orderByDesc('measurement_date')
                ->limit(10)->get(),
            'stunted' => PediatricGrowthRecord::where('height_percentile', '<', 3)
                ->with('patient:id,full_name,date_of_birth,file_number')
                ->orderByDesc('measurement_date')
                ->limit(10)->get(),
        ];

        return Inertia::render('Admin/Pediatric/Growth', [
            'records' => $records,
            'alerts' => $alerts,
        ]);
    }

    /**
     * Pediatric module settings page.
     */
    public function settings()
    {
        $settings = [
            'pediatric_consultation_fee' => Setting::get('pediatric_consultation_fee', '0'),
            'pediatric_followup_fee' => Setting::get('pediatric_followup_fee', '0'),
            'pediatric_vaccination_reminder_days' => Setting::get('pediatric_vaccination_reminder_days', '7'),
            'pediatric_overdue_reminder_interval' => Setting::get('pediatric_overdue_reminder_interval', '14'),
            'pediatric_sms_reminders_enabled' => Setting::get('pediatric_sms_reminders_enabled', '1'),
            'pediatric_growth_alert_low_percentile' => Setting::get('pediatric_growth_alert_low_percentile', '3'),
            'pediatric_growth_alert_high_percentile' => Setting::get('pediatric_growth_alert_high_percentile', '97'),
            'pediatric_max_age_years' => Setting::get('pediatric_max_age_years', '18'),
            'pediatric_default_vaccine_schedule' => Setting::get('pediatric_default_vaccine_schedule', 'who_iraq'),
        ];

        return Inertia::render('Admin/Pediatric/Settings', [
            'settings' => $settings,
        ]);
    }

    /**
     * Update pediatric module settings.
     */
    public function updateSettings(Request $request)
    {
        $validated = $request->validate([
            'pediatric_consultation_fee' => 'required|numeric|min:0',
            'pediatric_followup_fee' => 'required|numeric|min:0',
            'pediatric_vaccination_reminder_days' => 'required|integer|min:1|max:30',
            'pediatric_overdue_reminder_interval' => 'required|integer|min:7|max:60',
            'pediatric_sms_reminders_enabled' => 'required|in:0,1',
            'pediatric_growth_alert_low_percentile' => 'required|numeric|min:1|max:25',
            'pediatric_growth_alert_high_percentile' => 'required|numeric|min:75|max:99',
            'pediatric_max_age_years' => 'required|integer|min:12|max:21',
            'pediatric_default_vaccine_schedule' => 'required|in:who_iraq,who_standard,custom',
        ]);

        foreach ($validated as $key => $value) {
            Setting::set($key, $value);
        }

        return redirect()->back()->with('success', 'Pediatric settings updated successfully');
    }
}
