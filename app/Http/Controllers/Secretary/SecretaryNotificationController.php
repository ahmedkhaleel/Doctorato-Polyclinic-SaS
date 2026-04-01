<?php

namespace App\Http\Controllers\Secretary;

use App\Models\Booking;
use App\Models\ContactMessage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SecretaryNotificationController extends BaseSecretaryController
{
    public function index(Request $request): JsonResponse
    {
        $items = collect();

        // Recent unread bookings
        $bookings = Booking::where('is_read', false)
            ->with(['service', 'doctor.user', 'bookingServices.service', 'bookingServices.doctor.user', 'patient'])
            ->latest()
            ->limit(10)
            ->get();

        foreach ($bookings as $booking) {
            $serviceName = $booking->service?->name_en
                ?? $booking->bookingServices?->first()?->service?->name_en
                ?? 'Service';

            $doctorName = $booking->doctor?->user?->name
                ?? $booking->bookingServices?->first()?->doctor?->user?->name
                ?? null;

            $patientName = $booking->full_name ?? $booking->patient?->full_name ?? 'New Booking';

            $preferredDate = $booking->preferred_date?->format('Y-m-d')
                ?? $booking->appointments?->first()?->appointment_date?->format('Y-m-d')
                ?? '';

            $preferredTime = $booking->preferred_time
                ?? $booking->appointments?->first()?->start_time
                ?? '';

            $items->push([
                'id' => 'booking_' . $booking->id,
                'type' => 'new_booking',
                'title' => $patientName,
                'subtitle' => $serviceName . ($preferredDate ? ' — ' . $preferredDate : ''),
                'url' => '/secretary/bookings/' . $booking->id,
                'time' => $booking->created_at->toISOString(),
                'booking_number' => $booking->booking_number,
                'doctor_name' => $doctorName,
                'service_name' => $serviceName,
                'date' => $preferredDate,
                'preferred_time' => $preferredTime,
            ]);
        }

        // Recent unread contact messages
        $messages = ContactMessage::where('is_read', false)
            ->latest()
            ->limit(10)
            ->get();

        foreach ($messages as $msg) {
            $items->push([
                'id' => 'message_' . $msg->id,
                'type' => 'new_message',
                'title' => $msg->name ?? 'New Message',
                'subtitle' => \Illuminate\Support\Str::limit($msg->message ?? $msg->subject ?? '', 60),
                'url' => '/secretary',
                'time' => $msg->created_at->toISOString(),
            ]);
        }

        // Database notifications (dental alerts)
        $dbNotifications = $request->user()->unreadNotifications()
            ->latest()
            ->limit(15)
            ->get();

        foreach ($dbNotifications as $notification) {
            $data = $notification->data;
            $type = $data['type'] ?? 'general';

            $items->push([
                'id' => 'notif_' . $notification->id,
                'notif_id' => $notification->id,
                'type' => $type,
                'title' => $this->getNotifTitle($data, $type),
                'subtitle' => $data['message'] ?? '',
                'url' => $this->getNotifUrl($data, $type),
                'time' => $notification->created_at->toISOString(),
                'priority' => $data['priority'] ?? null,
            ]);
        }

        // Sort by time descending, take 20
        $sorted = $items->sortByDesc('time')->values()->take(20);

        $unreadDbNotifs = $request->user()->unreadNotifications()->count();

        return response()->json([
            'items' => $sorted,
            'unread_count' => $bookings->count() + $messages->count() + $unreadDbNotifs,
        ]);
    }

    public function markAllRead(Request $request): JsonResponse
    {
        Booking::where('is_read', false)->update(['is_read' => true]);
        ContactMessage::where('is_read', false)->update(['is_read' => true]);
        $request->user()->unreadNotifications->markAsRead();

        return response()->json(['success' => true]);
    }

    protected function getNotifTitle(array $data, string $type): string
    {
        return match ($type) {
            'dental_lab_overdue' => $data['patient_name'] ?? 'Lab Order Overdue',
            'dental_appointment_reminder' => $data['patient_name'] ?? 'Dental Appointment',
            'dental_followup_reminder' => $data['patient_name'] ?? 'Follow-up Needed',
            'dental_plan_due' => $data['patient_name'] ?? 'Treatment Plan Due',
            default => $data['patient_name'] ?? 'Notification',
        };
    }

    protected function getNotifUrl(array $data, string $type): string
    {
        return match ($type) {
            'dental_lab_overdue' => '/secretary/dental/lab-orders',
            'dental_appointment_reminder' => isset($data['visit_id']) ? '/secretary/visits/' . $data['visit_id'] : '/secretary/visits',
            'dental_followup_reminder' => isset($data['patient_id']) ? '/secretary/patients/' . $data['patient_id'] : '/secretary/patients',
            'dental_plan_due' => '/secretary/dental/treatment-plans',
            default => '/secretary',
        };
    }
}
