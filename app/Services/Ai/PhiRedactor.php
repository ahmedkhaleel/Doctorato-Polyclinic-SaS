<?php

namespace App\Services\Ai;

use App\Models\Setting;

/**
 * Minimises PHI before text leaves the system. Replaces Egyptian phone numbers,
 * 14-digit national IDs, and emails with stable placeholders, and can restore them
 * in the AI output. Active only when ai_phi_redaction is enabled.
 */
class PhiRedactor
{
    private array $map = [];

    public function enabled(): bool
    {
        return (bool) Setting::get('ai_phi_redaction', true);
    }

    public function redact(string $text): string
    {
        if (! $this->enabled() || $text === '') {
            return $text;
        }

        $patterns = [
            'NATID' => '/\b\d{14}\b/',
            'PHONE' => '/\b(?:\+?20)?01\d{9}\b/',
            'EMAIL' => '/[A-Za-z0-9._%+\-]+@[A-Za-z0-9.\-]+\.[A-Za-z]{2,}/',
        ];

        foreach ($patterns as $label => $pattern) {
            $text = preg_replace_callback($pattern, function ($m) use ($label) {
                $token = '['.$label.'_'.(count($this->map) + 1).']';
                $this->map[$token] = $m[0];

                return $token;
            }, $text);
        }

        return $text;
    }

    /** Restore the original values into AI output. */
    public function restore(string $text): string
    {
        return strtr($text, $this->map);
    }

    public function reset(): void
    {
        $this->map = [];
    }

    /** Whether any identifier was redacted in the current cycle (PHI present). */
    public function hasReplacements(): bool
    {
        return $this->map !== [];
    }
}
