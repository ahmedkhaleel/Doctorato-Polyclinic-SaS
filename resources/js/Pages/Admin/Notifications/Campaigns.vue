<script setup>
import { ref, reactive, computed, watch } from 'vue';
import { usePage, router, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import AiAssist from '@/Components/Ai/AiAssist.vue';
import { usePermissions } from '@/Composables/usePermissions.js';
import { useConfirm } from '@/Composables/useConfirm.js';

const props = defineProps({ campaigns: Array });

const page = usePage();
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');
const t = (ar, en) => (isRtl.value ? ar : en);
const { confirm } = useConfirm();
const { can } = usePermissions();
const canSend = can('notifications.send');

const channelMeta = {
    whatsapp: { ar: 'واتساب', en: 'WhatsApp', color: '#25D366' },
    sms: { ar: 'SMS', en: 'SMS', color: '#1B365D' },
    email: { ar: 'بريد', en: 'Email', color: '#C4A265' },
};
const statusStyle = { draft: 'bg-gray-100 text-gray-600', scheduled: 'bg-amber-100 text-amber-700', sending: 'bg-blue-100 text-blue-700', sent: 'bg-green-100 text-green-700' };

const showForm = ref(false);
const form = reactive({
    name: '', channel: 'whatsapp', subject: '', body_ar: '', body_en: '',
    ab_enabled: false, subject_b: '', body_ar_b: '', body_en_b: '',
    scheduled_at: '', send_now: false,
    rules: { audience: 'patients', gender: '', age_min: '', age_max: '', created_within_days: '', inactive_days: '', marketing_channel: '', statuses: [], priority: '', module: '' },
});
const audience = ref(null);

function cleanRules() {
    const r = {};
    Object.entries(form.rules).forEach(([k, v]) => {
        if (Array.isArray(v)) { if (v.length) r[k] = v; }
        else if (v !== '' && v !== null) r[k] = v;
    });
    if (r.audience === 'patients') delete r.audience;
    return r;
}

// CRM-3: lead pipeline statuses selectable as a campaign segment
const leadStatuses = [
    { value: 'new', ar: 'جديد', en: 'New' },
    { value: 'contacted', ar: 'تم التواصل', en: 'Contacted' },
    { value: 'qualified', ar: 'مؤهل', en: 'Qualified' },
    { value: 'appointment_booked', ar: 'موعد محجوز', en: 'Appointment booked' },
    { value: 'consultation_done', ar: 'تمت الاستشارة', en: 'Consultation done' },
    { value: 'negotiation', ar: 'تفاوض', en: 'Negotiation' },
    { value: 'dormant', ar: 'خامل', en: 'Dormant' },
];
const leadModules = [
    { value: 'derma', ar: 'الجلدية', en: 'Derma' }, { value: 'dental', ar: 'الأسنان', en: 'Dental' },
    { value: 'pediatric', ar: 'الأطفال', en: 'Pediatric' }, { value: 'obgyn', ar: 'النساء والتوليد', en: 'OB/GYN' },
    { value: 'psychiatry', ar: 'الطب النفسي', en: 'Psychiatry' }, { value: 'neurology', ar: 'المخ والأعصاب', en: 'Neurology' },
    { value: 'physiotherapy', ar: 'العلاج الطبيعي', en: 'Physiotherapy' },
];
let previewTimer = null;
watch(() => JSON.stringify(form.rules), () => {
    clearTimeout(previewTimer);
    previewTimer = setTimeout(() => previewAudience(), 400);
});

// Direct fetch for the preview JSON endpoint (avoids an Inertia visit).
async function previewAudience() {
    const res = await fetch('/admin/notification-campaigns/preview', {
        method: 'POST', credentials: 'same-origin',
        headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': page.props.csrf_token || document.querySelector('meta[name=csrf-token]')?.content || '' },
        body: JSON.stringify({ rules: cleanRules() }),
    });
    if (res.ok) audience.value = (await res.json()).count;
}

function submit(sendNow) {
    form.send_now = sendNow;
    router.post('/admin/notification-campaigns', { ...form, rules: cleanRules() }, {
        preserveScroll: true,
        onSuccess: () => { showForm.value = false; resetForm(); },
    });
}
function resetForm() {
    Object.assign(form, { name: '', channel: 'whatsapp', subject: '', body_ar: '', body_en: '',
        ab_enabled: false, subject_b: '', body_ar_b: '', body_en_b: '', scheduled_at: '', send_now: false,
        rules: { audience: 'patients', gender: '', age_min: '', age_max: '', created_within_days: '', inactive_days: '', marketing_channel: '', statuses: [], priority: '', module: '' } });
    audience.value = null;
}
function sendCampaign(c) {
    confirm(t('إرسال هذه الحملة الآن؟', 'Send this campaign now?'), () => router.post(`/admin/notification-campaigns/${c.id}/send`, {}, { preserveScroll: true }));
}
function deleteCampaign(c) {
    confirm(t('حذف الحملة؟', 'Delete campaign?'), () => router.post(`/admin/notification-campaigns/${c.id}/delete`, {}, { preserveScroll: true }));
}
const fmt = (iso) => iso ? new Date(iso).toLocaleString(isRtl.value ? 'ar-EG' : 'en-GB') : '—';
</script>

<template>
    <AdminLayout :title="t('الحملات', 'Campaigns')">
        <div class="max-w-6xl mx-auto p-4 md:p-6 space-y-5" :dir="isRtl ? 'rtl' : 'ltr'">
            <div class="flex items-center justify-between">
                <h1 class="text-xl font-bold text-gray-900">{{ t('حملات الإشعارات', 'Notification Campaigns') }}</h1>
                <div class="flex gap-2">
                    <Link href="/admin/notifications-hub" class="text-sm font-semibold px-4 py-2 rounded-lg border border-gray-200 text-gray-600 hover:bg-gray-50">{{ t('المركز', 'Hub') }}</Link>
                    <button v-if="canSend" @click="showForm = !showForm; if (showForm) previewAudience()" class="px-4 py-2 rounded-lg text-white text-sm font-semibold" style="background:#1B365D">+ {{ t('حملة جديدة', 'New Campaign') }}</button>
                </div>
            </div>

            <!-- Builder -->
            <div v-if="showForm" class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 space-y-4">
                <div class="grid sm:grid-cols-2 gap-3">
                    <label class="block"><span class="text-xs text-gray-500">{{ t('اسم الحملة', 'Campaign name') }}</span>
                        <input v-model="form.name" class="mt-1 w-full rounded-lg border-gray-200 text-sm" /></label>
                    <label class="block"><span class="text-xs text-gray-500">{{ t('القناة', 'Channel') }}</span>
                        <select v-model="form.channel" class="mt-1 w-full rounded-lg border-gray-200 text-sm">
                            <option v-for="(m, k) in channelMeta" :key="k" :value="k">{{ t(m.ar, m.en) }}</option>
                        </select></label>
                </div>
                <input v-if="form.channel === 'email'" v-model="form.subject" :placeholder="t('الموضوع', 'Subject')" class="w-full rounded-lg border-gray-200 text-sm" />
                <label class="block"><span class="text-xs text-gray-500">{{ t('النص (عربي)', 'Body (Arabic)') }}</span>
                    <textarea v-model="form.body_ar" rows="3" class="mt-1 w-full rounded-lg border-gray-200 text-sm"></textarea></label>
                <AiAssist feature="campaign_copy" label-ar="صياغة نص الحملة (عربي)" label-en="Draft campaign copy (AR)"
                    :build-vars="() => ({ product: form.name, channel: form.channel, goal: form.name })"
                    @insert="(txt) => form.body_ar = txt" />
                <label class="block"><span class="text-xs text-gray-500">{{ t('النص (إنجليزي)', 'Body (English)') }}</span>
                    <textarea v-model="form.body_en" rows="2" class="mt-1 w-full rounded-lg border-gray-200 text-sm"></textarea></label>
                <AiAssist feature="campaign_copy" label-ar="صياغة نص الحملة (إنجليزي)" label-en="Draft campaign copy (EN)"
                    :build-vars="() => ({ product: form.name, channel: form.channel, goal: form.name })"
                    @insert="(txt) => form.body_en = txt" />

                <!-- A/B -->
                <label class="inline-flex items-center gap-2 text-sm"><input type="checkbox" v-model="form.ab_enabled" class="rounded" /> {{ t('اختبار A/B (نسخة بديلة)', 'A/B test (variant B)') }}</label>
                <div v-if="form.ab_enabled" class="rounded-xl border border-dashed border-[#C4A265]/40 bg-[#C4A265]/5 p-4 space-y-2">
                    <p class="text-xs font-semibold text-[#C4A265]">{{ t('النسخة B (تُرسل لنصف الجمهور)', 'Variant B (sent to half the audience)') }}</p>
                    <input v-if="form.channel === 'email'" v-model="form.subject_b" :placeholder="t('موضوع B', 'Subject B')" class="w-full rounded-lg border-gray-200 text-sm" />
                    <textarea v-model="form.body_ar_b" rows="2" :placeholder="t('نص B بالعربية', 'Body B (Arabic)')" class="w-full rounded-lg border-gray-200 text-sm"></textarea>
                    <textarea v-model="form.body_en_b" rows="2" :placeholder="t('نص B بالإنجليزية', 'Body B (English)')" class="w-full rounded-lg border-gray-200 text-sm"></textarea>
                </div>

                <!-- Audience rules -->
                <div class="rounded-xl border border-dashed border-gray-200 p-4">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="font-bold text-gray-800 text-sm">{{ t('الجمهور المستهدف', 'Target audience') }}</h3>
                        <button @click="previewAudience" class="text-xs font-semibold text-[#1B365D] hover:underline">{{ t('تحديث العدد', 'Refresh count') }}</button>
                    </div>
                    <!-- CRM-3: audience type — patients (default) or CRM leads -->
                    <div class="flex gap-2 mb-3">
                        <button type="button" @click="form.rules.audience = 'patients'"
                            class="px-3 py-1.5 rounded-lg text-xs font-semibold border transition-colors"
                            :class="form.rules.audience !== 'leads' ? 'bg-[#1B365D] text-white border-[#1B365D]' : 'bg-white text-gray-600 border-gray-200 hover:bg-gray-50'">
                            {{ t('المرضى', 'Patients') }}
                        </button>
                        <button type="button" @click="form.rules.audience = 'leads'"
                            class="px-3 py-1.5 rounded-lg text-xs font-semibold border transition-colors"
                            :class="form.rules.audience === 'leads' ? 'bg-[#C4A265] text-white border-[#C4A265]' : 'bg-white text-gray-600 border-gray-200 hover:bg-gray-50'">
                            {{ t('العملاء المحتملون (CRM)', 'Leads (CRM)') }}
                        </button>
                    </div>

                    <div v-if="form.rules.audience === 'leads'" class="space-y-3">
                        <div class="flex flex-wrap gap-1.5">
                            <button v-for="st in leadStatuses" :key="st.value" type="button"
                                @click="form.rules.statuses.includes(st.value) ? form.rules.statuses.splice(form.rules.statuses.indexOf(st.value), 1) : form.rules.statuses.push(st.value)"
                                class="px-2.5 py-1 rounded-full text-[11px] font-semibold border transition-colors"
                                :class="form.rules.statuses.includes(st.value) ? 'bg-[#C4A265]/15 text-[#C4A265] border-[#C4A265]/40' : 'bg-white text-gray-500 border-gray-200 hover:bg-gray-50'">
                                {{ t(st.ar, st.en) }}
                            </button>
                        </div>
                        <div class="grid sm:grid-cols-3 gap-3">
                            <select v-model="form.rules.priority" class="rounded-lg border-gray-200 text-sm"><option value="">{{ t('كل الأولويات', 'Any priority') }}</option><option value="1">{{ t('ساخن', 'Hot') }}</option><option value="2">{{ t('دافئ', 'Warm') }}</option><option value="3">{{ t('بارد', 'Cold') }}</option></select>
                            <select v-model="form.rules.module" class="rounded-lg border-gray-200 text-sm"><option value="">{{ t('كل التخصصات', 'Any module') }}</option><option v-for="m in leadModules" :key="m.value" :value="m.value">{{ t(m.ar, m.en) }}</option></select>
                            <input type="number" v-model="form.rules.inactive_days" :placeholder="t('بلا تواصل منذ (يوم)', 'No contact for (days)')" class="rounded-lg border-gray-200 text-sm" />
                        </div>
                        <p class="text-[11px] text-gray-400">{{ t('ترسل لقائمة CRM داخل خط الأنابيب فقط (لا تشمل المحوّلين أو المفقودين) وتسجل على ملف كل عميل.', 'Sends to in-pipeline CRM leads only (excludes converted/lost) and is logged on each lead timeline.') }}</p>
                    </div>

                    <div v-else class="grid sm:grid-cols-3 gap-3">
                        <select v-model="form.rules.gender" class="rounded-lg border-gray-200 text-sm"><option value="">{{ t('كل الأجناس', 'Any gender') }}</option><option value="male">{{ t('ذكر', 'Male') }}</option><option value="female">{{ t('أنثى', 'Female') }}</option></select>
                        <input type="number" v-model="form.rules.age_min" :placeholder="t('أقل عمر', 'Min age')" class="rounded-lg border-gray-200 text-sm" />
                        <input type="number" v-model="form.rules.age_max" :placeholder="t('أكبر عمر', 'Max age')" class="rounded-lg border-gray-200 text-sm" />
                        <input type="number" v-model="form.rules.created_within_days" :placeholder="t('مرضى جدد خلال (يوم)', 'New within (days)')" class="rounded-lg border-gray-200 text-sm" />
                        <input type="number" v-model="form.rules.inactive_days" :placeholder="t('لم يزر منذ (يوم)', 'Inactive for (days)')" class="rounded-lg border-gray-200 text-sm" />
                        <select v-model="form.rules.marketing_channel" class="rounded-lg border-gray-200 text-sm"><option value="">{{ t('بلا شرط موافقة', 'Any consent') }}</option><option value="whatsapp">{{ t('موافقة واتساب', 'WhatsApp opt-in') }}</option><option value="sms">{{ t('موافقة SMS', 'SMS opt-in') }}</option><option value="email">{{ t('موافقة بريد', 'Email opt-in') }}</option></select>
                    </div>
                    <p v-if="audience !== null" class="mt-3 text-sm font-semibold text-[#1B365D]">{{ t('الجمهور المقدّر:', 'Estimated audience:') }} {{ audience }}</p>
                </div>

                <label class="block"><span class="text-xs text-gray-500">{{ t('جدولة (اختياري)', 'Schedule (optional)') }}</span>
                    <input type="datetime-local" v-model="form.scheduled_at" class="mt-1 w-full rounded-lg border-gray-200 text-sm" /></label>

                <div class="flex justify-end gap-2">
                    <button @click="showForm = false" class="px-4 py-2 rounded-lg border border-gray-200 text-gray-600 text-sm">{{ t('إلغاء', 'Cancel') }}</button>
                    <button @click="submit(false)" class="px-4 py-2 rounded-lg border border-[#1B365D] text-[#1B365D] text-sm font-semibold">{{ form.scheduled_at ? t('جدولة', 'Schedule') : t('حفظ كمسودة', 'Save draft') }}</button>
                    <button v-if="!form.scheduled_at" @click="submit(true)" :disabled="!form.name || !form.body_ar" class="px-4 py-2 rounded-lg text-white text-sm font-semibold disabled:opacity-50" style="background:#1B365D">{{ t('إرسال الآن', 'Send now') }}</button>
                </div>
            </div>

            <!-- List -->
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                <table class="w-full text-sm">
                    <thead><tr class="text-gray-400 text-xs border-b border-gray-100 bg-gray-50/50">
                        <th class="text-start px-4 py-3">{{ t('الاسم', 'Name') }}</th>
                        <th class="px-4 py-3">{{ t('القناة', 'Channel') }}</th>
                        <th class="px-4 py-3">{{ t('الحالة', 'Status') }}</th>
                        <th class="px-4 py-3">{{ t('أُرسلت', 'Sent') }}</th>
                        <th class="px-4 py-3">{{ t('موعد', 'When') }}</th>
                        <th class="px-4 py-3"></th>
                    </tr></thead>
                    <tbody>
                        <tr v-for="c in campaigns" :key="c.id" class="border-b border-gray-50">
                            <td class="px-4 py-3">
                                <span class="font-medium text-gray-800">{{ c.name }}</span>
                                <span v-if="c.ab_enabled" class="ms-2 text-[10px] font-bold px-1.5 py-0.5 rounded bg-[#C4A265]/15 text-[#C4A265]">A/B</span>
                                <div v-if="c.ab_results" class="text-[11px] text-gray-400 mt-0.5">
                                    A: {{ c.ab_results.A.read_rate ?? '—' }}% · B: {{ c.ab_results.B.read_rate ?? '—' }}%
                                    <span v-if="c.ab_results.winner" class="text-green-600 font-semibold">· {{ t('الفائز', 'winner') }} {{ c.ab_results.winner }}</span>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-center"><span class="text-xs font-bold px-2 py-0.5 rounded-full text-white" :style="{ background: channelMeta[c.channel]?.color }">{{ t(channelMeta[c.channel]?.ar, channelMeta[c.channel]?.en) }}</span></td>
                            <td class="px-4 py-3 text-center"><span class="text-xs font-semibold px-2 py-0.5 rounded-full" :class="statusStyle[c.status]">{{ c.status }}</span></td>
                            <td class="px-4 py-3 text-center text-gray-600">{{ c.sent_count }} / {{ c.audience_count }}</td>
                            <td class="px-4 py-3 text-center text-xs text-gray-400">{{ fmt(c.scheduled_at || c.sent_at || c.created_at) }}</td>
                            <td class="px-4 py-3 text-end">
                                <button v-if="canSend && c.status !== 'sent'" @click="sendCampaign(c)" class="text-xs font-semibold text-[#1B365D] hover:underline me-2">{{ t('إرسال', 'Send') }}</button>
                                <button @click="deleteCampaign(c)" class="text-xs text-red-400 hover:underline">{{ t('حذف', 'Delete') }}</button>
                            </td>
                        </tr>
                        <tr v-if="!campaigns.length"><td colspan="6" class="text-center text-gray-400 py-10">{{ t('لا توجد حملات', 'No campaigns yet') }}</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AdminLayout>
</template>
