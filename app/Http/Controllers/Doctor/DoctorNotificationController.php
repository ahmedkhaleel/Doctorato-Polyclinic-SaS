<?php

namespace App\Http\Controllers\Doctor;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DoctorNotificationController extends BaseDoctorController
{
    /**
     * JSON endpoint for the notification bell (unread only).
     */
    public function index(Request $request): JsonResponse
    {
        $notifications = $request->user()
            ->unreadNotifications()
            ->latest()
            ->limit(20)
            ->get()
            ->map(function ($notification) {
                $data = $notification->data;
                $type = $data['type'] ?? 'general';

                $item = [
                    'id' => $notification->id,
                    'type' => $type,
                    'title' => $this->getTitle($data, $type),
                    'subtitle' => $this->getSubtitle($data, $type),
                    'url' => $this->getUrl($data, $type),
                    'time' => $notification->created_at->toISOString(),
                ];

                // Pass extra booking fields for the popup
                if ($type === 'new_booking') {
                    $item['booking_number'] = $data['booking_number'] ?? null;
                    $item['doctor_name'] = $data['doctor_name'] ?? null;
                    $item['service_name'] = $data['service_name'] ?? null;
                    $item['preferred_date'] = $data['preferred_date'] ?? null;
                    $item['preferred_time'] = $data['preferred_time'] ?? null;
                }

                return $item;
            });

        return response()->json([
            'items' => $notifications,
            'unread_count' => $request->user()->unreadNotifications()->count(),
        ]);
    }

    /**
     * Full notification history page (read + unread, paginated).
     */
    public function history(Request $request): Response
    {
        $filter = $request->input('filter', 'all'); // all | unread

        $query = $request->user()->notifications()->latest();

        if ($filter === 'unread') {
            $query->whereNull('read_at');
        }

        $paginated = $query->paginate(20)->withQueryString();

        $items = $paginated->through(function ($notification) {
            $data = $notification->data;
            $type = $data['type'] ?? 'general';

            return [
                'id' => $notification->id,
                'type' => $type,
                'title' => $this->getTitle($data, $type),
                'subtitle' => $this->getSubtitle($data, $type),
                'url' => $this->getUrl($data, $type),
                'time' => $notification->created_at->toISOString(),
                'read' => $notification->read_at !== null,
            ];
        });

        return Inertia::render('Doctor/Notifications/Index', [
            'notifications' => $items,
            'filter' => $filter,
            'unreadCount' => $request->user()->unreadNotifications()->count(),
        ]);
    }

    public function markAllRead(Request $request): JsonResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        return response()->json(['success' => true]);
    }

    public function markRead(Request $request, string $id): JsonResponse
    {
        $notification = $request->user()
            ->notifications()
            ->where('id', $id)
            ->first();

        if ($notification) {
            $notification->markAsRead();
        }

        return response()->json(['success' => true]);
    }

    private function getTitle(array $data, string $type): string
    {
        return match ($type) {
            'new_booking' => $data['patient_name'] ?? 'New Booking',
            'new_visit' => $data['patient_name'] ?? 'New Visit',
            'booking_reminder' => $data['patient_name'] ?? 'Appointment Reminder',
            'dental_lab_overdue' => $data['patient_name'] ?? 'Lab Order Overdue',
            'dental_appointment_reminder' => $data['patient_name'] ?? 'Dental Appointment',
            'dental_plan_due' => $data['patient_name'] ?? 'Treatment Plan Due',
            'dental_followup_reminder' => $data['patient_name'] ?? 'Follow-up Needed',
            'pediatric_vaccination_due' => $data['patient_name'] ?? 'Vaccination Due',
            'pediatric_vaccination_overdue' => $data['patient_name'] ?? 'Vaccination Overdue',
            'pediatric_growth_alert' => $data['patient_name'] ?? 'Growth Alert',
            'pediatric_milestone_alert' => $data['patient_name'] ?? 'Milestone Alert',
            default => $data['message'] ?? 'Notification',
        };
    }

    private function getSubtitle(array $data, string $type): string
    {
        return match ($type) {
            'new_booking' => 'New booking for ' . ($data['service_name'] ?? 'service') . ' on ' . ($data['preferred_date'] ?? ''),
            'new_visit' => ucfirst($data['visit_type'] ?? 'visit') . ' - ' . ($data['service_name'] ?? 'service'),
            'booking_reminder' => 'Appointment today at ' . ($data['preferred_time'] ?? 'scheduled time'),
            'dental_lab_overdue' => $data['message'] ?? ('Lab order overdue: ' . ($data['item_type'] ?? 'item') . ' — ' . ($data['lab_name'] ?? '')),
            'dental_appointment_reminder' => $data['message'] ?? ('Dental appointment today: ' . ($data['patient_name'] ?? '')),
            'dental_plan_due' => $data['message'] ?? ('Treatment plan ' . ($data['reason'] ?? 'due') . ': ' . ($data['plan_title'] ?? '')),
            'dental_followup_reminder' => $data['message'] ?? ('Follow-up needed: ' . ($data['treatment_type'] ?? 'treatment') . ' for ' . ($data['patient_name'] ?? '')),
            'pediatric_vaccination_due' => ($data['vaccination_count'] ?? 0) . ' vaccination(s) upcoming: ' . ($data['vaccine_names'] ?? ''),
            'pediatric_vaccination_overdue' => '⚠ ' . ($data['vaccination_count'] ?? 0) . ' vaccination(s) overdue: ' . ($data['vaccine_names'] ?? ''),
            'pediatric_growth_alert' => $data['message'] ?? 'Growth measurement outside normal range',
            'pediatric_milestone_alert' => ($data['delayed_count'] ?? 0) . ' developmental milestone(s) delayed',
            default => $data['message'] ?? '',
        };
    }

    private function getUrl(array $data, string $type): string
    {
        return match ($type) {
            'new_visit' => isset($data['visit_id']) ? '/doctor/visits/' . $data['visit_id'] : '/doctor/visits',
            'new_booking', 'booking_reminder' => '/doctor/bookings',
            'dental_lab_overdue' => '/doctor/dental/lab-orders',
            'dental_appointment_reminder' => isset($data['visit_id']) ? '/doctor/visits/' . $data['visit_id'] : '/doctor/visits',
            'dental_plan_due' => isset($data['plan_id']) ? '/doctor/dental/treatment-plans/' . $data['plan_id'] : '/doctor/dental/treatment-plans',
            'dental_followup_reminder' => isset($data['patient_id']) ? '/doctor/patients/' . $data['patient_id'] : '/doctor/patients',
            'pediatric_vaccination_due', 'pediatric_vaccination_overdue' => isset($data['patient_id']) ? '/doctor/pediatric/patients/' . $data['patient_id'] . '?tab=vaccines' : '/doctor/pediatric/patients',
            'pediatric_growth_alert' => isset($data['patient_id']) ? '/doctor/pediatric/patients/' . $data['patient_id'] . '?tab=growth' : '/doctor/pediatric/patients',
            'pediatric_milestone_alert' => isset($data['patient_id']) ? '/doctor/pediatric/patients/' . $data['patient_id'] . '?tab=milestones' : '/doctor/pediatric/patients',
            default => '/doctor',
        };
    }
}
