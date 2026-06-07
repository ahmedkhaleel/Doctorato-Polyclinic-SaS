<script setup>
import { Link, useForm, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import DoctorLayout from '@/Layouts/DoctorLayout.vue';
import BodyMap from '@/Components/Charts/BodyMap.vue';
import { useCurrency } from '@/Composables/useCurrency';
import { useEscapeKey } from '@/Composables/useEscapeKey';

defineOptions({ layout: DoctorLayout });

const page = usePage();
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');
const { formatCurrency } = useCurrency();
const ACCENT = '#8B5CF6';

const props = defineProps({
    patient: { type: Object, required: true },
    dermaSessions: { type: Array, default: () => [] },
    cosmeticSessions: { type: Array, default: () => [] },
    plans: { type: Array, default: () => [] },
    photos: { type: Array, default: () => [] },
    consents: { type: Array, default: () => [] },
    sessionTypes: { type: Array, default: () => [] },
    lesions: { type: Array, default: () => [] },
    lesionTypes: { type: Array, default: () => [] },
    procedures: { type: Array, default: () => [] },
});

// ── Body lesion map (CV-3) ──
const bodyView = ref('front');
const lesionForm = useForm({ view: 'front', x: null, y: null, lesion_type: 'nevus', size_mm: '', note: '' });
const placing = ref(false);
function onLesionAdd({ x, y, view }) {
    lesionForm.x = x; lesionForm.y = y; lesionForm.view = view; placing.value = true;
}
function saveLesion() {
    lesionForm.post(route('doctor.derma.lesions.store', props.patient.id), {
        preserveScroll: true,
        onSuccess: () => { placing.value = false; lesionForm.reset('x', 'y', 'size_mm', 'note'); },
    });
}
function deleteLesion(id) {
    useForm({}).post(route('doctor.derma.lesions.destroy', id), { preserveScroll: true });
}
function lesionTypeLabel(t) {
    const m = isRtl.value
        ? { nevus: 'وحمة', macule: 'بقعة', papule: 'حطاطة', plaque: 'لويحة', nodule: 'عقدة', patch: 'رقعة', vesicle: 'حويصلة', pustule: 'بثرة', ulcer: 'قرحة', scar: 'ندبة', other: 'أخرى' }
        : {};
    return m[t] || t;
}

const modal = ref(null); // 'derma' | 'cosmetic' | 'photo'
const close = () => { modal.value = null; };
useEscapeKey(close);

const form = useForm({
    session_type: 'laser', treatment_plan_id: '', area_treated: '', product_used: '',
    session_number: '', total_sessions: '', cost: '', next_session_date: '', notes: '',
});
function logSession() {
    form.post(route('doctor.derma.sessions.store', props.patient.id), {
        preserveScroll: true, onSuccess: () => { close(); form.reset(); },
    });
}

const cosForm = useForm({ procedure_id: '', area_treated: '', product_used: '', dose_units: '', session_number: '', cost: '', notes: '' });
function logCosmetic() {
    cosForm.post(route('doctor.derma.cosmetic-sessions.store', props.patient.id), {
        preserveScroll: true, onSuccess: () => { close(); cosForm.reset(); },
    });
}

const photoForm = useForm({ category: 'before', body_area: '', notes: '', image: null });
function uploadPhoto() {
    photoForm.post(route('doctor.derma.photos.store', props.patient.id), {
        preserveScroll: true, forceFormData: true, onSuccess: () => { close(); photoForm.reset(); },
    });
}

const beforeAfter = computed(() => props.photos.filter(p => ['before', 'after'].includes(p.category)));
function fmt(d) { return d ? new Date(d).toLocaleDateString(isRtl.value ? 'ar-EG' : 'en-GB', { day: 'numeric', month: 'short', year: 'numeric' }) : '—'; }
function typeLabel(t) {
    const m = isRtl.value
        ? { laser: 'ليزر', peel: 'تقشير', phototherapy: 'علاج ضوئي', injection: 'حقن', cryotherapy: 'تبريد', other: 'أخرى' }
        : { laser: 'Laser', peel: 'Peel', phototherapy: 'Phototherapy', injection: 'Injection', cryotherapy: 'Cryotherapy', other: 'Other' };
    return m[t] || t;
}
</script>

<template>
    <div class="space-y-6 max-w-5xl mx-auto" :dir="isRtl ? 'rtl' : 'ltr'">
        <Link :href="route('doctor.derma.patients.index')" class="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-violet-700 transition">
            <svg class="w-4 h-4" :class="isRtl ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            {{ isRtl ? 'كل المرضى' : 'All patients' }}
        </Link>

        <!-- Header -->
        <div class="relative overflow-hidden rounded-2xl p-6 text-white shadow-lg" style="background: linear-gradient(120deg,#1B365D,#24456f 60%,#8B5CF6 170%)">
            <div class="absolute -top-10 end-6 w-40 h-40 rounded-full opacity-15" style="background:#C4A265"></div>
            <div class="relative z-10 flex items-center justify-between flex-wrap gap-4">
                <div class="flex items-center gap-4">
                    <div class="w-14 h-14 rounded-full bg-white/15 flex items-center justify-center text-2xl font-bold">{{ (patient.full_name || '?').charAt(0) }}</div>
                    <div>
                        <h1 class="text-2xl font-bold">{{ patient.full_name }}</h1>
                        <p class="text-white/70 text-sm">{{ patient.phone }} · {{ patient.file_number }}</p>
                    </div>
                </div>
                <div class="flex items-center gap-2 flex-wrap">
                    <button @click="modal = 'derma'" class="inline-flex items-center gap-1.5 bg-white/15 hover:bg-white/25 backdrop-blur px-4 py-2.5 rounded-xl font-semibold transition text-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        {{ isRtl ? 'جلسة جلدية' : 'Derma Session' }}
                    </button>
                    <button @click="modal = 'cosmetic'" class="inline-flex items-center gap-1.5 bg-white/15 hover:bg-white/25 backdrop-blur px-4 py-2.5 rounded-xl font-semibold transition text-sm">
                        + {{ isRtl ? 'جلسة تجميل' : 'Cosmetic' }}
                    </button>
                    <button @click="modal = 'photo'" class="inline-flex items-center gap-1.5 bg-white/15 hover:bg-white/25 backdrop-blur px-4 py-2.5 rounded-xl font-semibold transition text-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        {{ isRtl ? 'رفع صورة' : 'Photo' }}
                    </button>
                </div>
            </div>
        </div>

        <!-- Body Lesion Map (CV-3) -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
            <div class="flex items-center gap-2 mb-3">
                <div class="w-8 h-8 rounded-lg flex items-center justify-center" style="background:#8B5CF61A">
                    <svg class="w-4 h-4" style="color:#8B5CF6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                </div>
                <h2 class="font-bold text-gray-800">{{ isRtl ? 'خريطة آفات الجسم' : 'Body Lesion Map' }}</h2>
                <span class="text-[11px] font-bold px-2 py-0.5 rounded-full" style="background:#8B5CF61A;color:#8B5CF6">{{ lesions.length }}</span>
            </div>
            <div class="grid md:grid-cols-2 gap-5">
                <div>
                    <BodyMap :lesions="lesions" v-model:view="bodyView" editable accent="#8B5CF6" :is-rtl="isRtl" :height="300" @add="onLesionAdd" />
                    <!-- inline add form -->
                    <Transition enter-active-class="transition-all duration-200" enter-from-class="opacity-0 -translate-y-1" enter-to-class="opacity-100 translate-y-0">
                        <form v-if="placing" @submit.prevent="saveLesion" class="mt-3 bg-violet-50/60 border border-violet-100 rounded-xl p-3 space-y-2">
                            <p class="text-[11px] text-violet-700 font-semibold">{{ isRtl ? `موضع جديد (${lesionForm.view === 'front' ? 'أمامي' : 'خلفي'})` : `New point (${lesionForm.view})` }}</p>
                            <div class="grid grid-cols-2 gap-2">
                                <select v-model="lesionForm.lesion_type" class="doctorato-input w-full px-2 py-1.5 border border-gray-200 rounded-lg text-xs">
                                    <option v-for="t in lesionTypes" :key="t" :value="t">{{ lesionTypeLabel(t) }}</option>
                                </select>
                                <input v-model="lesionForm.size_mm" type="number" step="0.1" min="0" :placeholder="isRtl ? 'الحجم (مم)' : 'Size (mm)'" class="doctorato-input w-full px-2 py-1.5 border border-gray-200 rounded-lg text-xs" />
                            </div>
                            <input v-model="lesionForm.note" type="text" :placeholder="isRtl ? 'ملاحظة (اختياري)' : 'Note (optional)'" class="doctorato-input w-full px-2 py-1.5 border border-gray-200 rounded-lg text-xs" />
                            <div class="flex gap-2">
                                <button type="submit" :disabled="lesionForm.processing" class="px-3 py-1.5 text-xs font-semibold text-white rounded-lg" style="background:#8B5CF6">{{ isRtl ? 'حفظ' : 'Save' }}</button>
                                <button type="button" @click="placing = false" class="px-3 py-1.5 text-xs font-medium text-gray-500 bg-gray-100 rounded-lg">{{ isRtl ? 'إلغاء' : 'Cancel' }}</button>
                            </div>
                        </form>
                    </Transition>
                </div>
                <div>
                    <h3 class="text-xs font-bold text-gray-500 uppercase mb-2">{{ isRtl ? 'الآفات المسجّلة' : 'Recorded lesions' }}</h3>
                    <div v-if="lesions.length" class="space-y-1.5 max-h-[300px] overflow-y-auto">
                        <div v-for="l in lesions" :key="l.id" class="flex items-center justify-between rounded-lg border border-gray-100 px-3 py-2 text-xs">
                            <div class="flex items-center gap-2 min-w-0">
                                <span class="w-2 h-2 rounded-full shrink-0" style="background:#8B5CF6"></span>
                                <span class="font-semibold text-gray-700">{{ lesionTypeLabel(l.lesion_type) }}</span>
                                <span class="text-[10px] text-gray-400">{{ l.view === 'front' ? (isRtl ? 'أمامي' : 'front') : (isRtl ? 'خلفي' : 'back') }}<span v-if="l.size_mm"> · {{ l.size_mm }}mm</span></span>
                            </div>
                            <button type="button" @click="deleteLesion(l.id)" class="text-gray-300 hover:text-red-500 transition-colors" :aria-label="isRtl ? 'حذف' : 'Delete'" :title="isRtl ? 'حذف' : 'Delete'">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                            </button>
                        </div>
                    </div>
                    <p v-else class="text-sm text-gray-400 text-center py-8">{{ isRtl ? 'لا آفات مسجّلة — اضغط على الجسم للإضافة' : 'No lesions — tap the body to add' }}</p>
                </div>
            </div>
        </div>

        <!-- Treatment plans -->
        <div v-if="plans.length" class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
            <h2 class="font-bold text-gray-800 mb-3">{{ isRtl ? 'خطط العلاج' : 'Treatment Plans' }}</h2>
            <ul class="space-y-2">
                <li v-for="pl in plans" :key="pl.id" class="flex items-center justify-between text-sm rounded-xl bg-gray-50/70 p-3">
                    <span class="font-medium text-gray-800">{{ isRtl ? pl.title_ar : pl.title_en }}</span>
                    <span class="text-xs text-gray-500">{{ pl.completed_sessions ?? 0 }}/{{ pl.estimated_sessions ?? '—' }} {{ isRtl ? 'جلسة' : 'sessions' }}</span>
                </li>
            </ul>
        </div>

        <!-- Sessions -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
            <h2 class="font-bold text-gray-800 mb-4">{{ isRtl ? 'سجل الجلسات' : 'Sessions Log' }}</h2>
            <div v-if="dermaSessions.length === 0 && cosmeticSessions.length === 0" class="text-center text-gray-400 py-8 text-sm">{{ isRtl ? 'لا جلسات بعد' : 'No sessions yet' }}</div>
            <ul v-else class="space-y-2">
                <li v-for="s in dermaSessions" :key="'d'+s.id" class="rounded-xl bg-gray-50/70 p-3">
                    <div class="flex items-center justify-between text-sm">
                        <span class="font-medium" :style="{ color: ACCENT }">{{ typeLabel(s.session_type) }}<span v-if="s.area_treated" class="text-gray-500"> — {{ s.area_treated }}</span></span>
                        <span class="text-xs text-gray-400" dir="ltr">{{ fmt(s.completed_at) }}</span>
                    </div>
                    <div class="flex items-center gap-3 mt-1 text-xs text-gray-500">
                        <span v-if="s.session_number">{{ s.session_number }}<template v-if="s.total_sessions">/{{ s.total_sessions }}</template></span>
                        <span v-if="Number(s.cost) > 0">{{ formatCurrency(s.cost) }}</span>
                    </div>
                </li>
                <li v-for="s in cosmeticSessions" :key="'c'+s.id" class="rounded-xl bg-amber-50/50 p-3">
                    <div class="flex items-center justify-between text-sm">
                        <span class="font-medium text-[#C4A265]">{{ isRtl ? (s.procedure?.name_ar) : (s.procedure?.name_en) }}<span v-if="s.area_treated" class="text-gray-500"> — {{ s.area_treated }}</span></span>
                        <span class="text-xs text-gray-400" dir="ltr">{{ fmt(s.created_at) }}</span>
                    </div>
                    <div v-if="Number(s.cost) > 0" class="mt-1 text-xs text-gray-500">{{ formatCurrency(s.cost) }}</div>
                </li>
            </ul>
        </div>

        <!-- Before / After -->
        <div v-if="beforeAfter.length" class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
            <h2 class="font-bold text-gray-800 mb-3">{{ isRtl ? 'صور قبل / بعد' : 'Before / After' }}</h2>
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                <div v-for="ph in beforeAfter" :key="ph.id" class="relative rounded-xl overflow-hidden aspect-square bg-gray-100">
                    <img :src="ph.url" :alt="ph.category" class="w-full h-full object-cover" loading="lazy" />
                    <span class="absolute top-2 start-2 text-[10px] font-bold px-2 py-0.5 rounded-full text-white" :style="{ background: ph.category === 'before' ? '#6B7280' : ACCENT }">{{ ph.category === 'before' ? (isRtl ? 'قبل' : 'Before') : (isRtl ? 'بعد' : 'After') }}</span>
                </div>
            </div>
        </div>

        <!-- Consents -->
        <div v-if="consents.length" class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
            <h2 class="font-bold text-gray-800 mb-3">{{ isRtl ? 'الموافقات الموقّعة' : 'Signed Consents' }}</h2>
            <ul class="divide-y divide-gray-50">
                <li v-for="c in consents" :key="c.id" class="py-2.5 flex items-center justify-between text-sm">
                    <span class="text-gray-800">{{ (isRtl ? c.procedure?.name_ar : c.procedure?.name_en) || (isRtl ? 'موافقة عامة' : 'General consent') }}</span>
                    <span class="text-xs font-semibold px-2.5 py-1 rounded-full" :class="c.signed_at ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700'">
                        {{ c.signed_at ? (isRtl ? 'موقّعة ' : 'Signed ') + fmt(c.signed_at) : (isRtl ? 'غير موقّعة' : 'Unsigned') }}
                    </span>
                </li>
            </ul>
        </div>

        <!-- Modals -->
        <Teleport to="body">
            <Transition name="modal">
                <div v-if="modal" v-focus-trap="() => (modal = false)" role="dialog" aria-modal="true" class="fixed inset-0 z-50 flex items-center justify-center p-4" :dir="isRtl ? 'rtl' : 'ltr'">
                    <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="close"></div>
                    <div role="dialog" aria-modal="true" class="relative bg-white rounded-2xl shadow-2xl w-full max-w-lg max-h-[90vh] overflow-y-auto">
                        <div class="p-5 border-b border-gray-100 flex items-center justify-between sticky top-0 bg-white rounded-t-2xl">
                            <h3 class="font-bold text-gray-800">{{ modal === 'derma' ? (isRtl ? 'تسجيل جلسة جلدية' : 'Log Derma Session') : modal === 'cosmetic' ? (isRtl ? 'تسجيل جلسة تجميل' : 'Log Cosmetic Session') : (isRtl ? 'رفع صورة' : 'Upload Photo') }}</h3>
                            <button @click="close" class="text-gray-400 hover:text-gray-600" :aria-label="isRtl ? 'إغلاق' : 'Close'" :title="isRtl ? 'إغلاق' : 'Close'"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
                        </div>

                        <!-- Derma session -->
                        <form v-if="modal === 'derma'" @submit.prevent="logSession" class="p-5 space-y-3">
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">{{ isRtl ? 'نوع الجلسة' : 'Session type' }} *</label>
                                <select v-model="form.session_type" required class="w-full rounded-xl border-gray-200 text-sm focus:border-violet-400 focus:ring-violet-400">
                                    <option v-for="t in sessionTypes" :key="t" :value="t">{{ typeLabel(t) }}</option>
                                </select>
                            </div>
                            <div class="grid grid-cols-2 gap-3">
                                <div><label class="block text-xs font-medium text-gray-600 mb-1">{{ isRtl ? 'المنطقة' : 'Area' }}</label><input v-model="form.area_treated" type="text" class="w-full rounded-xl border-gray-200 text-sm focus:border-violet-400 focus:ring-violet-400" /></div>
                                <div><label class="block text-xs font-medium text-gray-600 mb-1">{{ isRtl ? 'المنتج' : 'Product' }}</label><input v-model="form.product_used" type="text" class="w-full rounded-xl border-gray-200 text-sm focus:border-violet-400 focus:ring-violet-400" /></div>
                            </div>
                            <div class="grid grid-cols-3 gap-3">
                                <div><label class="block text-xs font-medium text-gray-600 mb-1">{{ isRtl ? 'رقم الجلسة' : 'Session #' }}</label><input v-model="form.session_number" type="number" min="1" class="w-full rounded-xl border-gray-200 text-sm focus:border-violet-400 focus:ring-violet-400" /></div>
                                <div><label class="block text-xs font-medium text-gray-600 mb-1">{{ isRtl ? 'الإجمالي' : 'Total' }}</label><input v-model="form.total_sessions" type="number" min="1" class="w-full rounded-xl border-gray-200 text-sm focus:border-violet-400 focus:ring-violet-400" /></div>
                                <div><label class="block text-xs font-medium text-gray-600 mb-1">{{ isRtl ? 'التكلفة' : 'Cost' }}</label><input v-model="form.cost" type="number" step="0.01" min="0" class="w-full rounded-xl border-gray-200 text-sm focus:border-violet-400 focus:ring-violet-400" /></div>
                            </div>
                            <div><label class="block text-xs font-medium text-gray-600 mb-1">{{ isRtl ? 'موعد الجلسة القادمة' : 'Next session' }}</label><input v-model="form.next_session_date" type="date" class="w-full rounded-xl border-gray-200 text-sm focus:border-violet-400 focus:ring-violet-400" /></div>
                            <div><label class="block text-xs font-medium text-gray-600 mb-1">{{ isRtl ? 'ملاحظات' : 'Notes' }}</label><textarea v-model="form.notes" rows="2" class="w-full rounded-xl border-gray-200 text-sm focus:border-violet-400 focus:ring-violet-400"></textarea></div>
                            <p class="text-xs text-gray-400">{{ isRtl ? 'عند إدخال تكلفة، تُصدَر فاتورة تلقائياً موسومة بالجلدية.' : 'Entering a cost auto-creates a derma-tagged invoice.' }}</p>
                            <div class="flex justify-end gap-2 pt-2">
                                <button type="button" @click="close" class="px-4 py-2.5 rounded-xl text-gray-600 hover:bg-gray-100 text-sm font-medium">{{ isRtl ? 'إلغاء' : 'Cancel' }}</button>
                                <button type="submit" :disabled="form.processing" class="px-5 py-2.5 rounded-xl text-white text-sm font-semibold disabled:opacity-50" :style="{ background: ACCENT }">{{ isRtl ? 'حفظ' : 'Save' }}</button>
                            </div>
                        </form>

                        <!-- Cosmetic session -->
                        <form v-else-if="modal === 'cosmetic'" @submit.prevent="logCosmetic" class="p-5 space-y-3">
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">{{ isRtl ? 'الإجراء' : 'Procedure' }}</label>
                                <select v-model="cosForm.procedure_id" class="w-full rounded-xl border-gray-200 text-sm focus:border-violet-400 focus:ring-violet-400">
                                    <option value="">{{ isRtl ? '—' : '—' }}</option>
                                    <option v-for="pr in procedures" :key="pr.id" :value="pr.id">{{ isRtl ? pr.name_ar : pr.name_en }}</option>
                                </select>
                            </div>
                            <div class="grid grid-cols-2 gap-3">
                                <div><label class="block text-xs font-medium text-gray-600 mb-1">{{ isRtl ? 'المنطقة' : 'Area' }}</label><input v-model="cosForm.area_treated" type="text" class="w-full rounded-xl border-gray-200 text-sm focus:border-violet-400 focus:ring-violet-400" /></div>
                                <div><label class="block text-xs font-medium text-gray-600 mb-1">{{ isRtl ? 'الوحدات' : 'Dose units' }}</label><input v-model="cosForm.dose_units" type="number" step="0.1" min="0" class="w-full rounded-xl border-gray-200 text-sm focus:border-violet-400 focus:ring-violet-400" /></div>
                            </div>
                            <div class="grid grid-cols-2 gap-3">
                                <div><label class="block text-xs font-medium text-gray-600 mb-1">{{ isRtl ? 'رقم الجلسة' : 'Session #' }}</label><input v-model="cosForm.session_number" type="number" min="1" class="w-full rounded-xl border-gray-200 text-sm focus:border-violet-400 focus:ring-violet-400" /></div>
                                <div><label class="block text-xs font-medium text-gray-600 mb-1">{{ isRtl ? 'التكلفة' : 'Cost' }}</label><input v-model="cosForm.cost" type="number" step="0.01" min="0" class="w-full rounded-xl border-gray-200 text-sm focus:border-violet-400 focus:ring-violet-400" /></div>
                            </div>
                            <div><label class="block text-xs font-medium text-gray-600 mb-1">{{ isRtl ? 'ملاحظات' : 'Notes' }}</label><textarea v-model="cosForm.notes" rows="2" class="w-full rounded-xl border-gray-200 text-sm focus:border-violet-400 focus:ring-violet-400"></textarea></div>
                            <p class="text-xs text-gray-400">{{ isRtl ? 'يُخصم المستلزم من المخزون وتُصدَر فاتورة تلقائياً.' : 'Consumes inventory + auto-bills if priced.' }}</p>
                            <div class="flex justify-end gap-2 pt-2">
                                <button type="button" @click="close" class="px-4 py-2.5 rounded-xl text-gray-600 hover:bg-gray-100 text-sm font-medium">{{ isRtl ? 'إلغاء' : 'Cancel' }}</button>
                                <button type="submit" :disabled="cosForm.processing" class="px-5 py-2.5 rounded-xl text-white text-sm font-semibold disabled:opacity-50" style="background:#C4A265">{{ isRtl ? 'حفظ' : 'Save' }}</button>
                            </div>
                        </form>

                        <!-- Photo upload -->
                        <form v-else @submit.prevent="uploadPhoto" class="p-5 space-y-3">
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">{{ isRtl ? 'التصنيف' : 'Category' }} *</label>
                                <select v-model="photoForm.category" class="w-full rounded-xl border-gray-200 text-sm focus:border-violet-400 focus:ring-violet-400">
                                    <option value="before">{{ isRtl ? 'قبل' : 'Before' }}</option>
                                    <option value="after">{{ isRtl ? 'بعد' : 'After' }}</option>
                                    <option value="progress">{{ isRtl ? 'أثناء' : 'Progress' }}</option>
                                </select>
                            </div>
                            <div><label class="block text-xs font-medium text-gray-600 mb-1">{{ isRtl ? 'المنطقة' : 'Body area' }}</label><input v-model="photoForm.body_area" type="text" class="w-full rounded-xl border-gray-200 text-sm focus:border-violet-400 focus:ring-violet-400" /></div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">{{ isRtl ? 'الصورة' : 'Image' }} *</label>
                                <input type="file" accept="image/*" required @input="photoForm.image = $event.target.files[0]"
                                       class="w-full text-sm text-gray-600 file:me-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-violet-50 file:text-violet-700 hover:file:bg-violet-100" />
                                <p v-if="photoForm.errors.image" class="text-xs text-red-600 mt-1">{{ photoForm.errors.image }}</p>
                                <p v-if="photoForm.progress" class="text-xs text-gray-400 mt-1">{{ photoForm.progress.percentage }}%</p>
                            </div>
                            <div class="flex justify-end gap-2 pt-2">
                                <button type="button" @click="close" class="px-4 py-2.5 rounded-xl text-gray-600 hover:bg-gray-100 text-sm font-medium">{{ isRtl ? 'إلغاء' : 'Cancel' }}</button>
                                <button type="submit" :disabled="photoForm.processing" class="px-5 py-2.5 rounded-xl text-white text-sm font-semibold disabled:opacity-50" :style="{ background: ACCENT }">{{ isRtl ? 'رفع' : 'Upload' }}</button>
                            </div>
                        </form>
                    </div>
                </div>
            </Transition>
        </Teleport>
    </div>
</template>

<style scoped>
.modal-enter-active, .modal-leave-active { transition: opacity .25s ease; }
.modal-enter-from, .modal-leave-to { opacity: 0; }
@media (prefers-reduced-motion: reduce) { .modal-enter-active, .modal-leave-active { transition: none; } }
</style>
