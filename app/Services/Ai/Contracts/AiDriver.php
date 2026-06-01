<?php

namespace App\Services\Ai\Contracts;

use App\Services\Ai\AiResult;

interface AiDriver
{
    /** @param array<int,array{role:string,content:string}> $messages */
    public function chat(array $messages, array $options = []): AiResult;

    /** @param string|array<int,string> $input @return array<int,array<float>> */
    public function embed(string|array $input, array $options = []): array;

    /** Transcribe audio (Whisper). $contents is the raw file bytes. Returns text. */
    public function transcribe(string $contents, string $filename, array $options = []): string;

    /** Lightweight connectivity check. Returns [ok=>bool, message=>string, model=>?string]. */
    public function ping(): array;

    /** Whether the driver is configured and the AI layer is enabled. */
    public function isReady(): bool;

    public function name(): string;
}
