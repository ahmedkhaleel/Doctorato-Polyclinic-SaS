<?php

namespace App\Services\Ai\Drivers;

use App\Services\Ai\Contracts\AiDriver;
use App\Services\Ai\Exceptions\AiUnavailableException;

/** Safe fallback used when the AI layer is disabled or unconfigured. Never calls out. */
class NullDriver implements AiDriver
{
    public function chat(array $messages, array $options = []): \App\Services\Ai\AiResult
    {
        throw new AiUnavailableException('disabled', 'AI layer is disabled.');
    }

    public function embed(string|array $input, array $options = []): array
    {
        throw new AiUnavailableException('disabled', 'AI layer is disabled.');
    }

    public function transcribe(string $contents, string $filename, array $options = []): string
    {
        throw new AiUnavailableException('disabled', 'AI layer is disabled.');
    }

    public function ping(): array
    {
        return ['ok' => false, 'message' => 'AI layer is disabled or no API key configured.', 'model' => null];
    }

    public function isReady(): bool
    {
        return false;
    }

    public function name(): string
    {
        return 'null';
    }
}
