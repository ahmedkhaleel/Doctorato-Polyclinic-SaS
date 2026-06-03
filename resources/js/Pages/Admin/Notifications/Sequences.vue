<script setup>
import { ref, reactive, computed } from 'vue';
import { usePage, router, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { usePermissions } from '@/Composables/usePermissions.js';
import { useConfirm } from '@/Composables/useConfirm.js';

const props = defineProps({ sequences: Array, events: Array });

const page = usePage();
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');
const t = (ar, en) => (isRtl.value ? ar : en);
const { confirm } = useConfirm();
const { can } = usePermissions();
const canEdit = can('notifications.update');

const channelMeta = {
    '': { ar: 'تلقائي (توجيه)', en: 'Auto (routing)' },
    whatsapp: { ar: 'واتساب', en: 'WhatsApp' }, sms: { ar: 'SMS', en: 'SMS' },
    email: { ar: 'بريد', en: 'Email' }, in_app: { ar: 'داخلي', en: 'In-App' },
};

const showForm = ref(false);
const form = reactive({ id: null, name: '', trigger_event: '', is_active: true, steps: [] });

function blankStep() {
    return { delay_minutes: 0, channel: '', subject: '', body_ar: '', body_en: '' };
}
function openNew() {
    Object.assign(form, { id: null, name: '', trigger_event: '', is_active: true, steps: [blankStep()] });
    showForm.value = true;
}
function edit(seq) {
    Object.assign(form, {
        id: seq.id, name: seq.name, trigger_event: seq.trigger_event || '', is_active: seq.is_active,
        steps: (seq.steps || []).map((s) => ({ delay_minutes: s.delay_minutes, channel: s.channel || '', subject: s.subject || '', body_ar: s.body_ar, body_en: s.body_en || '' })),
    });
    if (!form.steps.length) form.steps.push(blankStep());
    showForm.value = true;
}
function addStep() { form.steps.push(blankStep()); }
function removeStep(i) { form.steps.splice(i, 1); }
function save() {
    const url = form.id ? `/admin/notification-sequences/${form.id}/update` : '/admin/notification-sequences';
    router.post(url, { ...form, trigger_event: form.trigger_event || null }, { preserveScroll: true, onSuccess: () => { showForm.value = false; } });
}
function remove(seq) {
    confirm(t('حذف السلسلة وكل تسجيلاتها؟', 'Delete sequence and all enrolments?'), () => router.post(`/admin/notification-sequences/${seq.id}/delete`, {}, { preserveScroll: true }));
}

const enrollFor = ref(null);
const enrollPatientId = ref('');
function doEnroll(seq) {
    if (!enrollPatientId.value) return;
    router.post(`/admin/notification-sequences/${seq.id}/enroll`, { patient_id: enrollPatientId.value }, {
        preserveScroll: true, onSuccess: () => { enrollFor.value = null; enrollPatientId.value = ''; },
    });
}

const humanDelay = (m) => {
    if (m === 0) return t('فوراً', 'immediately');
    if (m < 60) return `${m} ${t('دقيقة', 'min')}`;
    if (m < 1440) return `${Math.round(m / 60)} ${t('ساعة', 'hr')}`;
    return `${Math.round(m / 1440)} ${t('يوم', 'days')}`;
};
</script>

<template>
    <AdminLayout :title="t('سلاسل التنقيط', 'Drip Sequences')">
        <div class="max-w-5xl mx-auto p-4 md:p-6 space-y-5" :dir="isRtl ? 'rtl' : 'ltr'">
            <div class="flex items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 rounded-xl flex items-center justify-center text-white shadow" style="background:linear-gradient(135deg,#1B365D,#2a4a7a)">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
                    </div>
                    <div>
                        <h1 class="text-xl font-bold text-gray-900">{{ t('سلاسل التنقيط', 'Drip Sequences') }}</h1>
                        <p class="text-xs text-gray-500">{{ t('رحلات تلقائية متعددة الخطوات (ترحيب، رعاية ما بعد العلاج)', 'Automated multi-step journeys (welcome, post-op care)') }}</p>
                    </div>
                </div>
                <div class="flex gap-2">
                    <Link href="/admin/notifications-hub" class="text-sm font-semibold px-4 py-2 rounded-lg border border-gray-200 text-gray-600 hover:bg-gray-50">{{ t('المركز', 'Hub') }}</Link>
                    <button v-if="can('notifications.create')" @click="openNew" class="px-4 py-2 rounded-lg text-white text-sm font-semibold" style="background:#1B365D">+ {{ t('سلسلة', 'Sequence') }}</button>
                </div>
            </div>

            <!-- Builder -->
            <div v-if="showForm" class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 space-y-4">
                <div class="grid sm:grid-cols-2 gap-3">
                    <label class="block"><span class="text-xs text-gray-500">{{ t('اسم السلسلة', 'Sequence name') }}</span>
                        <input v-model="form.name" class="mt-1 w-full rounded-lg border-gray-200 text-sm" /></label>
                    <label class="block"><span class="text-xs text-gray-500">{{ t('حدث التسجيل التلقائي (اختياري)', 'Auto-enrol trigger (optional)') }}</span>
                        <select v-model="form.trigger_event" class="mt-1 w-full rounded-lg border-gray-200 text-sm">
                            <option value="">{{ t('— تسجيل يدوي فقط —', '— manual only —') }}</option>
                            <option v-for="e in events" :key="e.key" :value="e.key">{{ t(e.label_ar, e.label_en) }}</option>
                        </select></label>
                </div>
                <label class="inline-flex items-center gap-2 text-sm"><input type="checkbox" v-model="form.is_active" class="rounded" /> {{ t('مُفعّلة', 'Active') }}</label>

                <!-- Steps -->
                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <h3 class="font-bold text-gray-800 text-sm">{{ t('الخطوات', 'Steps') }}</h3>
                        <button @click="addStep" class="text-xs font-semibold text-[#1B365D] hover:underline">+ {{ t('خطوة', 'Step') }}</button>
                    </div>
                    <div v-for="(step, i) in form.steps" :key="i" class="rounded-xl border border-gray-100 p-4 relative">
                        <div class="flex items-center gap-2 mb-3">
                            <span class="w-6 h-6 rounded-full bg-[#1B365D] text-white text-xs font-bold flex items-center justify-center">{{ i + 1 }}</span>
                            <span class="text-xs text-gray-500">{{ t('بعد', 'after') }}</span>
                            <input type="number" min="0" v-model.number="step.delay_minutes" class="w-24 rounded-lg border-gray-200 text-sm" />
                            <span class="text-xs text-gray-500">{{ t('دقيقة', 'min') }} ({{ humanDelay(step.delay_minutes) }})</span>
                            <select v-model="step.channel" class="ms-auto rounded-lg border-gray-200 text-xs">
                                <option v-for="(m, k) in channelMeta" :key="k" :value="k">{{ t(m.ar, m.en) }}</option>
                            </select>
                            <button v-if="form.steps.length > 1" @click="removeStep(i)" class="text-red-400 text-xs hover:underline">{{ t('حذف', 'Remove') }}</button>
                        </div>
                        <input v-if="step.channel === 'email'" v-model="step.subject" :placeholder="t('الموضوع', 'Subject')" class="w-full rounded-lg border-gray-200 text-sm mb-2" />
                        <textarea v-model="step.body_ar" rows="2" :placeholder="t('النص بالعربية', 'Body (Arabic)')" class="w-full rounded-lg border-gray-200 text-sm mb-2"></textarea>
                        <textarea v-model="step.body_en" rows="2" :placeholder="t('النص بالإنجليزية (اختياري)', 'Body (English, optional)')" class="w-full rounded-lg border-gray-200 text-sm"></textarea>
                    </div>
                </div>

                <div class="flex justify-end gap-2">
                    <button @click="showForm = false" class="px-4 py-2 rounded-lg border border-gray-200 text-gray-600 text-sm">{{ t('إلغاء', 'Cancel') }}</button>
                    <button @click="save" :disabled="!form.name || !form.steps.length" class="px-4 py-2 rounded-lg text-white text-sm font-semibold disabled:opacity-50" style="background:#1B365D">{{ t('حفظ', 'Save') }}</button>
                </div>
            </div>

            <!-- List -->
            <div v-if="!sequences.length && !showForm" class="text-center text-gray-400 py-12 bg-white rounded-2xl border border-gray-100">{{ t('لا توجد سلاسل بعد', 'No sequences yet') }}</div>
            <div v-for="seq in sequences" :key="seq.id" class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <div class="flex items-center gap-2">
                            <h3 class="font-bold text-gray-900">{{ seq.name }}</h3>
                            <span class="text-xs font-bold px-2 py-0.5 rounded-full" :class="seq.is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-400'">{{ seq.is_active ? t('مُفعّلة', 'Active') : t('متوقفة', 'Off') }}</span>
                        </div>
                        <p class="text-xs text-gray-500 mt-1">
                            {{ seq.steps.length }} {{ t('خطوة', 'steps') }} ·
                            {{ seq.active_count }} {{ t('نشط', 'active') }} · {{ seq.completed_count }} {{ t('مكتمل', 'done') }}
                            <span v-if="seq.trigger_event"> · {{ t('تشغيل تلقائي:', 'auto:') }} {{ seq.trigger_event }}</span>
                        </p>
                    </div>
                    <div class="flex gap-2 shrink-0 text-xs">
                        <button v-if="can('notifications.send')" @click="enrollFor = enrollFor === seq.id ? null : seq.id" class="font-semibold text-[#C4A265] hover:underline">{{ t('تسجيل مريض', 'Enrol') }}</button>
                        <button v-if="canEdit" @click="edit(seq)" class="font-semibold text-[#1B365D] hover:underline">{{ t('تعديل', 'Edit') }}</button>
                        <button v-if="canEdit" @click="remove(seq)" class="text-red-400 hover:underline">{{ t('حذف', 'Delete') }}</button>
                    </div>
                </div>
                <div v-if="enrollFor === seq.id" class="mt-3 flex items-center gap-2">
                    <input type="number" v-model="enrollPatientId" :placeholder="t('رقم ملف المريض (ID)', 'Patient ID')" class="rounded-lg border-gray-200 text-sm" />
                    <button @click="doEnroll(seq)" :disabled="!enrollPatientId" class="px-3 py-1.5 rounded-lg text-white text-xs font-semibold disabled:opacity-50" style="background:#1B365D">{{ t('تسجيل', 'Enrol') }}</button>
                </div>
                <!-- step timeline -->
                <div class="mt-3 flex flex-wrap gap-2">
                    <div v-for="(s, i) in seq.steps" :key="s.id" class="flex items-center gap-1.5 text-xs text-gray-500 bg-gray-50 rounded-lg px-2.5 py-1">
                        <span class="w-4 h-4 rounded-full bg-[#1B365D] text-white text-[9px] flex items-center justify-center">{{ i + 1 }}</span>
                        {{ humanDelay(s.delay_minutes) }} · {{ t(channelMeta[s.channel || '']?.ar, channelMeta[s.channel || '']?.en) }}
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
