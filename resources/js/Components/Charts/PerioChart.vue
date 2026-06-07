<script setup>
/**
 * PerioChart — periodontal pocket-depth chart (chart kit, dental).
 * Renders the dentition in two arches; each tooth is colour-coded by its deepest
 * pocket (≤3 healthy, 4–5 watch, ≥6 severe). Selecting a tooth opens an inline
 * capture form (6 sites + bleeding-on-probing + mobility) that upserts via the
 * perio route. Self-contained so host pages only mount it.
 */
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';

const props = defineProps({
    teeth: { type: Array, default: () => [] },       // ordered tooth numbers (FDI)
    perio: { type: Object, default: () => ({}) },    // keyed by tooth_number
    patientId: { type: [Number, String], required: true },
    isRtl: { type: Boolean, default: true },
});

const SITES = [
    { key: 'pd_mb', ar: 'إنسي شفوي', en: 'MB' },
    { key: 'pd_b', ar: 'شفوي', en: 'B' },
    { key: 'pd_db', ar: 'وحشي شفوي', en: 'DB' },
    { key: 'pd_ml', ar: 'إنسي لساني', en: 'ML' },
    { key: 'pd_l', ar: 'لساني', en: 'L' },
    { key: 'pd_dl', ar: 'وحشي لساني', en: 'DL' },
];

const arches = computed(() => [props.teeth.slice(0, 16), props.teeth.slice(16)]);

function maxPd(tooth) {
    const m = props.perio[tooth];
    if (!m) return null;
    return Math.max(...SITES.map(s => Number(m[s.key]) || 0));
}
function toothColor(tooth) {
    const v = maxPd(tooth);
    if (v == null || v === 0) return { bg: '#F1F5F9', text: '#94A3B8' };
    if (v >= 6) return { bg: '#FEE2E2', text: '#B91C1C' };
    if (v >= 4) return { bg: '#FEF3C7', text: '#B45309' };
    return { bg: '#D1FAE5', text: '#047857' };
}

const selected = ref(null);
const form = ref({ pd_mb: '', pd_b: '', pd_db: '', pd_ml: '', pd_l: '', pd_dl: '', bop: [], mobility: '' });
const saving = ref(false);

function selectTooth(t) {
    selected.value = t;
    const m = props.perio[t] || {};
    form.value = {
        pd_mb: m.pd_mb ?? '', pd_b: m.pd_b ?? '', pd_db: m.pd_db ?? '',
        pd_ml: m.pd_ml ?? '', pd_l: m.pd_l ?? '', pd_dl: m.pd_dl ?? '',
        bop: Array.isArray(m.bop) ? [...m.bop] : [], mobility: m.mobility ?? '',
    };
}
function toggleBop(siteKey) {
    const i = form.value.bop.indexOf(siteKey);
    if (i === -1) form.value.bop.push(siteKey); else form.value.bop.splice(i, 1);
}
function save() {
    saving.value = true;
    router.post(`/doctor/dental/chart/${props.patientId}/perio/${selected.value}`, { ...form.value }, {
        preserveScroll: true,
        onFinish: () => { saving.value = false; },
        onSuccess: () => { selected.value = null; },
    });
}
</script>

<template>
    <div :dir="isRtl ? 'rtl' : 'ltr'">
        <!-- arches -->
        <div class="space-y-2">
            <div v-for="(arch, ai) in arches" :key="ai" class="flex flex-wrap gap-1 justify-center">
                <button v-for="t in arch" :key="t" type="button" @click="selectTooth(t)"
                    class="w-8 h-9 rounded-md border text-[10px] font-mono font-bold flex flex-col items-center justify-center transition-all hover:ring-2 hover:ring-[#C4A265]/40"
                    :class="selected === t ? 'ring-2 ring-[#C4A265]' : ''"
                    :style="`background:${toothColor(t).bg}; color:${toothColor(t).text}; border-color:${toothColor(t).text}33`"
                    :title="maxPd(t) != null ? `${isRtl ? 'أعمق جيب' : 'Max PD'}: ${maxPd(t)}mm` : (isRtl ? 'غير مُخطّط' : 'not charted')">
                    {{ t }}
                    <span v-if="maxPd(t)" class="text-[8px] leading-none">{{ maxPd(t) }}</span>
                </button>
            </div>
        </div>

        <!-- legend -->
        <div class="flex items-center justify-center gap-3 mt-3 text-[10px] text-gray-500">
            <span class="inline-flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-sm" style="background:#D1FAE5"></span>≤3mm</span>
            <span class="inline-flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-sm" style="background:#FEF3C7"></span>4–5mm</span>
            <span class="inline-flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-sm" style="background:#FEE2E2"></span>≥6mm</span>
        </div>

        <!-- capture form -->
        <Transition enter-active-class="transition-all duration-200" enter-from-class="opacity-0 -translate-y-1" enter-to-class="opacity-100 translate-y-0">
            <form v-if="selected" @submit.prevent="save" class="mt-4 rounded-xl border border-gray-200 bg-gray-50/70 p-4">
                <div class="flex items-center justify-between mb-3">
                    <h4 class="text-sm font-bold text-gray-800">{{ isRtl ? `السن ${selected}` : `Tooth ${selected}` }}</h4>
                    <button type="button" @click="selected = null" class="text-gray-400 hover:text-gray-600" :aria-label="isRtl ? 'إغلاق' : 'Close'" :title="isRtl ? 'إغلاق' : 'Close'">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>
                <p class="text-[10px] font-bold text-gray-400 uppercase mb-1">{{ isRtl ? 'عمق الجيب (مم)' : 'Pocket depth (mm)' }}</p>
                <div class="grid grid-cols-3 gap-2 mb-3">
                    <div v-for="s in SITES" :key="s.key">
                        <label class="text-[10px] text-gray-500 block mb-0.5">{{ isRtl ? s.ar : s.en }}<span v-if="form.bop.includes(s.key)" class="text-red-500"> •</span></label>
                        <div class="flex items-center gap-1">
                            <input v-model="form[s.key]" type="number" min="0" max="15" class="doctorato-input w-full px-2 py-1.5 border border-gray-200 rounded-lg text-xs" />
                            <button type="button" @click="toggleBop(s.key)" class="w-6 h-6 shrink-0 rounded-md border text-[9px] font-bold transition-colors"
                                :class="form.bop.includes(s.key) ? 'bg-red-500 border-red-500 text-white' : 'bg-white border-gray-200 text-gray-400'"
                                :title="isRtl ? 'نزف عند السبر' : 'Bleeding on probing'">{{ isRtl ? 'نز' : 'B' }}</button>
                        </div>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <label class="text-xs text-gray-600">{{ isRtl ? 'الحركة' : 'Mobility' }}</label>
                    <select v-model="form.mobility" class="doctorato-input px-2 py-1.5 border border-gray-200 rounded-lg text-xs">
                        <option value="">—</option>
                        <option v-for="g in [0, 1, 2, 3]" :key="g" :value="g">{{ g }}</option>
                    </select>
                    <button type="submit" :disabled="saving" class="ml-auto px-4 py-1.5 text-xs font-semibold text-white rounded-lg bg-[#1B365D] disabled:opacity-50">{{ isRtl ? 'حفظ' : 'Save' }}</button>
                </div>
            </form>
        </Transition>
    </div>
</template>
