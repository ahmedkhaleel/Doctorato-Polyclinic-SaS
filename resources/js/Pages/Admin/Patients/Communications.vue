<script setup>
import { reactive, computed } from 'vue';
import { usePage, router, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { usePermissions } from '@/Composables/usePermissions.js';

const props = defineProps({
    patient: Object,
    preferences: Object,
    logs: Array,
    channelKeys: Array,
});

const page = usePage();
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');
const t = (ar, en) => (isRtl.value ? ar : en);
const { can } = usePermissions();

const channelMeta = {
    whatsapp: { ar: 'واتساب', en: 'WhatsApp', color: '#25D366' },
    sms: { ar: 'SMS', en: 'SMS', color: '#1B365D' },
    email: { ar: 'بريد', en: 'Email', color: '#C4A265' },
    in_app: { ar: 'داخلي', en: 'In-App', color: '#64748B' },
};
const statusStyle = {
    queued: 'bg-amber-100 text-amber-700', sent: 'bg-blue-100 text-blue-700',
    delivered: 'bg-green-100 text-green-700', read: 'bg-emerald-100 text-emerald-700',
    failed: 'bg-red-100 text-red-700', skipped: 'bg-gray-100 text-gray-500',
};
const statusLabel = (s) => ({ queued: t('بالانتظار','Queued'), sent: t('أُرسلت','Sent'), delivered: t('وصلت','Delivered'), read: t('قُرئت','Read'), failed: t('فشلت','Failed'), skipped: t('تخطّي','Skipped') }[s] || s);

// compose
const compose = reactive({ channel: 'whatsapp', subject: '', body: '' });
const sending = computed(() => false);
function sendMessage() {
    if (!compose.body.trim()) return;
    router.post(`/admin/patients/${props.patient.id}/communications/send`, compose, {
        preserveScroll: true,
        onSuccess: () => { compose.body = ''; compose.subject = ''; },
    });
}

// preferences
const prefs = reactive({ ...props.preferences, preferred_language: props.patient.preferred_language });
function savePrefs() {
    router.post(`/admin/patients/${props.patient.id}/communications/preferences`, prefs, { preserveScroll: true });
}
const cats = [
    { key: 'bookings', ar: 'الحجوزات', en: 'Bookings' },
    { key: 'reminders', ar: 'التذكيرات', en: 'Reminders' },
    { key: 'marketing', ar: 'التسويق', en: 'Marketing' },
];
const prefChannels = ['whatsapp', 'sms', 'email'];

const fmt = (iso) => iso ? new Date(iso).toLocaleString(isRtl.value ? 'ar-EG' : 'en-GB') : '';
</script>

<template>
    <AdminLayout :title="t('مراسلات المريض', 'Patient Communications')">
        <div class="max-w-6xl mx-auto p-4 md:p-6 space-y-5" :dir="isRtl ? 'rtl' : 'ltr'">
            <!-- Header -->
            <div class="flex items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 rounded-xl flex items-center justify-center text-white shadow" style="background:linear-gradient(135deg,#1B365D,#2a4a7a)">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.86 9.86 0 01-4-.8L3 20l1.3-3.5A7.9 7.9 0 013 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" /></svg>
                    </div>
                    <div>
                        <h1 class="text-xl font-bold text-gray-900">{{ patient.full_name }}</h1>
                        <p class="text-xs text-gray-500">{{ patient.file_number }} · {{ patient.phone }}</p>
                    </div>
                </div>
                <Link :href="`/admin/patients/${patient.id}`" class="text-sm font-semibold px-4 py-2 rounded-lg border border-gray-200 text-gray-600 hover:bg-gray-50">{{ t('ملف المريض', 'Patient File') }}</Link>
            </div>

            <div class="grid lg:grid-cols-3 gap-5">
                <!-- History timeline -->
                <div class="lg:col-span-2 bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
                    <h2 class="font-bold text-gray-900 mb-4">{{ t('سجل المراسلات', 'Communication History') }}</h2>
                    <div v-if="!logs.length" class="text-center text-gray-400 py-10 text-sm">{{ t('لا توجد مراسلات بعد', 'No messages yet') }}</div>
                    <div v-else class="space-y-3 max-h-[560px] overflow-y-auto pe-1">
                        <div v-for="log in logs" :key="log.id" class="flex gap-3"
                             :class="log.direction === 'inbound' ? 'flex-row-reverse text-end' : ''">
                            <div class="w-9 h-9 rounded-lg flex items-center justify-center text-white shrink-0 text-xs font-bold" :style="{ background: channelMeta[log.channel]?.color || '#64748B' }">
                                {{ (channelMeta[log.channel]?.en || log.channel).slice(0,2) }}
                            </div>
                            <div class="flex-1 min-w-0 rounded-xl border border-gray-100 p-3" :class="log.direction === 'inbound' ? 'bg-emerald-50/40' : 'bg-gray-50/40'">
                                <div class="flex items-center gap-2 flex-wrap" :class="log.direction === 'inbound' ? 'justify-end' : ''">
                                    <span class="text-xs font-mono text-gray-500">{{ log.event_key }}</span>
                                    <span class="text-xs font-semibold px-2 py-0.5 rounded-full" :class="statusStyle[log.status]">{{ statusLabel(log.status) }}</span>
                                    <span v-if="log.direction === 'inbound'" class="text-xs bg-emerald-100 text-emerald-700 px-2 py-0.5 rounded-full">{{ t('وارد', 'Inbound') }}</span>
                                </div>
                                <p v-if="log.body" class="text-sm text-gray-700 mt-1 whitespace-pre-wrap">{{ log.body }}</p>
                                <p v-if="log.error" class="text-xs text-red-400 mt-1">{{ log.error }}</p>
                                <p class="text-xs text-gray-400 mt-1">{{ fmt(log.created_at) }} <span v-if="log.read_at">· {{ t('قُرئت', 'read') }} {{ fmt(log.read_at) }}</span></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Side: compose + preferences -->
                <div class="space-y-5">
                    <!-- Compose -->
                    <div v-if="can('notifications.send')" class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
                        <h2 class="font-bold text-gray-900 mb-3">{{ t('إرسال رسالة', 'Send Message') }}</h2>
                        <div class="space-y-3">
                            <div class="flex gap-1.5 flex-wrap">
                                <button v-for="ch in channelKeys" :key="ch" @click="compose.channel = ch"
                                        class="px-3 py-1.5 rounded-lg text-xs font-semibold border transition"
                                        :class="compose.channel === ch ? 'text-white border-transparent' : 'text-gray-500 border-gray-200'"
                                        :style="compose.channel === ch ? { background: channelMeta[ch]?.color } : {}">
                                    {{ t(channelMeta[ch]?.ar, channelMeta[ch]?.en) }}
                                </button>
                            </div>
                            <input v-if="compose.channel === 'email'" v-model="compose.subject" :placeholder="t('الموضوع', 'Subject')" class="w-full rounded-lg border-gray-200 text-sm" />
                            <textarea v-model="compose.body" rows="4" :placeholder="t('اكتب رسالتك…', 'Type your message…')" class="w-full rounded-lg border-gray-200 text-sm"></textarea>
                            <button @click="sendMessage" :disabled="!compose.body.trim()" class="w-full px-4 py-2 rounded-lg text-white text-sm font-semibold disabled:opacity-50 transition" style="background:#1B365D">{{ t('إرسال', 'Send') }}</button>
                        </div>
                    </div>

                    <!-- Preferences -->
                    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
                        <h2 class="font-bold text-gray-900 mb-3">{{ t('تفضيلات الإشعارات', 'Notification Consent') }}</h2>
                        <table class="w-full text-xs">
                            <thead><tr class="text-gray-400"><th></th><th v-for="ch in prefChannels" :key="ch" class="pb-2">{{ t(channelMeta[ch]?.ar, channelMeta[ch]?.en) }}</th></tr></thead>
                            <tbody>
                                <tr v-for="cat in cats" :key="cat.key">
                                    <td class="py-1.5 text-gray-600 font-medium">{{ t(cat.ar, cat.en) }}</td>
                                    <td v-for="ch in prefChannels" :key="ch" class="text-center">
                                        <input type="checkbox" v-model="prefs[`notify_${ch}_${cat.key}`]" class="rounded text-[#1B365D]" />
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        <label class="block mt-3">
                            <span class="text-xs text-gray-500">{{ t('لغة الرسائل', 'Message language') }}</span>
                            <select v-model="prefs.preferred_language" class="mt-1 w-full rounded-lg border-gray-200 text-sm">
                                <option value="ar">العربية</option><option value="en">English</option>
                            </select>
                        </label>
                        <button @click="savePrefs" class="mt-3 w-full px-4 py-2 rounded-lg text-white text-sm font-semibold transition" style="background:#C4A265">{{ t('حفظ التفضيلات', 'Save Preferences') }}</button>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
