<?php

namespace App\Http\Controllers\Doctor;

use App\Models\Visit;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DoctorQueueController extends BaseDoctorController
{
    public function index(Request $request): Response
    {
        $doctorId = $this->doctorId($request);

        $filters = $request->validate([
            'view' => 'nullable|in:today,upcoming,past,all',
            'status' => 'nullable|in:waiting,in_progress,completed,cancelled',
            'module' => 'nullable|in:dental,dermatology',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date',
        ]);

        $view = $filters['view'] ?? 'today';

        // Today's stats (always shown)
        $todayVisits = Visit::where('doctor_id', $doctorId)
            ->whereDate('visit_date', today())
            ->get();

        $todayStats = [
            'total' => $todayVisits->count(),
            'waiting' => $todayVisits->where('status', 'waiting')->count(),
            'in_progress' => $todayVisits->where('status', 'in_progress')->count(),
            'completed' => $todayVisits->where('status', 'completed')->count(),
        ];

        // Build query based on view filter
        $query = Visit::with([
            'patient:id,full_name,file_number,phone',
            'service:id,name_en,name_ar',
            'booking:id,booking_number',
            'dentalTreatments:id,visit_id,treatment_type,tooth_number',
        ])
            ->where('doctor_id', $doctorId);

        if ($view === 'today') {
            $query->whereDate('visit_date', today());
        } elseif ($view === 'upcoming') {
            $query->whereDate('visit_date', '>', today())
                ->whereIn('status', ['waiting', 'in_progress']);
        } elseif ($view === 'past') {
            $query->whereDate('visit_date', '<', today());
        }
        // 'all' = no date filter

        if ($filters['status'] ?? null) {
            $query->where('status', $filters['status']);
        }

        if ($filters['module'] ?? null) {
            if ($filters['module'] === 'dental') {
                $query->where('module', 'dental');
            } else {
                $query->where(function ($q) {
                    $q->where('module', '!=', 'dental')->orWhereNull('module');
                });
            }
        }

        if ($filters['date_from'] ?? null) {
            $query->whereDate('visit_date', '>=', $filters['date_from']);
        }

        if ($filters['date_to'] ?? null) {
            $query->whereDate('visit_date', '<=', $filters['date_to']);
        }

        // Order: active first, then by date/time
        $query->orderByRaw("FIELD(status, 'in_progress', 'waiting', 'completed', 'cancelled')")
            ->orderBy('visit_date', 'desc')
            ->orderBy('scheduled_time', 'asc');

        $visits = $query->paginate(20)->withQueryString();

        // Medical risk alerts for dental patients in today's active visits
        $medicalAlerts = [];
        if ($view === 'today') {
            foreach ($visits->items() as $visit) {
                if ($visit->module === 'dental' && $visit->patient) {
                    $riskFlags = $visit->patient->getDentalRiskFlags();
                    if (!empty($riskFlags)) {
                        $medicalAlerts[$visit->id] = $riskFlags;
                    }
                }
            }
        }

        // Upcoming count for badge
        $upcomingCount = Visit::where('doctor_id', $doctorId)
            ->whereDate('visit_date', '>', today())
            ->whereIn('status', ['waiting', 'in_progress'])
            ->count();

        return Inertia::render('Doctor/Queue/Index', [
            'visits' => $visits,
            'todayStats' => $todayStats,
            'upcomingCount' => $upcomingCount,
            'filters' => $request->only(['view', 'status', 'module', 'date_from', 'date_to']),
            'medicalAlerts' => $medicalAlerts,
        ]);
    }
}
