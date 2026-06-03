<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import ConfirmModal from '@/Components/Admin/ConfirmModal.vue';
import { useConfirm } from '@/Composables/useConfirm.js';

const { _state, _accept, _dismiss } = useConfirm();

const page = usePage();
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const title = computed(() => _state.title || (isRtl.value ? 'تأكيد الإجراء' : 'Confirm action'));
const confirmText = computed(() => _state.confirmText || (isRtl.value ? 'تأكيد' : 'Confirm'));
const cancelText = computed(() => _state.cancelText || (isRtl.value ? 'إلغاء' : 'Cancel'));
</script>

<template>
    <ConfirmModal
        :show="_state.show"
        :title="title"
        :message="_state.message"
        :confirm-text="confirmText"
        :cancel-text="cancelText"
        :confirm-color="_state.confirmColor"
        @confirm="_accept"
        @cancel="_dismiss"
    />
</template>
