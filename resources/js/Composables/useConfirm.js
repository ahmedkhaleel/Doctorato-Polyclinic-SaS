import { reactive } from 'vue';

/**
 * Global, branded, bilingual confirmation dialog — a drop-in replacement for
 * the native window.confirm() across the admin panel.
 *
 * A single <GlobalConfirmDialog/> is mounted once in AdminLayout and reads this
 * shared reactive state. Pages call confirm() with a callback that runs only
 * when the user accepts — mirroring the old `if (window.confirm(x)) { … }`
 * shape with a minimal edit and no async/await plumbing.
 *
 * Usage:
 *   const { confirm } = useConfirm();
 *   confirm('Delete this item?', () => router.post('/x/delete'));
 *   // or with options:
 *   confirm({ title: 'Delete', message: '…', confirmColor: 'red' }, onYes);
 */
const state = reactive({
    show: false,
    title: '',
    message: '',
    confirmText: '',
    cancelText: '',
    confirmColor: 'red',
    _onConfirm: null,
});

export function useConfirm() {
    function confirm(optsOrMessage, onConfirm) {
        const o = typeof optsOrMessage === 'string' ? { message: optsOrMessage } : (optsOrMessage || {});
        state.title = o.title || '';
        state.message = o.message || '';
        state.confirmText = o.confirmText || '';
        state.cancelText = o.cancelText || '';
        state.confirmColor = o.confirmColor || 'red';
        state._onConfirm = typeof onConfirm === 'function' ? onConfirm : (typeof o.onConfirm === 'function' ? o.onConfirm : null);
        state.show = true;
    }

    function _accept() {
        state.show = false;
        const cb = state._onConfirm;
        state._onConfirm = null;
        if (cb) cb();
    }

    function _dismiss() {
        state.show = false;
        state._onConfirm = null;
    }

    return { confirm, _accept, _dismiss, _state: state };
}
