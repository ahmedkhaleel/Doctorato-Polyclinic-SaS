<script setup>
import { computed, ref } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';

const props = defineProps({
    visits: { type: Array, default: () => [] },
    patient: { type: Object, required: true },
    role: { type: String, default: 'admin' },
});

const page = usePage();
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const moduleFilter = ref('all');

const filteredVisits = computed(() => {
    if (moduleFilter.value === 'all') return props.visits;
    return props.visits.filter(v => v.module === moduleFilter.value);
});

const modules = [
    { id: 'all', labelAr: 'الكل', labelEn: 'All', color: 'gray' },
    { id: 'derma', labelAr: 'جلدية', labelEn: 'Derma', color: 'amber' },
    { id: 'dental', labelAr: 'أسنان', labelEn: 'Dental', color: 'cyan' },
    { id: 'pediatric', labelAr: 'أطفال', labelEn: 'Pediatric', color: 'emerald' },
];

const visitsByModule = computed(() => ({
    all: props.visits.length,
    derma: props.visits.filter(v => v.module === 'derma').length,
    dental: props.visits.filter(v => v.module === 'dental').length,
    pediatric: props.visits.filter(v => v.module === 'pediatric').length,
}));

function formatDate(d) {
    if (!d) return '-';
    try {
        return new Date(d).toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' });
    } catch { return d; }
}

function visitHref(v) {
    if (props.role === 'admin') return `/admin/visits/${v.id}`;
    if (props.role === 'doctor') return `/doctor/visits/${v.id}`;
    if (props.role === 'secretary') return `/secretary/visits/${v.id}`;
    return null;
}

function statusLabel(s) {
    const ar = { completed: 'مكتمل', waiting: 'انتظار', in_progress: 'جاري', cancelled: 'ملغي', pending: 'قيد الانتظار' };
    const en = { completed: 'Completed', waiting: 'Waiting', in_progress: 'In Progress', cancelled: 'Cancelled', pending: 'Pending' };
    return (isRtl.value ? ar[s] : en[s]) || s;
}

function statusClass(s) {
    if (s === 'completed') return 'bg-emerald-50 text-emerald-600';
    if (s === 'cancelled') return 'bg-gray-50 text-gray-500';
    if (s === 'in_progress') return 'bg-slate-50 text-[#1B365D]';
    return 'bg-amber-50 text-amber-600';
}

function moduleBadgeClass(m) {
    if (m === 'dental') return 'bg-slate-50 text-[#1B365D]';
    if (m === 'pediatric') return 'bg-emerald-50 text-emerald-700';
    if (m === 'derma') return 'bg-[#C4A265]/10 text-[#C4A265]';
    return 'bg-gray-50 text-gray-600';
}
</script>

<template>
    <div class="space-y-4">
        <!-- Filter pills -->
        <div class="flex flex-wrap gap-2">
            <button v-for="m in modules" :key="m.id" @click="moduleFilter = m.id"
                class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full text-xs font-semibold transition"
                :class="moduleFilter === m.id ? 'bg-gray-900 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'">
                {{ isRtl ? m.labelAr : m.labelEn }}
                <span class="inline-flex items-center justify-center min-w-[18px] h-4 px-1 text-[9px] font-bold rounded-full"
                    :class="moduleFilter === m.id ? 'bg-white/20 text-white' : 'bg-white text-gray-600'">
                    {{ visitsByModule[m.id] }}
                </span>
            </button>
        </div>

        <!-- Visits List -->
        <div v-if="filteredVisits.length" class="bg-white rounded-xl border border-gray-100 overflow-hidden">
            <div class="divide-y divide-gray-50">
                <div v-for="visit in filteredVisits" :key="visit.id" class="px-5 py-3 flex items-center gap-3 hover:bg-gray-50 transition">
                    <div class="w-11 h-11 rounded-lg bg-gray-50 text-gray-700 flex flex-col items-center justify-center flex-shrink-0">
                        <span class="text-[9px] font-bold uppercase">{{ visit.visit_date ? new Date(visit.visit_date).toLocaleDateString('en', { month: 'short' }) : '' }}</span>
                        <span class="text-sm font-black leading-none">{{ visit.visit_date ? new Date(visit.visit_date).getDate() : '-' }}</span>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 mb-0.5">
                            <p class="text-sm font-semibold text-gray-800 truncate">
                                {{ isRtl ? (visit.service?.name_ar || visit.service?.name_en) : (visit.service?.name_en || visit.service?.name_ar) || visit.visit_type || '-' }}
                            </p>
                            <span v-if="visit.module" class="text-[9px] font-bold px-1.5 py-0.5 rounded uppercase" :class="moduleBadgeClass(visit.module)">
                                {{ visit.module }}
                            </span>
                            <span v-if="visit.consultation_type === 'online'"
                                class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-700 border border-emerald-200">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                </svg>
                                {{ isRtl ? 'أونلاين' : 'Online' }}
                            </span>
                        </div>
                        <p class="text-xs text-gray-500 truncate">
                            {{ isRtl ? (visit.doctor?.name_ar || visit.doctor?.name_en) : (visit.doctor?.name_en || visit.doctor?.name_ar) || '-' }}
                            <span v-if="visit.diagnosis"> · {{ visit.diagnosis }}</span>
                        </p>
                    </div>
                    <span class="text-[10px] font-semibold px-2 py-0.5 rounded-full flex-shrink-0" :class="statusClass(visit.status)">
                        {{ statusLabel(visit.status) }}
                    </span>
                    <Link v-if="visitHref(visit)" :href="visitHref(visit)"
                        class="text-xs font-semibold text-[#C4A265] hover:text-[#8B6F3F] transition px-2 flex-shrink-0">
                        {{ isRtl ? 'عرض' : 'View' }}
                    </Link>
                </div>
            </div>
        </div>

        <div v-else class="bg-gray-50 rounded-xl p-8 text-center">
            <p class="text-sm text-gray-500">{{ isRtl ? 'لا توجد زيارات' : 'No visits' }}</p>
        </div>
    </div>
</template>
