<?php

namespace App\Http\Controllers;

use App\Services\Notifications\NotificationFeedService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Shared in-app notification feed for staff panels (admin/doctor/secretary).
 * Recipient is the authenticated User. Each panel mounts these via its own
 * route prefix and passes its layout name through to the shared page.
 */
class StaffNotificationController extends Controller
{
    public function __construct(private NotificationFeedService $feed) {}

    public function index(Request $request, string $panel = 'admin'): Response
    {
        $user = $request->user();

        return Inertia::render('Shared/NotificationsFeed', [
            'panel' => $panel,
            'notifications' => $this->feed->paginate($user),
            'unreadCount' => $this->feed->unreadCount($user),
        ]);
    }

    public function bell(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'unread' => $this->feed->unreadCount($user),
            'items' => $this->feed->feed($user, 8),
        ]);
    }

    public function markRead(Request $request, int $id): RedirectResponse
    {
        $this->feed->markRead($request->user(), $id);

        return back();
    }

    public function markAllRead(Request $request): RedirectResponse
    {
        $this->feed->markAllRead($request->user());

        return back();
    }
}
