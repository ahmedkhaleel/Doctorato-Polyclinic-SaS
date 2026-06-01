<?php

namespace App\Services\Ai\Features;

use App\Services\Ai\AiManager;

/** Audio → text (Whisper). Used for telemedicine notes and doctor dictation. */
class Transcriber
{
    public function __construct(private readonly AiManager $ai) {}

    public function transcribe(string $contents, string $filename, array $options = []): string
    {
        return $this->ai->transcribe('consult_transcription', $contents, $filename, $options);
    }
}
