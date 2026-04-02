<?php

namespace App\Http\Controllers\Doctor;

use App\Models\Booking;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DoctorBookingController extends BaseDoctorController
{
    public function index(Request $request): Response
    {
        $doctorId = $this->doctorId($request);

        // Validate filter inputs
        $filters = $request->validate([
            'status' => 'nullable|string|in:confirmed,in_progress,completed,cancelled',
            'search' => 'nullable|string|max:100',
            'module' => 'nullable|string|max:50',
        ]);

        // Only show CONFIRMED+ bookings assigned to this doctor via booking_services
        // (not the legacy doctor_id field which is set from website initial selection)
        $query = Booking::with([
                'patient:id,full_name,phone',
                'bookingServices' => fn ($q) => $q->where('doctor_id', $doctorId)->with('service:id,name_en,name_ar'),
                'appointments' => fn ($q) => $q->where('doctor_id', $doctorId)->orderBy('appointment_date')->orderBy('start_time'),
            ])
            ->whereHas('bookingServices', fn ($q) => $q->where('doctor_id', $doctorId))
            ->whereNotIn('status', ['unconfirmed']);

        if ($module = $filters['module'] ?? null) {
            $query->where('module', $module);
        }

        if ($status = $filters['status'] ?? null) {
            $query->where('status', $status);
        }

        if ($search = $filters['search'] ?? null) {
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('booking_number', 'like', "%{$search}%")
                    ->orWhereHas('patient', fn ($pq) => $pq->where('full_name', 'like', "%{$search}%")->orWhere('phone', 'like', "%{$search}%"));
            });
        }

        $bookings = $query->latest()->paginate(15)->withQueryString();

        // Transform bookings to include relevant service/appointment data for this doctor
        $bookings->getCollection()->transform(function ($booking) {
            $doctorService = $booking->bookingServices->first();
            $nextAppointment = $booking->appointments
                ->where('status', '!=', 'completed')
                ->where('status', '!=', 'cancelled')
                ->where('status', '!=', 'no_show')
                ->first();

            $booking->doctor_service_name = app()->getLocale() === 'ar'
                ? ($doctorService?->service?->name_ar ?? $doctorService?->service?->name_en ?? '-')
                : ($doctorService?->service?->name_en ?? '-');
            $booking->doctor_sessions_count = $doctorService?->sessions_count ?? 0;
            $booking->doctor_completed_sessions = $doctorService?->completed_sessions ?? 0;
            $booking->next_appointment_date = $nextAppointment?->appointment_date?->format('Y-m-d');
            $booking->next_appointment_time = $nextAppointment?->start_time;
            $booking->patient_name = $booking->patient?->full_name ?? $booking->full_name;
            $booking->patient_phone = $booking->patient?->phone ?? $booking->phone;

            // Clean up heavy relations from response
            unset($booking->bookingServices, $booking->appointments, $booking->patient);

            return $booking;
        });

        return Inertia::render('Doctor/Bookings/Index', [
            'bookings' => $bookings,
            'filters' => $request->only(['status', 'search', 'module']),
        ]);
    }
}
