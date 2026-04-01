import DOMPurify from 'dompurify';

/**
 * Configure DOMPurify globally — allow safe tags only.
 * This is used by all v-html renders to prevent XSS attacks.
 */
const purifyConfig = {
    ALLOWED_TAGS: [
        'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
        'p', 'br', 'hr',
        'strong', 'b', 'em', 'i', 'u', 's', 'strike', 'del', 'sub', 'sup',
        'a', 'img',
        'ul', 'ol', 'li',
        'blockquote', 'pre', 'code',
        'table', 'thead', 'tbody', 'tfoot', 'tr', 'th', 'td',
        'div', 'span',
        'figure', 'figcaption',
    ],
    ALLOWED_ATTR: [
        'href', 'title', 'target', 'rel',
        'src', 'alt', 'width', 'height',
        'class', 'style',
        'colspan', 'rowspan',
    ],
    ALLOWED_URI_REGEXP: /^(?:(?:https?|mailto):|[^a-z]|[a-z+.\-]+(?:[^a-z+.\-:]|$))/i,
    ADD_ATTR: ['target'],
};

/**
 * Sanitize HTML string using DOMPurify.
 * Use this whenever rendering user/admin-provided HTML with v-html.
 *
 * @param {string|null} html - The HTML string to sanitize
 * @returns {string} - Sanitized HTML safe for v-html
 */
export function sanitizeHtml(html) {
    if (!html) return '';
    return DOMPurify.sanitize(html, purifyConfig);
}

/**
 * Vue composable that provides sanitizeHtml function.
 */
export function useSanitize() {
    return { sanitizeHtml };
}
