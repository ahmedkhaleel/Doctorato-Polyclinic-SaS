<?php

namespace App\Http\Controllers\Patient;

use App\Services\Notifications\NotificationFeedService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Patient-facing in-app notification feed (the bell + the full page).
 * Reads notification_logs (channel=in_app) for the logged-in patient.
 */
class PatientNotificationController extends BasePatientController
{
    public function __construct(private NotificationFeedService $feed) {}

    public function index(Request $request): Response
    {
        $patient = $this->patient($request);

        return Inertia::render('Patient/Notifications/Index', [
            'notifications' => $this->feed->paginate($patient),
            'unreadCount' => $this->feed->unreadCount($patient),
        ]);
    }

    /** Lightweight JSON for the bell (recent items + unread count) — polled. */
    public function bell(Request $request): JsonResponse
    {
        $patient = $this->patient($request);

        return response()->json([
            'unread' => $this->feed->unreadCount($patient),
            'items' => $this->feed->feed($patient, 8),
        ]);
    }

    public function markRead(Request $request, int $id): RedirectResponse
    {
        $this->feed->markRead($this->patient($request), $id);

        return back();
    }

    public function markAllRead(Request $request): RedirectResponse
    {
        $this->feed->markAllRead($this->patient($request));

        return back();
    }
}
