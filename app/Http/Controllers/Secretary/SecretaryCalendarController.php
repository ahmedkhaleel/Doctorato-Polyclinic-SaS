<?php

namespace App\Http\Controllers\Secretary;

use App\Models\Booking;
use App\Models\Doctor;
use App\Models\DoctorSchedule;
use App\Models\DoctorVacation;
use App\Models\Visit;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SecretaryCalendarController extends BaseSecretaryController
{
    public function index(Request $request): Response
    {
        $month = $request->input('month', now()->format('Y-m'));
        $doctorId = $request->input('doctor_id');

        $startDate = Carbon::parse($month . '-01')->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();

        // Get visits for the month
        $visitsQuery = Visit::with(['patient:id,full_name,file_number', 'doctor:id,name_en,name_ar', 'service:id,name_en,name_ar'])
            ->whereBetween('visit_date', [$startDate, $endDate]);

        if ($doctorId) {
            $visitsQuery->where('doctor_id', $doctorId);
        }

        $visits = $visitsQuery->orderBy('visit_date')->orderBy('created_at')->get()->map(function ($visit) {
            return [
                'id' => $visit->id,
                'type' => 'visit',
                'date' => $visit->visit_date->format('Y-m-d'),
                'title' => $visit->patient?->full_name ?? 'Unknown',
                'doctor' => $visit->doctor?->name_en ?? '-',
                'doctor_id' => $visit->doctor_id,
                'service' => $visit->service?->name_en ?? '-',
                'visit_type' => $visit->visit_type,
                'status' => $visit->status,
                'time' => $visit->started_at?->format('H:i'),
                'patient_file' => $visit->patient?->file_number,
            ];
        });

        // Get bookings for the month
        $bookingsQuery = Booking::with(['doctor:id,name_en,name_ar', 'service:id,name_en,name_ar'])
            ->whereBetween('preferred_date', [$startDate, $endDate]);

        if ($doctorId) {
            $bookingsQuery->where('doctor_id', $doctorId);
        }

        $bookings = $bookingsQuery->orderBy('preferred_date')->get()->map(function ($booking) {
            return [
                'id' => $booking->id,
                'type' => 'booking',
                'date' => $booking->preferred_date->format('Y-m-d'),
                'title' => $booking->full_name,
                'doctor' => $booking->doctor?->name_en ?? '-',
                'doctor_id' => $booking->doctor_id,
                'service' => $booking->service?->name_en ?? '-',
                'status' => $booking->status,
                'time' => $booking->preferred_time,
                'phone' => $booking->phone,
            ];
        });

        $events = $visits->concat($bookings)->groupBy('date')->map(function ($dayEvents) {
            return $dayEvents->values();
        });

        $doctors = Doctor::active()->select('id', 'name_en', 'name_ar')->get();

        $vacations = DoctorVacation::where(function ($q) use ($startDate, $endDate) {
            $q->whereBetween('start_date', [$startDate, $endDate])
              ->orWhereBetween('end_date', [$startDate, $endDate])
              ->orWhere(function ($q2) use ($startDate, $endDate) {
                  $q2->where('start_date', '<=', $startDate)->where('end_date', '>=', $endDate);
              });
        })->get()->map(function ($v) {
            return [
                'doctor_id' => $v->doctor_id,
                'start_date' => $v->start_date->format('Y-m-d'),
                'end_date' => $v->end_date->format('Y-m-d'),
                'reason' => $v->reason,
            ];
        });

        $schedules = DoctorSchedule::where('is_active', true)->get()->groupBy('doctor_id')->map(function ($scheds) {
            return $scheds->pluck('day_of_week')->values();
        });

        $stats = [
            'total_visits' => $visits->count(),
            'completed_visits' => $visits->where('status', 'completed')->count(),
            'waiting_visits' => $visits->where('status', 'waiting')->count(),
            'total_bookings' => $bookings->count(),
            'pending_bookings' => $bookings->where('status', 'new')->count(),
            'confirmed_bookings' => $bookings->where('status', 'confirmed')->count(),
        ];

        return Inertia::render('Secretary/Calendar/Index', [
            'events' => $events,
            'doctors' => $doctors,
            'vacations' => $vacations,
            'schedules' => $schedules,
            'stats' => $stats,
            'filters' => [
                'month' => $month,
                'doctor_id' => $doctorId,
            ],
        ]);
    }
}
