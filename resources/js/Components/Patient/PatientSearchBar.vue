<script setup>
import { ref, computed, watch, onBeforeUnmount } from 'vue';
import { usePage } from '@inertiajs/vue3';

const props = defineProps({
    patient: { type: Object, required: true },
    visits: { type: Array, default: () => [] },
    invoices: { type: Array, default: () => [] },
    prescriptions: { type: Array, default: () => [] },
    dermaData: { type: Object, default: null },
    dentalData: { type: Object, default: null },
    pediatricData: { type: Object, default: null },
});

const emit = defineEmits(['filter-change']);

const page = usePage();
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const query = ref('');
const results = ref([]);
const showDropdown = ref(false);
const focused = ref(false);
let debounceTimer = null;

function snippetAround(text, needle, pad = 40) {
    if (!text) return '';
    const s = String(text);
    const lower = s.toLowerCase();
    const n = needle.toLowerCase();
    const idx = lower.indexOf(n);
    if (idx < 0) return s.slice(0, 80);
    const start = Math.max(0, idx - pad);
    const end = Math.min(s.length, idx + n.length + pad);
    const prefix = start > 0 ? '…' : '';
    const suffix = end < s.length ? '…' : '';
    return prefix + s.slice(start, end) + suffix;
}

function fmtDate(d) {
    if (!d) return '';
    try {
        return new Date(d).toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' });
    } catch {
        return String(d);
    }
}

function computeMatches(q) {
    if (!q || q.trim().length < 2) return [];
    const needle = q.trim().toLowerCase();
    const out = [];

    // Visits: diagnosis + doctor_notes
    (props.visits || []).forEach((v) => {
        const diag = v.diagnosis || '';
        const notes = v.doctor_notes || '';
        const doctorName = v.doctor?.name_en || v.doctor?.name_ar || '';
        const serviceName = v.service?.name_en || v.service?.name_ar || '';
        if (
            diag.toLowerCase().includes(needle) ||
            notes.toLowerCase().includes(needle) ||
            doctorName.toLowerCase().includes(needle) ||
            serviceName.toLowerCase().includes(needle)
        ) {
            const source = [diag, notes, doctorName, serviceName].find(x => x && x.toLowerCase().includes(needle)) || diag || notes;
            out.push({
                type: 'visit',
                tab: 'visits',
                id: v.id,
                label: (isRtl.value ? 'زيارة' : 'Visit') + (serviceName ? ' · ' + serviceName : ''),
                date: fmtDate(v.visit_date || v.created_at),
                snippet: snippetAround(source, needle),
            });
        }
    });

    // Prescriptions: medications + items + diagnosis
    (props.prescriptions || []).forEach((p) => {
        const diag = p.diagnosis || '';
        const items = Array.isArray(p.items) ? p.items : [];
        const medStrings = items.map((it) =>
            [it.medication_name, it.medicine_name, it.name, it.dosage, it.instructions]
                .filter(Boolean)
                .join(' · ')
        ).join(' | ');
        const haystack = (diag + ' ' + medStrings).toLowerCase();
        if (haystack.includes(needle)) {
            out.push({
                type: 'prescription',
                tab: 'prescriptions',
                id: p.id,
                label: (isRtl.value ? 'وصفة طبية' : 'Prescription') + (p.doctor?.name_en ? ' · ' + p.doctor.name_en : ''),
                date: fmtDate(p.created_at),
                snippet: snippetAround(diag || medStrings, needle),
            });
        }
    });

    // Dental treatments
    const dentalTreatments = props.dentalData?.treatments || [];
    dentalTreatments.forEach((t) => {
        const desc = t.treatment_description || t.description || '';
        const procedure = t.procedure_name || t.procedure || '';
        const toothLabel = t.tooth_number ? (isRtl.value ? 'سن ' : 'Tooth ') + t.tooth_number : '';
        const haystack = (desc + ' ' + procedure + ' ' + toothLabel).toLowerCase();
        if (haystack.includes(needle)) {
            out.push({
                type: 'dental_treatment',
                tab: 'dental',
                id: t.id,
                label: (isRtl.value ? 'علاج أسنان' : 'Dental Treatment') + (toothLabel ? ' · ' + toothLabel : ''),
                date: fmtDate(t.treatment_date || t.created_at),
                snippet: snippetAround(desc || procedure, needle),
            });
        }
    });

    // Pediatric vaccinations
    const vaccinations = props.pediatricData?.vaccinations || [];
    vaccinations.forEach((v) => {
        const vaccine = v.vaccine_name || v.vaccine || '';
        const vaccineAr = v.vaccine_ar || '';
        const dose = v.dose || '';
        const haystack = (vaccine + ' ' + vaccineAr + ' ' + dose).toLowerCase();
        if (haystack.includes(needle)) {
            out.push({
                type: 'vaccination',
                tab: 'pediatric',
                id: v.id,
                label: (isRtl.value ? 'تطعيم' : 'Vaccination') + ' · ' + (isRtl.value && vaccineAr ? vaccineAr : vaccine),
                date: fmtDate(v.given_date || v.scheduled_date),
                snippet: dose,
            });
        }
    });

    // Pediatric allergies
    const allergies = props.pediatricData?.allergies || [];
    allergies.forEach((a) => {
        const name = a.allergen || a.name || '';
        const reaction = a.reaction || '';
        const haystack = (name + ' ' + reaction).toLowerCase();
        if (haystack.includes(needle)) {
            out.push({
                type: 'allergy',
                tab: 'pediatric',
                id: a.id,
                label: (isRtl.value ? 'حساسية' : 'Allergy') + ' · ' + name,
                date: '',
                snippet: reaction,
            });
        }
    });

    // Invoices (search by invoice_number / notes)
    (props.invoices || []).forEach((inv) => {
        const haystack = ((inv.invoice_number || '') + ' ' + (inv.notes || '')).toLowerCase();
        if (haystack.includes(needle)) {
            out.push({
                type: 'invoice',
                tab: 'invoices',
                id: inv.id,
                label: (isRtl.value ? 'فاتورة' : 'Invoice') + ' #' + (inv.invoice_number || inv.id),
                date: fmtDate(inv.invoice_date || inv.created_at),
                snippet: inv.status || '',
            });
        }
    });

    return out.slice(0, 10);
}

