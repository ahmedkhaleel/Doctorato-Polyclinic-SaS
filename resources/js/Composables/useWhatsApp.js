/**
 * Build WhatsApp click-to-chat URLs from a phone number, optionally
 * with a pre-filled message.
 *
 * Egyptian-context-aware: a 10- or 11-digit local number gets a "20"
 * country code prepended automatically, since most clinic patients
 * have phones stored as "01012345678" (no country prefix).
 *
 * Usage in a Vue component:
 *   import { useWhatsApp } from '@/Composables/useWhatsApp';
 *   const { whatsappLink, phoneDisplay } = useWhatsApp();
 *   <a :href="whatsappLink(patient.phone)">Chat</a>
 */
export function useWhatsApp(defaultCountryCode = '20') {
    /**
     * Strip everything that's not a digit, then ensure a country code.
     * Returns null if the input doesn't look like a real phone number.
     */
    function normalize(phone) {
        if (!phone) return null;
        const digits = String(phone).replace(/\D+/g, '');
        if (!digits) return null;

        // Already has a country code (12+ digits) — trust it.
        if (digits.length >= 12) return digits;

        // Egyptian local format: drop leading 0 if present, then prepend country code.
        if (digits.length === 10 || digits.length === 11) {
            const local = digits.startsWith('0') ? digits.slice(1) : digits;
            return defaultCountryCode + local;
        }

        // Too short to be valid — give up.
        if (digits.length < 8) return null;

        // Anything else (8-9 digits): just prepend the country code.
        return defaultCountryCode + digits;
    }

    /**
     * Build a wa.me link. Returns null if the phone is invalid so
     * callers can hide the button entirely (better than a broken link).
     */
    function whatsappLink(phone, message = '') {
        const normalized = normalize(phone);
        if (!normalized) return null;
        const base = `https://wa.me/${normalized}`;
        return message ? `${base}?text=${encodeURIComponent(message)}` : base;
    }

    /**
     * Pretty-print a phone for the UI. Doesn't change the value,
     * just formats it as +20 xxx xxx xxxx style for readability.
     */
    function phoneDisplay(phone) {
        const normalized = normalize(phone);
        if (!normalized) return phone || '';
        // Format: +CC XXX XXX XXXX
        const cc = normalized.slice(0, 2);
        const rest = normalized.slice(2);
        const groups = rest.match(/.{1,3}/g) || [rest];
        return `+${cc} ${groups.join(' ')}`;
    }

    return { whatsappLink, phoneDisplay, normalize };
}
