<?php

namespace App\Services\Notifications;

/** A rendered message ready to hand to a single channel driver. */
class NotificationMessage
{
    public function __construct(
        public string $channel,
        public ?string $to,          // phone / email / null (in-app)
        public string $body,
        public ?string $subject = null,
        public ?string $eventKey = null,
        public array $meta = [],
    ) {}
}
