<?php

namespace App\Http\Controllers\Doctor;

use App\Models\Booking;
use App\Models\PackageBundleBooking;
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
            'status' => 'nullable|string|in:confirmed,in_progress,completed,cancelled,pending',
            'search' => 'nullable|string|max:100',
            'module' => 'nullable|string|max:50',
            'tab' => 'nullable|string|in:bookings,packages',
        ]);

        $tab = $filters['tab'] ?? 'bookings';

        // ── Regular Bookings ────────────────────────────────
        $bookingsQuery = Booking::with([
                'patient:id,full_name,phone',
                'bookingServices' => fn ($q) => $q->where('doctor_id', $doctorId)->with('service:id,name_en,name_ar'),
                'appointments' => fn ($q) => $q->where('doctor_id', $doctorId)->orderBy('appointment_date')->orderBy('start_time'),
            ])
            ->whereHas('bookingServices', fn ($q) => $q->where('doctor_id', $doctorId))
            ->whereNotIn('status', ['unconfirmed']);

        if ($module = $filters['module'] ?? null) {
            $bookingsQuery->where('module', $module);
        }

        if ($tab === 'bookings' && ($status = $filters['status'] ?? null)) {
            $bookingsQuery->where('status', $status);
        }

        if ($tab === 'bookings' && ($search = $filters['search'] ?? null)) {
            $bookingsQuery->where(function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('booking_number', 'like', "%{$search}%")
                    ->orWhereHas('patient', fn ($pq) => $pq->where('full_name', 'like', "%{$search}%")->orWhere('phone', 'like', "%{$search}%"));
            });
        }

        // Count for tab badge (always needed)
        $bookingsCount = (clone $bookingsQuery)->count();

        // Only paginate if this tab is active
        $bookings = $tab === 'bookings'
            ? $bookingsQuery->latest()->paginate(15)->withQueryString()
            : null;

        if ($bookings) {
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

                unset($booking->bookingServices, $booking->appointments, $booking->patient);

                return $booking;
            });
        }

        // ── Package Bundle Bookings ─────────────────────────
        $bundleQuery = PackageBundleBooking::with([
                'patient:id,full_name,phone,file_number',
                'packageBundle:id,name_ar,name_en',
                'bundleServices' => fn ($q) => $q->where('doctor_id', $doctorId)->with('service:id,name_en,name_ar'),
            ])
            ->whereHas('bundleServices', fn ($q) => $q->where('doctor_id', $doctorId));

        if ($tab === 'packages' && ($status = $filters['status'] ?? null)) {
            $bundleQuery->where('status', $status);
        }

        if ($tab === 'packages' && ($search = $filters['search'] ?? null)) {
            $bundleQuery->where(function ($q) use ($search) {
                $q->where('booking_number', 'like', "%{$search}%")
                    ->orWhereHas('patient', fn ($pq) => $pq->where('full_name', 'like', "%{$search}%")->orWhere('phone', 'like', "%{$search}%"))
                    ->orWhereHas('packageBundle', fn ($pq) => $pq->where('name_ar', 'like', "%{$search}%")->orWhere('name_en', 'like', "%{$search}%"));
            });
        }

        // Count for tab badge
        $bundleCount = (clone $bundleQuery)->count();

        $bundleBookings = $tab === 'packages'
            ? $bundleQuery->latest()->paginate(15)->withQueryString()
            : null;

        if ($bundleBookings) {
            $bundleBookings->getCollection()->transform(function ($bb) {
                $locale = app()->getLocale();

                // Bundle name
                $bb->bundle_name = $locale === 'ar'
                    ? ($bb->packageBundle?->name_ar ?? $bb->packageBundle?->name_en ?? '-')
                    : ($bb->packageBundle?->name_en ?? $bb->packageBundle?->name_ar ?? '-');

                // Services assigned to this doctor
                $bb->doctor_services = $bb->bundleServices->map(fn ($bs) => [
                    'name' => $locale === 'ar'
                        ? ($bs->service?->name_ar ?? $bs->service?->name_en ?? '-')
                        : ($bs->service?->name_en ?? '-'),
                    'sessions_count' => $bs->sessions_count,
                    'completed_sessions' => $bs->completed_sessions,
                    'status' => $bs->status,
                ])->values()->toArray();

                // Overall progress for doctor's services
                $totalSessions = $bb->bundleServices->sum('sessions_count');
                $completedSessions = $bb->bundleServices->sum('completed_sessions');
                $bb->doctor_total_sessions = $totalSessions;
                $bb->doctor_completed_sessions = $completedSessions;
                $bb->progress_percent = $totalSessions > 0 ? round(($completedSessions / $totalSessions) * 100) : 0;

                $bb->patient_name = $bb->patient?->full_name ?? '-';
                $bb->patient_phone = $bb->patient?->phone ?? '-';

                unset($bb->bundleServices, $bb->packageBundle, $bb->patient);

                return $bb;
            });
        }

        return Inertia::render('Doctor/Bookings/Index', [
            'bookings' => $bookings,
            'bundleBookings' => $bundleBookings,
            'bookingsCount' => $bookingsCount,
            'bundleCount' => $bundleCount,
            'filters' => $request->only(['status', 'search', 'module', 'tab']),
        ]);
    }
}
