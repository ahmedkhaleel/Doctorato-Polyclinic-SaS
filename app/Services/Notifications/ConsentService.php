<?php

namespace App\Services\Notifications;

use App\Models\NotificationConsent;
use Illuminate\Database\Eloquent\Model;

/**
 * Records consent changes to the append-only audit log. The recipient row
 * (e.g. patients.notify_*) remains the source of truth for current state;
 * this captures who/when/how it changed.
 */
class ConsentService
{
    /**
     * Log a single consent change.
     */
    public static function record(Model $recipient, string $channel, string $category, bool $optedIn, string $source, ?string $ip = null): void
    {
        NotificationConsent::create([
            'recipient_type' => $recipient->getMorphClass(),
            'recipient_id' => $recipient->getKey(),
            'channel' => $channel,
            'category' => $category,
            'opted_in' => $optedIn,
            'source' => $source,
            'ip' => $ip,
        ]);
    }

    /**
     * Diff a set of notify_{channel}_{category} flags against the recipient's
     * current values and log only the ones that actually changed.
     *
     * @param  array  $flags  e.g. ['notify_sms_marketing' => true, ...]
     */
    public static function sync(Model $recipient, array $flags, string $source, ?string $ip = null): void
    {
        foreach ($flags as $key => $value) {
            if (! preg_match('/^notify_(email|sms|whatsapp)_(bookings|reminders|marketing)$/', $key, $m)) {
                continue;
            }
            $current = (bool) $recipient->getAttribute($key);
            $new = (bool) $value;
            if ($current !== $new) {
                self::record($recipient, $m[1], $m[2], $new, $source, $ip);
            }
        }
    }

    /** Opt the recipient out of ALL marketing channels (STOP / unsubscribe). */
    public static function optOutMarketing(Model $recipient, string $source, ?string $ip = null): void
    {
        $changed = [];
        foreach (['email', 'sms', 'whatsapp'] as $channel) {
            $col = "notify_{$channel}_marketing";
            if ($recipient->getAttribute($col)) {
                $changed[$col] = false;
            }
            self::record($recipient, $channel, 'marketing', false, $source, $ip);
        }
        if ($changed) {
            $recipient->forceFill($changed)->save();
        }
    }
}
