<?php

namespace App\Services\Ai;

/** Immutable result of an AI generation. */
class AiResult
{
    public function __construct(
        public readonly string $text,
        public readonly string $model,
        public readonly int $promptTokens = 0,
        public readonly int $completionTokens = 0,
        public readonly array $raw = [],
    ) {}

    public function totalTokens(): int
    {
        return $this->promptTokens + $this->completionTokens;
    }
}
