<script setup>
import { Link, useForm, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import DoctorLayout from '@/Layouts/DoctorLayout.vue';
import FormErrors from '@/Components/Ui/FormErrors.vue';
import { useEscapeKey } from '@/Composables/useEscapeKey';

defineOptions({ layout: DoctorLayout });

const page = usePage();
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');
const ACCENT = '#DB2777';

const props = defineProps({
    pregnancy: { type: Object, required: true },
    antenatalVisits: { type: Array, default: () => [] },
    ultrasounds: { type: Array, default: () => [] },
    labTests: { type: Array, default: () => [] },
    delivery: { type: Object, default: null },
    supplies: { type: Array, default: () => [] },
});

const isActive = computed(() => props.pregnancy.status === 'active');

// EDD progress (0–280 days).
const progress = computed(() => {
    const w = props.pregnancy.ga_weeks;
    if (!w) return 0;
    return Math.min(Math.round((w / 40) * 100), 100);
});
function dueText() {
    const d = props.pregnancy.days_until_edd;
    if (d === null || d === undefined) return '';
    if (d < 0) return isRtl.value ? `متأخّر ${Math.abs(d)} يوم` : `${Math.abs(d)} days overdue`;
    return isRtl.value ? `باقٍ ${d} يوم` : `${d} days to go`;
}
function trimesterLabel(t) {
    if (!t) return '';
    if (isRtl.value) return t === 1 ? 'الثلث الأول' : t === 2 ? 'الثلث الثاني' : 'الثلث الثالث';
    return t === 1 ? '1st trimester' : t === 2 ? '2nd trimester' : '3rd trimester';
}

// Unified timeline.
const timeline = computed(() => {
    const items = [];
    for (const a of props.antenatalVisits) items.push({ type: 'anc', date: a.visit_date, color: '#1B365D', title: isRtl.value ? 'زيارة متابعة' : 'Antenatal Visit', data: a });
    for (const u of props.ultrasounds) items.push({ type: 'us', date: u.scan_date, color: ACCENT, title: isRtl.value ? 'سونار' : 'Ultrasound', data: u });
    for (const l of props.labTests) items.push({ type: 'lab', date: l.result_date, color: '#0EA5E9', title: isRtl.value ? 'تحليل' : 'Lab Test', data: l });
    if (props.delivery) items.push({ type: 'delivery', date: props.delivery.delivery_date, color: '#10B981', title: isRtl.value ? 'الولادة' : 'Delivery', data: props.delivery });
    return items.sort((a, b) => String(b.date).localeCompare(String(a.date)));
});

// ── Modals ──
const modal = ref(null); // 'anc' | 'us' | 'lab' | 'delivery'
const pid = props.pregnancy.id;

const ancForm = useForm({ visit_date: new Date().toISOString().slice(0, 10), weight_kg: '', bp_systolic: '', bp_diastolic: '', fundal_height_cm: '', fetal_heart_rate: '', presentation: '', edema: false, urine_protein: '', urine_glucose: '', complaints: '', plan: '', next_visit_date: '', bill: true });
const usForm = useForm({ scan_date: new Date().toISOString().slice(0, 10), scan_type: 'growth', gestational_age_weeks: '', bpd_mm: '', hc_mm: '', ac_mm: '', fl_mm: '', efw_grams: '', placenta_position: '', afi: '', fetal_count: 1, fetal_heart: true, presentation: '', findings: '', supply_id: '', consumption_qty: '', bill: true });
const labForm = useForm({ test_type: '', value: '', unit: '', reference_range: '', result_date: new Date().toISOString().slice(0, 10), is_abnormal: false, notes: '' });
const delForm = useForm({ delivery_date: new Date().toISOString().slice(0, 10), delivery_mode: 'nvd', place: '', gestational_age_at_delivery: '', outcome: 'live', baby_weight_grams: '', baby_sex: '', apgar_1: '', apgar_5: '', complications: '', notes: '', supply_id: '', consumption_qty: '', create_newborn: true, bill: true });

const close = () => { modal.value = null; };
useEscapeKey(close);
const submit = (form, routeName) => form.post(route(routeName, pid), { preserveScroll: true, onSuccess: () => { close(); form.reset(); } });

function fmtDate(d) { return d ? new Date(d).toLocaleDateString(isRtl.value ? 'ar-EG' : 'en-GB', { day: 'numeric', month: 'short', year: 'numeric' }) : '—'; }
</script>

<template>
    <div class="space-y-6 max-w-5xl mx-auto" :dir="isRtl ? 'rtl' : 'ltr'">
        <Link :href="route('doctor.obgyn.pregnancies.index')" class="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-rose-700 transition">
            <svg class="w-4 h-4" :class="isRtl ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            {{ isRtl ? 'كل ملفات الحمل' : 'All pregnancies' }}
        </Link>

        <!-- Header -->
        <div class="relative overflow-hidden rounded-2xl p-6 text-white shadow-lg" style="background: linear-gradient(120deg,#1B365D 0%,#24456f 60%,#DB2777 170%)">
            <div class="absolute -top-10 end-6 w-40 h-40 rounded-full opacity-15" style="background:#C4A265"></div>
            <div class="relative z-10">
                <div class="flex items-start justify-between flex-wrap gap-4">
                    <div class="flex items-center gap-4">
                        <div class="w-14 h-14 rounded-full bg-white/15 flex items-center justify-center text-2xl font-bold">{{ (pregnancy.patient?.full_name || '?').charAt(0) }}</div>
                        <div>
                            <h1 class="text-2xl font-bold">{{ pregnancy.patient?.full_name }}</h1>
                            <p class="text-white/70 text-sm">{{ pregnancy.patient?.phone }}</p>
                            <div class="flex items-center gap-2 mt-2 flex-wrap">
                                <span v-if="pregnancy.ga_label" class="text-xs font-semibold px-2.5 py-1 rounded-full bg-white/15">{{ pregnancy.ga_label }}</span>
                                <span v-if="pregnancy.trimester" class="text-xs font-semibold px-2.5 py-1 rounded-full bg-white/15">{{ trimesterLabel(pregnancy.trimester) }}</span>
                                <span v-if="pregnancy.is_high_risk" class="text-xs font-semibold px-2.5 py-1 rounded-full bg-red-500/80">{{ isRtl ? 'عالي الخطورة' : 'High-risk' }}</span>
                                <span v-if="!isActive" class="text-xs font-semibold px-2.5 py-1 rounded-full bg-emerald-500/80">{{ isRtl ? 'وُلد' : 'Delivered' }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="text-end">
                        <p class="text-white/70 text-xs">{{ isRtl ? 'الولادة المتوقعة' : 'EDD' }}</p>
                        <p class="text-xl font-bold" dir="ltr">{{ pregnancy.edd || '—' }}</p>
                        <p class="text-white/80 text-xs mt-0.5">{{ dueText() }}</p>
                    </div>
                </div>
                <!-- progress -->
                <div v-if="pregnancy.ga_weeks" class="mt-5">
                    <div class="h-2 rounded-full bg-white/20 overflow-hidden">
                        <div class="h-full rounded-full bg-white transition-all duration-700" :style="{ width: progress + '%' }"></div>
                    </div>
                    <div class="flex justify-between text-[11px] text-white/60 mt-1"><span>0w</span><span>40w</span></div>
                </div>
            </div>
        </div>

        <!-- Actions -->
        <div class="flex items-center gap-2 flex-wrap">
            <template v-if="isActive">
                <button @click="modal = 'anc'" class="inline-flex items-center gap-1.5 px-4 py-2.5 rounded-xl text-sm font-semibold text-white" style="background:#1B365D">+ {{ isRtl ? 'زيارة متابعة' : 'Antenatal Visit' }}</button>
                <button @click="modal = 'us'" class="inline-flex items-center gap-1.5 px-4 py-2.5 rounded-xl text-sm font-semibold text-white" :style="{ background: ACCENT }">+ {{ isRtl ? 'سونار' : 'Ultrasound' }}</button>
                <button @click="modal = 'lab'" class="inline-flex items-center gap-1.5 px-4 py-2.5 rounded-xl text-sm font-semibold text-white" style="background:#0EA5E9">+ {{ isRtl ? 'تحليل' : 'Lab' }}</button>
                <button @click="modal = 'delivery'" class="inline-flex items-center gap-1.5 px-4 py-2.5 rounded-xl text-sm font-semibold text-white" style="background:#10B981">+ {{ isRtl ? 'تسجيل ولادة' : 'Record Delivery' }}</button>
            </template>
            <a :href="route('doctor.obgyn.antenatal-card', pregnancy.id)" target="_blank" rel="noopener"
               class="inline-flex items-center gap-1.5 px-4 py-2.5 rounded-xl text-sm font-semibold border border-gray-200 text-gray-700 hover:bg-gray-50 transition ms-auto">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                {{ isRtl ? 'طباعة كرت المتابعة' : 'Print Antenatal Card' }}
            </a>
        </div>

        <!-- Timeline -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
            <h2 class="font-bold text-gray-800 mb-4">{{ isRtl ? 'الخط الزمني' : 'Timeline' }}</h2>
            <div v-if="timeline.length === 0" class="text-center text-gray-400 py-10 text-sm">{{ isRtl ? 'لا توجد سجلات بعد' : 'No records yet' }}</div>
            <ol v-else class="relative space-y-4" :class="isRtl ? 'pe-4' : 'ps-4'">
                <li v-for="(item, i) in timeline" :key="i" class="relative ps-6">
                    <span class="absolute top-1.5 w-3 h-3 rounded-full ring-4 ring-white" :class="isRtl ? 'end-0' : 'start-0'" :style="{ background: item.color, [isRtl ? 'right' : 'left']: '-2px' }"></span>
                    <span class="absolute top-4 bottom-[-1rem] w-px bg-gray-100" :class="isRtl ? 'end-[3px]' : 'start-[3px]'" v-if="i < timeline.length - 1"></span>
                    <div class="bg-gray-50/70 rounded-xl p-4">
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-semibold" :style="{ color: item.color }">{{ item.title }}</span>
                            <span class="text-xs text-gray-400" dir="ltr">{{ fmtDate(item.date) }}</span>
                        </div>
                        <!-- ANC body -->
                        <div v-if="item.type === 'anc'" class="mt-2 grid grid-cols-2 sm:grid-cols-4 gap-2 text-xs text-gray-600">
                            <span v-if="item.data.gestational_age_weeks">GA: {{ item.data.gestational_age_weeks }}w</span>
                            <span v-if="item.data.weight_kg">{{ isRtl ? 'الوزن' : 'Wt' }}: {{ item.data.weight_kg }}kg</span>
                            <span v-if="item.data.blood_pressure">BP: {{ item.data.blood_pressure }}</span>
                            <span v-if="item.data.fetal_heart_rate">FHR: {{ item.data.fetal_heart_rate }}</span>
                        </div>
                        <p v-if="item.type === 'anc' && item.data.plan" class="mt-1 text-xs text-gray-500">{{ item.data.plan }}</p>
                        <!-- US body -->
                        <div v-if="item.type === 'us'" class="mt-2 flex flex-wrap gap-x-4 gap-y-1 text-xs text-gray-600">
                            <span class="font-medium">{{ item.data.scan_type }}</span>
                            <span v-if="item.data.efw_grams">EFW: {{ item.data.efw_grams }}g</span>
                            <span v-if="item.data.afi">AFI: {{ item.data.afi }}</span>
                            <span v-if="item.data.presentation">{{ item.data.presentation }}</span>
                        </div>
                        <p v-if="item.type === 'us' && item.data.findings" class="mt-1 text-xs text-gray-500">{{ item.data.findings }}</p>
                        <!-- Lab body -->
                        <div v-if="item.type === 'lab'" class="mt-1 text-xs" :class="item.data.is_abnormal ? 'text-red-600 font-medium' : 'text-gray-600'">
                            {{ item.data.test_type }}: {{ item.data.value }} {{ item.data.unit }} <span v-if="item.data.reference_range" class="text-gray-400">({{ item.data.reference_range }})</span>
                        </div>
                        <!-- Delivery body -->
                        <div v-if="item.type === 'delivery'" class="mt-2 flex flex-wrap gap-x-4 gap-y-1 text-xs text-gray-600">
                            <span class="font-medium">{{ item.data.delivery_mode }}</span>
                            <span>{{ item.data.outcome }}</span>
                            <span v-if="item.data.baby_weight_grams">{{ item.data.baby_weight_grams }}g</span>
                            <span v-if="item.data.baby_sex">{{ item.data.baby_sex }}</span>
                        </div>
                        <div v-if="item.type === 'delivery' && item.data.newborn_patient" class="mt-2">
                            <span class="inline-flex items-center gap-1 text-xs font-semibold px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-700">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4"/></svg>
                                {{ isRtl ? 'سُجّل المولود بطب الأطفال' : 'Newborn registered in Pediatrics' }} — {{ item.data.newborn_patient.full_name }} ({{ item.data.newborn_patient.file_number }})
                            </span>
                        </div>
                    </div>
                </li>
            </ol>
        </div>

        <!-- ───── Modals ───── -->
        <Teleport to="body">
            <Transition name="modal">
                <div v-if="modal" v-focus-trap="() => (modal = false)" role="dialog" aria-modal="true" class="fixed inset-0 z-50 flex items-center justify-center p-4" :dir="isRtl ? 'rtl' : 'ltr'">
                    <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="close"></div>
                    <div role="dialog" aria-modal="true" class="relative bg-white rounded-2xl shadow-2xl w-full max-w-lg max-h-[90vh] overflow-y-auto">
                        <div class="p-5 border-b border-gray-100 flex items-center justify-between sticky top-0 bg-white rounded-t-2xl">
                            <h3 class="font-bold text-gray-800">
                                {{ modal === 'anc' ? (isRtl ? 'زيارة متابعة حمل' : 'Antenatal Visit') : modal === 'us' ? (isRtl ? 'سونار توليد' : 'Obstetric Ultrasound') : modal === 'lab' ? (isRtl ? 'تحليل' : 'Lab Test') : (isRtl ? 'تسجيل الولادة' : 'Record Delivery') }}
                            </h3>
                            <button @click="close" class="text-gray-400 hover:text-gray-600" :aria-label="isRtl ? 'إغلاق' : 'Close'" :title="isRtl ? 'إغلاق' : 'Close'"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
                        </div>

                        <!-- ANC -->
                        <form v-if="modal === 'anc'" @submit.prevent="submit(ancForm, 'doctor.obgyn.antenatal.store')" class="p-5 space-y-3">
                            <FormErrors :errors="ancForm.errors" />
                            <div><label class="block text-xs font-medium text-gray-600 mb-1">{{ isRtl ? 'التاريخ' : 'Date' }} *</label><input v-model="ancForm.visit_date" type="date" required class="w-full rounded-xl border-gray-200 text-sm focus:border-rose-400 focus:ring-rose-400" /></div>
                            <div class="grid grid-cols-3 gap-3">
                                <div><label class="block text-xs font-medium text-gray-600 mb-1">{{ isRtl ? 'الوزن (كجم)' : 'Weight (kg)' }}</label><input v-model="ancForm.weight_kg" type="number" step="0.1" class="w-full rounded-xl border-gray-200 text-sm focus:border-rose-400 focus:ring-rose-400" /></div>
                                <div><label class="block text-xs font-medium text-gray-600 mb-1">{{ isRtl ? 'ضغط انقباضي' : 'BP Sys' }}</label><input v-model="ancForm.bp_systolic" type="number" class="w-full rounded-xl border-gray-200 text-sm focus:border-rose-400 focus:ring-rose-400" /></div>
                                <div><label class="block text-xs font-medium text-gray-600 mb-1">{{ isRtl ? 'ضغط انبساطي' : 'BP Dia' }}</label><input v-model="ancForm.bp_diastolic" type="number" class="w-full rounded-xl border-gray-200 text-sm focus:border-rose-400 focus:ring-rose-400" /></div>
                            </div>
                            <div class="grid grid-cols-2 gap-3">
                                <div><label class="block text-xs font-medium text-gray-600 mb-1">{{ isRtl ? 'ارتفاع القاع (سم)' : 'Fundal Ht (cm)' }}</label><input v-model="ancForm.fundal_height_cm" type="number" step="0.1" class="w-full rounded-xl border-gray-200 text-sm focus:border-rose-400 focus:ring-rose-400" /></div>
                                <div><label class="block text-xs font-medium text-gray-600 mb-1">{{ isRtl ? 'نبض الجنين' : 'Fetal HR' }}</label><input v-model="ancForm.fetal_heart_rate" type="number" class="w-full rounded-xl border-gray-200 text-sm focus:border-rose-400 focus:ring-rose-400" /></div>
                            </div>
                            <div><label class="block text-xs font-medium text-gray-600 mb-1">{{ isRtl ? 'الشكوى' : 'Complaints' }}</label><textarea v-model="ancForm.complaints" rows="2" class="w-full rounded-xl border-gray-200 text-sm focus:border-rose-400 focus:ring-rose-400"></textarea></div>
                            <div><label class="block text-xs font-medium text-gray-600 mb-1">{{ isRtl ? 'الخطة' : 'Plan' }}</label><textarea v-model="ancForm.plan" rows="2" class="w-full rounded-xl border-gray-200 text-sm focus:border-rose-400 focus:ring-rose-400"></textarea></div>
                            <div><label class="block text-xs font-medium text-gray-600 mb-1">{{ isRtl ? 'موعد الزيارة القادمة' : 'Next Visit' }}</label><input v-model="ancForm.next_visit_date" type="date" class="w-full rounded-xl border-gray-200 text-sm focus:border-rose-400 focus:ring-rose-400" /></div>
                            <label class="flex items-center gap-2 text-sm text-gray-700"><input v-model="ancForm.bill" type="checkbox" class="rounded text-rose-600" /> {{ isRtl ? 'إصدار فاتورة' : 'Create invoice' }}</label>
                            <div class="flex justify-end gap-2 pt-2"><button type="button" @click="close" class="px-4 py-2.5 rounded-xl text-gray-600 hover:bg-gray-100 text-sm font-medium">{{ isRtl ? 'إلغاء' : 'Cancel' }}</button><button type="submit" :disabled="ancForm.processing" class="px-5 py-2.5 rounded-xl text-white text-sm font-semibold disabled:opacity-50" style="background:#1B365D">{{ isRtl ? 'حفظ' : 'Save' }}</button></div>
                        </form>

                        <!-- Ultrasound -->
                        <form v-else-if="modal === 'us'" @submit.prevent="submit(usForm, 'doctor.obgyn.ultrasound.store')" class="p-5 space-y-3">
                            <FormErrors :errors="usForm.errors" />
                            <div class="grid grid-cols-2 gap-3">
                                <div><label class="block text-xs font-medium text-gray-600 mb-1">{{ isRtl ? 'التاريخ' : 'Date' }} *</label><input v-model="usForm.scan_date" type="date" required class="w-full rounded-xl border-gray-200 text-sm focus:border-rose-400 focus:ring-rose-400" /></div>
                                <div><label class="block text-xs font-medium text-gray-600 mb-1">{{ isRtl ? 'النوع' : 'Type' }}</label>
                                    <select v-model="usForm.scan_type" class="w-full rounded-xl border-gray-200 text-sm focus:border-rose-400 focus:ring-rose-400"><option value="dating">{{ isRtl ? 'تأريخ' : 'Dating' }}</option><option value="anomaly">{{ isRtl ? 'تشوهات' : 'Anomaly' }}</option><option value="growth">{{ isRtl ? 'نمو' : 'Growth' }}</option><option value="doppler">Doppler</option></select>
                                </div>
                            </div>
                            <div class="grid grid-cols-4 gap-2">
                                <div><label class="block text-xs font-medium text-gray-600 mb-1">BPD</label><input v-model="usForm.bpd_mm" type="number" step="0.1" class="w-full rounded-xl border-gray-200 text-sm focus:border-rose-400 focus:ring-rose-400" /></div>
                                <div><label class="block text-xs font-medium text-gray-600 mb-1">HC</label><input v-model="usForm.hc_mm" type="number" step="0.1" class="w-full rounded-xl border-gray-200 text-sm focus:border-rose-400 focus:ring-rose-400" /></div>
                                <div><label class="block text-xs font-medium text-gray-600 mb-1">AC</label><input v-model="usForm.ac_mm" type="number" step="0.1" class="w-full rounded-xl border-gray-200 text-sm focus:border-rose-400 focus:ring-rose-400" /></div>
                                <div><label class="block text-xs font-medium text-gray-600 mb-1">FL</label><input v-model="usForm.fl_mm" type="number" step="0.1" class="w-full rounded-xl border-gray-200 text-sm focus:border-rose-400 focus:ring-rose-400" /></div>
                            </div>
                            <div class="grid grid-cols-3 gap-3">
                                <div><label class="block text-xs font-medium text-gray-600 mb-1">EFW (g)</label><input v-model="usForm.efw_grams" type="number" class="w-full rounded-xl border-gray-200 text-sm focus:border-rose-400 focus:ring-rose-400" /></div>
                                <div><label class="block text-xs font-medium text-gray-600 mb-1">AFI</label><input v-model="usForm.afi" type="number" step="0.1" class="w-full rounded-xl border-gray-200 text-sm focus:border-rose-400 focus:ring-rose-400" /></div>
                                <div><label class="block text-xs font-medium text-gray-600 mb-1">{{ isRtl ? 'عدد الأجنّة' : 'Fetuses' }}</label><input v-model="usForm.fetal_count" type="number" min="1" class="w-full rounded-xl border-gray-200 text-sm focus:border-rose-400 focus:ring-rose-400" /></div>
                            </div>
                            <div><label class="block text-xs font-medium text-gray-600 mb-1">{{ isRtl ? 'الملاحظات' : 'Findings' }}</label><textarea v-model="usForm.findings" rows="2" class="w-full rounded-xl border-gray-200 text-sm focus:border-rose-400 focus:ring-rose-400"></textarea></div>
                            <div v-if="supplies.length" class="grid grid-cols-3 gap-3">
                                <div class="col-span-2"><label class="block text-xs font-medium text-gray-600 mb-1">{{ isRtl ? 'مستلزم (اختياري)' : 'Supply (optional)' }}</label>
                                    <select v-model="usForm.supply_id" class="w-full rounded-xl border-gray-200 text-sm focus:border-rose-400 focus:ring-rose-400"><option value="">—</option><option v-for="s in supplies" :key="s.id" :value="s.id">{{ isRtl ? s.name_ar : s.name_en }}</option></select>
                                </div>
                                <div><label class="block text-xs font-medium text-gray-600 mb-1">{{ isRtl ? 'الكمية' : 'Qty' }}</label><input v-model="usForm.consumption_qty" type="number" step="0.1" min="0" class="w-full rounded-xl border-gray-200 text-sm focus:border-rose-400 focus:ring-rose-400" /></div>
                            </div>
                            <label class="flex items-center gap-2 text-sm text-gray-700"><input v-model="usForm.bill" type="checkbox" class="rounded text-rose-600" /> {{ isRtl ? 'إصدار فاتورة' : 'Create invoice' }}</label>
                            <div class="flex justify-end gap-2 pt-2"><button type="button" @click="close" class="px-4 py-2.5 rounded-xl text-gray-600 hover:bg-gray-100 text-sm font-medium">{{ isRtl ? 'إلغاء' : 'Cancel' }}</button><button type="submit" :disabled="usForm.processing" class="px-5 py-2.5 rounded-xl text-white text-sm font-semibold disabled:opacity-50" :style="{ background: ACCENT }">{{ isRtl ? 'حفظ' : 'Save' }}</button></div>
                        </form>

                        <!-- Lab -->
                        <form v-else-if="modal === 'lab'" @submit.prevent="submit(labForm, 'doctor.obgyn.lab.store')" class="p-5 space-y-3">
                            <FormErrors :errors="labForm.errors" />
                            <div><label class="block text-xs font-medium text-gray-600 mb-1">{{ isRtl ? 'نوع التحليل' : 'Test type' }} *</label><input v-model="labForm.test_type" type="text" required class="w-full rounded-xl border-gray-200 text-sm focus:border-rose-400 focus:ring-rose-400" placeholder="CBC, OGTT…" /></div>
                            <div class="grid grid-cols-3 gap-3">
                                <div><label class="block text-xs font-medium text-gray-600 mb-1">{{ isRtl ? 'القيمة' : 'Value' }}</label><input v-model="labForm.value" type="text" class="w-full rounded-xl border-gray-200 text-sm focus:border-rose-400 focus:ring-rose-400" /></div>
                                <div><label class="block text-xs font-medium text-gray-600 mb-1">{{ isRtl ? 'الوحدة' : 'Unit' }}</label><input v-model="labForm.unit" type="text" class="w-full rounded-xl border-gray-200 text-sm focus:border-rose-400 focus:ring-rose-400" /></div>
                                <div><label class="block text-xs font-medium text-gray-600 mb-1">{{ isRtl ? 'المرجع' : 'Range' }}</label><input v-model="labForm.reference_range" type="text" class="w-full rounded-xl border-gray-200 text-sm focus:border-rose-400 focus:ring-rose-400" /></div>
                            </div>
                            <div><label class="block text-xs font-medium text-gray-600 mb-1">{{ isRtl ? 'التاريخ' : 'Date' }}</label><input v-model="labForm.result_date" type="date" class="w-full rounded-xl border-gray-200 text-sm focus:border-rose-400 focus:ring-rose-400" /></div>
                            <label class="flex items-center gap-2 text-sm text-gray-700"><input v-model="labForm.is_abnormal" type="checkbox" class="rounded text-rose-600" /> {{ isRtl ? 'نتيجة غير طبيعية' : 'Abnormal result' }}</label>
                            <div class="flex justify-end gap-2 pt-2"><button type="button" @click="close" class="px-4 py-2.5 rounded-xl text-gray-600 hover:bg-gray-100 text-sm font-medium">{{ isRtl ? 'إلغاء' : 'Cancel' }}</button><button type="submit" :disabled="labForm.processing" class="px-5 py-2.5 rounded-xl text-white text-sm font-semibold disabled:opacity-50" style="background:#0EA5E9">{{ isRtl ? 'حفظ' : 'Save' }}</button></div>
                        </form>

                        <!-- Delivery -->
                        <form v-else-if="modal === 'delivery'" @submit.prevent="submit(delForm, 'doctor.obgyn.delivery.store')" class="p-5 space-y-3">
                            <FormErrors :errors="delForm.errors" />
                            <div class="grid grid-cols-2 gap-3">
                                <div><label class="block text-xs font-medium text-gray-600 mb-1">{{ isRtl ? 'تاريخ الولادة' : 'Delivery date' }} *</label><input v-model="delForm.delivery_date" type="date" required class="w-full rounded-xl border-gray-200 text-sm focus:border-rose-400 focus:ring-rose-400" /></div>
                                <div><label class="block text-xs font-medium text-gray-600 mb-1">{{ isRtl ? 'النوع' : 'Mode' }}</label><select v-model="delForm.delivery_mode" class="w-full rounded-xl border-gray-200 text-sm focus:border-rose-400 focus:ring-rose-400"><option value="nvd">{{ isRtl ? 'طبيعية' : 'Vaginal' }}</option><option value="cesarean">{{ isRtl ? 'قيصرية' : 'Cesarean' }}</option><option value="instrumental">{{ isRtl ? 'بمساعدة' : 'Instrumental' }}</option></select></div>
                            </div>
                            <div class="grid grid-cols-2 gap-3">
                                <div><label class="block text-xs font-medium text-gray-600 mb-1">{{ isRtl ? 'النتيجة' : 'Outcome' }}</label><select v-model="delForm.outcome" class="w-full rounded-xl border-gray-200 text-sm focus:border-rose-400 focus:ring-rose-400"><option value="live">{{ isRtl ? 'مولود حي' : 'Live birth' }}</option><option value="stillbirth">{{ isRtl ? 'ولادة ميتة' : 'Stillbirth' }}</option></select></div>
                                <div><label class="block text-xs font-medium text-gray-600 mb-1">{{ isRtl ? 'وزن المولود (جم)' : 'Baby wt (g)' }}</label><input v-model="delForm.baby_weight_grams" type="number" class="w-full rounded-xl border-gray-200 text-sm focus:border-rose-400 focus:ring-rose-400" /></div>
                            </div>
                            <div class="grid grid-cols-3 gap-3">
                                <div><label class="block text-xs font-medium text-gray-600 mb-1">{{ isRtl ? 'الجنس' : 'Sex' }}</label><select v-model="delForm.baby_sex" class="w-full rounded-xl border-gray-200 text-sm focus:border-rose-400 focus:ring-rose-400"><option value="">—</option><option value="male">{{ isRtl ? 'ذكر' : 'Male' }}</option><option value="female">{{ isRtl ? 'أنثى' : 'Female' }}</option></select></div>
                                <div><label class="block text-xs font-medium text-gray-600 mb-1">Apgar 1'</label><input v-model="delForm.apgar_1" type="number" min="0" max="10" class="w-full rounded-xl border-gray-200 text-sm focus:border-rose-400 focus:ring-rose-400" /></div>
                                <div><label class="block text-xs font-medium text-gray-600 mb-1">Apgar 5'</label><input v-model="delForm.apgar_5" type="number" min="0" max="10" class="w-full rounded-xl border-gray-200 text-sm focus:border-rose-400 focus:ring-rose-400" /></div>
                            </div>
                            <div v-if="supplies.length" class="grid grid-cols-3 gap-3">
                                <div class="col-span-2"><label class="block text-xs font-medium text-gray-600 mb-1">{{ isRtl ? 'مستلزم (اختياري)' : 'Supply (optional)' }}</label>
                                    <select v-model="delForm.supply_id" class="w-full rounded-xl border-gray-200 text-sm focus:border-rose-400 focus:ring-rose-400"><option value="">—</option><option v-for="s in supplies" :key="s.id" :value="s.id">{{ isRtl ? s.name_ar : s.name_en }}</option></select>
                                </div>
                                <div><label class="block text-xs font-medium text-gray-600 mb-1">{{ isRtl ? 'الكمية' : 'Qty' }}</label><input v-model="delForm.consumption_qty" type="number" step="0.1" min="0" class="w-full rounded-xl border-gray-200 text-sm focus:border-rose-400 focus:ring-rose-400" /></div>
                            </div>
                            <label class="flex items-center gap-2 text-sm text-gray-700"><input v-model="delForm.create_newborn" type="checkbox" class="rounded text-emerald-600" /> {{ isRtl ? 'إنشاء ملف طفل (طب الأطفال) للمولود' : 'Create pediatric file for newborn' }}</label>
                            <label class="flex items-center gap-2 text-sm text-gray-700"><input v-model="delForm.bill" type="checkbox" class="rounded text-emerald-600" /> {{ isRtl ? 'إصدار فاتورة الولادة' : 'Create delivery invoice' }}</label>
                            <div class="flex justify-end gap-2 pt-2"><button type="button" @click="close" class="px-4 py-2.5 rounded-xl text-gray-600 hover:bg-gray-100 text-sm font-medium">{{ isRtl ? 'إلغاء' : 'Cancel' }}</button><button type="submit" :disabled="delForm.processing" class="px-5 py-2.5 rounded-xl text-white text-sm font-semibold disabled:opacity-50" style="background:#10B981">{{ isRtl ? 'حفظ' : 'Save' }}</button></div>
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
