<script setup>
import { Link, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import DoctorLayout from '@/Layouts/DoctorLayout.vue';
import TrendLine from '@/Components/Charts/TrendLine.vue';

defineOptions({ layout: DoctorLayout });

const page = usePage();
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');
const ACCENT = '#0D9488';

const props = defineProps({
    patient: { type: Object, required: true },
    assessments: { type: Array, default: () => [] },
    plans: { type: Array, default: () => [] },
    sessions: { type: Array, default: () => [] },
    romHistory: { type: Array, default: () => [] },
    romNormatives: { type: Object, default: () => ({}) },
    exerciseCatalog: { type: Array, default: () => [] },
    prescriptions: { type: Array, default: () => [] },
});

const tab = ref('overview');
const showPlanForm = ref(false);
const showSessionForm = ref(false);
const showAssessForm = ref(false);
const showRxForm = ref(false);

const activePrescriptions = computed(() => props.prescriptions.filter((r) => r.status === 'active'));

const activePlans = computed(() => props.plans.filter((p) => ['planned', 'in_progress'].includes(p.status)));
const statusColor = (s) => ({ in_progress: '#0D9488', planned: '#6366F1', on_hold: '#D97706', completed: '#059669', cancelled: '#9CA3AF' }[s] || '#6B7280');
const t = (en, ar) => (isRtl.value ? ar : en);
const dateLabel = (d) => (d ? new Date(d).toLocaleDateString(isRtl.value ? 'ar-EG' : 'en-GB', { day: 'numeric', month: 'short', year: 'numeric' }) : '');

// ── Pain trend across sessions (before vs after) ──
const painSeries = computed(() => {
    const pts = props.sessions
        .filter((s) => s.pain_after != null)
        .slice()
        .reverse()
        .map((s, i) => ({ x: i + 1, y: Number(s.pain_after) }));
    return pts.length >= 2 ? [{ key: 'pain', label: t('Pain (post-session)', 'الألم بعد الجلسة'), color: '#EF4444', points: pts }] : [];
});

// ── ROM trend (knee flexion example or first available joint/motion) ──
const romSeries = computed(() => {
    const byKey = {};
    props.romHistory.forEach((r) => {
        const k = `${r.joint}/${r.motion}/${r.side}`;
        (byKey[k] ||= []).push(r);
    });
    const firstKey = Object.keys(byKey)[0];
    if (!firstKey || byKey[firstKey].length < 2) return { series: [], band: null, title: '' };
    const rows = byKey[firstKey];
    const pts = rows.map((r, i) => ({ x: i + 1, y: Number(r.arom || 0) }));
    const norm = Number(rows[0].normal_ref || 0);
    return {
        series: [{ key: 'arom', label: t('Active ROM (°)', 'المدى النشط (°)'), color: ACCENT, points: pts }],
        band: norm > 0 ? pts.map((p) => ({ x: p.x, low: norm * 0.9, high: norm })) : [],
        title: firstKey,
    };
});

// ── Plan form ──
const planForm = useForm({ title_ar: '', title_en: '', problem_list: '', frequency: '3x/week', duration_weeks: 4, estimated_sessions: 12, modalities: '', notes: '' });
function submitPlan() {
    planForm.transform((d) => ({ ...d, modalities: d.modalities ? d.modalities.split(',').map((x) => x.trim()).filter(Boolean) : [] }))
        .post(route('doctor.physiotherapy.plans.store', props.patient.id), { preserveScroll: true, onSuccess: () => { planForm.reset(); showPlanForm.value = false; } });
}
function setPlanStatus(plan, status) {
    useForm({ status }).post(route('doctor.physiotherapy.plans.status', plan.id), { preserveScroll: true });
}

// ── Session form ──
const sessionForm = useForm({ treatment_plan_id: '', session_date: new Date().toISOString().slice(0, 10), pain_before: null, pain_after: null, modalities: '', techniques: '', soap: '', cost: null, attended: true, bill: true });
function submitSession() {
    sessionForm.transform((d) => ({ ...d, modalities: d.modalities ? d.modalities.split(',').map((x) => x.trim()).filter(Boolean) : [] }))
        .post(route('doctor.physiotherapy.sessions.store', props.patient.id), { preserveScroll: true, onSuccess: () => { sessionForm.reset('pain_before', 'pain_after', 'modalities', 'techniques', 'soap', 'cost'); showSessionForm.value = false; } });
}

// ── Assessment form with repeatable rows ──
const assessForm = useForm({
    assessment_date: new Date().toISOString().slice(0, 10),
    subjective: '', objective: '', diagnosis: '', plan: '',
    rom: [], strength: [], pain_points: [],
});
const joints = computed(() => Object.keys(props.romNormatives));
const motionsFor = (joint) => Object.keys(props.romNormatives[joint] || {});
function addRom() { assessForm.rom.push({ joint: 'knee', motion: 'flexion', side: 'right', arom: null, prom: null }); }
function addMmt() { assessForm.strength.push({ muscle_group: '', side: 'right', grade: 5 }); }
function addPain() { assessForm.pain_points.push({ view: 'front', x: 50, y: 50, intensity: 5, pain_type: 'aching' }); }
function submitAssess() {
    assessForm.post(route('doctor.physiotherapy.assessments.store', props.patient.id), {
        preserveScroll: true,
        onSuccess: () => { assessForm.reset(); showAssessForm.value = false; },
    });
}

// ── HEP prescription ──
const rxForm = useForm({ exercise_id: '', sets: 3, reps: 10, hold_sec: null, frequency: 'daily', notes: '' });
function onPickExercise() {
    const ex = props.exerciseCatalog.find((e) => e.id === Number(rxForm.exercise_id));
    if (ex) {
        rxForm.sets = ex.default_sets || rxForm.sets;
        rxForm.reps = ex.default_reps || rxForm.reps;
        rxForm.hold_sec = ex.default_hold_sec || null;
    }
}
function submitRx() {
    rxForm.post(route('doctor.physiotherapy.exercises.store', props.patient.id), { preserveScroll: true, onSuccess: () => { rxForm.reset(); showRxForm.value = false; } });
}
function stopRx(rx) {
    useForm({}).post(route('doctor.physiotherapy.exercises.stop', rx.id), { preserveScroll: true });
}
const exName = (ex) => (ex ? (isRtl.value ? ex.name_ar : ex.name_en) : '');
</script>

<template>
    <div class="space-y-5" :dir="isRtl ? 'rtl' : 'ltr'">
        <!-- Patient header -->
        <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100 flex items-center justify-between flex-wrap gap-3">
            <div>
                <Link :href="route('doctor.physiotherapy.patients.index')" class="text-xs text-gray-400 hover:text-teal-600">← {{ t('Patients', 'المرضى') }}</Link>
                <h1 class="text-xl font-bold text-gray-800 mt-1">{{ patient.full_name }}</h1>
                <p class="text-sm text-gray-500">{{ patient.file_number }} · <span dir="ltr">{{ patient.phone }}</span></p>
            </div>
            <div class="flex gap-2">
                <button @click="showAssessForm = !showAssessForm; tab = 'assessments'" class="px-4 py-2 rounded-xl text-sm font-semibold text-white" :style="{ backgroundColor: ACCENT }">+ {{ t('Assessment', 'تقييم') }}</button>
                <button @click="showSessionForm = !showSessionForm" class="px-4 py-2 rounded-xl text-sm font-semibold bg-gray-100 text-gray-700 hover:bg-gray-200">+ {{ t('Session', 'جلسة') }}</button>
                <button @click="showPlanForm = !showPlanForm" class="px-4 py-2 rounded-xl text-sm font-semibold bg-gray-100 text-gray-700 hover:bg-gray-200">+ {{ t('Plan', 'خطة') }}</button>
                <button @click="showRxForm = !showRxForm" class="px-4 py-2 rounded-xl text-sm font-semibold bg-gray-100 text-gray-700 hover:bg-gray-200">+ {{ t('Exercise', 'تمرين') }}</button>
            </div>
        </div>

        <!-- New plan form -->
        <form v-if="showPlanForm" @submit.prevent="submitPlan" class="bg-white rounded-2xl p-5 shadow-sm border border-teal-100 space-y-3">
            <h3 class="font-semibold text-gray-800">{{ t('New Treatment Plan', 'خطة علاجية جديدة') }}</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <input v-model="planForm.title_en" :placeholder="t('Title (EN)', 'العنوان (EN)')" class="form-in" />
                <input v-model="planForm.title_ar" :placeholder="t('Title (AR)', 'العنوان (AR)')" class="form-in" />
                <input v-model="planForm.frequency" :placeholder="t('Frequency e.g. 3x/week', 'التكرار مثل 3x/week')" class="form-in" />
                <input v-model.number="planForm.duration_weeks" type="number" min="1" :placeholder="t('Weeks', 'أسابيع')" class="form-in" />
                <input v-model.number="planForm.estimated_sessions" type="number" min="0" :placeholder="t('Estimated sessions', 'عدد الجلسات المتوقع')" class="form-in" />
                <input v-model="planForm.modalities" :placeholder="t('Modalities (comma separated)', 'الوسائل (مفصولة بفواصل)')" class="form-in" />
            </div>
            <textarea v-model="planForm.problem_list" :placeholder="t('Problem list', 'قائمة المشكلات')" rows="2" class="form-in w-full"></textarea>
            <div class="flex justify-end gap-2">
                <button type="button" @click="showPlanForm = false" class="px-4 py-2 text-sm text-gray-500">{{ t('Cancel', 'إلغاء') }}</button>
                <button type="submit" :disabled="planForm.processing" class="px-5 py-2 rounded-xl text-sm font-semibold text-white" :style="{ backgroundColor: ACCENT }">{{ t('Save', 'حفظ') }}</button>
            </div>
        </form>

        <!-- New session form -->
        <form v-if="showSessionForm" @submit.prevent="submitSession" class="bg-white rounded-2xl p-5 shadow-sm border border-teal-100 space-y-3">
            <h3 class="font-semibold text-gray-800">{{ t('Log Session', 'تسجيل جلسة') }}</h3>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                <select v-model="sessionForm.treatment_plan_id" class="form-in">
                    <option value="">{{ t('No plan', 'بدون خطة') }}</option>
                    <option v-for="p in activePlans" :key="p.id" :value="p.id">{{ (isRtl ? p.title_ar : p.title_en) || ('#' + p.id) }}</option>
                </select>
                <input v-model="sessionForm.session_date" type="date" class="form-in" />
                <input v-model.number="sessionForm.pain_before" type="number" min="0" max="10" :placeholder="t('Pain before', 'الألم قبل')" class="form-in" />
                <input v-model.number="sessionForm.pain_after" type="number" min="0" max="10" :placeholder="t('Pain after', 'الألم بعد')" class="form-in" />
                <input v-model="sessionForm.modalities" :placeholder="t('Modalities', 'الوسائل')" class="form-in md:col-span-2" />
                <input v-model.number="sessionForm.cost" type="number" min="0" :placeholder="t('Cost', 'التكلفة')" class="form-in" />
                <label class="flex items-center gap-2 text-sm text-gray-600"><input v-model="sessionForm.bill" type="checkbox" class="rounded" /> {{ t('Bill', 'فوترة') }}</label>
            </div>
            <textarea v-model="sessionForm.soap" :placeholder="t('SOAP note', 'ملاحظة SOAP')" rows="2" class="form-in w-full"></textarea>
            <div class="flex justify-end gap-2">
                <button type="button" @click="showSessionForm = false" class="px-4 py-2 text-sm text-gray-500">{{ t('Cancel', 'إلغاء') }}</button>
                <button type="submit" :disabled="sessionForm.processing" class="px-5 py-2 rounded-xl text-sm font-semibold text-white" :style="{ backgroundColor: ACCENT }">{{ t('Save', 'حفظ') }}</button>
            </div>
        </form>

        <!-- Prescribe exercise form -->
        <form v-if="showRxForm" @submit.prevent="submitRx" class="bg-white rounded-2xl p-5 shadow-sm border border-teal-100 space-y-3">
            <h3 class="font-semibold text-gray-800">{{ t('Prescribe Home Exercise', 'وصف تمرين منزلي') }}</h3>
            <div class="grid grid-cols-2 md:grid-cols-5 gap-3">
                <select v-model="rxForm.exercise_id" @change="onPickExercise" class="form-in md:col-span-2" required>
                    <option value="">{{ t('Select exercise…', 'اختر تمريناً…') }}</option>
                    <option v-for="ex in exerciseCatalog" :key="ex.id" :value="ex.id">{{ exName(ex) }} ({{ ex.region }})</option>
                </select>
                <input v-model.number="rxForm.sets" type="number" min="1" :placeholder="t('Sets', 'مجموعات')" class="form-in" />
                <input v-model.number="rxForm.reps" type="number" min="1" :placeholder="t('Reps', 'تكرارات')" class="form-in" />
                <input v-model.number="rxForm.hold_sec" type="number" min="0" :placeholder="t('Hold s', 'ثبات ث')" class="form-in" />
                <input v-model="rxForm.frequency" :placeholder="t('Frequency', 'التكرار')" class="form-in" />
                <input v-model="rxForm.notes" :placeholder="t('Notes', 'ملاحظات')" class="form-in md:col-span-4" />
            </div>
            <div class="flex justify-end gap-2">
                <button type="button" @click="showRxForm = false" class="px-4 py-2 text-sm text-gray-500">{{ t('Cancel', 'إلغاء') }}</button>
                <button type="submit" :disabled="rxForm.processing" class="px-5 py-2 rounded-xl text-sm font-semibold text-white" :style="{ backgroundColor: ACCENT }">{{ t('Prescribe', 'وصف') }}</button>
            </div>
        </form>

        <!-- HEP list -->
        <div v-if="activePrescriptions.length" class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100">
            <h2 class="font-semibold text-gray-800 mb-3">{{ t('Home Exercise Program', 'برنامج التمارين المنزلية') }}</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <div v-for="rx in activePrescriptions" :key="rx.id" class="flex items-center gap-3 p-3 rounded-xl border border-gray-100">
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-800">{{ exName(rx.exercise) }}</p>
                        <p class="text-xs text-gray-500">
                            <span v-if="rx.sets">{{ rx.sets }}×{{ rx.reps }}</span>
                            <span v-if="rx.hold_sec"> · {{ rx.hold_sec }}s</span>
                            <span v-if="rx.frequency"> · {{ rx.frequency }}</span>
                        </p>
                    </div>
                    <span class="text-[11px] px-2 py-1 rounded-full bg-teal-50 text-teal-600 font-medium">{{ rx.adherence_14d }}/14</span>
                    <button @click="stopRx(rx)" class="text-gray-300 hover:text-red-500" :title="t('Stop', 'إيقاف')">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- Tabs -->
        <div class="flex gap-1 bg-gray-100 rounded-xl p-1 w-fit">
            <button @click="tab = 'overview'" class="px-4 py-1.5 rounded-lg text-sm font-medium transition" :class="tab === 'overview' ? 'bg-white shadow-sm text-gray-800' : 'text-gray-500'">{{ t('Overview', 'نظرة عامة') }}</button>
            <button @click="tab = 'assessments'" class="px-4 py-1.5 rounded-lg text-sm font-medium transition" :class="tab === 'assessments' ? 'bg-white shadow-sm text-gray-800' : 'text-gray-500'">{{ t('Assessments', 'التقييمات') }}</button>
        </div>

        <!-- OVERVIEW -->
        <div v-show="tab === 'overview'" class="grid grid-cols-1 lg:grid-cols-2 gap-5">
            <!-- Plans -->
            <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100">
                <h2 class="font-semibold text-gray-800 mb-4">{{ t('Treatment Plans', 'الخطط العلاجية') }}</h2>
                <p v-if="!plans.length" class="text-sm text-gray-400 py-6 text-center">{{ t('No plans', 'لا توجد خطط') }}</p>
                <div v-for="p in plans" :key="p.id" class="py-3 border-b border-gray-50 last:border-0">
                    <div class="flex items-center justify-between gap-2">
                        <p class="text-sm font-medium text-gray-800">{{ (isRtl ? p.title_ar : p.title_en) || ('#' + p.id) }}</p>
                        <select :value="p.status" @change="(e) => setPlanStatus(p, e.target.value)" class="text-[11px] font-semibold px-2 py-1 rounded-full border-0 cursor-pointer"
                                :style="{ backgroundColor: statusColor(p.status) + '1A', color: statusColor(p.status) }">
                            <option value="in_progress">in_progress</option>
                            <option value="on_hold">on_hold</option>
                            <option value="completed">completed</option>
                            <option value="cancelled">cancelled</option>
                        </select>
                    </div>
                    <div class="h-2 rounded-full bg-gray-100 overflow-hidden mt-2">
                        <div class="h-full rounded-full" :style="{ width: p.progress_percentage + '%', backgroundColor: ACCENT }"></div>
                    </div>
                    <p class="text-[11px] text-gray-400 mt-1">{{ p.completed_sessions }}/{{ p.estimated_sessions }} · {{ p.frequency }}</p>
                </div>
            </div>

            <!-- Trends + sessions -->
            <div class="space-y-5">
                <div v-if="painSeries.length || romSeries.series.length" class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100">
                    <h2 class="font-semibold text-gray-800 mb-3">{{ t('Progress', 'التقدّم') }}</h2>
                    <div v-if="painSeries.length"><TrendLine :series="painSeries" :is-rtl="isRtl" :height="160" /></div>
                    <div v-if="romSeries.series.length" class="mt-3">
                        <p class="text-xs text-gray-400 mb-1">{{ romSeries.title }}</p>
                        <TrendLine :series="romSeries.series" :band="romSeries.band" :is-rtl="isRtl" :height="160" />
                    </div>
                </div>
                <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100">
                    <h2 class="font-semibold text-gray-800 mb-3">{{ t('Session Log', 'سجل الجلسات') }}</h2>
                    <p v-if="!sessions.length" class="text-sm text-gray-400 py-6 text-center">{{ t('No sessions', 'لا توجد جلسات') }}</p>
                    <table v-else class="w-full text-sm">
                        <tbody>
                            <tr v-for="s in sessions" :key="s.id" class="border-b border-gray-50 last:border-0">
                                <td class="py-2 text-gray-500 text-xs">#{{ s.session_number }}</td>
                                <td class="py-2 text-gray-600">{{ dateLabel(s.session_date) }}</td>
                                <td class="py-2 text-xs" :class="!s.attended && 'text-red-400'">{{ s.attended ? t('Attended', 'حضر') : t('Missed', 'تغيّب') }}</td>
                                <td class="py-2 text-end text-xs text-gray-500">
                                    <span v-if="s.pain_before != null && s.pain_after != null">{{ s.pain_before }}→{{ s.pain_after }}</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ASSESSMENTS -->
        <div v-show="tab === 'assessments'" class="space-y-5">
            <!-- New assessment form -->
            <form v-if="showAssessForm" @submit.prevent="submitAssess" class="bg-white rounded-2xl p-5 shadow-sm border border-teal-100 space-y-4">
                <h3 class="font-semibold text-gray-800">{{ t('New Assessment', 'تقييم جديد') }}</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <input v-model="assessForm.assessment_date" type="date" class="form-in" />
                    <input v-model="assessForm.diagnosis" :placeholder="t('Diagnosis', 'التشخيص')" class="form-in" />
                </div>
                <textarea v-model="assessForm.subjective" :placeholder="t('Subjective (S)', 'الشكوى (S)')" rows="2" class="form-in w-full"></textarea>
                <textarea v-model="assessForm.objective" :placeholder="t('Objective (O)', 'الفحص (O)')" rows="2" class="form-in w-full"></textarea>

                <!-- ROM rows -->
                <div>
                    <div class="flex items-center justify-between mb-2"><span class="text-sm font-medium text-gray-700">{{ t('Range of Motion', 'المدى الحركي') }}</span><button type="button" @click="addRom" class="text-teal-600 text-sm">+ {{ t('Add', 'إضافة') }}</button></div>
                    <div v-for="(r, i) in assessForm.rom" :key="'rom' + i" class="grid grid-cols-2 md:grid-cols-5 gap-2 mb-2">
                        <select v-model="r.joint" class="form-in"><option v-for="j in joints" :key="j" :value="j">{{ j }}</option></select>
                        <select v-model="r.motion" class="form-in"><option v-for="m in motionsFor(r.joint)" :key="m" :value="m">{{ m }}</option></select>
                        <select v-model="r.side" class="form-in"><option value="right">{{ t('Right', 'يمين') }}</option><option value="left">{{ t('Left', 'يسار') }}</option><option value="bilateral">{{ t('Both', 'كلاهما') }}</option></select>
                        <input v-model.number="r.arom" type="number" :placeholder="t('AROM°', 'نشط°')" class="form-in" />
                        <input v-model.number="r.prom" type="number" :placeholder="t('PROM°', 'سلبي°')" class="form-in" />
                    </div>
                </div>

                <!-- MMT rows -->
                <div>
                    <div class="flex items-center justify-between mb-2"><span class="text-sm font-medium text-gray-700">{{ t('Muscle Strength (0–5)', 'قوة العضلات (0–5)') }}</span><button type="button" @click="addMmt" class="text-teal-600 text-sm">+ {{ t('Add', 'إضافة') }}</button></div>
                    <div v-for="(m, i) in assessForm.strength" :key="'mmt' + i" class="grid grid-cols-2 md:grid-cols-3 gap-2 mb-2">
                        <input v-model="m.muscle_group" :placeholder="t('Muscle group', 'المجموعة العضلية')" class="form-in" />
                        <select v-model="m.side" class="form-in"><option value="right">{{ t('Right', 'يمين') }}</option><option value="left">{{ t('Left', 'يسار') }}</option><option value="bilateral">{{ t('Both', 'كلاهما') }}</option></select>
                        <input v-model.number="m.grade" type="number" min="0" max="5" :placeholder="t('Grade', 'الدرجة')" class="form-in" />
                    </div>
                </div>

                <!-- Pain rows -->
                <div>
                    <div class="flex items-center justify-between mb-2"><span class="text-sm font-medium text-gray-700">{{ t('Pain Points', 'نقاط الألم') }}</span><button type="button" @click="addPain" class="text-teal-600 text-sm">+ {{ t('Add', 'إضافة') }}</button></div>
                    <div v-for="(p, i) in assessForm.pain_points" :key="'pain' + i" class="grid grid-cols-2 md:grid-cols-5 gap-2 mb-2">
                        <select v-model="p.view" class="form-in"><option value="front">{{ t('Front', 'أمامي') }}</option><option value="back">{{ t('Back', 'خلفي') }}</option></select>
                        <input v-model.number="p.x" type="number" min="0" max="100" placeholder="X%" class="form-in" />
                        <input v-model.number="p.y" type="number" min="0" max="100" placeholder="Y%" class="form-in" />
                        <input v-model.number="p.intensity" type="number" min="0" max="10" :placeholder="t('Intensity', 'الشدة')" class="form-in" />
                        <input v-model="p.pain_type" :placeholder="t('Type', 'النوع')" class="form-in" />
                    </div>
                </div>

                <div class="flex justify-end gap-2">
                    <button type="button" @click="showAssessForm = false" class="px-4 py-2 text-sm text-gray-500">{{ t('Cancel', 'إلغاء') }}</button>
                    <button type="submit" :disabled="assessForm.processing" class="px-5 py-2 rounded-xl text-sm font-semibold text-white" :style="{ backgroundColor: ACCENT }">{{ t('Save Assessment', 'حفظ التقييم') }}</button>
                </div>
            </form>

            <!-- Assessment history -->
            <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100">
                <h2 class="font-semibold text-gray-800 mb-4">{{ t('Assessment History', 'سجل التقييمات') }}</h2>
                <p v-if="!assessments.length" class="text-sm text-gray-400 py-6 text-center">{{ t('No assessments', 'لا توجد تقييمات') }}</p>
                <div v-for="a in assessments" :key="a.id" class="py-3 border-b border-gray-50 last:border-0">
                    <div class="flex items-center justify-between">
                        <p class="text-sm font-medium text-gray-800">{{ a.diagnosis || t('Assessment', 'تقييم') }}</p>
                        <span class="text-xs text-gray-400">{{ dateLabel(a.assessment_date) }}</span>
                    </div>
                    <div class="flex gap-3 mt-1 text-[11px] text-gray-500">
                        <span>{{ t('ROM', 'مدى') }}: {{ a.rom_measurements?.length || 0 }}</span>
                        <span>{{ t('MMT', 'قوة') }}: {{ a.strength_tests?.length || 0 }}</span>
                        <span>{{ t('Pain', 'ألم') }}: {{ a.pain_points?.length || 0 }}</span>
                    </div>
                    <p v-if="a.subjective" class="text-xs text-gray-500 mt-1 line-clamp-2">{{ a.subjective }}</p>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.form-in {
    padding: 0.5rem 0.75rem;
    border-radius: 0.75rem;
    border: 1px solid #e5e7eb;
    font-size: 0.875rem;
    line-height: 1.25rem;
    background: #fff;
    outline: none;
    transition: border-color 0.15s, box-shadow 0.15s;
}
.form-in:focus {
    border-color: #0d9488;
    box-shadow: 0 0 0 2px rgba(13, 148, 136, 0.25);
}
</style>
