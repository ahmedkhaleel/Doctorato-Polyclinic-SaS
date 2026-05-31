<?php

namespace App\Services\Notifications;

use App\Models\NotificationChannelRoute;
use App\Models\NotificationEvent;
use App\Models\NotificationLog;
use App\Models\NotificationTemplate;
use App\Services\Notifications\Channels\EmailChannel;
use App\Services\Notifications\Channels\InAppChannel;
use App\Services\Notifications\Channels\SmsChannel;
use App\Services\Notifications\Channels\WhatsAppChannel;
use App\Services\Notifications\Contracts\ChannelDriver;
use Illuminate\Database\Eloquent\Model;

/**
 * Central dispatcher for the Notifications Hub.
 *
 * Pipeline per event:
 *   resolveRoute → (per channel) consent → quota → dedup → render → send → log
 *
 * External channels (whatsapp/sms/email) form a FALLBACK group ordered by route
 * priority — the first one that succeeds wins, so the recipient is never charged
 * on two channels for the same event. in_app is free and always recorded.
 */
class NotificationService
{
    private const EXTERNAL = ['whatsapp', 'sms', 'email'];

    public function __construct(private NotificationQuota $quota) {}

    /**
     * Process an event for a recipient. Returns the NotificationLog rows created.
     *
     * @param  string  $eventKey  e.g. "booking.confirmed"
     * @param  Model|null  $recipient  Patient | Lead | User | null
     * @param  array  $data  template vars + optional 'body','subject','to','dedup_key','locale'
     * @param  array|null  $forceChannels  override the routed channels for this send
     * @return NotificationLog[]
     */
    public function process(string $eventKey, ?Model $recipient, array $data = [], ?array $forceChannels = null, bool $allowDefer = true): array
    {
        $event = NotificationEvent::where('key', $eventKey)->first();
        if (! $event || ! $event->is_active) {
            return [];
        }

        // Quiet hours: hold reminder/marketing until the window opens instead of
        // dropping them. Transactional always proceeds. $allowDefer is false when
        // the scheduled-dispatch worker re-runs a held notification.
        if ($allowDefer && $forceChannels === null && $event->category !== 'transactional'
            && ! $this->withinSendWindow($event->category)) {
            \App\Models\ScheduledNotification::create([
                'event_key' => $eventKey,
                'recipient_type' => $recipient?->getMorphClass(),
                'recipient_id' => $recipient?->getKey(),
                'data' => $data,
                'channels' => null,
                'reason' => 'quiet_hours',
                'send_after' => $this->quietWindowEnd(),
            ]);

            return [];
        }

        $channels = $forceChannels !== null
            ? $forceChannels
            : NotificationChannelRoute::where('event_key', $eventKey)
                ->where('enabled', true)
                ->orderBy('priority')
                ->pluck('channel')
                ->all();

        // Smart routing: reorder the fallback group by the recipient's learned
        // channel preference (opt-in via setting; only for routed sends).
        if ($forceChannels === null && $recipient
            && \App\Models\Setting::get('notifications_smart_routing') === '1') {
            $channels = app(ChannelPreferenceService::class)->reorder($recipient, $channels);
        }

        $logs = [];
        $externalSucceeded = false;

        foreach ($channels as $channel) {
            $isExternal = in_array($channel, self::EXTERNAL, true);

            // Fallback group: once one external channel succeeds, skip the rest.
            if ($isExternal && $externalSucceeded) {
                continue;
            }

            $driver = $this->driverFor($channel);
            if (! $driver || ! $driver->isConfigured()) {
                continue; // channel disabled / unknown — silently skip
            }

            if (! $this->hasConsent($recipient, $event->category, $channel)) {
                $logs[] = $this->logSkipped($recipient, $channel, $event, 'no consent', $data);

                continue;
            }

            // Marketing frequency cap (per recipient, rolling 7 days).
            if ($channel !== 'in_app' && ! $this->frequencyOk($recipient, $event->category)) {
                $logs[] = $this->logSkipped($recipient, $channel, $event, 'frequency cap', $data);

                continue;
            }

            if (! $this->quota->allows($channel)) {
                $logs[] = $this->logSkipped($recipient, $channel, $event, $this->quota->blockReason($channel), $data);

                continue;
            }

            $dedupKey = isset($data['dedup_key']) ? $data['dedup_key'].':'.$channel : null;
            if ($dedupKey && $this->isDuplicate($dedupKey)) {
                $logs[] = $this->logSkipped($recipient, $channel, $event, 'duplicate', $data, $dedupKey);

                continue;
            }

            $to = $this->resolveTo($recipient, $channel, $data);
            if ($isExternal && ! $to) {
                continue; // can't reach the recipient on this channel
            }

            [$body, $subject, $templateId] = $this->render($eventKey, $channel, $recipient, $data);

            // Persist the rendered body + subject so the patient history shows the
            // real message text and notifications:retry can resend without re-render.
            $meta = $data['meta'] ?? [];
            $meta['body'] = $meta['body'] ?? $body;
            if ($subject) {
                $meta['subject'] = $subject;
            }

            // One-click unsubscribe link for marketing email (compliance).
            if ($channel === 'email' && $event->category === 'marketing' && $recipient && $recipient->getKey()) {
                $meta['unsubscribe_url'] = $this->unsubscribeUrl($recipient);
            }

            $log = NotificationLog::create([
                'recipient_type' => $recipient ? $recipient->getMorphClass() : null,
                'recipient_id' => $recipient?->getKey(),
                'to' => $to,
                'channel' => $channel,
                'event_key' => $eventKey,
                'template_id' => $templateId,
                'status' => NotificationLog::STATUS_QUEUED,
                'dedup_key' => $dedupKey,
                'meta' => $meta,
            ]);

            $result = $driver->send(new NotificationMessage($channel, $to, $body, $subject, $eventKey, $meta));

            $log->update([
                'status' => $result->success ? NotificationLog::STATUS_SENT : NotificationLog::STATUS_FAILED,
                'provider' => $result->provider,
                'provider_ref' => $result->providerRef,
                'cost' => $result->cost,
                'error' => $result->error,
                'sent_at' => $result->success ? now() : null,
            ]);

            $logs[] = $log;

            if ($isExternal && $result->success) {
                $externalSucceeded = true;
            }
        }

        return $logs;
    }

