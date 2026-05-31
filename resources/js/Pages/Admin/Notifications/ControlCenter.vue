<script setup>
import { ref, reactive, computed } from 'vue';
import { usePage, router, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { usePermissions } from '@/Composables/usePermissions.js';

const props = defineProps({
    channelKeys: Array,
    channels: Array,
    events: Array,
    routes: Object,
    templates: Array,
    providerOptions: Object,
    smsSettings: Object,
    globalSettings: Object,
    stats: Object,
});

const page = usePage();
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');
const t = (ar, en) => (isRtl.value ? ar : en);
const { can } = usePermissions();
const canEdit = can('notifications.update');

const activeTab = ref('overview');
const tabs = [
    { key: 'overview',  ar: 'نظرة عامة',  en: 'Overview' },
    { key: 'channels',  ar: 'القنوات',    en: 'Channels' },
    { key: 'routing',   ar: 'التوجيه',    en: 'Routing' },
    { key: 'events',    ar: 'الأحداث',    en: 'Events' },
    { key: 'templates', ar: 'القوالب',    en: 'Templates' },
    { key: 'settings',  ar: 'الإعدادات',  en: 'Settings' },
];

const channelMeta = {
    whatsapp: { ar: 'واتساب', en: 'WhatsApp', color: '#25D366', icon: 'M12 2a10 10 0 00-8.7 15l-1.3 4 4.1-1.3A10 10 0 1012 2z' },
    sms:      { ar: 'رسائل SMS', en: 'SMS', color: '#1B365D', icon: 'M8 10h.01M12 10h.01M16 10h.01M21 12a9 9 0 11-3.8-7.3L21 4l-1 4.5A8.9 8.9 0 0121 12z' },
    email:    { ar: 'البريد', en: 'Email', color: '#C4A265', icon: 'M3 8l9 6 9-6M3 8v8a2 2 0 002 2h14a2 2 0 002-2V8M3 8l2-2h14l2 2' },
    in_app:   { ar: 'داخل النظام', en: 'In-App', color: '#64748B', icon: 'M15 17h5l-1.4-1.4A2 2 0 0118 14.2V11a6 6 0 10-12 0v3.2c0 .5-.2 1-.6 1.4L4 17h5m6 0v1a3 3 0 11-6 0v-1' },
};

const reduce = typeof window !== 'undefined' && window.matchMedia?.('(prefers-reduced-motion: reduce)').matches;

// ── channels editing ────────────────────────────────────
const channelForm = reactive({});
props.channels.forEach((c) => {
    channelForm[c.channel] = {
        enabled: c.enabled, provider: c.provider, from_name: c.from_name,
        daily_cap: c.daily_cap, monthly_cap: c.monthly_cap, config: {}, sms: {},
    };
});
// seed sms sub-form from settings
if (channelForm.sms) {
    Object.assign(channelForm.sms.sms, {
        sms_provider: props.smsSettings.sms_provider,
        sms_sender_name: props.smsSettings.sms_sender_name,
        sms_default_country_code: props.smsSettings.sms_default_country_code,
        sms_smsmisr_sender: props.smsSettings.sms_smsmisr_sender,
        sms_smsmisr_username: props.smsSettings.sms_smsmisr_username,
        sms_smsmisr_environment: props.smsSettings.sms_smsmisr_environment,
        sms_twilio_from_number: props.smsSettings.sms_twilio_from_number,
    });
}
const saving = ref(null);
function saveChannel(key) {
    saving.value = key;
    router.post(`/admin/notifications-hub/channels/${key}`, channelForm[key], {
        preserveScroll: true, onFinish: () => (saving.value = null),
    });
}

// ── routing matrix ──────────────────────────────────────
function routeState(eventKey, channel) {
    return props.routes?.[eventKey]?.[channel] || { enabled: false, priority: 0 };
}
function toggleRoute(eventKey, channel) {
    const cur = routeState(eventKey, channel);
    router.post('/admin/notifications-hub/routes', {
        event_key: eventKey, channel, enabled: !cur.enabled, priority: cur.priority,
    }, { preserveScroll: true, preserveState: false });
}

// ── events ──────────────────────────────────────────────
function toggleEvent(ev) {
    router.post(`/admin/notifications-hub/events/${ev.key}`, { is_active: !ev.is_active }, {
        preserveScroll: true, preserveState: false,
    });
}
const eventsByCategory = computed(() => {
    const g = {};
    (props.events || []).forEach((e) => { (g[e.category] ||= []).push(e); });
    return g;
});

// ── templates ───────────────────────────────────────────
const tplModal = ref(false);
const tplForm = reactive({ id: null, event_key: '', channel: 'sms', subject: '', body_ar: '', body_en: '', is_active: true });
function newTemplate() {
    Object.assign(tplForm, { id: null, event_key: props.events?.[0]?.key || '', channel: 'sms', subject: '', body_ar: '', body_en: '', is_active: true });
    tplModal.value = true;
}
function editTemplate(tpl) {
    Object.assign(tplForm, tpl);
    tplModal.value = true;
}
function saveTemplate() {
    const url = tplForm.id ? `/admin/notifications-hub/templates/${tplForm.id}/update` : '/admin/notifications-hub/templates';
    router.post(url, tplForm, { preserveScroll: true, onSuccess: () => (tplModal.value = false) });
}
function deleteTemplate(tpl) {
    if (confirm(t('حذف هذا القالب؟', 'Delete this template?'))) {
        router.post(`/admin/notifications-hub/templates/${tpl.id}/delete`, {}, { preserveScroll: true });
    }
}

// ── settings ────────────────────────────────────────────
const settingsForm = reactive({
    notifications_global_daily_cap: props.globalSettings.notifications_global_daily_cap,
    sms_cost_per_segment: props.globalSettings.sms_cost_per_segment,
    whatsapp_webhook_verify_token: '',
    notifications_quiet_start: props.globalSettings.notifications_quiet_start || '',
    notifications_quiet_end: props.globalSettings.notifications_quiet_end || '',
    notifications_marketing_weekly_cap: props.globalSettings.notifications_marketing_weekly_cap,
    notifications_smart_routing: props.globalSettings.notifications_smart_routing,
    notifications_monthly_cost_cap: props.globalSettings.notifications_monthly_cost_cap,
});
function saveSettings() {
    router.post('/admin/notifications-hub/settings', settingsForm, { preserveScroll: true });
}

const testForm = reactive({ channel: 'sms', to: '' });
function sendTest() {
    router.post('/admin/notifications-hub/test', testForm, { preserveScroll: true });
}

const categoryLabel = (c) => ({ transactional: t('معاملات', 'Transactional'), reminder: t('تذكيرات', 'Reminders'), marketing: t('تسويق', 'Marketing') }[c] || c);
const statusColor = (ch) => (channelMeta[ch]?.color || '#64748B');
</script>

<template>
    <AdminLayout :title="t('مركز الإشعارات الموحد', 'Notifications Hub')">
        <div class="max-w-7xl mx-auto p-4 md:p-6 space-y-6" :dir="isRtl ? 'rtl' : 'ltr'" :class="{ 'nh-anim': !reduce }">
            <!-- Header -->
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 rounded-2xl flex items-center justify-center text-white shadow-lg"
                         style="background:linear-gradient(135deg,#1B365D,#2a4a7a)">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z" /></svg>
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">{{ t('مركز الإشعارات الموحد', 'Notifications Hub') }}</h1>
                        <p class="text-sm text-gray-500">{{ t('تحكم كامل في واتساب و SMS والبريد والإشعارات الداخلية', 'Unified control over WhatsApp, SMS, Email & In-App notifications') }}</p>
                    </div>
                </div>
                <div class="flex items-center gap-2 flex-wrap">
                    <Link href="/admin/notifications-hub/whatsapp-templates" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg border border-gray-200 text-gray-700 text-sm font-semibold hover:bg-gray-50 transition">
                        {{ t('قوالب واتساب', 'WA Templates') }}
                    </Link>
                    <Link href="/admin/notifications-hub/analytics" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg border border-gray-200 text-gray-700 text-sm font-semibold hover:bg-gray-50 transition">
                        {{ t('التحليلات', 'Analytics') }}
                    </Link>
                    <Link href="/admin/notifications-hub/logs" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg text-white text-sm font-semibold shadow-sm hover:opacity-90 transition" style="background:#1B365D">
                        {{ t('سجل الإرسال', 'Delivery Log') }}
                    </Link>
                </div>
            </div>

            <!-- Stats -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 nh-card">
                    <p class="text-xs text-gray-500 font-medium">{{ t('أُرسلت اليوم', 'Sent Today') }}</p>
                    <p class="text-3xl font-bold mt-1" style="color:#1B365D">{{ stats.today_total }}</p>
                </div>
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 nh-card">
                    <p class="text-xs text-gray-500 font-medium">{{ t('في الانتظار', 'Queued') }}</p>
                    <p class="text-3xl font-bold mt-1 text-amber-500">{{ stats.queued }}</p>
                </div>
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 nh-card">
                    <p class="text-xs text-gray-500 font-medium">{{ t('فشلت اليوم', 'Failed Today') }}</p>
                    <p class="text-3xl font-bold mt-1 text-red-500">{{ stats.failed_today }}</p>
                </div>
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 nh-card">
                    <p class="text-xs text-gray-500 font-medium">{{ t('تكلفة الشهر', 'Month Cost') }}</p>
                    <p class="text-3xl font-bold mt-1" style="color:#C4A265">{{ Number(stats.month_cost).toFixed(2) }}</p>
                </div>
            </div>

            <!-- Tabs -->
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                <div class="flex overflow-x-auto border-b border-gray-100">
                    <button v-for="tab in tabs" :key="tab.key" @click="activeTab = tab.key"
                            class="px-5 py-3.5 text-sm font-semibold whitespace-nowrap transition relative"
                            :class="activeTab === tab.key ? 'text-[#1B365D]' : 'text-gray-400 hover:text-gray-600'">
                        {{ t(tab.ar, tab.en) }}
                        <span v-if="activeTab === tab.key" class="absolute bottom-0 inset-x-2 h-0.5 rounded-full" style="background:#C4A265"></span>
                    </button>
                </div>

                <div class="p-5 md:p-6">
                    <!-- OVERVIEW -->
                    <div v-if="activeTab === 'overview'" class="grid sm:grid-cols-2 gap-4">
                        <div v-for="c in channels" :key="c.channel" class="rounded-xl border border-gray-100 p-4 flex items-center gap-4 nh-card">
                            <div class="w-11 h-11 rounded-xl flex items-center justify-center text-white shrink-0" :style="{ background: statusColor(c.channel) }">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="channelMeta[c.channel]?.icon" /></svg>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="font-bold text-gray-900">{{ t(channelMeta[c.channel]?.ar, channelMeta[c.channel]?.en) }}</p>
                                <p class="text-xs text-gray-500">{{ c.provider || '—' }} · {{ stats.today_per_channel?.[c.channel] || 0 }} {{ t('اليوم', 'today') }}</p>
                            </div>
                            <div class="flex flex-col items-end gap-1">
                                <span class="text-xs font-bold px-2 py-0.5 rounded-full" :class="c.enabled ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500'">{{ c.enabled ? t('مُفعّل', 'On') : t('متوقف', 'Off') }}</span>
                                <span class="text-xs font-medium px-2 py-0.5 rounded-full" :class="c.configured ? 'bg-blue-50 text-blue-600' : 'bg-amber-50 text-amber-600'">{{ c.configured ? t('مهيأ', 'Ready') : t('غير مهيأ', 'Setup') }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- CHANNELS -->
                    <div v-else-if="activeTab === 'channels'" class="space-y-5">
                        <div v-for="c in channels" :key="c.channel" class="rounded-xl border border-gray-100 p-5">
                            <div class="flex items-center justify-between mb-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-lg flex items-center justify-center text-white" :style="{ background: statusColor(c.channel) }">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="channelMeta[c.channel]?.icon" /></svg>
                                    </div>
                                    <h3 class="font-bold text-gray-900">{{ t(channelMeta[c.channel]?.ar, channelMeta[c.channel]?.en) }}</h3>
                                </div>
                                <label class="inline-flex items-center cursor-pointer">
                                    <input type="checkbox" v-model="channelForm[c.channel].enabled" :disabled="!canEdit" class="sr-only peer">
                                    <div class="w-11 h-6 bg-gray-200 rounded-full peer peer-checked:bg-[#1B365D] peer-checked:after:translate-x-5 after:content-[''] after:absolute after:top-0.5 after:start-0.5 after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all relative"></div>
                                </label>
                            </div>

                            <div v-if="c.channel !== 'in_app'" class="grid sm:grid-cols-2 gap-3">
                                <label class="block">
                                    <span class="text-xs text-gray-500">{{ t('المزود', 'Provider') }}</span>
                                    <select v-model="channelForm[c.channel].provider" :disabled="!canEdit" class="mt-1 w-full rounded-lg border-gray-200 text-sm">
                                        <option v-for="p in (providerOptions[c.channel] || [])" :key="p" :value="p">{{ p }}</option>
                                    </select>
                                </label>
                                <label class="block">
                                    <span class="text-xs text-gray-500">{{ t('اسم المرسِل', 'Sender Name') }}</span>
                                    <input v-model="channelForm[c.channel].from_name" :disabled="!canEdit" class="mt-1 w-full rounded-lg border-gray-200 text-sm" />
                                </label>
                                <label class="block">
                                    <span class="text-xs text-gray-500">{{ t('الحد اليومي', 'Daily Cap') }}</span>
                                    <input type="number" min="0" v-model="channelForm[c.channel].daily_cap" :disabled="!canEdit" class="mt-1 w-full rounded-lg border-gray-200 text-sm" :placeholder="t('بلا حد', 'No limit')" />
                                </label>
                                <label class="block">
                                    <span class="text-xs text-gray-500">{{ t('الحد الشهري', 'Monthly Cap') }}</span>
                                    <input type="number" min="0" v-model="channelForm[c.channel].monthly_cap" :disabled="!canEdit" class="mt-1 w-full rounded-lg border-gray-200 text-sm" :placeholder="t('بلا حد', 'No limit')" />
                                </label>
                            </div>

                            <!-- email credentials -->
                            <div v-if="c.channel === 'email'" class="grid sm:grid-cols-2 gap-3 mt-3 pt-3 border-t border-dashed border-gray-100">
                                <input v-model="channelForm.email.config.host" :disabled="!canEdit" class="rounded-lg border-gray-200 text-sm" :placeholder="t('SMTP Host', 'SMTP Host')" />
                                <input v-model="channelForm.email.config.port" :disabled="!canEdit" class="rounded-lg border-gray-200 text-sm" placeholder="587" />
                                <input v-model="channelForm.email.config.username" :disabled="!canEdit" class="rounded-lg border-gray-200 text-sm" :placeholder="t('اسم المستخدم', 'Username')" />
                                <input v-model="channelForm.email.config.password" type="password" :disabled="!canEdit" class="rounded-lg border-gray-200 text-sm" :placeholder="c.config_presence?.password ? '••••••• (' + t('محفوظ', 'saved') + ')' : t('كلمة المرور', 'Password')" />
                                <input v-model="channelForm.email.config.from_address" :disabled="!canEdit" class="rounded-lg border-gray-200 text-sm" :placeholder="t('البريد المرسِل', 'From Address')" />
                                <select v-model="channelForm.email.config.encryption" :disabled="!canEdit" class="rounded-lg border-gray-200 text-sm">
                                    <option value="tls">TLS</option><option value="ssl">SSL</option>
                                </select>
                            </div>

                            <!-- whatsapp credentials -->
                            <div v-if="c.channel === 'whatsapp'" class="grid sm:grid-cols-2 gap-3 mt-3 pt-3 border-t border-dashed border-gray-100">
                                <template v-if="channelForm.whatsapp.provider !== 'bridge'">
                                    <input v-model="channelForm.whatsapp.config.phone_number_id" :disabled="!canEdit" class="rounded-lg border-gray-200 text-sm" placeholder="Phone Number ID" />
                                    <input v-model="channelForm.whatsapp.config.access_token" type="password" :disabled="!canEdit" class="rounded-lg border-gray-200 text-sm" :placeholder="c.config_presence?.access_token ? '••••••• (' + t('محفوظ', 'saved') + ')' : 'Access Token'" />
                                </template>
                                <template v-else>
                                    <input v-model="channelForm.whatsapp.config.base_url" :disabled="!canEdit" class="rounded-lg border-gray-200 text-sm" placeholder="Bridge Base URL" />
                                    <input v-model="channelForm.whatsapp.config.api_key" type="password" :disabled="!canEdit" class="rounded-lg border-gray-200 text-sm" :placeholder="c.config_presence?.api_key ? '••••••• (' + t('محفوظ', 'saved') + ')' : 'API Key'" />
                                    <input v-model="channelForm.whatsapp.config.session" :disabled="!canEdit" class="rounded-lg border-gray-200 text-sm" placeholder="Session" />
                                </template>
                            </div>

                            <!-- sms provider settings -->
                            <div v-if="c.channel === 'sms'" class="grid sm:grid-cols-2 gap-3 mt-3 pt-3 border-t border-dashed border-gray-100">
                                <select v-model="channelForm.sms.sms.sms_provider" :disabled="!canEdit" class="rounded-lg border-gray-200 text-sm">
                                    <option v-for="p in providerOptions.sms" :key="p" :value="p">{{ p }}</option>
                                </select>
                                <input v-model="channelForm.sms.sms.sms_default_country_code" :disabled="!canEdit" class="rounded-lg border-gray-200 text-sm" placeholder="20" />
                                <template v-if="channelForm.sms.sms.sms_provider === 'smsmisr'">
                                    <input v-model="channelForm.sms.sms.sms_smsmisr_sender" :disabled="!canEdit" class="rounded-lg border-gray-200 text-sm" :placeholder="t('اسم المرسِل', 'Sender ID')" />
                                    <input v-model="channelForm.sms.sms.sms_smsmisr_username" :disabled="!canEdit" class="rounded-lg border-gray-200 text-sm" :placeholder="t('اسم المستخدم', 'Username')" />
                                    <input v-model="channelForm.sms.sms.sms_smsmisr_password" type="password" :disabled="!canEdit" class="rounded-lg border-gray-200 text-sm" :placeholder="smsSettings.has_smsmisr_password ? '••••••• (' + t('محفوظ', 'saved') + ')' : t('كلمة المرور', 'Password')" />
                                    <select v-model="channelForm.sms.sms.sms_smsmisr_environment" :disabled="!canEdit" class="rounded-lg border-gray-200 text-sm">
                                        <option value="1">{{ t('بيئة حية', 'Live') }}</option><option value="2">{{ t('بيئة اختبار', 'Test') }}</option>
                                    </select>
                                </template>
                                <template v-else-if="channelForm.sms.sms.sms_provider === 'twilio'">
                                    <input v-model="channelForm.sms.sms.sms_twilio_account_sid" :disabled="!canEdit" class="rounded-lg border-gray-200 text-sm" placeholder="Account SID" />
                                    <input v-model="channelForm.sms.sms.sms_twilio_auth_token" type="password" :disabled="!canEdit" class="rounded-lg border-gray-200 text-sm" :placeholder="smsSettings.has_twilio_token ? '••••••• (' + t('محفوظ', 'saved') + ')' : 'Auth Token'" />
                                    <input v-model="channelForm.sms.sms.sms_twilio_from_number" :disabled="!canEdit" class="rounded-lg border-gray-200 text-sm" placeholder="From (+20...)" />
                                </template>
                            </div>

                            <div v-if="canEdit" class="mt-4 flex justify-end">
                                <button @click="saveChannel(c.channel)" :disabled="saving === c.channel" class="px-4 py-2 rounded-lg text-white text-sm font-semibold disabled:opacity-50 transition" style="background:#1B365D">
                                    {{ saving === c.channel ? t('جارٍ الحفظ…', 'Saving…') : t('حفظ', 'Save') }}
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- ROUTING -->
                    <div v-else-if="activeTab === 'routing'" class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="text-gray-400 text-xs border-b border-gray-100">
                                    <th class="text-start py-2 pe-3">{{ t('الحدث', 'Event') }}</th>
                                    <th v-for="ch in channelKeys" :key="ch" class="px-3 py-2">{{ t(channelMeta[ch]?.ar, channelMeta[ch]?.en) }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="ev in events" :key="ev.key" class="border-b border-gray-50 hover:bg-gray-50/50">
                                    <td class="py-2.5 pe-3">
                                        <p class="font-medium text-gray-800">{{ t(ev.label_ar, ev.label_en) }}</p>
                                        <p class="text-xs text-gray-400 font-mono">{{ ev.key }}</p>
                                    </td>
                                    <td v-for="ch in channelKeys" :key="ch" class="text-center px-3">
                                        <button @click="canEdit && toggleRoute(ev.key, ch)" :disabled="!canEdit"
                                                class="w-7 h-7 rounded-lg inline-flex items-center justify-center transition"
                                                :class="routeState(ev.key, ch).enabled ? 'text-white' : 'bg-gray-100 text-gray-300'"
                                                :style="routeState(ev.key, ch).enabled ? { background: statusColor(ch) } : {}">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" /></svg>
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- EVENTS -->
                    <div v-else-if="activeTab === 'events'" class="space-y-5">
                        <div v-for="(list, cat) in eventsByCategory" :key="cat">
                            <h3 class="text-xs font-bold uppercase tracking-wide text-gray-400 mb-2">{{ categoryLabel(cat) }}</h3>
                            <div class="grid sm:grid-cols-2 gap-2">
                                <div v-for="ev in list" :key="ev.key" class="flex items-center justify-between rounded-lg border border-gray-100 px-4 py-2.5">
                                    <div>
                                        <p class="font-medium text-gray-800 text-sm">{{ t(ev.label_ar, ev.label_en) }}</p>
                                        <p class="text-xs text-gray-400 font-mono">{{ ev.key }}</p>
                                    </div>
                                    <label class="inline-flex items-center cursor-pointer">
                                        <input type="checkbox" :checked="ev.is_active" @change="canEdit && toggleEvent(ev)" :disabled="!canEdit" class="sr-only peer">
                                        <div class="w-9 h-5 bg-gray-200 rounded-full peer peer-checked:bg-[#C4A265] peer-checked:after:translate-x-4 after:content-[''] after:absolute after:top-0.5 after:start-0.5 after:bg-white after:rounded-full after:h-4 after:w-4 after:transition-all relative"></div>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- TEMPLATES -->
                    <div v-else-if="activeTab === 'templates'" class="space-y-3">
                        <div class="flex justify-end">
                            <button v-if="can('notifications.create')" @click="newTemplate" class="px-4 py-2 rounded-lg text-white text-sm font-semibold transition" style="background:#1B365D">+ {{ t('قالب جديد', 'New Template') }}</button>
                        </div>
                        <div v-if="!templates.length" class="text-center text-gray-400 py-8 text-sm">{{ t('لا توجد قوالب بعد', 'No templates yet') }}</div>
                        <div v-for="tpl in templates" :key="tpl.id" class="rounded-lg border border-gray-100 p-4 flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <span class="text-xs font-mono text-gray-500">{{ tpl.event_key }}</span>
                                    <span class="text-xs font-bold px-2 py-0.5 rounded-full text-white" :style="{ background: statusColor(tpl.channel) }">{{ tpl.channel }}</span>
                                    <span v-if="!tpl.is_active" class="text-xs bg-gray-100 text-gray-400 px-2 py-0.5 rounded-full">{{ t('متوقف', 'Inactive') }}</span>
                                </div>
                                <p class="text-sm text-gray-700 mt-1 line-clamp-2">{{ tpl.body_ar || tpl.body_en }}</p>
                            </div>
                            <div v-if="canEdit" class="flex gap-1 shrink-0">
                                <button @click="editTemplate(tpl)" class="p-2 rounded-lg hover:bg-gray-100 text-gray-500"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg></button>
                                <button @click="deleteTemplate(tpl)" class="p-2 rounded-lg hover:bg-red-50 text-red-400"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg></button>
                            </div>
                        </div>
                    </div>

                    <!-- SETTINGS -->
                    <div v-else-if="activeTab === 'settings'" class="grid md:grid-cols-2 gap-6">
                        <div class="space-y-4">
                            <h3 class="font-bold text-gray-900">{{ t('الحدود والتكلفة', 'Caps & Cost') }}</h3>
                            <label class="block">
                                <span class="text-xs text-gray-500">{{ t('الحد اليومي الإجمالي (كل القنوات)', 'Global Daily Cap (all channels)') }}</span>
                                <input type="number" min="0" v-model="settingsForm.notifications_global_daily_cap" :disabled="!canEdit" class="mt-1 w-full rounded-lg border-gray-200 text-sm" :placeholder="t('0 = بلا حد', '0 = no limit')" />
                            </label>
                            <label class="block">
                                <span class="text-xs text-gray-500">{{ t('تكلفة الرسالة (لكل جزء)', 'SMS cost per segment') }}</span>
                                <input type="number" step="0.0001" min="0" v-model="settingsForm.sms_cost_per_segment" :disabled="!canEdit" class="mt-1 w-full rounded-lg border-gray-200 text-sm" />
                            </label>
                            <label class="block">
                                <span class="text-xs text-gray-500">{{ t('سقف الرسائل التسويقية / أسبوع', 'Marketing cap / week (per recipient)') }}</span>
                                <input type="number" min="0" v-model="settingsForm.notifications_marketing_weekly_cap" :disabled="!canEdit" class="mt-1 w-full rounded-lg border-gray-200 text-sm" :placeholder="t('0 = بلا حد', '0 = no limit')" />
                            </label>
                            <label class="block">
                                <span class="text-xs text-gray-500">{{ t('سقف التكلفة الشهري (تنبيه)', 'Monthly cost cap (alert)') }}</span>
                                <input type="number" step="0.01" min="0" v-model="settingsForm.notifications_monthly_cost_cap" :disabled="!canEdit" class="mt-1 w-full rounded-lg border-gray-200 text-sm" :placeholder="t('0 = بلا تنبيه', '0 = no alert')" />
                            </label>
                            <div class="grid grid-cols-2 gap-3">
                                <label class="block">
                                    <span class="text-xs text-gray-500">{{ t('بداية أوقات الهدوء', 'Quiet hours start') }}</span>
                                    <input type="time" v-model="settingsForm.notifications_quiet_start" :disabled="!canEdit" class="mt-1 w-full rounded-lg border-gray-200 text-sm" />
                                </label>
                                <label class="block">
                                    <span class="text-xs text-gray-500">{{ t('نهاية أوقات الهدوء', 'Quiet hours end') }}</span>
                                    <input type="time" v-model="settingsForm.notifications_quiet_end" :disabled="!canEdit" class="mt-1 w-full rounded-lg border-gray-200 text-sm" />
                                </label>
                            </div>
                            <p class="text-[11px] text-gray-400">{{ t('خلال أوقات الهدوء تُمنع رسائل التذكير والتسويق فقط (المعاملات تُرسَل دائماً).', 'During quiet hours only reminder/marketing messages are held — transactional always sends.') }}</p>
                            <label class="flex items-center gap-2 pt-1">
                                <input type="checkbox" v-model="settingsForm.notifications_smart_routing" :disabled="!canEdit" class="rounded text-[#1B365D]" />
                                <span class="text-sm text-gray-700">{{ t('التوجيه الذكي (ترتيب القنوات حسب تفاعل كل مريض)', 'Smart routing (order channels by each patient\'s engagement)') }}</span>
                            </label>
                            <label class="block">
                                <span class="text-xs text-gray-500">{{ t('رمز تحقق Webhook لواتساب', 'WhatsApp webhook verify token') }}</span>
                                <input v-model="settingsForm.whatsapp_webhook_verify_token" :disabled="!canEdit" class="mt-1 w-full rounded-lg border-gray-200 text-sm" :placeholder="globalSettings.has_whatsapp_verify_token ? '••••••• (' + t('محفوظ', 'saved') + ')' : ''" />
                            </label>
                            <button v-if="canEdit" @click="saveSettings" class="px-4 py-2 rounded-lg text-white text-sm font-semibold transition" style="background:#1B365D">{{ t('حفظ الإعدادات', 'Save Settings') }}</button>
                        </div>
                        <div class="space-y-4">
                            <h3 class="font-bold text-gray-900">{{ t('إرسال رسالة اختبار', 'Send Test Message') }}</h3>
                            <label class="block">
                                <span class="text-xs text-gray-500">{{ t('القناة', 'Channel') }}</span>
                                <select v-model="testForm.channel" class="mt-1 w-full rounded-lg border-gray-200 text-sm">
                                    <option v-for="ch in channelKeys" :key="ch" :value="ch">{{ t(channelMeta[ch]?.ar, channelMeta[ch]?.en) }}</option>
                                </select>
                            </label>
                            <label class="block">
                                <span class="text-xs text-gray-500">{{ t('إلى (هاتف/بريد)', 'To (phone/email)') }}</span>
                                <input v-model="testForm.to" class="mt-1 w-full rounded-lg border-gray-200 text-sm" />
                            </label>
                            <button v-if="can('notifications.send')" @click="sendTest" :disabled="!testForm.to" class="px-4 py-2 rounded-lg text-white text-sm font-semibold disabled:opacity-50 transition" style="background:#C4A265">{{ t('إرسال', 'Send Test') }}</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Template modal -->
        <Teleport to="body">
            <div v-if="tplModal" class="fixed inset-0 z-50 flex items-center justify-center p-4" :dir="isRtl ? 'rtl' : 'ltr'">
                <div class="absolute inset-0 bg-black/40" @click="tplModal = false"></div>
                <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-lg p-6 space-y-3 max-h-[90vh] overflow-y-auto">
                    <h3 class="font-bold text-lg text-gray-900">{{ tplForm.id ? t('تعديل قالب', 'Edit Template') : t('قالب جديد', 'New Template') }}</h3>
                    <div class="grid grid-cols-2 gap-3">
                        <label class="block"><span class="text-xs text-gray-500">{{ t('الحدث', 'Event') }}</span>
                            <select v-model="tplForm.event_key" class="mt-1 w-full rounded-lg border-gray-200 text-sm">
                                <option v-for="ev in events" :key="ev.key" :value="ev.key">{{ t(ev.label_ar, ev.label_en) }}</option>
                            </select>
                        </label>
                        <label class="block"><span class="text-xs text-gray-500">{{ t('القناة', 'Channel') }}</span>
                            <select v-model="tplForm.channel" class="mt-1 w-full rounded-lg border-gray-200 text-sm">
                                <option v-for="ch in channelKeys" :key="ch" :value="ch">{{ t(channelMeta[ch]?.ar, channelMeta[ch]?.en) }}</option>
                            </select>
                        </label>
                    </div>
                    <label class="block"><span class="text-xs text-gray-500">{{ t('الموضوع (للبريد)', 'Subject (email)') }}</span>
                        <input v-model="tplForm.subject" class="mt-1 w-full rounded-lg border-gray-200 text-sm" /></label>
                    <label class="block"><span class="text-xs text-gray-500">{{ t('النص بالعربية', 'Body (Arabic)') }}</span>
                        <textarea v-model="tplForm.body_ar" rows="3" class="mt-1 w-full rounded-lg border-gray-200 text-sm" placeholder="{{name}}"></textarea></label>
                    <label class="block"><span class="text-xs text-gray-500">{{ t('النص بالإنجليزية', 'Body (English)') }}</span>
                        <textarea v-model="tplForm.body_en" rows="3" class="mt-1 w-full rounded-lg border-gray-200 text-sm"></textarea></label>
                    <label class="inline-flex items-center gap-2 text-sm"><input type="checkbox" v-model="tplForm.is_active" class="rounded" /> {{ t('مُفعّل', 'Active') }}</label>
                    <div class="flex justify-end gap-2 pt-2">
                        <button @click="tplModal = false" class="px-4 py-2 rounded-lg border border-gray-200 text-gray-600 text-sm">{{ t('إلغاء', 'Cancel') }}</button>
                        <button @click="saveTemplate" class="px-4 py-2 rounded-lg text-white text-sm font-semibold" style="background:#1B365D">{{ t('حفظ', 'Save') }}</button>
                    </div>
                </div>
            </div>
        </Teleport>
    </AdminLayout>
</template>

<style scoped>
.nh-anim .nh-card { animation: nhRise .45s cubic-bezier(.22,1,.36,1) both; }
.nh-anim .nh-card:nth-child(2) { animation-delay: .05s; }
.nh-anim .nh-card:nth-child(3) { animation-delay: .1s; }
.nh-anim .nh-card:nth-child(4) { animation-delay: .15s; }
@keyframes nhRise { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
@media (prefers-reduced-motion: reduce) { .nh-card { animation: none !important; } }
</style>
