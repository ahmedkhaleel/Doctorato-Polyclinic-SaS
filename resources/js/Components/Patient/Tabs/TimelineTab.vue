<script setup>
import { ref, computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';

const props = defineProps({
    patient: { type: Object, required: true },
    visits: { type: Array, default: () => [] },
    invoices: { type: Array, default: () => [] },
    prescriptions: { type: Array, default: () => [] },
    dentalData: { type: Object, default: null },
    pediatricData: { type: Object, default: null },
    role: { type: String, default: 'admin' },
});

const page = usePage();
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const eventTypes = [
    { key: 'visit', labelAr: 'زيارات', labelEn: 'Visits', color: '#3b82f6' },
    { key: 'invoice', labelAr: 'فواتير', labelEn: 'Invoices', color: '#f59e0b' },
    { key: 'prescription', labelAr: 'وصفات', labelEn: 'Prescriptions', color: '#8b5cf6' },
    { key: 'dental_treatment', labelAr: 'علاج أسنان', labelEn: 'Dental', color: '#06b6d4' },
    { key: 'vaccination', labelAr: 'تطعيمات', labelEn: 'Vaccinations', color: '#10b981' },
    { key: 'growth', labelAr: 'قياسات نمو', labelEn: 'Growth', color: '#14b8a6' },
];

const enabledTypes = ref(Object.fromEntries(eventTypes.map(t => [t.key, true])));

function rolePath(segment) {
    if (props.role === 'admin') return '/admin';
    if (props.role === 'doctor') return '/doctor';
    if (props.role === 'secretary') return '/secretary';
    return '/admin';
}

function fmtDate(d) {
    if (!d) return '';
    try {
        return new Date(d).toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' });
    } catch {
        return String(d);
    }
}

function parseDate(d) {
    if (!d) return 0;
    try { return new Date(d).getTime() || 0; } catch { return 0; }
}

const allEvents = computed(() => {
    const events = [];

    // Visits
    (props.visits || []).forEach((v) => {
        const date = v.visit_date || v.created_at;
        events.push({
            type: 'visit',
            date,
            sortKey: parseDate(date),
            title: (isRtl.value ? 'زيارة' : 'Visit') + (v.service?.name_en || v.service?.name_ar ? ' · ' + (isRtl.value ? (v.service?.name_ar || v.service?.name_en) : (v.service?.name_en || v.service?.name_ar)) : ''),
            subtitle: v.doctor ? (isRtl.value ? (v.doctor.name_ar || v.doctor.name_en) : (v.doctor.name_en || v.doctor.name_ar)) : '',
            extra: v.diagnosis || v.status,
            href: `${rolePath()}/visits/${v.id}`,
            color: '#3b82f6',
        });
    });

    // Invoices
    (props.invoices || []).forEach((inv) => {
        const date = inv.invoice_date || inv.created_at;
        events.push({
            type: 'invoice',
            date,
            sortKey: parseDate(date),
            title: (isRtl.value ? 'فاتورة' : 'Invoice') + ' #' + (inv.invoice_number || inv.id),
            subtitle: inv.status || '',
            extra: inv.total != null ? (isRtl.value ? 'الإجمالي: ' : 'Total: ') + inv.total : '',
            href: `${rolePath()}/invoices/${inv.id}`,
            color: '#f59e0b',
        });
    });

    // Prescriptions
    (props.prescriptions || []).forEach((p) => {
        const date = p.created_at;
        const meds = Array.isArray(p.items)
            ? p.items.map(it => it.medication_name || it.medicine_name || it.name).filter(Boolean).slice(0, 3).join(', ')
            : '';
        events.push({
            type: 'prescription',
            date,
            sortKey: parseDate(date),
            title: isRtl.value ? 'وصفة طبية' : 'Prescription',
            subtitle: p.doctor ? (isRtl.value ? (p.doctor.name_ar || p.doctor.name_en) : (p.doctor.name_en || p.doctor.name_ar)) : '',
            extra: meds || p.diagnosis || '',
            href: `${rolePath()}/prescriptions/${p.id}`,
            color: '#8b5cf6',
        });
    });

    // Dental treatments
    const dentalTreatments = props.dentalData?.treatments || [];
    dentalTreatments.forEach((t) => {
        const date = t.treatment_date || t.created_at;
        const toothLabel = t.tooth_number ? (isRtl.value ? 'سن ' : 'Tooth ') + t.tooth_number : '';
        events.push({
            type: 'dental_treatment',
            date,
            sortKey: parseDate(date),
            title: (isRtl.value ? 'علاج أسنان' : 'Dental Treatment') + (toothLabel ? ' · ' + toothLabel : ''),
            subtitle: t.doctor ? (isRtl.value ? (t.doctor.name_ar || t.doctor.name_en) : (t.doctor.name_en || t.doctor.name_ar)) : '',
            extra: t.treatment_description || t.procedure_name || t.status || '',
            href: null,
            color: '#06b6d4',
        });
    });

    // Pediatric vaccinations
    const vaccinations = props.pediatricData?.vaccinations || [];
    vaccinations.forEach((v) => {
        const date = v.given_date || v.scheduled_date;
        events.push({
            type: 'vaccination',
            date,
            sortKey: parseDate(date),
            title: (isRtl.value ? 'تطعيم' : 'Vaccination') + ' · ' + (isRtl.value && v.vaccine_ar ? v.vaccine_ar : (v.vaccine_name || v.vaccine || '')),
            subtitle: v.dose || '',
            extra: v.status || '',
            href: null,
            color: '#10b981',
        });
    });

    // Pediatric growth records
    const growth = props.pediatricData?.growthRecords || [];
    growth.forEach((g) => {
        const date = g.measurement_date || g.created_at;
        const parts = [];
        if (g.weight_kg) parts.push((isRtl.value ? 'الوزن ' : 'W ') + g.weight_kg + 'kg');
        if (g.height_cm) parts.push((isRtl.value ? 'الطول ' : 'H ') + g.height_cm + 'cm');
        if (g.bmi) parts.push('BMI ' + g.bmi);
        events.push({
            type: 'growth',
            date,
            sortKey: parseDate(date),
            title: isRtl.value ? 'قياس نمو' : 'Growth Measurement',
            subtitle: parts.join(' · '),
            extra: g.notes || '',
            href: null,
            color: '#14b8a6',
        });
    });

    return events
        .filter(e => enabledTypes.value[e.type])
        .sort((a, b) => b.sortKey - a.sortKey);
});

const groupedByMonth = computed(() => {
    const groups = {};
    allEvents.value.forEach((e) => {
        if (!e.sortKey) {
            const key = isRtl.value ? 'بدون تاريخ' : 'No date';
            (groups[key] ||= []).push(e);
            return;
        }
        const d = new Date(e.sortKey);
        const key = d.toLocaleDateString('en-GB', { month: 'long', year: 'numeric' });
        (groups[key] ||= []).push(e);
    });
    return groups;
});

function eventTypeLabel(type) {
    const t = eventTypes.find(x => x.key === type);
    if (!t) return type;
    return isRtl.value ? t.labelAr : t.labelEn;
}

function toggleAll(val) {
    Object.keys(enabledTypes.value).forEach(k => { enabledTypes.value[k] = val; });
}
</script>

<template>
    <div>
        <!-- Filter bar -->
        <div class="mb-5">
            <div class="flex items-center gap-2 flex-wrap">
                <span class="text-[11px] font-semibold text-gray-500 uppercase tracking-wider">
                    {{ isRtl ? 'تصفية' : 'Filter' }}:
                </span>
                <button
                    v-for="t in eventTypes"
                    :key="t.key"
                    type="button"
                    @click="enabledTypes[t.key] = !enabledTypes[t.key]"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold transition-all border"
                    :class="enabledTypes[t.key]
                        ? 'text-white border-transparent shadow-sm'
                        : 'text-gray-500 bg-gray-50 border-gray-200 hover:bg-gray-100'"
                    :style="enabledTypes[t.key] ? { backgroundColor: t.color } : {}"
                >
                    <span class="inline-block w-1.5 h-1.5 rounded-full"
                        :style="enabledTypes[t.key] ? { backgroundColor: 'rgba(255,255,255,0.85)' } : { backgroundColor: t.color }"></span>
                    {{ isRtl ? t.labelAr : t.labelEn }}
                </button>
                <button
                    type="button"
                    @click="toggleAll(true)"
                    class="text-[11px] text-gray-400 hover:text-gray-600 ms-auto"
                >
                    {{ isRtl ? 'الكل' : 'All' }}
                </button>
                <button
                    type="button"
                    @click="toggleAll(false)"
                    class="text-[11px] text-gray-400 hover:text-gray-600"
                >
                    {{ isRtl ? 'لا شيء' : 'None' }}
                </button>
            </div>
        </div>

        <!-- Empty state -->
        <div v-if="allEvents.length === 0" class="text-center py-16 text-gray-400">
            <svg class="w-12 h-12 mx-auto mb-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <p class="text-sm">{{ isRtl ? 'لا توجد أحداث' : 'No events' }}</p>
        </div>

        <!-- Timeline grouped by month -->
        <div v-else class="space-y-6">
            <div v-for="(events, month) in groupedByMonth" :key="month">
                <h3 class="text-[11px] font-bold text-gray-400 uppercase tracking-widest mb-3 ps-2">
                    {{ month }}
                </h3>
                <div class="relative ps-6 border-s-2 border-gray-100 space-y-4">
                    <div
                        v-for="(e, idx) in events"
                        :key="e.type + '_' + idx + '_' + (e.date || '')"
                        class="relative group"
                    >
                        <!-- Dot -->
                        <span
                            class="absolute -start-[29px] top-1.5 w-4 h-4 rounded-full ring-4 ring-white"
                            :style="{ backgroundColor: e.color }"
                        ></span>
                        <div
                            class="bg-gray-50/60 hover:bg-white hover:shadow-sm rounded-xl px-4 py-3 border border-gray-100 transition-all"
                        >
                            <div class="flex items-start justify-between gap-3 flex-wrap">
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <span class="text-[10px] font-bold uppercase tracking-wider px-1.5 py-0.5 rounded"
                                            :style="{ backgroundColor: e.color + '22', color: e.color }">
                                            {{ eventTypeLabel(e.type) }}
                                        </span>
                                        <span class="text-sm font-semibold text-gray-800">{{ e.title }}</span>
                                    </div>
                                    <div v-if="e.subtitle" class="text-xs text-gray-500 mt-1">{{ e.subtitle }}</div>
                                    <div v-if="e.extra" class="text-xs text-gray-600 mt-0.5 truncate">{{ e.extra }}</div>
                                </div>
                                <div class="flex items-center gap-2 flex-shrink-0">
                                    <span class="text-[11px] text-gray-400 font-mono">{{ fmtDate(e.date) }}</span>
                                    <Link
                                        v-if="e.href"
                                        :href="e.href"
                                        class="text-[11px] text-gray-500 hover:text-gray-700 font-semibold"
                                    >
                                        {{ isRtl ? 'فتح' : 'Open' }}
                                    </Link>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
