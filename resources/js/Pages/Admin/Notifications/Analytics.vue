<script setup>
import { computed } from 'vue';
import { usePage, router, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    days: Number,
    channelKeys: Array,
    byChannel: Object,
    totals: Object,
    perEvent: Array,
    daily: Array,
    failures: Array,
});

const page = usePage();
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');
const t = (ar, en) => (isRtl.value ? ar : en);

const channelMeta = {
    whatsapp: { ar: 'واتساب', en: 'WhatsApp', color: '#25D366' },
    sms: { ar: 'SMS', en: 'SMS', color: '#1B365D' },
    email: { ar: 'بريد', en: 'Email', color: '#C4A265' },
    in_app: { ar: 'داخلي', en: 'In-App', color: '#64748B' },
};

function setDays(d) {
    router.get('/admin/notifications-hub/analytics', { days: d }, { preserveState: true, replace: true });
}

const maxDaily = computed(() => Math.max(1, ...props.daily.map((d) => d.total)));
const maxEvent = computed(() => Math.max(1, ...props.perEvent.map((e) => Number(e.total))));
</script>

<template>
    <AdminLayout :title="t('تحليلات الإشعارات', 'Notification Analytics')">
        <div class="max-w-7xl mx-auto p-4 md:p-6 space-y-6" :dir="isRtl ? 'rtl' : 'ltr'">
            <!-- Header -->
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 rounded-xl flex items-center justify-center text-white shadow" style="background:linear-gradient(135deg,#1B365D,#2a4a7a)">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" /></svg>
                    </div>
                    <h1 class="text-xl font-bold text-gray-900">{{ t('تحليلات الإشعارات', 'Notification Analytics') }}</h1>
                </div>
                <div class="flex items-center gap-2">
                    <div class="flex rounded-lg border border-gray-200 overflow-hidden text-sm">
                        <button v-for="d in [7, 30, 90]" :key="d" @click="setDays(d)" class="px-3 py-1.5 font-semibold transition" :class="days === d ? 'text-white' : 'text-gray-600 hover:bg-gray-50'" :style="days === d ? 'background:#1B365D' : ''">{{ d }}{{ t('ي', 'd') }}</button>
                    </div>
                    <Link href="/admin/notifications-hub" class="text-sm font-semibold px-4 py-2 rounded-lg border border-gray-200 text-gray-600 hover:bg-gray-50">{{ t('المركز', 'Hub') }}</Link>
                </div>
            </div>

            <!-- KPI cards -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
                    <p class="text-xs text-gray-500 font-medium">{{ t('وصلت', 'Delivered') }}</p>
                    <p class="text-3xl font-bold mt-1" style="color:#1B365D">{{ totals.sent }}</p>
                </div>
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
                    <p class="text-xs text-gray-500 font-medium">{{ t('فشلت', 'Failed') }}</p>
                    <p class="text-3xl font-bold mt-1 text-red-500">{{ totals.failed }}</p>
                </div>
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
                    <p class="text-xs text-gray-500 font-medium">{{ t('متخطّاة', 'Skipped') }}</p>
                    <p class="text-3xl font-bold mt-1 text-amber-500">{{ totals.skipped }}</p>
                </div>
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
                    <p class="text-xs text-gray-500 font-medium">{{ t('التكلفة', 'Cost') }}</p>
                    <p class="text-3xl font-bold mt-1" style="color:#C4A265">{{ Number(totals.cost).toFixed(2) }}</p>
                </div>
            </div>

            <!-- Per-channel -->
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
                <h2 class="font-bold text-gray-900 mb-4">{{ t('الأداء حسب القناة', 'Performance by Channel') }}</h2>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead><tr class="text-gray-400 text-xs border-b border-gray-100">
                            <th class="text-start py-2">{{ t('القناة', 'Channel') }}</th>
                            <th class="px-3 py-2">{{ t('وصلت', 'Reached') }}</th>
                            <th class="px-3 py-2">{{ t('فشلت', 'Failed') }}</th>
                            <th class="px-3 py-2">{{ t('معدل التسليم', 'Delivery %') }}</th>
                            <th class="px-3 py-2">{{ t('معدل القراءة', 'Read %') }}</th>
                            <th class="px-3 py-2">{{ t('التكلفة', 'Cost') }}</th>
                        </tr></thead>
                        <tbody>
                            <tr v-for="ch in channelKeys" :key="ch" class="border-b border-gray-50">
                                <td class="py-2.5"><span class="text-xs font-bold px-2 py-0.5 rounded-full text-white" :style="{ background: channelMeta[ch].color }">{{ t(channelMeta[ch].ar, channelMeta[ch].en) }}</span></td>
                                <td class="text-center px-3 font-semibold text-gray-800">{{ byChannel[ch].sent + byChannel[ch].delivered + byChannel[ch].read }}</td>
                                <td class="text-center px-3 text-red-500">{{ byChannel[ch].failed }}</td>
                                <td class="text-center px-3">{{ byChannel[ch].delivery_rate ?? '—' }}<span v-if="byChannel[ch].delivery_rate != null">%</span></td>
                                <td class="text-center px-3">{{ byChannel[ch].read_rate ?? '—' }}<span v-if="byChannel[ch].read_rate != null">%</span></td>
                                <td class="text-center px-3 text-gray-500">{{ Number(byChannel[ch].cost).toFixed(2) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Daily trend (CSS bars) -->
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
                <h2 class="font-bold text-gray-900 mb-4">{{ t('الإرسال اليومي', 'Daily Volume') }}</h2>
                <div v-if="!daily.length" class="text-center text-gray-400 py-6 text-sm">{{ t('لا توجد بيانات', 'No data') }}</div>
                <div v-else class="flex items-end gap-1 h-40">
                    <div v-for="d in daily" :key="d.d" class="flex-1 flex flex-col items-center justify-end group" :title="`${d.d}: ${d.total}`">
                        <div class="w-full rounded-t transition-all" :style="{ height: `${Math.max(4, d.total / maxDaily * 100)}%`, background: '#1B365D' }"></div>
                    </div>
                </div>
            </div>

            <div class="grid lg:grid-cols-2 gap-6">
                <!-- Per-event -->
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
                    <h2 class="font-bold text-gray-900 mb-4">{{ t('حسب الحدث', 'By Event') }}</h2>
                    <div v-if="!perEvent.length" class="text-center text-gray-400 py-6 text-sm">{{ t('لا توجد بيانات', 'No data') }}</div>
                    <div v-for="e in perEvent" :key="e.event_key" class="mb-2">
                        <div class="flex justify-between text-xs mb-0.5">
                            <span class="font-mono text-gray-600 truncate">{{ e.event_key }}</span>
                            <span class="text-gray-500">{{ e.total }}<span v-if="Number(e.failed) > 0" class="text-red-400"> · {{ e.failed }} ✗</span></span>
                        </div>
                        <div class="h-2 rounded-full bg-gray-100 overflow-hidden"><div class="h-full rounded-full" :style="{ width: `${Number(e.total) / maxEvent * 100}%`, background: '#C4A265' }"></div></div>
                    </div>
                </div>

                <!-- Top failures -->
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
                    <h2 class="font-bold text-gray-900 mb-4">{{ t('أعلى أسباب الفشل', 'Top Failure Reasons') }}</h2>
                    <div v-if="!failures.length" class="text-center text-gray-400 py-6 text-sm">{{ t('لا أخطاء 🎉', 'No failures 🎉') }}</div>
                    <ul class="space-y-2">
                        <li v-for="f in failures" :key="f.error" class="flex justify-between items-start gap-3 text-sm border-b border-gray-50 pb-2">
                            <span class="text-gray-700 break-words">{{ f.error }}</span>
                            <span class="font-bold text-red-500 shrink-0">{{ f.c }}</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