    /**
     * Re-send a previously-failed log in place (used by notifications:retry).
     * Respects channel config + quota; increments meta.retry_count.
     */
    public function resend(NotificationLog $log): bool
    {
        $driver = $this->driverFor($log->channel);
        if (! $driver || ! $driver->isConfigured() || ! $this->quota->allows($log->channel)) {
            return false;
        }

        $meta = $log->meta ?? [];
        $result = $driver->send(new NotificationMessage(
            $log->channel, $log->to, $meta['body'] ?? '', $meta['subject'] ?? null, $log->event_key, $meta
        ));

        $meta['retry_count'] = ($meta['retry_count'] ?? 0) + 1;
        $log->update([
            'status' => $result->success ? NotificationLog::STATUS_SENT : NotificationLog::STATUS_FAILED,
            'provider' => $result->provider,
            'provider_ref' => $result->providerRef,
            'cost' => $result->cost,
            'error' => $result->error,
            'sent_at' => $result->success ? now() : $log->sent_at,
            'meta' => $meta,
        ]);

        return $result->success;
    }

    /** Map a channel key to its driver instance. */
    private function driverFor(string $channel): ?ChannelDriver
    {
        return match ($channel) {
            'in_app' => app(InAppChannel::class),
            'email' => app(EmailChannel::class),
            'sms' => app(SmsChannel::class),
            'whatsapp' => app(WhatsAppChannel::class),
            default => null,
        };
    }

    /** Transactional always sends; reminder/marketing respect per-channel consent. */
    private function hasConsent(?Model $recipient, string $category, string $channel): bool
    {
        // Essential (transactional) messages and free in-app records always go out.
        if ($category === 'transactional' || $channel === 'in_app' || ! $recipient) {
            return true;
        }

        if (! method_exists($recipient, 'wantsNotification')) {
            return true;
        }

        // Consent is tracked for email, sms and whatsapp. Any other channel
        // the model can't express defaults to allowed.
        if (! in_array($channel, ['email', 'sms', 'whatsapp'], true)) {
            return true;
        }

        // Event categories (reminder/marketing) → patient preference categories.
        $consentCategory = ['reminder' => 'reminders', 'marketing' => 'marketing'][$category] ?? $category;

        return (bool) $recipient->wantsNotification($consentCategory, $channel);
    }

    private function isDuplicate(string $dedupKey): bool
    {
        return NotificationLog::where('dedup_key', $dedupKey)
            ->whereIn('status', [
                NotificationLog::STATUS_QUEUED,
                NotificationLog::STATUS_SENT,
                NotificationLog::STATUS_DELIVERED,
                NotificationLog::STATUS_READ,
            ])
            ->exists();
    }

