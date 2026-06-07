<script setup>
import { router, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import SecretaryLayout from '@/Layouts/SecretaryLayout.vue';
import UiEmptyState from '@/Components/Ui/EmptyState.vue';
import DeskHeader from '@/Components/Secretary/DeskHeader.vue';

defineOptions({ layout: SecretaryLayout });

const page = usePage();
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const props = defineProps({
    module: { type: String, default: 'psychiatry' },
    appointments: { type: Array, default: () => [] },
    roster: { type: Array, default: () => [] },
    doctors: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
});

// Psychiatry = indigo, Neurology = teal — matches the doctor/admin accents.
const ACCENT = computed(() => (props.module === 'neurology' ? '#0D9488' : '#4F46E5'));
const moduleTitle = computed(() => props.module === 'neurology'
    ? (isRtl.value ? 'المخ والأعصاب' : 'Neurology')
    : (isRtl.value ? 'الطب النفسي' : 'Psychiatry'));

const search = ref(props.filters.search || '');
function apply() {
    router.get(route(`secretary.${props.module}.overview`), { search: search.value }, { preserveState: true, replace: true });
}

function statusBadge(s) {
    return { confirmed: 'bg-emerald-100 text-emerald-700', pending: 'bg-amber-100 text-amber-700' }[s] || 'bg-gray-100 text-gray-600';
}
function statusText(s) {
    const m = isRtl.value
        ? { confirmed: 'مؤكّد', pending: 'قيد الانتظار' }
        : { confirmed: 'Confirmed', pending: 'Pending' };
    return m[s] || s;
}
function money(v) {
    return Number(v || 0).toLocaleString(isRtl.value ? 'ar-EG' : 'en-US', { minimumFractionDigits: 0, maximumFractionDigits: 2 });
}
function docName(d) {
    if (!d) return isRtl.value ? '—' : '—';
    return isRtl.value ? (d.name_ar || d.name_en) : (d.name_en || d.name_ar);
}
</script>

<template>
    <div class="space-y-5" :dir="isRtl ? 'rtl' : 'ltr'">
        <!-- Header -->
        <DeskHeader
            :accent="ACCENT"
            :title="`${moduleTitle} — ${isRtl ? 'مكتب الاستقبال' : 'Front Desk'}`"
            :subtitle="isRtl ? 'مواعيد وفوترة فقط — لا تظهر البيانات السريرية هنا' : 'Appointments & billing only — no clinical data shown here'">
            <template #actions>
                <a :href="`/secretary/bookings/create?module=${module}`"
                   class="inline-flex items-center gap-2 bg-white/95 text-[#1B365D] px-4 py-2.5 rounded-xl font-semibold shadow-sm hover:bg-white transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    {{ isRtl ? 'حجز موعد' : 'Book Appointment' }}
                </a>
            </template>
        </DeskHeader>

        <!-- Search -->
        <div class="flex items-center gap-2">
            <div class="relative flex-1 max-w-md">
                <input v-model="search" @keyup.enter="apply" type="text"
                       :placeholder="isRtl ? 'بحث بالاسم أو الهاتف…' : 'Search by name or phone…'"
                       class="w-full rounded-xl border border-gray-200 ps-10 pe-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-200" />
                <svg class="w-4 h-4 text-gray-400 absolute top-3 ltr:left-3 rtl:right-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            </div>
            <button @click="apply" class="px-4 py-2.5 rounded-xl text-sm font-semibold border border-gray-200 text-gray-600 hover:bg-gray-50">{{ isRtl ? 'بحث' : 'Search' }}</button>
        </div>

        <!-- Upcoming appointments -->
        <section class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
            <div class="px-5 py-3.5 border-b border-gray-100 flex items-center gap-2">
                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                <h2 class="text-sm font-bold text-gray-700">{{ isRtl ? 'المواعيد القادمة' : 'Upcoming Appointments' }}</h2>
                <span class="ms-auto text-xs text-gray-400">{{ appointments.length }}</span>
            </div>
            <div v-if="appointments.length" class="divide-y divide-gray-50">
                <div v-for="a in appointments" :key="a.id" class="px-5 py-3 flex items-center gap-3 hover:bg-gray-50/60">
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-semibold text-gray-800 truncate">{{ a.patient.full_name }}</p>
                        <p class="text-xs text-gray-400">{{ a.patient.phone }} · {{ docName(a.doctor) }}</p>
                    </div>
                    <div class="text-end">
                        <p class="text-xs font-semibold text-gray-700">{{ a.date }}<span v-if="a.time"> · {{ a.time }}</span></p>
                        <span class="inline-block mt-0.5 text-[11px] font-bold px-2 py-0.5 rounded-full" :class="statusBadge(a.status)">{{ statusText(a.status) }}</span>
                    </div>
                </div>
            </div>
            <UiEmptyState v-else icon="calendar" :title="isRtl ? 'لا مواعيد قادمة' : 'No upcoming appointments'" />
        </section>

        <!-- Patient roster -->
        <section class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
            <div class="px-5 py-3.5 border-b border-gray-100 flex items-center gap-2">
                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-1.13a4 4 0 10-4-4 4 4 0 004 4z"/></svg>
                <h2 class="text-sm font-bold text-gray-700">{{ isRtl ? 'سجل المرضى' : 'Patient Roster' }}</h2>
                <span class="ms-auto text-xs text-gray-400">{{ roster.length }}</span>
            </div>
            <div v-if="roster.length" class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 text-gray-500">
                        <tr>
                            <th class="px-5 py-2.5 text-start font-semibold">{{ isRtl ? 'المريض' : 'Patient' }}</th>
                            <th class="px-5 py-2.5 text-start font-semibold">{{ isRtl ? 'رقم الملف' : 'File #' }}</th>
                            <th class="px-5 py-2.5 text-start font-semibold">{{ isRtl ? 'الهاتف' : 'Phone' }}</th>
                            <th class="px-5 py-2.5 text-end font-semibold">{{ isRtl ? 'مستحقّ' : 'Outstanding' }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        <tr v-for="p in roster" :key="p.id" class="hover:bg-gray-50/60">
                            <td class="px-5 py-2.5 font-semibold text-gray-800">{{ p.full_name }}</td>
                            <td class="px-5 py-2.5 text-gray-500">{{ p.file_number || '—' }}</td>
                            <td class="px-5 py-2.5 text-gray-500">{{ p.phone }}</td>
                            <td class="px-5 py-2.5 text-end">
                                <span :class="p.outstanding > 0 ? 'text-rose-600 font-bold' : 'text-gray-400'">{{ money(p.outstanding) }}</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <UiEmptyState v-else icon="users" :title="isRtl ? 'لا مرضى بعد' : 'No patients yet'" />
        </section>
    </div>
</template>
