<?php

namespace App\Services\Notifications\Channels;

use App\Models\NotificationChannel;
use App\Services\Notifications\Contracts\ChannelDriver;
use App\Services\Notifications\DeliveryResult;
use App\Services\Notifications\NotificationMessage;
use App\Services\SmsService;

/**
 * SMS channel. Wraps the existing SmsService (unifonic/twilio/gateway) as-is.
 * The SMS Misr ("اس ام اس مصر") provider is added to SmsService in P2; this
 * driver does not need to change when that lands.
 */
class SmsChannel implements ChannelDriver
{
    public function key(): string
    {
        return 'sms';
    }

    public function isConfigured(): bool
    {
        $channel = NotificationChannel::for('sms');

        return $channel && $channel->enabled;
    }

    public function send(NotificationMessage $message): DeliveryResult
    {
        if (! $message->to) {
            return DeliveryResult::fail('No phone number.', 'sms');
        }

        $fromName = optional(NotificationChannel::for('sms'))->from_name;
        $result = SmsService::send($message->to, $message->body, $fromName);

        $provider = $result['provider'] ?? 'sms';

        if ($result['success'] ?? false) {
            return DeliveryResult::ok($provider, (float) SmsService::estimateCost($message->body));
        }

        return DeliveryResult::fail($result['message'] ?? 'SMS send failed.', $provider);
    }
}