    private function resolveTo(?Model $recipient, string $channel, array $data): ?string
    {
        if (isset($data['to'])) {
            return $data['to'];
        }
        if (! $recipient) {
            return null;
        }

        return match ($channel) {
            'email' => $recipient->email ?? null,
            'sms', 'whatsapp' => $recipient->whatsapp ?? $recipient->phone ?? null,
            default => null,
        };
    }

    /**
     * Render body/subject for a channel. Prefers a stored template; falls back to
     * an explicit body in $data, then the event label.
     *
     * @return array{0:string,1:?string,2:?int}
     */
    private function render(string $eventKey, string $channel, ?Model $recipient, array $data): array
    {
        $locale = $data['locale'] ?? $this->localeFor($recipient);

        $template = NotificationTemplate::where('event_key', $eventKey)
            ->where('channel', $channel)
            ->where('is_active', true)
            ->first();

        if ($template) {
            return [$template->render($locale, $data), $template->subject, $template->id];
        }

        $body = $data['body'] ?? '';
        $subject = $data['subject'] ?? null;

        return [$body, $subject, null];
    }

    /** Reminder/marketing are suppressed inside the quiet window; transactional always passes. */
    private function withinSendWindow(string $category): bool
    {
        if ($category === 'transactional') {
            return true;
        }

        $start = \App\Models\Setting::get('notifications_quiet_start'); // "HH:MM"
        $end = \App\Models\Setting::get('notifications_quiet_end');
        if (! $start || ! $end) {
            return true; // no quiet hours configured
        }

        $now = now()->format('H:i');
        // Window may wrap past midnight (e.g. 22:00 → 08:00).
        if ($start <= $end) {
            return ! ($now >= $start && $now < $end);
        }

        return ! ($now >= $start || $now < $end);
    }

    /** Signed one-click unsubscribe URL for a patient recipient (90-day validity). */
    private function unsubscribeUrl(Model $recipient): ?string
    {
        if ($recipient->getMorphClass() !== (new \App\Models\Patient)->getMorphClass()) {
            return null;
        }

        try {
            return \Illuminate\Support\Facades\URL::temporarySignedRoute(
                'notifications.unsubscribe', now()->addDays(90), ['patient' => $recipient->getKey()]
            );
        } catch (\Throwable $e) {
            return null;
        }
    }

    /** The next datetime the quiet window closes (when held sends resume). */
    private function quietWindowEnd(): \Illuminate\Support\Carbon
    {
        $end = \App\Models\Setting::get('notifications_quiet_end') ?: '08:00';
        $resume = now()->setTimeFromTimeString($end);
        if ($resume->lessThanOrEqualTo(now())) {
            $resume->addDay();
        }

        return $resume;
    }

    /** Marketing-only rolling-window cap per recipient (0 / no recipient = unlimited). */
    private function frequencyOk(?Model $recipient, string $category): bool
    {
        if ($category !== 'marketing' || ! $recipient) {
            return true;
        }

        $cap = (int) \App\Models\Setting::get('notifications_marketing_weekly_cap', '0');
        if ($cap <= 0) {
            return true;
        }

        $marketingKeys = NotificationEvent::where('category', 'marketing')->pluck('key');
        $count = NotificationLog::where('recipient_type', $recipient->getMorphClass())
            ->where('recipient_id', $recipient->getKey())
            ->where('channel', '!=', 'in_app')
            ->whereIn('event_key', $marketingKeys)
            ->whereIn('status', [NotificationLog::STATUS_SENT, NotificationLog::STATUS_DELIVERED, NotificationLog::STATUS_READ])
            ->where('created_at', '>=', now()->subDays(7))
            ->count();

        return $count < $cap;
    }

    private function localeFor(?Model $recipient): string
    {
        // Patients store this as `preferred_language`; other models may use `preferred_locale`.
        $pref = $recipient->preferred_language ?? $recipient->preferred_locale ?? null;
        if (! empty($pref)) {
            return $pref;
        }

        return app()->getLocale() ?: 'ar';
    }

    private function logSkipped(?Model $recipient, string $channel, NotificationEvent $event, string $reason, array $data, ?string $dedupKey = null): NotificationLog
    {
        return NotificationLog::create([
            'recipient_type' => $recipient ? $recipient->getMorphClass() : null,
            'recipient_id' => $recipient?->getKey(),
            'to' => $this->resolveTo($recipient, $channel, $data),
            'channel' => $channel,
            'event_key' => $event->key,
            'status' => NotificationLog::STATUS_SKIPPED,
            'error' => $reason,
            'dedup_key' => $dedupKey,
        ]);
    }
}
