/**
 * Converts Arabic (٠-٩) and Persian/Urdu (۰-۹) numerals to English (0-9).
 * Useful for normalizing phone numbers and other numeric inputs.
 */
export function toEnglishNumbers(str) {
    if (!str) return str;
    return String(str)
        // Arabic-Indic digits: ٠١٢٣٤٥٦٧٨٩
        .replace(/[٠-٩]/g, (d) => d.charCodeAt(0) - 0x0660)
        // Extended Arabic-Indic (Persian/Urdu) digits: ۰۱۲۳۴۵۶۷۸۹
        .replace(/[۰-۹]/g, (d) => d.charCodeAt(0) - 0x06F0);
}

export function useArabicNumbers() {
    return { toEnglishNumbers };
}
