<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Doctor;
use App\Models\DoctorPayout;
use App\Models\DoctorSchedule;
use App\Models\DoctorServiceRate;
use App\Models\DoctorVacation;
use App\Models\Leave;
use App\Models\Patient;
use App\Models\Service;
use App\Models\Setting;
use App\Models\Role;
use App\Models\User;
use App\Models\Visit;
use App\Models\Attendance;
use App\Services\AuditLogger;
use App\Http\Requests\StoreDoctorRequest;
use App\Http\Requests\UpdateDoctorRequest;
use App\Services\ModuleManager;
use App\Traits\SanitizesHtml;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

class DoctorController extends Controller
{
    use SanitizesHtml;

    public function index(Request $request): Response
    {
        $doctors = Doctor::query()
            ->whereIn('module', ['derma', 'dental'])
            ->when($request->module, function ($query, $module) {
                $query->where('module', $module);
            })
            ->when($request->search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name_ar', 'like', "%{$search}%")
                      ->orWhere('name_en', 'like', "%{$search}%");
                });
            })
            ->orderBy('display_order')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Admin/Doctors/Index', [
            'doctors' => $doctors,
            'filters' => $request->only(['search', 'module']),
            'modules' => collect(ModuleManager::getForFrontend())->only(['derma', 'dental'])->all(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Doctors/Create', [
            'users' => User::select('id', 'name', 'email')->orderBy('name')->get(),
            'services' => Service::with('category:id,name_en')->select('id', 'name_en', 'name_ar', 'category_id', 'module')->active()->bookable()->orderBy('name_en')->get(),
            'pricingSettings' => [
                'dermatology_consultant_fee' => Setting::get('dermatology_consultant_fee', 0),
                'dermatology_specialist_fee' => Setting::get('dermatology_specialist_fee', 0),
                'cosmetic_consultation_fee' => Setting::get('cosmetic_consultation_fee', 0),
                'followup_fee' => Setting::get('followup_fee', 0),
                'dental_consultant_fee' => Setting::get('dental_consultant_fee', 0),
                'dental_specialist_fee' => Setting::get('dental_specialist_fee', 0),
            ],
            'modules' => collect(ModuleManager::getForFrontend())->only(['derma', 'dental'])->all(),
        ]);
    }

    public function store(StoreDoctorRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $this->sanitizeFields($data, ['bio_ar', 'bio_en']);

        // Auto-create user account if requested
        $createUser = ! empty($data['create_user_account']) && ! empty($data['create_user_password']);
        $createUserPassword = $data['create_user_password'] ?? null;
        unset($data['create_user_account'], $data['create_user_password']);

        if ($request->hasFile('photo')) {
            $data['photo'] = $request->file('photo')->store('uploads/doctors', 'public');
        }

        // Remove nested data before creating
        $schedules = $data['schedules'] ?? [];
        $vacations = $data['vacations'] ?? [];
        $serviceRates = $data['service_rates'] ?? [];
        unset($data['schedules'], $data['vacations'], $data['service_rates']);

        $doctor = DB::transaction(function () use ($data, $createUser, $createUserPassword, $schedules, $vacations, $serviceRates) {
            if ($createUser && ! empty($data['email'])) {
                $doctorRole = Role::where('name', 'doctor')->first();
                $user = User::create([
                    'name' => $data['name_en'],
                    'email' => $data['email'],
                    'password' => Hash::make($createUserPassword),
                    'role_id' => $doctorRole?->id,
                    'is_active' => true,
                ]);
                $data['user_id'] = $user->id;
            }

            $doctor = Doctor::create($data);

            // Save schedules — explicitly cast is_active to boolean
            foreach ($schedules as $schedule) {
                $doctor->schedules()->create([
                    'day_of_week' => (int) $schedule['day_of_week'],
                    'start_time' => $schedule['start_time'],
                    'end_time' => $schedule['end_time'],
                    'is_active' => filter_var($schedule['is_active'] ?? false, FILTER_VALIDATE_BOOLEAN),
                ]);
            }

            // Save vacations
            foreach ($vacations as $vacation) {
                $doctor->vacations()->create($vacation);
            }

            // Save service rates
            foreach ($serviceRates as $rate) {
                $doctor->serviceRates()->create($rate);
            }

            return $doctor;
        });

        AuditLogger::log('created', $doctor);

        $message = 'Doctor created successfully.';
        if ($createUser) {
            $message .= " User account created — doctor can login at /doctor/login with email: {$data['email']}";
        }

        return redirect()->route('admin.doctors.index')->with('success', $message);
    }

    public function show(Doctor $doctor): Response
    {
        $doctor->load([
            'schedules',
            'vacations' => fn ($q) => $q->orderByDesc('start_date'),
            'serviceRates.service:id,name_ar,name_en',
        ]);

        // Performance Stats
        $totalVisits = $doctor->visits()->count();
        $completedVisits = $doctor->visits()->where('status', 'completed')->count();
        $consultationCount = $doctor->visits()->where('visit_type', 'consultation')->where('status', 'completed')->count();
        $sessionCount = $doctor->visits()->where('visit_type', 'session')->where('status', 'completed')->count();

        $totalRevenue = (float) DB::table('visits')
            ->where('visits.doctor_id', $doctor->id)
            ->where('visits.status', 'completed')
            ->join('invoices', 'visits.id', '=', 'invoices.visit_id')
            ->sum('invoices.total');

        $totalCommission = (float) $doctor->visits()
            ->where('status', 'completed')
            ->sum('commission_amount');

        $thisMonthRevenue = (float) DB::table('visits')
            ->where('visits.doctor_id', $doctor->id)
            ->where('visits.status', 'completed')
            ->whereMonth('visits.visit_date', now()->month)
            ->whereYear('visits.visit_date', now()->year)
            ->join('invoices', 'visits.id', '=', 'invoices.visit_id')
            ->sum('invoices.total');

        $performanceStats = [
            'total_visits' => $totalVisits,
            'completed_visits' => $completedVisits,
            'consultations' => $consultationCount,
            'sessions' => $sessionCount,
            'total_revenue' => round($totalRevenue, 2),
            'total_commission' => round($totalCommission, 2),
            'this_month_revenue' => round($thisMonthRevenue, 2),
        ];

        // Visits (latest 20)
        $visits = $doctor->visits()
            ->with(['patient:id,full_name,phone,file_number', 'service:id,name_ar,name_en'])
            ->latest('visit_date')
            ->take(20)
            ->get();

        // Today's Queue
        $todayQueue = $doctor->visits()
            ->with(['patient:id,full_name,phone', 'service:id,name_ar,name_en'])
            ->today()
            ->orderBy('created_at')
            ->get();

        // Unique Patients
        $patients = DB::table('visits')
            ->where('doctor_id', $doctor->id)
            ->join('patients', 'visits.patient_id', '=', 'patients.id')
            ->select(
                'patients.id',
                'patients.full_name',
                'patients.phone',
                'patients.file_number',
                DB::raw('COUNT(visits.id) as visit_count'),
                DB::raw('MAX(visits.visit_date) as last_visit_date')
            )
            ->groupBy('patients.id', 'patients.full_name', 'patients.phone', 'patients.file_number')
            ->orderByDesc('last_visit_date')
            ->get();

        // Prescriptions
        $prescriptions = $doctor->prescriptions()
            ->with(['patient:id,full_name', 'items', 'visit:id,visit_date'])
            ->latest()
            ->take(20)
            ->get();

        // Monthly Commission (last 12 months)
        $monthlyCommission = DB::table('visits')
            ->where('doctor_id', $doctor->id)
            ->where('status', 'completed')
            ->where('visit_date', '>=', now()->subMonths(12)->startOfMonth())
            ->select(
                DB::raw("DATE_FORMAT(visit_date, '%Y-%m') as month"),
                DB::raw('SUM(commission_amount) as commission'),
                DB::raw('COUNT(*) as visit_count')
            )
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        // Monthly Revenue (last 12 months)
        $monthlyRevenue = DB::table('visits')
            ->where('visits.doctor_id', $doctor->id)
            ->where('visits.status', 'completed')
            ->where('visits.visit_date', '>=', now()->subMonths(12)->startOfMonth())
            ->join('invoices', 'visits.id', '=', 'invoices.visit_id')
            ->select(
                DB::raw("DATE_FORMAT(visits.visit_date, '%Y-%m') as month"),
                DB::raw('SUM(invoices.total) as revenue')
            )
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        // Leaves (via user_id)
        $leaves = $doctor->user_id
            ? Leave::where('user_id', $doctor->user_id)
                ->with('approver:id,name')
                ->orderByDesc('start_date')
                ->get()
            : collect();

        // Bookings for this doctor
        $bookings = Booking::where('doctor_id', $doctor->id)
            ->with('service:id,name_ar,name_en')
            ->latest()
            ->take(20)
            ->get();

        // Commission Statement — detailed per-visit breakdown
        $commissionStatement = DB::table('visits')
            ->where('visits.doctor_id', $doctor->id)
            ->where('visits.status', 'completed')
            ->whereNotNull('visits.commission_amount')
            ->where('visits.commission_amount', '>', 0)
            ->leftJoin('invoices', 'visits.id', '=', 'invoices.visit_id')
            ->leftJoin('patients', 'visits.patient_id', '=', 'patients.id')
            ->leftJoin('services', 'visits.service_id', '=', 'services.id')
            ->select(
                'visits.id',
                'visits.visit_date',
                'visits.visit_type',
                'visits.commission_rate',
                'visits.commission_amount',
                'patients.full_name as patient_name',
                'patients.id as patient_id',
                'services.name_en as service_name',
                'services.supply_cost',
                'invoices.total as invoice_total',
            )
            ->orderByDesc('visits.visit_date')
            ->take(50)
            ->get();

        // This month commission
        $thisMonthCommission = (float) $doctor->visits()
            ->where('status', 'completed')
            ->whereMonth('visit_date', now()->month)
            ->whereYear('visit_date', now()->year)
            ->sum('commission_amount');

        $performanceStats['this_month_commission'] = round($thisMonthCommission, 2);

        // All patients for prescription creation dropdown
        $allPatients = Patient::select('id', 'full_name', 'phone', 'file_number')
            ->orderBy('full_name')
            ->get();

        // Doctor Payout Summary
        $payoutSummary = [
            'total_paid' => round((float) DoctorPayout::where('doctor_id', $doctor->id)->paid()->sum('net_amount'), 2),
            'total_pending' => round((float) DoctorPayout::where('doctor_id', $doctor->id)->confirmed()->sum('net_amount'), 2),
            'total_unpaid' => round(max($totalCommission - (float) DoctorPayout::where('doctor_id', $doctor->id)->active()->sum('net_amount'), 0), 2),
        ];

        // Recent Payouts (last 5)
        $recentPayouts = DoctorPayout::where('doctor_id', $doctor->id)
            ->select('id', 'payout_number', 'period_start', 'period_end', 'net_amount', 'status', 'paid_at', 'created_at')
            ->latest()
            ->take(5)
            ->get();

        // Attendance Records (last 3 months)
        $attendanceRecords = $doctor->user_id
            ? Attendance::where('user_id', $doctor->user_id)
                ->where('date', '>=', now()->subMonths(3)->startOfMonth())
                ->orderByDesc('date')
                ->get()
            : collect();

        $attendanceSummary = [
            'present' => $attendanceRecords->where('status', 'present')->count(),
            'absent' => $attendanceRecords->where('status', 'absent')->count(),
            'late' => $attendanceRecords->where('status', 'late')->count(),
            'leave' => $attendanceRecords->where('status', 'leave')->count(),
            'overtime_hours' => round((float) $attendanceRecords->sum('overtime_hours'), 1),
        ];

        return Inertia::render('Admin/Doctors/Show', [
            'doctor' => $doctor,
            'performanceStats' => $performanceStats,
            'visits' => $visits,
            'todayQueue' => $todayQueue,
            'patients' => $patients,
            'prescriptions' => $prescriptions,
            'monthlyCommission' => $monthlyCommission,
            'monthlyRevenue' => $monthlyRevenue,
            'leaves' => $leaves,
            'bookings' => $bookings,
            'commissionStatement' => $commissionStatement,
            'allPatients' => $allPatients,
            'payoutSummary' => $payoutSummary,
            'recentPayouts' => $recentPayouts,
            'attendanceRecords' => $attendanceRecords,
            'attendanceSummary' => $attendanceSummary,
        ]);
    }

    public function edit(Doctor $doctor): Response
    {
        $doctor->load(['schedules', 'vacations', 'serviceRates.service:id,name_en,name_ar']);

        return Inertia::render('Admin/Doctors/Edit', [
            'doctor' => $doctor,
            'users' => User::select('id', 'name', 'email')->orderBy('name')->get(),
            'services' => Service::with('category:id,name_en')->select('id', 'name_en', 'name_ar', 'category_id', 'module')->active()->bookable()->orderBy('name_en')->get(),
            'pricingSettings' => [
                'dermatology_consultant_fee' => Setting::get('dermatology_consultant_fee', 0),
                'dermatology_specialist_fee' => Setting::get('dermatology_specialist_fee', 0),
                'cosmetic_consultation_fee' => Setting::get('cosmetic_consultation_fee', 0),
                'followup_fee' => Setting::get('followup_fee', 0),
                'dental_consultant_fee' => Setting::get('dental_consultant_fee', 0),
                'dental_specialist_fee' => Setting::get('dental_specialist_fee', 0),
            ],
            'modules' => collect(ModuleManager::getForFrontend())->only(['derma', 'dental'])->all(),
        ]);
    }

    public function update(UpdateDoctorRequest $request, Doctor $doctor): RedirectResponse
    {
        $data = $request->validated();

        $this->sanitizeFields($data, ['bio_ar', 'bio_en']);

        if ($request->hasFile('photo')) {
            $data['photo'] = $request->file('photo')->store('uploads/doctors', 'public');
        }

        // Extract nested data
        $schedules = $data['schedules'] ?? null;
        $vacations = $data['vacations'] ?? null;
        $serviceRates = $data['service_rates'] ?? null;
        unset($data['schedules'], $data['vacations'], $data['service_rates']);

        DB::transaction(function () use ($doctor, $data, $schedules, $vacations, $serviceRates) {
            $doctor->update($data);

            // Sync schedules (replace all 7 days)
            if ($schedules !== null) {
                $doctor->schedules()->delete();
                foreach ($schedules as $schedule) {
                    $doctor->schedules()->create([
                        'day_of_week' => (int) $schedule['day_of_week'],
                        'start_time' => $schedule['start_time'],
                        'end_time' => $schedule['end_time'],
                        'is_active' => filter_var($schedule['is_active'] ?? false, FILTER_VALIDATE_BOOLEAN),
                    ]);
                }
            }

            // Sync vacations (replace all)
            if ($vacations !== null) {
                $doctor->vacations()->delete();
                foreach ($vacations as $vacation) {
                    unset($vacation['id']);
                    $doctor->vacations()->create($vacation);
                }
            }

            // Sync service rates (replace all)
            if ($serviceRates !== null) {
                $doctor->serviceRates()->delete();
                foreach ($serviceRates as $rate) {
                    $doctor->serviceRates()->create($rate);
                }
            }
        });

        AuditLogger::log('updated', $doctor);

        return redirect()->route('admin.doctors.show', $doctor)->with('success', 'Doctor updated successfully.');
    }

    public function destroy(Doctor $doctor): RedirectResponse
    {
        AuditLogger::log('deleted', $doctor);
        $doctor->delete();

        return redirect()->route('admin.doctors.index')->with('success', 'Doctor deleted successfully.');
    }

    /**
     * Quick-create a User account for a doctor who doesn't have one yet.
     */
    public function createUserAccount(Request $request, Doctor $doctor): RedirectResponse
    {
        if ($doctor->user_id) {
            return back()->with('error', 'This doctor already has a linked user account.');
        }

        $data = $request->validate([
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
            'name' => 'required|string|max:255',
        ]);

        DB::transaction(function () use ($data, $doctor) {
            $doctorRole = Role::where('name', 'doctor')->first();

            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'role_id' => $doctorRole?->id,
                'is_active' => true,
            ]);

            $doctor->update(['user_id' => $user->id]);
        });

        AuditLogger::log('updated', $doctor, ['action' => 'Created and linked user account', 'user_id' => $doctor->user_id]);

        return back()->with('success', "User account created successfully. Doctor can now login at /doctor/login with email: {$data['email']}");
    }
}
