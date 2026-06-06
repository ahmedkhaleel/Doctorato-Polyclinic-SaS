<script setup>
import { router, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import SecretaryLayout from '@/Layouts/SecretaryLayout.vue';

defineOptions({ layout: SecretaryLayout });

const page = usePage();
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');
const ACCENT = '#7C3AED';

const props = defineProps({
    upcoming: { type: Array, default: () => [] },
    unpaid: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
});

const search = ref(props.filters.search || '');
function apply() {
    router.get(route('secretary.telemedicine.overview'), { search: search.value }, { preserveState: true, replace: true });
}

function payBadge(s) {
    return s === 'paid' ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700';
}
function payText(s) {
    return s === 'paid' ? (isRtl.value ? 'مدفوع' : 'Paid') : (isRtl.value ? 'غير مدفوع' : 'Unpaid');
}
function statusText(s) {
    const m = isRtl.value
        ? { scheduled: 'مجدول', waiting: 'بالانتظار', in_progress: 'جارية', completed: 'مكتملة' }
        : { scheduled: 'Scheduled', waiting: 'Waiting', in_progress: 'In progress', completed: 'Completed' };
    return m[s] || s;
}
function money(v) {
    return Number(v || 0).toLocaleString(isRtl.value ? 'ar-EG' : 'en-US', { minimumFractionDigits: 0, maximumFractionDigits: 2 });
}
function docName(d) { return d ? (isRtl.value ? (d.name_ar || d.name_en) : (d.name_en || d.name_ar)) : '—'; }
</script>

<template>
    <div class="space-y-5" :dir="isRtl ? 'rtl' : 'ltr'">
        <!-- Header -->
        <div class="flex items-center gap-3">
            <span class="w-2 h-8 rounded-full" :style="{ background: ACCENT }"></span>
            <div>
                <h1 class="text-xl font-bold text-gray-800">{{ isRtl ? 'التطبيب عن بُعد — مكتب الاستقبال' : 'Telemedicine — Front Desk' }}</h1>
                <p class="text-xs text-gray-400 mt-0.5">{{ isRtl ? 'جدولة ومتابعة الدفع — لا تُفتح غرفة الفيديو ولا تظهر الملاحظات السريرية' : 'Scheduling & payment follow-up — no video room or clinical notes' }}</p>
            </div>
        </div>

        <!-- Search -->
        <div class="flex items-center gap-2">
            <div class="relative flex-1 max-w-md">
                <input v-model="search" @keyup.enter="apply" type="text"
                       :placeholder="isRtl ? 'بحث بالاسم أو الهاتف…' : 'Search by name or phone…'"
                       class="w-full rounded-xl border border-gray-200 ps-10 pe-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-violet-200" />
                <svg class="w-4 h-4 text-gray-400 absolute top-3 ltr:left-3 rtl:right-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            </div>
            <button @click="apply" class="px-4 py-2.5 rounded-xl text-sm font-semibold border border-gray-200 text-gray-600 hover:bg-gray-50">{{ isRtl ? 'بحث' : 'Search' }}</button>
        </div>

        <!-- Payment-chase queue -->
        <section v-if="unpaid.length" class="bg-rose-50/50 rounded-2xl border border-rose-100 shadow-sm overflow-hidden">
            <div class="px-5 py-3.5 border-b border-rose-100 flex items-center gap-2">
                <svg class="w-4 h-4 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <h2 class="text-sm font-bold text-rose-700">{{ isRtl ? 'بانتظار الدفع' : 'Awaiting Payment' }}</h2>
                <span class="ms-auto text-xs text-rose-400">{{ unpaid.length }}</span>
            </div>
            <div class="divide-y divide-rose-100/60">
                <div v-for="c in unpaid" :key="c.id" class="px-5 py-3 flex items-center gap-3">
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-semibold text-gray-800 truncate">{{ c.patient?.full_name || '—' }}</p>
                        <p class="text-xs text-gray-400">{{ c.patient?.phone }} · {{ docName(c.doctor) }} · {{ c.date }}<span v-if="c.start_time"> · {{ c.start_time }}</span></p>
                    </div>
                    <span class="text-sm font-bold text-rose-600">{{ money(c.fee) }}</span>
                </div>
            </div>
        </section>

        <!-- Upcoming consultations -->
        <section class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
            <div class="px-5 py-3.5 border-b border-gray-100 flex items-center gap-2">
                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                <h2 class="text-sm font-bold text-gray-700">{{ isRtl ? 'الاستشارات القادمة' : 'Upcoming Consultations' }}</h2>
                <span class="ms-auto text-xs text-gray-400">{{ upcoming.length }}</span>
            </div>
            <div v-if="upcoming.length" class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 text-gray-500">
                        <tr>
                            <th class="px-5 py-2.5 text-start font-semibold">{{ isRtl ? 'المريض' : 'Patient' }}</th>
                            <th class="px-5 py-2.5 text-start font-semibold">{{ isRtl ? 'الطبيب' : 'Doctor' }}</th>
                            <th class="px-5 py-2.5 text-start font-semibold">{{ isRtl ? 'الموعد' : 'When' }}</th>
                            <th class="px-5 py-2.5 text-center font-semibold">{{ isRtl ? 'الحالة' : 'Status' }}</th>
                            <th class="px-5 py-2.5 text-end font-semibold">{{ isRtl ? 'الدفع' : 'Payment' }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        <tr v-for="c in upcoming" :key="c.id" class="hover:bg-gray-50/60">
                            <td class="px-5 py-2.5 font-semibold text-gray-800">{{ c.patient?.full_name || '—' }}<span class="block text-xs font-normal text-gray-400">{{ c.patient?.phone }}</span></td>
                            <td class="px-5 py-2.5 text-gray-600">{{ docName(c.doctor) }}</td>
                            <td class="px-5 py-2.5 text-gray-600">{{ c.date }}<span v-if="c.start_time" class="text-gray-400"> · {{ c.start_time }}</span></td>
                            <td class="px-5 py-2.5 text-center text-gray-600">{{ statusText(c.status) }}</td>
                            <td class="px-5 py-2.5 text-end"><span class="text-[11px] font-bold px-2 py-0.5 rounded-full" :class="payBadge(c.payment_status)">{{ payText(c.payment_status) }}</span></td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div v-else class="px-5 py-10 text-center"><p class="text-sm text-gray-400">{{ isRtl ? 'لا استشارات قادمة' : 'No upcoming consultations' }}</p></div>
        </section>
    </div>
</template>
