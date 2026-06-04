/**
 * v-focus-trap — accessibility helper for modals/dialogs.
 *
 * Attach to a modal's panel element. Because modals are rendered with `v-if`,
 * this directive's mounted/unmounted lifecycle fires exactly when the modal
 * opens and closes, so it can:
 *   1. remember what was focused before the modal opened,
 *   2. move focus into the modal (first focusable element, or the panel),
 *   3. trap Tab / Shift+Tab inside the modal (focus can't escape behind it),
 *   4. close on Escape — by calling the bound handler,
 *   5. restore focus to the trigger element when the modal closes.
 *
 * Usage:
 *   <div v-if="showModal" ...>            <!-- overlay -->
 *     <div v-focus-trap="() => (showModal = false)" role="dialog" aria-modal="true">
 *       ...
 *     </div>
 *   </div>
 *
 * The binding value is OPTIONAL — without it, Escape does nothing but the
 * focus trap + restore still work. Pass a function to enable Escape-to-close.
 */

const FOCUSABLE = [
    'a[href]',
    'button:not([disabled])',
    'textarea:not([disabled])',
    'input:not([disabled]):not([type="hidden"])',
    'select:not([disabled])',
    '[tabindex]:not([tabindex="-1"])',
].join(',');

function focusable(el) {
    return Array.from(el.querySelectorAll(FOCUSABLE)).filter(
        (n) => n.offsetWidth > 0 || n.offsetHeight > 0 || n === document.activeElement
    );
}

function onKeydown(e) {
    const el = this; // bound to the directive element
    const close = el.__focusTrapClose;

    if (e.key === 'Escape' || e.key === 'Esc') {
        if (typeof close === 'function') {
            e.stopPropagation();
            close(e);
        }
        return;
    }

    if (e.key !== 'Tab') {
        return;
    }

    const items = focusable(el);
    if (items.length === 0) {
        // Nothing focusable — keep focus on the panel itself.
        e.preventDefault();
        el.focus();
        return;
    }

    const first = items[0];
    const last = items[items.length - 1];
    const active = document.activeElement;

    if (e.shiftKey) {
        if (active === first || !el.contains(active)) {
            e.preventDefault();
            last.focus();
        }
    } else if (active === last || !el.contains(active)) {
        e.preventDefault();
        first.focus();
    }
}

export const focusTrap = {
    mounted(el, binding) {
        el.__focusTrapClose = binding.value;
        el.__focusTrapPrev = document.activeElement;
        el.__focusTrapHandler = onKeydown.bind(el);

        // Make the panel itself focusable as a fallback target.
        if (!el.hasAttribute('tabindex')) {
            el.setAttribute('tabindex', '-1');
        }

        // Move focus inside the modal on the next tick (after it paints).
        requestAnimationFrame(() => {
            const items = focusable(el);
            (items[0] || el).focus();
        });

        el.addEventListener('keydown', el.__focusTrapHandler, true);
    },

    updated(el, binding) {
        // Keep the close handler current if the bound function identity changes.
        el.__focusTrapClose = binding.value;
    },

    unmounted(el) {
        el.removeEventListener('keydown', el.__focusTrapHandler, true);

        // Restore focus to whatever was focused before the modal opened.
        const prev = el.__focusTrapPrev;
        if (prev && typeof prev.focus === 'function' && document.contains(prev)) {
            prev.focus();
        }

        delete el.__focusTrapClose;
        delete el.__focusTrapPrev;
        delete el.__focusTrapHandler;
    },
};

export default focusTrap;
