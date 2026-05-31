<script setup>
import { reactive, computed, watch } from 'vue';
import { usePage, router, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    scheduled: Object,
    filters: Object,
    statuses: Array,
    counts: Object,
});

const page = usePage();
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');
const t = (ar, en) => (isRtl.value ? ar : en);

const f = reactive({
    status: props.filters?.status || '',
    event_key: props.filters?.event_key || '',
});

let timer = null;
watch(f, () => {
    clearTimeout(timer);
    timer = setTimeout(() => {
        router.get('/admin/notifications-hub/scheduled', { ...f }, { preserveState: true, replace: true, preserveScroll: true });
    }, 300);
});

const channelMeta = {
    whatsapp: { ar: 'واتساب', en: 'WhatsApp', color: '#25D366' },
    sms: { ar: 'SMS', en: 'SMS', color: '#1B365D' },
    email: { ar: 'بريد', en: 'Email', color: '#C4A265' },
    in_app: { ar: 'داخلي', en: 'In-App', color: '#64748B' },
};
const statusStyle = {
    pending: 'bg-amber-100 text-amber-700',
    processed: 'bg-emerald-100 text-emerald-700',
    cancelled: 'bg-gray-100 text-gray-500',
};
const statusLabel = (s) => ({ pending: t('قيد الانتظار', 'Pending'), processed: t('أُرسلت', 'Processed'), cancelled: t('أُلغيت', 'Cancelled') }[s] || s);

function cancel(id) {
    if (confirm(t('إلغاء هذا الإشعار المجدول؟', 'Cancel this scheduled notification?'))) {
        router.post(`/admin/notifications-hub/scheduled/${id}/cancel`, {}, { preserveScroll: true });
    }
}
</script>

<template>
    <AdminLayout :title="t('الإشعارات المجدولة', 'Scheduled Notifications')">
        <div class="max-w-6xl mx-auto p-4 md:p-6 space-y-5" :dir="isRtl ? 'rtl' : 'ltr'">
            <!-- Header -->
            <div class="flex items-center gap-3">
                <div class="w-11 h-11 rounded-xl flex items-center justify-center text-white shadow" style="background:linear-gradient(135deg,#DB2777,#9D174D)">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                </div>
                <div>
                    <h1 class="text-xl font-bold text-gray-900">{{ t('الإشعارات المجدولة', 'Scheduled Notifications') }}</h1>
                    <p class="text-xs text-gray-500">{{ t('رسائل في انتظار الإرسال عبر المجدول', 'Messages queued for the dispatch scheduler') }}</p>
                </div>
            </div>

            <!-- Counts -->
            <div class="grid grid-cols-3 gap-3">
                <div class="bg-white rounded-xl border border-gray-100 p-4 shadow-sm">
                    <span class="text-xs text-gray-500">{{ t('قيد الانتظار', 'Pending') }}</span>
                    <p class="text-2xl font-extrabold text-amber-600 mt-1">{{ counts.pending }}</p>
                </div>
                <div class="bg-white rounded-xl border border-gray-100 p-4 shadow-sm">
                    <span class="text-xs text-gray-500">{{ t('أُرسلت', 'Processed') }}</span>
                    <p class="text-2xl font-extrabold text-emerald-600 mt-1">{{ counts.processed }}</p>
                </div>
                <div class="bg-white rounded-xl border border-gray-100 p-4 shadow-sm">
                    <span class="text-xs text-gray-500">{{ t('أُلغيت', 'Cancelled') }}</span>
                    <p class="text-2xl font-extrabold text-gray-400 mt-1">{{ counts.cancelled }}</p>
                </div>
            </div>

            <!-- Filters -->
            <div class="flex flex-wrap gap-2">
                <select v-model="f.status" class="rounded-lg border-gray-200 text-sm">
                    <option value="">{{ t('كل الحالات', 'All statuses') }}</option>
                    <option v-for="s in statuses" :key="s" :value="s">{{ statusLabel(s) }}</option>
                </select>
                <input v-model="f.event_key" :placeholder="t('مفتاح الحدث', 'Event key')" class="rounded-lg border-gray-200 text-sm" />
            </div>

            <!-- Table -->
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-gray-400 text-xs border-b border-gray-100 bg-gray-50/50">
                            <th class="text-start px-4 py-3">{{ t('الحدث', 'Event') }}</th>
                            <th class="text-start px-4 py-3">{{ t('المستلِم', 'Recipient') }}</th>
                            <th class="px-4 py-3">{{ t('القنوات', 'Channels') }}</th>
                            <th class="px-4 py-3">{{ t('موعد الإرسال', 'Send After') }}</th>
                            <th class="px-4 py-3">{{ t('الحالة', 'Status') }}</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="n in scheduled.data" :key="n.id" class="border-b border-gray-50">
                            <td class="px-4 py-3 font-medium text-gray-800">
                                {{ n.event_key }}
                                <span v-if="n.reason" class="block text-[11px] text-gray-400">{{ n.reason }}</span>
                            </td>
                            <td class="px-4 py-3 text-gray-600">{{ n.recipient }}</td>
                            <td class="px-4 py-3 text-center">
                                <span v-for="c in n.channels" :key="c" class="inline-block text-[10px] font-bold px-1.5 py-0.5 rounded mx-0.5 text-white"
                                      :style="{ backgroundColor: (channelMeta[c]?.color || '#64748B') }">
                                    {{ isRtl ? (channelMeta[c]?.ar || c) : (channelMeta[c]?.en || c) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-center text-gray-500 text-xs whitespace-nowrap">{{ n.send_after || '—' }}</td>
                            <td class="px-4 py-3 text-center">
                                <span class="text-xs font-bold px-2 py-0.5 rounded-full" :class="statusStyle[n.status] || 'bg-gray-100 text-gray-600'">{{ statusLabel(n.status) }}</span>
                            </td>
                            <td class="px-4 py-3 text-end">
                                <button v-if="n.status === 'pending'" @click="cancel(n.id)" class="text-xs font-semibold text-red-500 hover:underline">{{ t('إلغاء', 'Cancel') }}</button>
                            </td>
                        </tr>
                        <tr v-if="!scheduled.data.length">
                            <td colspan="6" class="text-center text-gray-400 py-10">{{ t('لا توجد إشعارات مجدولة', 'No scheduled notifications') }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div v-if="scheduled.links && scheduled.links.length > 3" class="flex flex-wrap gap-1 justify-center">
                <component :is="link.url ? Link : 'span'" v-for="link in scheduled.links" :key="link.label"
                           :href="link.url || undefined"
                           class="px-3 py-1.5 rounded-lg text-sm border"
                           :class="link.active ? 'bg-[#1B365D] text-white border-[#1B365D]' : 'bg-white text-gray-600 border-gray-200 hover:bg-gray-50'"
                           v-html="link.label" />
            </div>
        </div>
    </AdminLayout>
</template>
