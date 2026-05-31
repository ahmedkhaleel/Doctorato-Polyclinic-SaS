<?php

namespace App\Services\Notifications;

use App\Models\NotificationLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * Reads the in-app notification feed (notification_logs where channel=in_app)
 * for any recipient (Patient | User). Powers the bell + feed across all panels.
 */
class NotificationFeedService
{
    /** Statuses that should appear in a feed (in_app never "fails"). */
    private const VISIBLE = [
        NotificationLog::STATUS_SENT,
        NotificationLog::STATUS_DELIVERED,
        NotificationLog::STATUS_READ,
    ];

    private function base(Model $recipient)
    {
        return NotificationLog::query()
            ->where('channel', 'in_app')
            ->where('recipient_type', $recipient->getMorphClass())
            ->where('recipient_id', $recipient->getKey())
            ->whereIn('status', self::VISIBLE);
    }

    /** Recent items for the bell dropdown / feed. */
    public function feed(Model $recipient, int $limit = 20): Collection
    {
        return $this->base($recipient)->latest()->limit($limit)->get()->map(fn ($l) => $this->present($l));
    }

    /** Paginated feed for the full page. */
    public function paginate(Model $recipient, int $perPage = 25)
    {
        return $this->base($recipient)->latest()->paginate($perPage)
            ->through(fn ($l) => $this->present($l));
    }

    public function unreadCount(Model $recipient): int
    {
        return (int) $this->base($recipient)->whereNull('read_at')->count();
    }

    /** Mark one item read (only if it belongs to the recipient). Returns success. */
    public function markRead(Model $recipient, int $id): bool
    {
        return $this->base($recipient)->whereNull('read_at')->where('id', $id)
            ->update(['read_at' => now(), 'status' => NotificationLog::STATUS_READ]) > 0;
    }

    public function markAllRead(Model $recipient): int
    {
        return $this->base($recipient)->whereNull('read_at')
            ->update(['read_at' => now(), 'status' => NotificationLog::STATUS_READ]);
    }

    private function present(NotificationLog $log): array
    {
        $meta = $log->meta ?? [];

        return [
            'id' => $log->id,
            'event_key' => $log->event_key,
            'title' => $meta['title'] ?? null,
            'body' => $meta['body'] ?? '',
            'url' => $meta['url'] ?? null,
            'icon' => $this->iconFor($log->event_key),
            'is_read' => $log->read_at !== null,
            'created_at' => $log->created_at?->toIso8601String(),
        ];
    }

    /** Map an event family to a UI icon key (the frontend renders the SVG). */
    private function iconFor(?string $eventKey): string
    {
        $family = explode('.', (string) $eventKey)[0];

        return match ($family) {
            'booking', 'appointment' => 'calendar',
            'payment', 'invoice' => 'receipt',
            'loyalty' => 'star',
            'visit' => 'clipboard',
            'dental', 'derma', 'pediatric', 'obgyn' => 'heart',
            default => 'bell',
        };
    }
}
