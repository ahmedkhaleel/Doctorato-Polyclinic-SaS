<script setup>
import { computed } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { useLocale } from '@/Composables/useLocale.js';
import { useConfirm } from '@/Composables/useConfirm.js';

const { t } = useLocale();
const { confirm } = useConfirm();

const page = usePage();
const locale = computed(() => page.props.locale || 'ar');
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const props = defineProps({
    message: Object,
});

function deleteMessage() {
    confirm(t('a_confirm_delete_message'), () => {
        router.post(`/admin/contact-messages/${props.message.id}/delete`);
    });
}

function formatDate(dateString) {
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}
</script>

<template>
    <AdminLayout :title="$t('a_message_details')">
        <div class="space-y-6 pb-10">
            <div class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-[#1B365D] via-[#1B365D] to-[#C4A265] p-6 md:p-8 shadow-xl">
                <div class="absolute -top-20 ltr:-right-20 rtl:-left-20 w-72 h-72 bg-[#1B365D]/30 rounded-full blur-3xl"></div>
                <div class="absolute -bottom-16 ltr:-left-16 rtl:-right-16 w-56 h-56 bg-[#C4A265]/20 rounded-full blur-3xl"></div>
                <div class="relative z-10 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                    <div>
                        <h1 class="text-2xl md:text-3xl font-bold text-white">{{ $t('a_message_details') }}</h1>
                        <p class="mt-1 text-white/80 text-sm">{{ message.subject || message.name }}</p>
                    </div>
                    <Link href="/admin/contact-messages" class="inline-flex items-center gap-2 rounded-xl bg-white/15 hover:bg-white/25 px-4 py-2 text-sm font-semibold text-white backdrop-blur transition">
                        {{ $t('a_back_to_messages') }}
                    </Link>
                </div>
            </div>

            <div class="max-w-4xl">
                <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-4 md:p-6 space-y-5">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500 mb-1">{{ $t('a_name') }}</label>
                            <p class="text-sm text-slate-900 font-medium">{{ message.name }}</p>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500 mb-1">{{ $t('a_email') }}</label>
                            <p class="text-sm text-slate-900 break-all">{{ message.email }}</p>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500 mb-1">{{ $t('a_phone') }}</label>
                            <p class="text-sm text-slate-900">{{ message.phone || '-' }}</p>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500 mb-1">{{ $t('a_date') }}</label>
                            <p class="text-sm text-slate-900">{{ formatDate(message.created_at) }}</p>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500 mb-1">{{ $t('a_subject') }}</label>
                        <p class="text-sm text-slate-900 font-medium">{{ message.subject || '-' }}</p>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500 mb-2">{{ $t('a_message') }}</label>
                        <div class="p-4 bg-slate-50 rounded-xl border border-slate-100 text-sm text-slate-800 whitespace-pre-wrap leading-relaxed">{{ message.message }}</div>
                    </div>

                    <div class="flex flex-col sm:flex-row gap-3 pt-4 border-t border-slate-200">
                        <Link href="/admin/contact-messages" class="inline-flex items-center justify-center py-2.5 px-4 md:px-6 rounded-xl bg-slate-100 text-slate-700 text-sm font-semibold hover:bg-slate-200 transition">
                            {{ $t('a_back_to_messages') }}
                        </Link>
                        <button
                            @click="deleteMessage"
                            class="inline-flex items-center justify-center py-2.5 px-4 md:px-6 rounded-xl bg-red-600 text-white text-sm font-semibold hover:bg-red-700 transition"
                        >
                            {{ $t('a_delete_message') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