watch(query, (q) => {
    if (debounceTimer) clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => {
        const matches = computeMatches(q);
        results.value = matches;
        showDropdown.value = !!q && q.trim().length >= 2;
        emit('filter-change', { query: q, matches });
    }, 300);
});

onBeforeUnmount(() => {
    if (debounceTimer) clearTimeout(debounceTimer);
});

function selectResult(r) {
    showDropdown.value = false;
    emit('filter-change', { query: query.value, matches: results.value, selected: r });
}

function clear() {
    query.value = '';
    results.value = [];
    showDropdown.value = false;
    emit('filter-change', { query: '', matches: [] });
}

function typeBadgeColor(type) {
    const map = {
        visit: 'bg-slate-100 text-slate-700',
        prescription: 'bg-slate-100 text-[#1B365D]',
        dental_treatment: 'bg-slate-100 text-[#1B365D]',
        vaccination: 'bg-emerald-100 text-emerald-700',
        allergy: 'bg-amber-100 text-[#C4A265]',
        invoice: 'bg-amber-100 text-amber-700',
    };
    return map[type] || 'bg-gray-100 text-gray-700';
}

function typeLabel(type) {
    const ar = {
        visit: 'زيارة', prescription: 'وصفة', dental_treatment: 'أسنان',
        vaccination: 'تطعيم', allergy: 'حساسية', invoice: 'فاتورة',
    };
    const en = {
        visit: 'Visit', prescription: 'Rx', dental_treatment: 'Dental',
        vaccination: 'Vaccine', allergy: 'Allergy', invoice: 'Invoice',
    };
    return (isRtl.value ? ar[type] : en[type]) || type;
}

function onBlur() {
    // Delay to allow click on result
    setTimeout(() => {
        focused.value = false;
        showDropdown.value = false;
    }, 180);
}
</script>

<template>
    <div class="relative">
        <div class="flex items-center gap-2 px-3 py-2 rounded-xl bg-gray-50 border border-gray-200 focus-within:border-gray-300 focus-within:bg-white transition-all">
            <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
            </svg>
            <input
                v-model="query"
                type="text"
                :placeholder="isRtl ? 'بحث في ملف المريض (تشخيصات، أدوية، علاجات...)' : 'Search patient file (diagnoses, meds, treatments...)'"
                class="flex-1 bg-transparent outline-none text-sm text-gray-800 placeholder-gray-400"
                @focus="focused = true; showDropdown = query.trim().length >= 2"
                @blur="onBlur"
            />
            <button
                v-if="query"
                type="button"
                @click="clear"
                class="text-gray-400 hover:text-gray-600 p-0.5 rounded"
                :aria-label="isRtl ? 'مسح' : 'Clear'"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <!-- Results Dropdown -->
        <div
            v-if="showDropdown && results.length > 0"
            class="absolute top-full start-0 end-0 mt-1 bg-white rounded-xl shadow-lg border border-gray-200 z-20 overflow-hidden max-h-96 overflow-y-auto"
        >
            <button
                v-for="r in results"
                :key="r.type + '_' + r.id"
                type="button"
                @mousedown.prevent="selectResult(r)"
                class="w-full text-start px-3 py-2 hover:bg-gray-50 transition-colors border-b border-gray-100 last:border-b-0 flex items-start gap-3"
            >
                <span :class="['px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide flex-shrink-0 mt-0.5', typeBadgeColor(r.type)]">
                    {{ typeLabel(r.type) }}
                </span>
                <div class="flex-1 min-w-0">
                    <div class="text-sm font-semibold text-gray-800 truncate">{{ r.label }}</div>
                    <div v-if="r.snippet" class="text-xs text-gray-500 truncate">{{ r.snippet }}</div>
                </div>
                <div v-if="r.date" class="text-[11px] text-gray-400 flex-shrink-0 mt-0.5">{{ r.date }}</div>
            </button>
        </div>

        <!-- Empty state -->
        <div
            v-else-if="showDropdown && query.trim().length >= 2 && results.length === 0"
            class="absolute top-full start-0 end-0 mt-1 bg-white rounded-xl shadow-lg border border-gray-200 z-20 px-4 py-3 text-sm text-gray-500"
        >
            {{ isRtl ? 'لا توجد نتائج' : 'No results' }}
        </div>
    </div>
</template>
