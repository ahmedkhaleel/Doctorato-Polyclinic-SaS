<?php

namespace App\Http\Controllers\Webmaster;

use App\Http\Controllers\Admin\DoctorController as AdminDoctorController;
use App\Models\Booking;
use App\Models\Doctor;
use App\Models\Leave;
use App\Models\Patient;
use App\Models\Role;
use App\Models\Service;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

class DoctorController extends AdminDoctorController
{
    public function index(Request $request): Response
    {
        $doctors = Doctor::query()
            ->when($request->search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name_ar', 'like', "%{$search}%")
                      ->orWhere('name_en', 'like', "%{$search}%");
                });
            })
            ->orderBy('display_order')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Webmaster/Doctors/Index', [
            'doctors' => $doctors,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Webmaster/Doctors/Create', [
            'users' => User::select('id', 'name', 'email')->orderBy('name')->get(),
            'services' => Service::select('id', 'name_en', 'name_ar')->active()->orderBy('name_en')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name_ar' => 'required|string|max:255',
            'name_en' => 'required|string|max:255',
            'photo' => 'nullable|image|mimes:jpg,jpeg,png,webp,gif|max:20480',
            'specialization_ar' => 'required|string|max:255',
            'specialization_en' => 'required|string|max:255',
            'bio_ar' => 'nullable|string',
            'bio_en' => 'nullable|string',
            'qualifications_ar' => 'nullable|string',
            'qualifications_en' => 'nullable|string',
            'display_order' => 'nullable|integer',
            'status' => 'required|in:active,inactive',
            'user_id' => 'nullable|exists:users,id',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'consultation_fee' => 'nullable|numeric|min:0',
            'default_commission_percentage' => 'nullable|numeric|min:0|max:100',
            'clinic_notes' => 'nullable|string',
            'create_user_account' => 'nullable|boolean',
            'create_user_password' => 'nullable|required_if:create_user_account,true|string|min:6',
            'schedules' => 'nullable|array',
            'schedules.*.day_of_week' => 'required_with:schedules|integer|min:0|max:6',
            'schedules.*.start_time' => 'required_with:schedules|string',
            'schedules.*.end_time' => 'required_with:schedules|string',
            'schedules.*.is_active' => 'nullable',
            'vacations' => 'nullable|array',
            'vacations.*.start_date' => 'required_with:vacations|date',
            'vacations.*.end_date' => 'required_with:vacations|date',
            'vacations.*.reason' => 'nullable|string',
            'service_rates' => 'nullable|array',
            'service_rates.*.service_id' => 'required_with:service_rates|exists:services,id',
            'service_rates.*.commission_percentage' => 'required_with:service_rates|numeric|min:0|max:100',
        ]);

        $this->sanitizeFields($data, ['bio_ar', 'bio_en']);

        $createUser = ! empty($data['create_user_account']) && ! empty($data['create_user_password']);
        $createUserPassword = $data['create_user_password'] ?? null;
        unset($data['create_user_account'], $data['create_user_password']);

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

        if ($request->hasFile('photo')) {
            $data['photo'] = $request->file('photo')->store('uploads/doctors', 'public');
        }

        $schedules = $data['schedules'] ?? [];
        $vacations = $data['vacations'] ?? [];
        $serviceRates = $data['service_rates'] ?? [];
        unset($data['schedules'], $data['vacations'], $data['service_rates']);

        $doctor = Doctor::create($data);

        foreach ($schedules as $schedule) {
            $doctor->schedules()->create([
                'day_of_week' => (int) $schedule['day_of_week'],
                'start_time' => $schedule['start_time'],
                'end_time' => $schedule['end_time'],
                'is_active' => filter_var($schedule['is_active'] ?? false, FILTER_VALIDATE_BOOLEAN),
            ]);
        }

        foreach ($vacations as $vacation) {
            $doctor->vacations()->create($vacation);
        }

        foreach ($serviceRates as $rate) {
            $doctor->serviceRates()->create($rate);
        }

        AuditLogger::log('created', $doctor);

        $message = 'Doctor created successfully.';
        if ($createUser) {
            $message .= " User account created — doctor can login at /doctor/login with email: {$data['email']}";
        }

        return redirect()->route('webmaster.doctors.index')->with('success', $message);
    }

    public function show(Doctor $doctor): Response
    {
        $doctor->load([
            'schedules',
            'vacations' => fn ($q) => $q->orderByDesc('start_date'),
            'serviceRates.service:id,name_ar,name_en',
        ]);

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

        $visits = $doctor->visits()
            ->with(['patient:id,full_name,phone,file_number', 'service:id,name_ar,name_en'])
            ->latest('visit_date')
            ->take(20)
            ->get();

        $todayQueue = $doctor->visits()
            ->with(['patient:id,full_name,phone', 'service:id,name_ar,name_en'])
            ->today()
            ->orderBy('created_at')
            ->get();

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

        $prescriptions = $doctor->prescriptions()
            ->with(['patient:id,full_name', 'items', 'visit:id,visit_date'])
            ->latest()
            ->take(20)
            ->get();

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

        $leaves = $doctor->user_id
            ? Leave::where('user_id', $doctor->user_id)
                ->with('approver:id,name')
                ->orderByDesc('start_date')
                ->get()
            : collect();

        $bookings = Booking::where('doctor_id', $doctor->id)
            ->with('service:id,name_ar,name_en')
            ->latest()
            ->take(20)
            ->get();

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

        $thisMonthCommission = (float) $doctor->visits()
            ->where('status', 'completed')
            ->whereMonth('visit_date', now()->month)
            ->whereYear('visit_date', now()->year)
            ->sum('commission_amount');

        $performanceStats['this_month_commission'] = round($thisMonthCommission, 2);

        $allPatients = Patient::select('id', 'full_name', 'phone', 'file_number')
            ->orderBy('full_name')
            ->get();

        return Inertia::render('Webmaster/Doctors/Show', [
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
        ]);
    }

    public function edit(Doctor $doctor): Response
    {
        $doctor->load(['schedules', 'vacations', 'serviceRates.service:id,name_en,name_ar']);

        return Inertia::render('Webmaster/Doctors/Edit', [
            'doctor' => $doctor,
            'users' => User::select('id', 'name', 'email')->orderBy('name')->get(),
            'services' => Service::select('id', 'name_en', 'name_ar')->active()->orderBy('name_en')->get(),
        ]);
    }

    public function update(Request $request, Doctor $doctor): RedirectResponse
    {
        $data = $request->validate([
            'name_ar' => 'required|string|max:255',
            'name_en' => 'required|string|max:255',
            'photo' => 'nullable|image|mimes:jpg,jpeg,png,webp,gif|max:20480',
            'specialization_ar' => 'required|string|max:255',
            'specialization_en' => 'required|string|max:255',
            'bio_ar' => 'nullable|string',
            'bio_en' => 'nullable|string',
            'qualifications_ar' => 'nullable|string',
            'qualifications_en' => 'nullable|string',
            'display_order' => 'nullable|integer',
            'status' => 'required|in:active,inactive',
            'user_id' => 'nullable|exists:users,id',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'consultation_fee' => 'nullable|numeric|min:0',
            'default_commission_percentage' => 'nullable|numeric|min:0|max:100',
            'clinic_notes' => 'nullable|string',
            'schedules' => 'nullable|array',
            'schedules.*.day_of_week' => 'required_with:schedules|integer|min:0|max:6',
            'schedules.*.start_time' => 'required_with:schedules|string',
            'schedules.*.end_time' => 'required_with:schedules|string',
            'schedules.*.is_active' => 'nullable',
            'vacations' => 'nullable|array',
            'vacations.*.id' => 'nullable|integer',
            'vacations.*.start_date' => 'required_with:vacations|date',
            'vacations.*.end_date' => 'required_with:vacations|date',
            'vacations.*.reason' => 'nullable|string',
            'service_rates' => 'nullable|array',
            'service_rates.*.service_id' => 'required_with:service_rates|exists:services,id',
            'service_rates.*.commission_percentage' => 'required_with:service_rates|numeric|min:0|max:100',
        ]);

        $this->sanitizeFields($data, ['bio_ar', 'bio_en']);

        if ($request->hasFile('photo')) {
            $data['photo'] = $request->file('photo')->store('uploads/doctors', 'public');
        }

        $schedules = $data['schedules'] ?? null;
        $vacations = $data['vacations'] ?? null;
        $serviceRates = $data['service_rates'] ?? null;
        unset($data['schedules'], $data['vacations'], $data['service_rates']);

        $doctor->update($data);

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

        if ($vacations !== null) {
            $doctor->vacations()->delete();
            foreach ($vacations as $vacation) {
                unset($vacation['id']);
                $doctor->vacations()->create($vacation);
            }
        }

        if ($serviceRates !== null) {
            $doctor->serviceRates()->delete();
            foreach ($serviceRates as $rate) {
                $doctor->serviceRates()->create($rate);
            }
        }

        AuditLogger::log('updated', $doctor);

        return redirect()->route('webmaster.doctors.show', $doctor)->with('success', 'Doctor updated successfully.');
    }

    public function destroy(Doctor $doctor): RedirectResponse
    {
        AuditLogger::log('deleted', $doctor);
        $doctor->delete();

        return redirect()->route('webmaster.doctors.index')->with('success', 'Doctor deleted successfully.');
    }
}
