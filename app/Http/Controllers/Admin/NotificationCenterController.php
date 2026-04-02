<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\ContactMessage;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use Inertia\Response;

class NotificationCenterController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $filter = $request->input('filter', 'all'); // all, unread, read
        $type = $request->input('type');
        $search = $request->input('search');

        $notificationsTableExists = Schema::hasTable('notifications');
        $bookingsHasIsRead = Schema::hasColumn('bookings', 'is_read');
        $messagesHasIsRead = Schema::hasColumn('contact_messages', 'is_read');

        // Database notifications (paginated)
        $mapped = null;
        if ($notificationsTableExists) {
            $query = $user->notifications()->latest();

            if ($filter === 'unread') {
                $query->whereNull('read_at');
            } elseif ($filter === 'read') {
                $query->whereNotNull('read_at');
            }

            if ($type) {
                $query->where(function ($q) use ($type) {
                    $q->whereRaw("JSON_EXTRACT(data, '$.type') = ?", [$type]);
                });
            }

            if ($search) {
                $query->where('data', 'like', "%{$search}%");
            }

            $notifications = $query->paginate(30)->withQueryString();

            $mapped = $notifications->through(function ($notification) {
                try {
                    $data = is_array($notification->data) ? $notification->data : [];
                    $notifType = $data['type'] ?? 'general';

                    return [
                        'id' => $notification->id,
                        'type' => $notifType,
                        'title' => $this->getTitle($data, $notifType),
                        'message' => $data['message'] ?? $data['subtitle'] ?? '',
                        'url' => $this->getUrl($data, $notifType),
                        'priority' => $data['priority'] ?? 'normal',
                        'read_at' => $notification->read_at?->toIso8601String(),
                        'created_at' => $notification->created_at->toIso8601String(),
                        'time_ago' => $notification->created_at->diffForHumans(),
                    ];
                } catch (\Throwable $e) {
                    return [
                        'id' => $notification->id,
                        'type' => 'general',
                        'title' => 'Notification',
                        'message' => '',
                        'url' => '/admin',
                        'priority' => 'normal',
                        'read_at' => $notification->read_at?->toIso8601String(),
                        'created_at' => $notification->created_at?->toIso8601String() ?? now()->toIso8601String(),
                        'time_ago' => $notification->created_at?->diffForHumans() ?? '',
                    ];
                }
            });
        } else {
            // Return empty paginator if table doesn't exist
            $mapped = new \Illuminate\Pagination\LengthAwarePaginator([], 0, 30);
        }

        // Unread bookings
        $unreadBookings = collect();
        if ($bookingsHasIsRead) {
            $unreadBookings = Booking::where('is_read', false)
                ->latest()
                ->limit(50)
                ->get()
                ->map(fn (Booking $b) => [
                    'id' => 'booking_' . $b->id,
                    'type' => 'booking',
                    'title' => $b->full_name,
                    'message' => $b->service?->name_en ?? $b->phone,
                    'url' => "/admin/bookings/{$b->id}",
                    'priority' => 'normal',
                    'read_at' => null,
                    'created_at' => $b->created_at->toIso8601String(),
                    'time_ago' => $b->created_at->diffForHumans(),
                ]);
        }

        // Unread messages
        $unreadMessages = collect();
        if ($messagesHasIsRead) {
            $unreadMessages = ContactMessage::where('is_read', false)
                ->latest()
                ->limit(50)
                ->get()
                ->map(fn (ContactMessage $m) => [
                    'id' => 'message_' . $m->id,
                    'type' => 'message',
                    'title' => $m->name,
                    'message' => $m->subject ?: mb_substr($m->message, 0, 80),
                    'url' => "/admin/contact-messages/{$m->id}",
                    'priority' => 'normal',
                    'read_at' => null,
                    'created_at' => $m->created_at->toIso8601String(),
                    'time_ago' => $m->created_at->diffForHumans(),
                ]);
        }

        // Stats
        $stats = [
            'total' => $notificationsTableExists ? $user->notifications()->count() : 0,
            'unread' => $notificationsTableExists ? $user->unreadNotifications()->count() : 0,
            'unread_bookings' => $bookingsHasIsRead ? Booking::where('is_read', false)->count() : 0,
            'unread_messages' => $messagesHasIsRead ? ContactMessage::where('is_read', false)->count() : 0,
            'today' => $notificationsTableExists ? $user->notifications()->whereDate('created_at', today())->count() : 0,
        ];

        // Type breakdown for filter chips
        $types = [];
        if ($notificationsTableExists && $user->notifications()->count() > 0) {
            $types = $user->notifications()
                ->selectRaw("JSON_UNQUOTE(JSON_EXTRACT(data, '$.type')) as ntype, COUNT(*) as cnt")
                ->groupByRaw("JSON_UNQUOTE(JSON_EXTRACT(data, '$.type'))")
                ->pluck('cnt', 'ntype')
                ->toArray();
        }

        return Inertia::render('Admin/Notifications/Index', [
            'notifications' => $mapped,
            'bookingNotifications' => $filter !== 'read' ? $unreadBookings : collect(),
            'messageNotifications' => $filter !== 'read' ? $unreadMessages : collect(),
            'stats' => $stats,
            'types' => $types,
            'filters' => $request->only(['filter', 'type', 'search']),
        ]);
    }

    public function markRead(Request $request, string $id): RedirectResponse
    {
        if (Schema::hasTable('notifications')) {
            $request->user()->notifications()->where('id', $id)->update(['read_at' => now()]);
        }

        return back();
    }

    public function markAllRead(Request $request): RedirectResponse
    {
        if (Schema::hasTable('notifications')) {
            $request->user()->unreadNotifications->markAsRead();
        }
        if (Schema::hasColumn('bookings', 'is_read')) {
            Booking::where('is_read', false)->update(['is_read' => true]);
        }
        if (Schema::hasColumn('contact_messages', 'is_read')) {
            ContactMessage::where('is_read', false)->update(['is_read' => true]);
        }

        return back()->with('success', 'All notifications marked as read.');
    }

    public function destroy(Request $request, string $id): RedirectResponse
    {
        if (Schema::hasTable('notifications')) {
            $request->user()->notifications()->where('id', $id)->delete();
        }

        AuditLogger::log('deleted', null, ['notification_id' => $id], 'Deleted a notification');

        return back();
    }

    public function destroyAll(Request $request): RedirectResponse
    {
        if (!Schema::hasTable('notifications')) {
            return back();
        }

        $readFilter = $request->input('only_read', false);

        if ($readFilter) {
            $count = $request->user()->notifications()->whereNotNull('read_at')->count();
            $request->user()->notifications()->whereNotNull('read_at')->delete();
            AuditLogger::log('deleted', null, ['scope' => 'read_only', 'count' => $count], 'Cleared read notifications');
        } else {
            $count = $request->user()->notifications()->count();
            $request->user()->notifications()->delete();
            AuditLogger::log('deleted', null, ['scope' => 'all', 'count' => $count], 'Cleared all notifications');
        }

        return back()->with('success', 'Notifications cleared.');
    }

    protected function getTitle(array $data, string $type): string
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
            'booking' => 'New Booking',
            'message' => 'Contact Message',
            default => $data['lead_name'] ?? $data['patient_name'] ?? 'Notification',
        };
    }

    protected function getUrl(array $data, string $type): string
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
