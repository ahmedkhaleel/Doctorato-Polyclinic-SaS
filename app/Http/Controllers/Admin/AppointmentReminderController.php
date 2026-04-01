<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BookingAppointment;
use App\Models\Setting;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AppointmentReminderController extends Controller
{
    public function index(Request $request): Response
    {
        $today = today();
        $tomorrow = today()->addDay();

        // ── Upcoming Appointments (next 7 days) ───────────────
        $upcoming = BookingAppointment::with([
            'booking:id,full_name,phone,email',
            'doctor:id,name_en,name_ar',
            'bookingService.service:id,name_en,name_ar',
        ])
            ->whereDate('appointment_date', '>=', $today)
            ->whereDate('appointment_date', '<=', $today->copy()->addDays(7))
            ->whereIn('status', ['scheduled', 'confirmed'])
            ->orderBy('appointment_date')
            ->orderBy('start_time')
            ->get()
            ->map(function ($apt) use ($today, $tomorrow) {
                $reminderStatus = 'pending';
                if ($apt->appointment_date->isSameDay($today)) {
                    $reminderStatus = 'same_day';
                } elseif ($apt->appointment_date->isSameDay($tomorrow)) {
                    $reminderStatus = 'tomorrow';
                }

                return [
                    'id' => $apt->id,
                    'patient_name' => $apt->booking?->full_name,
                    'phone' => $apt->booking?->phone,
                    'email' => $apt->booking?->email,
                    'doctor_name_en' => $apt->doctor?->name_en,
                    'doctor_name_ar' => $apt->doctor?->name_ar,
                    'service_name_en' => $apt->bookingService?->service?->name_en,
                    'service_name_ar' => $apt->bookingService?->service?->name_ar,
                    'appointment_date' => $apt->appointment_date->format('Y-m-d'),
                    'start_time' => $apt->start_time,
                    'end_time' => $apt->end_time,
                    'status' => $apt->status,
                    'reminder_status' => $reminderStatus,
                    'session_number' => $apt->session_number,
                ];
            });

        // ── Stats ─────────────────────────────────────────────
        $todayCount = BookingAppointment::whereDate('appointment_date', $today)
            ->whereIn('status', ['scheduled', 'confirmed', 'checked_in', 'in_progress'])
            ->count();
        $tomorrowCount = BookingAppointment::whereDate('appointment_date', $tomorrow)
            ->whereIn('status', ['scheduled', 'confirmed'])
            ->count();
        $weekCount = BookingAppointment::whereDate('appointment_date', '>=', $today)
            ->whereDate('appointment_date', '<=', $today->copy()->addDays(7))
            ->whereIn('status', ['scheduled', 'confirmed'])
            ->count();
        $noShowsThisMonth = BookingAppointment::whereMonth('appointment_date', $today->month)
            ->whereYear('appointment_date', $today->year)
            ->where('status', 'no_show')
            ->count();

        // ── SMS Settings ──────────────────────────────────────
        $smsSettings = [
            'sms_enabled' => Setting::get('sms_enabled', '0') === '1',
            'sms_reminder_day_before' => Setting::get('sms_reminder_day_before', '1') === '1',
            'sms_reminder_same_day' => Setting::get('sms_reminder_same_day', '1') === '1',
            'sms_reminder_hour' => Setting::get('sms_reminder_hour', '09:00'),
        ];

        // ── No-Show Patients (this month) ─────────────────────
        $noShowPatients = BookingAppointment::with(['booking:id,full_name,phone', 'doctor:id,name_en,name_ar'])
            ->whereMonth('appointment_date', $today->month)
            ->where('status', 'no_show')
            ->latest('appointment_date')
            ->limit(20)
            ->get()
            ->map(fn ($apt) => [
                'id' => $apt->id,
                'patient_name' => $apt->booking?->full_name,
                'phone' => $apt->booking?->phone,
                'doctor_name_en' => $apt->doctor?->name_en,
                'doctor_name_ar' => $apt->doctor?->name_ar,
                'appointment_date' => $apt->appointment_date->format('Y-m-d'),
                'start_time' => $apt->start_time,
            ]);

        // ── Hourly Distribution for today ─────────────────────
        $hourlyToday = BookingAppointment::whereDate('appointment_date', $today)
            ->whereIn('status', ['scheduled', 'confirmed', 'checked_in', 'in_progress', 'completed'])
            ->selectRaw('HOUR(start_time) as hour, COUNT(*) as count')
            ->groupByRaw('HOUR(start_time)')
            ->orderBy('hour')
            ->pluck('count', 'hour')
            ->toArray();

        return Inertia::render('Admin/Appointments/Reminders', [
            'upcoming' => $upcoming,
            'stats' => [
                'today' => $todayCount,
                'tomorrow' => $tomorrowCount,
                'week' => $weekCount,
                'no_shows_month' => $noShowsThisMonth,
            ],
            'smsSettings' => $smsSettings,
            'noShowPatients' => $noShowPatients,
            'hourlyToday' => $hourlyToday,
        ]);
    }
}
