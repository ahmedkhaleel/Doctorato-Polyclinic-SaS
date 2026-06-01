<?php

namespace App\Services\Ai\Exceptions;

use RuntimeException;

/** Thrown when an AI call is blocked (disabled, feature off, over budget, rate-limited). */
class AiUnavailableException extends RuntimeException
{
    public function __construct(
        public readonly string $reason = 'disabled',
        string $message = 'AI is unavailable',
    ) {
        parent::__construct($message);
    }
}
