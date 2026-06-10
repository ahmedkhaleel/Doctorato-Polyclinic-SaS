<?php

namespace App\Services\Crm;

/**
 * CRM-1 — lightweight phone normalizer for lead capture/import/dedupe.
 *
 * Normalizes to digits-only international form (no '+'), which is exactly what
 * wa.me links and the SMS hub expect. EG/SA-aware (the clinic's markets):
 *   01012345678   → 201012345678   (Egypt mobile)
 *   00201012345678→ 201012345678
 *   +9665xxxxxxxx → 9665xxxxxxxx
 *   05xxxxxxxx    → 9665xxxxxxxx   (Saudi mobile)
 * Anything already international passes through; garbage returns the cleaned
 * digits (never null) so a lead is NEVER blocked — `isLikelyValid` lets the UI
 * flag suspicious numbers instead.
 */
class PhoneNormalizer
{
    public static function normalize(?string $phone): string
    {
        $raw = trim((string) $phone);
        if ($raw === '') {
            return '';
        }

        // Strip everything but digits (drops +, spaces, dashes, parentheses).
        $digits = preg_replace('/\D+/', '', $raw) ?? '';

        // 00 international prefix → drop it.
        if (str_starts_with($digits, '00')) {
            $digits = substr($digits, 2);
        }

        // Egypt: 01XXXXXXXXX (11 digits) → 2 + number.
        if (preg_match('/^01[0125][0-9]{8}$/', $digits)) {
            return '2'.$digits;
        }

        // Saudi: 05XXXXXXXX (10 digits) → 966 + number-without-leading-0.
        if (preg_match('/^05[0-9]{8}$/', $digits)) {
            return '966'.substr($digits, 1);
        }

        // Already-international Egypt/Saudi or anything else: as-is.
        return $digits;
    }

    /** Loose plausibility check (8–15 digits per E.164 envelope). */
    public static function isLikelyValid(?string $phone): bool
    {
        $n = self::normalize($phone);

        return $n !== '' && strlen($n) >= 8 && strlen($n) <= 15;
    }

    /**
     * The candidate forms to match against possibly-unnormalized stored rows
     * (normalized + raw-trimmed + local Egyptian/Saudi form).
     */
    public static function matchForms(?string $phone): array
    {
        $raw = trim((string) $phone);
        $norm = self::normalize($phone);
        $forms = array_filter([$raw, $norm]);

        // Re-derive the local form so '201012345678' also matches stored '01012345678'.
        if (str_starts_with($norm, '2') && strlen($norm) === 12) {
            $forms[] = substr($norm, 1); // 201… → 01… (local part already starts with 0)
        }
        if (str_starts_with($norm, '966') && strlen($norm) === 12) {
            $forms[] = '0'.substr($norm, 3); // 9665… → 05…
        }

        return array_values(array_unique($forms));
    }
}
