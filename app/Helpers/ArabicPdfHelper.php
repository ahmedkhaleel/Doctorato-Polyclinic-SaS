<?php

namespace App\Helpers;

use ArPHP\I18N\Arabic;

class ArabicPdfHelper
{
    protected static ?Arabic $arabic = null;

    protected static function getArabic(): Arabic
    {
        if (static::$arabic === null) {
            static::$arabic = new Arabic();
        }

        return static::$arabic;
    }

    /**
     * Check if a string contains Arabic characters.
     */
    public static function containsArabic(string $text): bool
    {
        return (bool) preg_match('/[\x{0600}-\x{06FF}\x{0750}-\x{077F}\x{08A0}-\x{08FF}\x{FB50}-\x{FDFF}\x{FE70}-\x{FEFF}]/u', $text);
    }

    /**
     * Shape Arabic text for proper rendering in PDF.
     * Converts Arabic characters to their presentation forms (joined shapes)
     * and handles RTL reordering for DomPDF compatibility.
     */
    public static function shape(?string $text): string
    {
        if (empty($text)) {
            return $text ?? '';
        }

        if (! static::containsArabic($text)) {
            return $text;
        }

        $arabic = static::getArabic();

        // Apply Arabic text glyphs (shaping - joins characters properly)
        $shaped = $arabic->utf8Glyphs($text);

        return $shaped;
    }

    /**
     * Process all Arabic text in an array of model data.
     * Useful for processing invoice items, prescription items, etc.
     */
    public static function shapeArray(array $data, array $keys): array
    {
        foreach ($keys as $key) {
            if (isset($data[$key]) && is_string($data[$key])) {
                $data[$key] = static::shape($data[$key]);
            }
        }

        return $data;
    }
}
