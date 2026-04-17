<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Doctor;
use App\Models\OnlineConsultation;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class OnlineConsultationController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->input('status', 'all');
        $search = $request->input('search');

        $query = OnlineConsultation::with([
            'patient:id,full_name,phone,file_number',
            'doctor:id,name_ar,name_en,specialization_ar,specialization_en,module',
        ])
        ->orderByDesc('scheduled_date')
        ->orderByDesc('start_time');

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('consultation_number', 'like', "%{$search}%")
                  ->orWhereHas('patient', fn($p) => $p->where('full_name', 'like', "%{$search}%")->orWhere('phone', 'like', "%{$search}%"))
                  ->orWhereHas('doctor', fn($d) => $d->where('name_ar', 'like', "%{$search}%")->orWhere('name_en', 'like', "%{$search}%"));
            });
        }

        $consultations = $query->paginate(20)->withQueryString();

        // Stats
        $today = Carbon::today();
        $thisMonth = Carbon::now()->startOfMonth();

        $stats = [
            'total_today' => OnlineConsultation::whereDate('scheduled_date', $today)->count(),
            'total_this_month' => OnlineConsultation::where('scheduled_date', '>=', $thisMonth)->count(),
            'revenue_this_month' => OnlineConsultation::where('scheduled_date', '>=', $thisMonth)
                ->where('payment_status', 'paid')
                ->sum('fee'),
            'upcoming_count' => OnlineConsultation::upcoming()->count(),
            'in_progress_count' => OnlineConsultation::where('status', OnlineConsultation::STATUS_IN_PROGRESS)->count(),
            'completed_count' => OnlineConsultation::where('status', OnlineConsultation::STATUS_COMPLETED)
                ->where('scheduled_date', '>=', $thisMonth)->count(),
            'missed_count' => OnlineConsultation::whereIn('status', [
                OnlineConsultation::STATUS_MISSED_DOCTOR,
                OnlineConsultation::STATUS_MISSED_PATIENT,
            ])->where('scheduled_date', '>=', $thisMonth)->count(),
        ];

        return Inertia::render('Admin/OnlineConsultations/Index', [
            'consultations' => $consultations,
            'filters' => ['status' => $status, 'search' => $search],
            'stats' => $stats,
        ]);
    }

    public function doctors(Request $request)
    {
        $doctors = Doctor::onlineEnabled()
            ->withCount([
                'onlineConsultations as total_sessions',
                'onlineConsultations as completed_sessions' => function ($q) {
                    $q->where('status', OnlineConsultation::STATUS_COMPLETED);
                },
            ])
            ->withSum([
                'onlineConsultations as total_revenue' => function ($q) {
                    $q->where('payment_status', 'paid');
                },
            ], 'fee')
            ->orderBy('display_order')
            ->get();

        return Inertia::render('Admin/OnlineConsultations/Doctors', [
            'doctors' => $doctors,
        ]);
    }

    public function show(OnlineConsultation $consultation)
    {
        $consultation->load([
            'patient:id,full_name,phone,email,file_number,date_of_birth,gender',
            'doctor:id,name_ar,name_en,specialization_ar,specialization_en,module,photo',
            'visit:id,status,diagnosis,doctor_notes,completed_at',
            'paymentTransactions',
        ]);

        return Inertia::render('Admin/OnlineConsultations/Show', [
            'consultation' => $consultation,
        ]);
    }
}
