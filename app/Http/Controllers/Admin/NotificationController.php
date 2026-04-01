<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\ContactMessage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * Get latest unread notifications (bookings + messages + database notifications merged).
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        // Get latest unread bookings
        $bookings = Booking::with('service')
            ->where('is_read', false)
            ->latest()
            ->limit(10)
            ->get()
            ->map(fn (Booking $b) => [
                'id'       => 'booking_' . $b->id,
                'type'     => 'booking',
                'title'    => $b->full_name,
                'subtitle' => $b->service?->name_en ?? $b->phone,
                'time'     => $b->created_at->toIso8601String(),
                'url'      => "/admin/bookings/{$b->id}",
            ]);

        // Get latest unread messages
        $messages = ContactMessage::where('is_read', false)
            ->latest()
            ->limit(10)
            ->get()
            ->map(fn (ContactMessage $m) => [
                'id'       => 'message_' . $m->id,
                'type'     => 'message',
                'title'    => $m->name,
                'subtitle' => $m->subject ?: mb_substr($m->message, 0, 50) . '...',
                'time'     => $m->created_at->toIso8601String(),
                'url'      => "/admin/contact-messages/{$m->id}",
            ]);

        // Get database notifications (leads, follow-ups, reports, sequences)
        $dbNotifications = $user->unreadNotifications()
            ->latest()
            ->limit(15)
            ->get()
            ->map(function ($notification) {
                $data = $notification->data;
                $type = $data['type'] ?? 'general';

                return [
                    'id'       => 'notif_' . $notification->id,
                    'notif_id' => $notification->id,
                    'type'     => $type,
                    'title'    => $this->getNotifTitle($data, $type),
                    'subtitle' => $data['message'] ?? '',
                    'time'     => $notification->created_at->toIso8601String(),
                    'url'      => $this->getNotifUrl($data, $type),
                    'priority' => $data['priority'] ?? null,
                ];
            });

        // Merge all, sort by time descending, limit to 20
        $items = $bookings->concat($messages)->concat($dbNotifications)
            ->sortByDesc('time')
            ->take(20)
            ->values();

        $unreadBookings = Booking::where('is_read', false)->count();
        $unreadMessages = ContactMessage::where('is_read', false)->count();
        $unreadDbNotifs = $user->unreadNotifications()->count();

        return response()->json([
            'items'              => $items,
            'unread_bookings'    => $unreadBookings,
            'unread_messages'    => $unreadMessages,
            'unread_system'      => $unreadDbNotifs,
            'unread_count'       => $unreadBookings + $unreadMessages + $unreadDbNotifs,
        ]);
    }

    /**
     * Mark all notifications as read.
     */
    public function markAllRead(Request $request): JsonResponse
    {
        Booking::where('is_read', false)->update(['is_read' => true]);
        ContactMessage::where('is_read', false)->update(['is_read' => true]);
        $request->user()->unreadNotifications->markAsRead();

        return response()->json([
            'unread_bookings' => 0,
            'unread_messages' => 0,
            'unread_system'   => 0,
            'unread_count'    => 0,
        ]);
    }

    /**
     * Mark a single notification as read.
     */
    public function markRead(Request $request, string $type, string $id): JsonResponse
    {
        if ($type === 'booking') {
            Booking::where('id', $id)->update(['is_read' => true]);
        } elseif ($type === 'message') {
            ContactMessage::where('id', $id)->update(['is_read' => true]);
        } elseif ($type === 'notif') {
            $request->user()->notifications()->where('id', $id)->update(['read_at' => now()]);
        }

        $unreadBookings = Booking::where('is_read', false)->count();
        $unreadMessages = ContactMessage::where('is_read', false)->count();
        $unreadDbNotifs = $request->user()->unreadNotifications()->count();

        return response()->json([
            'unread_bookings' => $unreadBookings,
            'unread_messages' => $unreadMessages,
            'unread_system'   => $unreadDbNotifs,
            'unread_count'    => $unreadBookings + $unreadMessages + $unreadDbNotifs,
        ]);
    }

    /**
     * Get notification title based on type.
     */
    protected function getNotifTitle(array $data, string $type): string
    {
        return match ($type) {
            'new_website_lead' => $data['lead_name'] ?? 'New Lead',
            'lead_assigned' => $data['lead_name'] ?? 'Lead Assigned',
            'follow_up_reminder' => $data['lead_name'] ?? 'Follow-up Reminder',
            'follow_up_overdue' => $data['lead_name'] ?? 'Overdue Follow-up',
            'sequence_step' => $data['lead_name'] ?? 'Automation Step',
            'daily_lead_report' => 'Daily CRM Report',
            'dental_lab_overdue' => $data['patient_name'] ?? 'Lab Order Overdue',
            'dental_appointment_reminder' => $data['patient_name'] ?? 'Dental Appointment',
            'dental_plan_due' => $data['patient_name'] ?? 'Treatment Plan Due',
            'dental_followup_reminder' => $data['patient_name'] ?? 'Follow-up Needed',
            default => $data['lead_name'] ?? $data['patient_name'] ?? 'Notification',
        };
    }

    /**
     * Get notification URL based on type.
     */
    protected function getNotifUrl(array $data, string $type): string
    {
        return match ($type) {
            'new_website_lead', 'lead_assigned' => isset($data['lead_id']) ? "/admin/leads/{$data['lead_id']}" : '/admin/leads',
            'follow_up_reminder', 'follow_up_overdue' => isset($data['lead_id']) ? "/admin/leads/{$data['lead_id']}" : '/admin/leads',
            'sequence_step' => isset($data['lead_id']) ? "/admin/leads/{$data['lead_id']}" : '/admin/sequences',
            'daily_lead_report' => '/admin/crm',
            'dental_lab_overdue' => isset($data['lab_order_id']) ? "/admin/dental/lab-orders/{$data['lab_order_id']}/edit" : '/admin/dental/lab-orders',
            'dental_appointment_reminder' => isset($data['visit_id']) ? "/admin/visits/{$data['visit_id']}" : '/admin/visits',
            'dental_plan_due' => isset($data['plan_id']) ? "/admin/dental/treatment-plans/{$data['plan_id']}/edit" : '/admin/dental/treatment-plans',
            'dental_followup_reminder' => isset($data['patient_id']) ? "/admin/patients/{$data['patient_id']}" : '/admin/patients',
            default => '/admin',
        };
    }
}
