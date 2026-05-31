<script setup>
import { reactive, computed, watch } from 'vue';
import { usePage, router, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    logs: Object,
    filters: Object,
    channelKeys: Array,
    statuses: Array,
    stats: Object,
});

const page = usePage();
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');
const t = (ar, en) => (isRtl.value ? ar : en);

const f = reactive({
    channel: props.filters.channel || '',
    status: props.filters.status || '',
    search: props.filters.search || '',
});

let timer = null;
watch(f, () => {
    clearTimeout(timer);
    timer = setTimeout(() => {
        router.get('/admin/notifications-hub/logs', { ...f }, { preserveState: true, replace: true, preserveScroll: true });
    }, 300);
});

const channelMeta = {
    whatsapp: { ar: 'واتساب', en: 'WhatsApp', color: '#25D366' },
    sms: { ar: 'SMS', en: 'SMS', color: '#1B365D' },
    email: { ar: 'بريد', en: 'Email', color: '#C4A265' },
    in_app: { ar: 'داخلي', en: 'In-App', color: '#64748B' },
};
const statusStyle = {
    queued: 'bg-amber-100 text-amber-700',
    sent: 'bg-blue-100 text-blue-700',
    delivered: 'bg-green-100 text-green-700',
    read: 'bg-emerald-100 text-emerald-700',
    failed: 'bg-red-100 text-red-700',
    skipped: 'bg-gray-100 text-gray-500',
};
const statusLabel = (s) => ({
    queued: t('بالانتظار', 'Queued'), sent: t('أُرسلت', 'Sent'), delivered: t('وصلت', 'Delivered'),
    read: t('قُرئت', 'Read'), failed: t('فشلت', 'Failed'), skipped: t('تخطّي', 'Skipped'),
}[s] || s);
</script>

<template>
    <AdminLayout :title="t('سجل الإرسال', 'Delivery Log')">
        <div class="max-w-7xl mx-auto p-4 md:p-6 space-y-5" :dir="isRtl ? 'rtl' : 'ltr'">
            <div class="flex items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 rounded-xl flex items-center justify-center text-white shadow" style="background:linear-gradient(135deg,#1B365D,#2a4a7a)">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                    </div>
                    <h1 class="text-xl font-bold text-gray-900">{{ t('سجل الإرسال', 'Delivery Log') }}</h1>
                </div>
                <Link href="/admin/notifications-hub" class="text-sm font-semibold px-4 py-2 rounded-lg border border-gray-200 text-gray-600 hover:bg-gray-50">{{ t('مركز الإشعارات', 'Hub') }}</Link>
            </div>

            <!-- Filters -->
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4 grid sm:grid-cols-3 gap-3">
                <input v-model="f.search" :placeholder="t('بحث (هاتف/بريد/حدث)', 'Search (to / event)')" class="rounded-lg border-gray-200 text-sm" />
                <select v-model="f.channel" class="rounded-lg border-gray-200 text-sm">
                    <option value="">{{ t('كل القنوات', 'All channels') }}</option>
                    <option v-for="ch in channelKeys" :key="ch" :value="ch">{{ t(channelMeta[ch]?.ar, channelMeta[ch]?.en) }}</option>
                </select>
                <select v-model="f.status" class="rounded-lg border-gray-200 text-sm">
                    <option value="">{{ t('كل الحالات', 'All statuses') }}</option>
                    <option v-for="s in statuses" :key="s" :value="s">{{ statusLabel(s) }}</option>
                </select>
            </div>

            <!-- Table -->
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-gray-400 text-xs border-b border-gray-100 bg-gray-50/50">
                                <th class="text-start px-4 py-3">{{ t('الحدث', 'Event') }}</th>
                                <th class="text-start px-4 py-3">{{ t('القناة', 'Channel') }}</th>
                                <th class="text-start px-4 py-3">{{ t('إلى', 'To') }}</th>
                                <th class="text-start px-4 py-3">{{ t('الحالة', 'Status') }}</th>
                                <th class="text-start px-4 py-3">{{ t('التكلفة', 'Cost') }}</th>
                                <th class="text-start px-4 py-3">{{ t('الوقت', 'Time') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="log in logs.data" :key="log.id" class="border-b border-gray-50 hover:bg-gray-50/50">
                                <td class="px-4 py-3 font-mono text-xs text-gray-600">{{ log.event_key }}</td>
                                <td class="px-4 py-3">
                                    <span class="text-xs font-bold px-2 py-0.5 rounded-full text-white" :style="{ background: channelMeta[log.channel]?.color || '#64748B' }">{{ t(channelMeta[log.channel]?.ar, channelMeta[log.channel]?.en) || log.channel }}</span>
                                </td>
                                <td class="px-4 py-3 text-gray-700">{{ log.to || '—' }}</td>
                                <td class="px-4 py-3">
                                    <span class="text-xs font-semibold px-2 py-0.5 rounded-full" :class="statusStyle[log.status]">{{ statusLabel(log.status) }}</span>
                                    <span v-if="log.error" class="block text-xs text-red-400 mt-0.5 max-w-xs truncate">{{ log.error }}</span>
                                </td>
                                <td class="px-4 py-3 text-gray-500">{{ log.cost ? Number(log.cost).toFixed(4) : '—' }}</td>
                                <td class="px-4 py-3 text-gray-400 text-xs">{{ new Date(log.created_at).toLocaleString(isRtl ? 'ar-EG' : 'en-GB') }}</td>
                            </tr>
                            <tr v-if="!logs.data.length"><td colspan="6" class="text-center text-gray-400 py-10">{{ t('لا توجد سجلات', 'No logs found') }}</td></tr>
                        </tbody>
                    </table>
                </div>
                <!-- Pagination -->
                <div v-if="logs.links && logs.links.length > 3" class="flex flex-wrap gap-1 p-4 border-t border-gray-100">
                    <Link v-for="link in logs.links" :key="link.label" :href="link.url || ''" v-html="link.label"
                          class="px-3 py-1.5 rounded-lg text-sm transition"
                          :class="[link.active ? 'text-white' : 'text-gray-600 hover:bg-gray-100', !link.url ? 'opacity-40 pointer-events-none' : '']"
                          :style="link.active ? 'background:#1B365D' : ''" />
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
