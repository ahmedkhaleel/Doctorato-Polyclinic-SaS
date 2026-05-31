<?php

namespace App\Services\Notifications\Contracts;

use App\Services\Notifications\DeliveryResult;
use App\Services\Notifications\NotificationMessage;

interface ChannelDriver
{
    /** Channel key: whatsapp | sms | email | in_app */
    public function key(): string;

    /** Whether this channel is enabled and has the credentials it needs. */
    public function isConfigured(): bool;

    public function send(NotificationMessage $message): DeliveryResult;
}
