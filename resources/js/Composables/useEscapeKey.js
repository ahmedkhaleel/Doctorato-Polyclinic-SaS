import { onBeforeUnmount, onMounted } from 'vue';

/**
 * Calls `handler` whenever the Escape key is pressed while the component is
 * mounted — used to dismiss modals/dialogs for keyboard accessibility.
 * The handler is a no-op closer, so it is safe to call even when no modal
 * is currently open.
 */
export function useEscapeKey(handler) {
    const onKey = (e) => {
        if (e.key === 'Escape') {
            handler(e);
        }
    };
    onMounted(() => window.addEventListener('keydown', onKey));
    onBeforeUnmount(() => window.removeEventListener('keydown', onKey));
}
