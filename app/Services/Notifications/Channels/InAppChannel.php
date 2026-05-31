<?php

namespace App\Services\Notifications\Channels;

use App\Models\NotificationChannel;
use App\Services\Notifications\Contracts\ChannelDriver;
use App\Services\Notifications\DeliveryResult;
use App\Services\Notifications\NotificationMessage;

/**
 * In‑app channel. The notification_logs row IS the in‑app record shown in the
 * patient/lead file and the bell — so delivery is always successful and free.
 */
class InAppChannel implements ChannelDriver
{
    public function key(): string
    {
        return 'in_app';
    }

    public function isConfigured(): bool
    {
        return (bool) optional(NotificationChannel::for('in_app'))->enabled;
    }

    public function send(NotificationMessage $message): DeliveryResult
    {
        return DeliveryResult::ok('internal', 0.0);
    }
}
