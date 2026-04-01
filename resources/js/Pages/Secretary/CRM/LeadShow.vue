<script setup>
import { ref, computed, watch } from 'vue';
import { Link, useForm, router, usePage } from '@inertiajs/vue3';
import SecretaryLayout from '@/Layouts/SecretaryLayout.vue';

const page = usePage();
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const props = defineProps({
    lead: Object,
    activities: Array,
    followUps: Array,
    templates: Array,
});

const activeTab = ref('activity');

// Activity form
const activityForm = useForm({
    type: 'note',
    description: '',
    direction: '',
    outcome: '',
});

function submitActivity() {
    activityForm.post(`/secretary/crm/leads/${props.lead.id}/activity`, {
        preserveScroll: true,
        onSuccess: () => activityForm.reset(),
    });
}

// Follow-up form
const showFollowUpForm = ref(false);
const followUpForm = useForm({
    type: 'call',
    scheduled_at: '',
    notes: '',
});

function submitFollowUp() {
    followUpForm.post(`/secretary/crm/leads/${props.lead.id}/follow-up`, {
        preserveScroll: true,
        onSuccess: () => {
            followUpForm.reset();
            showFollowUpForm.value = false;
        },
    });
}

// Follow-up actions
const completeForm = useForm({ result: '' });
const completingFollowUp = ref(null);

function openComplete(fu) {
    completingFollowUp.value = fu.id;
    completeForm.result = '';
}

function submitComplete(fuId) {
    completeForm.post(`/secretary/crm/follow-ups/${fuId}/complete`, {
        preserveScroll: true,
        onSuccess: () => {
            completingFollowUp.value = null;
            completeForm.reset();
        },
    });
}

function missFollowUp(fuId) {
    if (confirm('Mark this follow-up as missed?')) {
        router.post(`/secretary/crm/follow-ups/${fuId}/miss`, {}, { preserveScroll: true });
    }
}

// Quick Send
const showQuickSend = ref(false);
const quickSendForm = useForm({
    template_id: '',
    channel: 'whatsapp',
    language: 'en',
});

const filteredTemplates = computed(() => {
    return (props.templates || []).filter(t => t.channel === quickSendForm.channel);
});

const selectedTemplate = computed(() => {
    return (props.templates || []).find(t => t.id == quickSendForm.template_id);
});

const previewMessage = computed(() => {
    if (!selectedTemplate.value) return '';
    const body = quickSendForm.language === 'ar' ? selectedTemplate.value.body_ar : selectedTemplate.value.body_en;
    if (!body) return '';
    return body
        .replace(/{name}/g, props.lead.full_name || '')
        .replace(/{first_name}/g, (props.lead.full_name || '').split(' ')[0])
        .replace(/{phone}/g, props.lead.phone || '')
        .replace(/{email}/g, props.lead.email || '')
        .replace(/{clinic_name}/g, 'Aura Derma Clinic')
        .replace(/{date}/g, new Date().toLocaleDateString('en-GB'));
});

function submitQuickSend() {
    quickSendForm.post(`/secretary/crm/leads/${props.lead.id}/quick-send`, {
        preserveScroll: true,
        onSuccess: () => {
            const flash = usePage().props.flash || {};
            if (flash.redirect_url) {
                window.open(flash.redirect_url, '_blank');
            }
            showQuickSend.value = false;
            quickSendForm.reset();
            quickSendForm.channel = 'whatsapp';
            quickSendForm.language = 'en';
        },
    });
}

watch(() => quickSendForm.channel, () => {
    quickSendForm.template_id = '';
});

// Status update (limited for secretary)
const statusForm = useForm({ status: '' });

function changeStatus(newStatus) {
    statusForm.status = newStatus;
    statusForm.post(`/secretary/crm/leads/${props.lead.id}/status`, {
        preserveScroll: true,
        onSuccess: () => statusForm.reset(),
    });
}

const statusLabels = {
    new: isRtl.value ? 'جديد' : 'New', contacted: isRtl.value ? 'تم التواصل' : 'Contacted', qualified: isRtl.value ? 'مؤهل' : 'Qualified',
    appointment_booked: 'Appt. Booked', consultation_done: 'Consultation',
    negotiation: 'Negotiation', converted: isRtl.value ? 'محوّل' : 'Converted', lost: isRtl.value ? 'خسارة' : 'Lost', dormant: isRtl.value ? 'خامد' : 'Dormant',
};
const statusColors = {
    new: 'bg-blue-100 text-blue-700',
    contacted: 'bg-indigo-100 text-indigo-700',
    qualified: 'bg-purple-100 text-purple-700',
    appointment_booked: 'bg-amber-100 text-amber-700',
    consultation_done: 'bg-teal-100 text-teal-700',
    negotiation: 'bg-orange-100 text-orange-700',
    converted: 'bg-green-100 text-green-700',
    lost: 'bg-red-100 text-red-700',
    dormant: 'bg-gray-100 text-gray-600',
};
const priorityLabels = { 1: 'Hot', 2: 'Warm', 3: 'Cold' };
const priorityColors = { 1: 'bg-red-100 text-red-700', 2: 'bg-amber-100 text-amber-700', 3: 'bg-blue-100 text-blue-700' };

const activityTypeIcons = {
    note: 'M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z',
    call: 'M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z',
    whatsapp: 'M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z',
    email: 'M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z',
    sms: 'M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z',
    meeting: 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z',
    status_change: 'M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4',
    assignment: 'M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z',
    system: 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z',
    follow_up_scheduled: 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z',
    follow_up_completed: 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
    booking_created: 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z',
    visit_completed: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4',
    payment_received: 'M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z',
};

const activityTypeColors = {
    note: 'bg-gray-100 text-gray-600',
    call: 'bg-green-100 text-green-600',
    whatsapp: 'bg-emerald-100 text-emerald-600',
    email: 'bg-blue-100 text-blue-600',
    sms: 'bg-purple-100 text-purple-600',
    meeting: 'bg-amber-100 text-amber-600',
    status_change: 'bg-indigo-100 text-indigo-600',
    assignment: 'bg-pink-100 text-pink-600',
    system: 'bg-gray-100 text-gray-500',
    follow_up_scheduled: 'bg-amber-100 text-amber-600',
    follow_up_completed: 'bg-green-100 text-green-600',
    booking_created: 'bg-blue-100 text-blue-600',
    visit_completed: 'bg-teal-100 text-teal-600',
    payment_received: 'bg-green-100 text-green-700',
};

function formatDate(date) {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
}

function formatDateTime(date) {
    if (!date) return '-';
    const d = new Date(date);
    return d.toLocaleDateString('en-GB', { day: '2-digit', month: 'short' }) + ' ' + d.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });
}

function timeAgo(date) {
    if (!date) return '';
    const diff = Math.floor((new Date() - new Date(date)) / 1000);
    if (diff < 60) return 'just now';
    if (diff < 3600) return Math.floor(diff / 60) + 'm ago';
    if (diff < 86400) return Math.floor(diff / 3600) + 'h ago';
    return Math.floor(diff / 86400) + 'd ago';
}

const pipelineStatuses = ['new', 'contacted', 'qualified', 'appointment_booked', 'consultation_done', 'negotiation'];
const currentStepIndex = pipelineStatuses.indexOf(props.lead.status);

// Secretary can move forward through these statuses only
const allowedStatuses = ['contacted', 'qualified', 'appointment_booked', 'consultation_done', 'negotiation'];
</script>

<template>
    <SecretaryLayout :title="`Lead: ${lead.full_name}`">
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
                <div class="flex items-start gap-4">
                    <Link href="/secretary/crm/leads" class="mt-1 text-sm text-gray-500 hover:text-gray-700 transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
                    </Link>
                    <div>
                        <div class="flex items-center gap-3 flex-wrap">
                            <h1 class="text-2xl font-bold text-gray-800">{{ lead.full_name }}</h1>
                            <span :class="statusColors[lead.status]" class="px-2.5 py-1 text-xs font-semibold rounded-full">
                                {{ statusLabels[lead.status] }}
                            </span>
                            <span :class="priorityColors[lead.priority]" class="px-2 py-0.5 text-[10px] font-bold rounded-full uppercase">
                                {{ priorityLabels[lead.priority] }}
                            </span>
                            <span class="text-xs font-mono px-2 py-0.5 rounded bg-gray-100 text-gray-500">Score: {{ lead.score }}</span>
                        </div>
                        <p class="text-sm text-gray-500 mt-0.5">
                            {{ lead.source?.name_en || 'Unknown source' }}
                            <span v-if="lead.campaign"> -- {{ lead.campaign.name }}</span>
                        </p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <!-- Quick Send Button -->
                    <button v-if="templates?.length && lead.status !== 'converted'"
                        @click="showQuickSend = !showQuickSend"
                        class="inline-flex items-center px-3 py-2 rounded-lg text-sm font-medium transition shadow-sm bg-green-600 hover:bg-green-700 text-white"
                    >
                        <svg class="w-4 h-4 ltr:mr-1.5 rtl:ml-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" /></svg>
                        Quick Send
                    </button>
                </div>
            </div>

            <!-- Quick Send Panel -->
            <div v-if="showQuickSend" class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wider">Quick Send Message</h3>
                    <button @click="showQuickSend = false" class="text-gray-400 hover:text-gray-600 transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>
                <form @submit.prevent="submitQuickSend" class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <!-- Channel -->
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1.5">Channel</label>
                            <div class="flex gap-2">
                                <button type="button" @click="quickSendForm.channel = 'whatsapp'"
                                    :class="quickSendForm.channel === 'whatsapp' ? 'bg-green-100 text-green-700 border-green-300' : 'bg-gray-50 text-gray-500 border-gray-200'"
                                    class="flex-1 px-3 py-2 rounded-lg border text-xs font-medium transition">
                                    WhatsApp
                                </button>
                                <button type="button" @click="quickSendForm.channel = 'sms'"
                                    :class="quickSendForm.channel === 'sms' ? 'bg-purple-100 text-purple-700 border-purple-300' : 'bg-gray-50 text-gray-500 border-gray-200'"
                                    class="flex-1 px-3 py-2 rounded-lg border text-xs font-medium transition">
                                    SMS
                                </button>
                                <button type="button" @click="quickSendForm.channel = 'email'"
                                    :class="quickSendForm.channel === 'email' ? 'bg-blue-100 text-blue-700 border-blue-300' : 'bg-gray-50 text-gray-500 border-gray-200'"
                                    class="flex-1 px-3 py-2 rounded-lg border text-xs font-medium transition">
                                    Email
                                </button>
                            </div>
                        </div>
                        <!-- Template -->
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1.5">Template</label>
                            <select v-model="quickSendForm.template_id" class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:ring-2 focus:ring-teal-500/30 focus:border-teal-500">
                                <option value="">Select template...</option>
                                <option v-for="t in filteredTemplates" :key="t.id" :value="t.id">{{ t.name }}</option>
                            </select>
                        </div>
                        <!-- Language -->
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1.5">Language</label>
                            <div class="flex gap-2">
                                <button type="button" @click="quickSendForm.language = 'en'"
                                    :class="quickSendForm.language === 'en' ? 'bg-teal-100 text-teal-700 border-teal-300' : 'bg-gray-50 text-gray-500 border-gray-200'"
                                    class="flex-1 px-3 py-2 rounded-lg border text-xs font-medium transition">
                                    English
                                </button>
                                <button type="button" @click="quickSendForm.language = 'ar'"
                                    :class="quickSendForm.language === 'ar' ? 'bg-teal-100 text-teal-700 border-teal-300' : 'bg-gray-50 text-gray-500 border-gray-200'"
                                    class="flex-1 px-3 py-2 rounded-lg border text-xs font-medium transition">
                                    Arabic
                                </button>
                            </div>
                        </div>
                    </div>
                    <!-- Preview -->
                    <div v-if="previewMessage" class="bg-gray-50 rounded-lg p-4 border border-gray-100">
                        <p class="text-xs font-medium text-gray-500 mb-2">Preview:</p>
                        <p class="text-sm text-gray-700 whitespace-pre-wrap" :dir="quickSendForm.language === 'ar' ? 'rtl' : 'ltr'">{{ previewMessage }}</p>
                    </div>
                    <div class="flex justify-end">
                        <button type="submit" :disabled="quickSendForm.processing || !quickSendForm.template_id"
                            class="inline-flex items-center px-4 py-2 text-xs font-medium text-white rounded-lg transition disabled:opacity-50"
                            :class="quickSendForm.channel === 'whatsapp' ? 'bg-green-600 hover:bg-green-700' : quickSendForm.channel === 'sms' ? 'bg-purple-600 hover:bg-purple-700' : 'bg-blue-600 hover:bg-blue-700'"
                        >
                            <svg class="w-4 h-4 ltr:mr-1.5 rtl:ml-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" /></svg>
                            {{ quickSendForm.channel === 'whatsapp' ? 'Send via WhatsApp' : quickSendForm.channel === 'sms' ? 'Log SMS' : 'Log Email' }}
                        </button>
                    </div>
                </form>
            </div>

            <!-- Pipeline Progress -->
            <div v-if="currentStepIndex >= 0" class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                <div class="flex items-center gap-2">
                    <div v-for="(status, idx) in pipelineStatuses" :key="status"
                        class="flex items-center gap-2 flex-1"
                    >
                        <div class="flex items-center gap-1.5 flex-1">
                            <div :class="idx <= currentStepIndex ? 'bg-teal-600 text-white' : 'bg-gray-200 text-gray-400'"
                                class="w-6 h-6 rounded-full flex items-center justify-center text-[10px] font-bold shrink-0 transition"
                            >{{ idx + 1 }}</div>
                            <span class="text-[10px] text-gray-500 truncate hidden md:inline">{{ statusLabels[status] }}</span>
                        </div>
                        <div v-if="idx < pipelineStatuses.length - 1"
                            :class="idx < currentStepIndex ? 'bg-teal-600' : 'bg-gray-200'"
                            class="h-0.5 flex-1 transition"
                        ></div>
                    </div>
                </div>
            </div>

            <!-- Converted Info -->
            <div v-if="lead.status === 'converted' && lead.patient" class="bg-green-50 border border-green-200 rounded-xl p-5 flex items-center gap-4">
                <svg class="w-8 h-8 text-green-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                <div>
                    <p class="text-sm font-semibold text-green-800">Lead Converted to Patient</p>
                    <p class="text-xs text-green-600 mt-0.5">Patient: {{ lead.patient.full_name }} ({{ lead.patient.file_number }})</p>
                </div>
            </div>

            <!-- Lost Info -->
            <div v-if="lead.status === 'lost'" class="bg-red-50 border border-red-200 rounded-xl p-5">
                <p class="text-sm font-semibold text-red-800">Lead Lost</p>
                <p class="text-xs text-red-600 mt-0.5">{{ lead.loss_reason || 'No reason provided' }}</p>
                <p class="text-xs text-red-400 mt-0.5">Lost on {{ formatDate(lead.lost_at) }}</p>
            </div>

            <!-- Main Content -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Left Column -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Quick Status Actions -->
                    <div v-if="lead.status !== 'converted' && lead.status !== 'lost'" class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                        <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">Move to Stage</h3>
                        <div class="flex flex-wrap gap-2">
                            <button v-for="s in allowedStatuses" :key="s"
                                @click="changeStatus(s)"
                                :disabled="lead.status === s"
                                :class="lead.status === s ? 'opacity-50 cursor-not-allowed ring-2 ring-teal-500' : 'hover:shadow-sm'"
                                class="px-3 py-1.5 text-xs font-medium rounded-lg border border-gray-200 transition"
                            >{{ statusLabels[s] }}</button>
                        </div>
                    </div>

                    <!-- Tabs -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                        <div class="border-b border-gray-100 px-5 flex gap-6">
                            <button @click="activeTab = 'activity'"
                                :class="activeTab === 'activity' ? 'border-teal-600 text-teal-600' : 'border-transparent text-gray-500 hover:text-gray-700'"
                                class="py-3 text-sm font-medium border-b-2 transition"
                            >Activity Timeline</button>
                            <button @click="activeTab = 'followups'"
                                :class="activeTab === 'followups' ? 'border-teal-600 text-teal-600' : 'border-transparent text-gray-500 hover:text-gray-700'"
                                class="py-3 text-sm font-medium border-b-2 transition"
                            >
                                Follow-ups
                                <span v-if="followUps?.length" class="ltr:ml-1 rtl:mr-1 text-[10px] px-1.5 py-0.5 rounded-full bg-gray-100 text-gray-500">{{ followUps.length }}</span>
                            </button>
                        </div>

                        <!-- Activity Tab -->
                        <div v-if="activeTab === 'activity'" class="p-5 space-y-5">
                            <!-- Log Activity Form -->
                            <form @submit.prevent="submitActivity" class="bg-gray-50/80 rounded-lg p-4 space-y-3">
                                <div class="grid grid-cols-2 md:grid-cols-3 gap-2">
                                    <select v-model="activityForm.type" class="text-sm border border-gray-200 rounded-lg py-2 px-3">
                                        <option value="note">Note</option>
                                        <option value="call">Call</option>
                                        <option value="whatsapp">WhatsApp</option>
                                        <option value="email">{{ isRtl ? 'البريد' : 'Email' }}</option>
                                        <option value="sms">SMS</option>
                                        <option value="meeting">Meeting</option>
                                    </select>
                                    <select v-if="['call','whatsapp','email','sms'].includes(activityForm.type)" v-model="activityForm.direction" class="text-sm border border-gray-200 rounded-lg py-2 px-3">
                                        <option value="">Direction</option>
                                        <option value="inbound">Inbound</option>
                                        <option value="outbound">Outbound</option>
                                    </select>
                                    <select v-if="activityForm.type === 'call'" v-model="activityForm.outcome" class="text-sm border border-gray-200 rounded-lg py-2 px-3">
                                        <option value="">Outcome</option>
                                        <option value="successful">Successful</option>
                                        <option value="no_answer">No Answer</option>
                                        <option value="busy">Busy</option>
                                        <option value="voicemail">Voicemail</option>
                                        <option value="callback_requested">Callback Requested</option>
                                        <option value="not_interested">Not Interested</option>
                                    </select>
                                </div>
                                <textarea v-model="activityForm.description" rows="2" placeholder="What happened? Add notes..." class="w-full text-sm border border-gray-200 rounded-lg py-2 px-3 resize-none focus:ring-2 focus:ring-teal-500/30 focus:border-teal-500"></textarea>
                                <div class="flex justify-end">
                                    <button type="submit" :disabled="activityForm.processing" class="px-4 py-2 text-xs font-medium text-white rounded-lg transition" style="background-color: #0d9488;">Log Activity</button>
                                </div>
                            </form>

                            <!-- Timeline -->
                            <div class="space-y-0">
                                <div v-for="act in activities" :key="act.id" class="flex gap-3 py-3 border-b border-gray-50 last:border-0">
                                    <div :class="activityTypeColors[act.type] || 'bg-gray-100 text-gray-500'" class="w-8 h-8 rounded-full flex items-center justify-center shrink-0 mt-0.5">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" :d="activityTypeIcons[act.type] || activityTypeIcons.system" /></svg>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center justify-between gap-2">
                                            <p class="text-sm font-medium text-gray-800">
                                                {{ act.subject || act.type.replace(/_/g, ' ') }}
                                            </p>
                                            <span class="text-[10px] text-gray-400 whitespace-nowrap">{{ timeAgo(act.created_at) }}</span>
                                        </div>
                                        <p v-if="act.description" class="text-xs text-gray-500 mt-0.5 line-clamp-2">{{ act.description }}</p>
                                        <div class="flex items-center gap-2 mt-1">
                                            <span v-if="act.direction" class="text-[10px] px-1.5 py-0.5 rounded bg-gray-100 text-gray-500 capitalize">{{ act.direction }}</span>
                                            <span v-if="act.outcome" class="text-[10px] px-1.5 py-0.5 rounded bg-gray-100 text-gray-500 capitalize">{{ act.outcome?.replace(/_/g, ' ') }}</span>
                                            <span v-if="act.performer" class="text-[10px] text-gray-400">by {{ act.performer.name }}</span>
                                        </div>
                                    </div>
                                </div>
                                <div v-if="!activities?.length" class="py-8 text-center text-sm text-gray-400">No activity recorded yet</div>
                            </div>
                        </div>

                        <!-- Follow-ups Tab -->
                        <div v-if="activeTab === 'followups'" class="p-5 space-y-4">
                            <div class="flex justify-end">
                                <button @click="showFollowUpForm = !showFollowUpForm" class="text-xs font-medium px-3 py-1.5 rounded-lg transition text-white" style="background-color: #0d9488;">
                                    {{ showFollowUpForm ? 'Cancel' : 'Schedule Follow-up' }}
                                </button>
                            </div>

                            <form v-if="showFollowUpForm" @submit.prevent="submitFollowUp" class="bg-gray-50/80 rounded-lg p-4 space-y-3">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                    <select v-model="followUpForm.type" class="text-sm border border-gray-200 rounded-lg py-2 px-3">
                                        <option value="call">Call</option>
                                        <option value="whatsapp">WhatsApp</option>
                                        <option value="email">{{ isRtl ? 'البريد' : 'Email' }}</option>
                                        <option value="sms">SMS</option>
                                        <option value="meeting">Meeting</option>
                                        <option value="other">Other</option>
                                    </select>
                                    <input v-model="followUpForm.scheduled_at" type="datetime-local" class="text-sm border border-gray-200 rounded-lg py-2 px-3" />
                                </div>
                                <textarea v-model="followUpForm.notes" rows="2" placeholder="Follow-up notes..." class="w-full text-sm border border-gray-200 rounded-lg py-2 px-3 resize-none"></textarea>
                                <div class="flex justify-end">
                                    <button type="submit" :disabled="followUpForm.processing" class="px-4 py-2 text-xs font-medium text-white rounded-lg" style="background-color: #0d9488;">Schedule</button>
                                </div>
                            </form>

                            <div class="space-y-2">
                                <div v-for="fu in followUps" :key="fu.id"
                                    :class="fu.status === 'pending' && new Date(fu.scheduled_at) < new Date() ? 'border-red-200 bg-red-50/30' : 'border-gray-100'"
                                    class="p-3 rounded-lg border transition"
                                >
                                    <div class="flex items-center gap-3">
                                        <div :class="{
                                            'bg-amber-100 text-amber-600': fu.status === 'pending',
                                            'bg-green-100 text-green-600': fu.status === 'completed',
                                            'bg-red-100 text-red-600': fu.status === 'missed',
                                            'bg-gray-100 text-gray-500': fu.status === 'cancelled' || fu.status === 'rescheduled',
                                        }" class="w-8 h-8 rounded-full flex items-center justify-center shrink-0">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <div class="flex items-center gap-2">
                                                <span class="text-sm font-medium text-gray-800 capitalize">{{ fu.type }}</span>
                                                <span class="text-[10px] px-1.5 py-0.5 rounded-full capitalize"
                                                    :class="{
                                                        'bg-amber-100 text-amber-700': fu.status === 'pending',
                                                        'bg-green-100 text-green-700': fu.status === 'completed',
                                                        'bg-red-100 text-red-700': fu.status === 'missed',
                                                        'bg-gray-100 text-gray-600': fu.status === 'cancelled' || fu.status === 'rescheduled',
                                                    }"
                                                >{{ fu.status }}</span>
                                            </div>
                                            <p class="text-xs text-gray-500 mt-0.5">{{ formatDateTime(fu.scheduled_at) }}</p>
                                            <p v-if="fu.notes" class="text-xs text-gray-400 mt-0.5">{{ fu.notes }}</p>
                                            <p v-if="fu.result" class="text-xs text-green-600 mt-0.5">Result: {{ fu.result }}</p>
                                        </div>
                                        <div class="flex items-center gap-1">
                                            <!-- Action buttons for pending follow-ups -->
                                            <template v-if="fu.status === 'pending'">
                                                <button @click="openComplete(fu)" title="Mark Complete" class="p-1.5 rounded-lg text-green-500 hover:bg-green-50 transition">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                                                </button>
                                                <button @click="missFollowUp(fu.id)" title="Mark Missed" class="p-1.5 rounded-lg text-red-500 hover:bg-red-50 transition">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                                                </button>
                                            </template>
                                        </div>
                                    </div>

                                    <!-- Complete form (inline) -->
                                    <div v-if="completingFollowUp === fu.id" class="mt-3 pt-3 border-t border-gray-100">
                                        <form @submit.prevent="submitComplete(fu.id)" class="flex items-end gap-2">
                                            <div class="flex-1">
                                                <label class="text-xs text-gray-500 mb-1 block">Result / Notes (optional)</label>
                                                <input v-model="completeForm.result" type="text" placeholder="e.g. Client agreed to come for consultation" class="w-full text-sm border border-gray-200 rounded-lg py-2 px-3 focus:ring-2 focus:ring-green-300 focus:border-green-400" />
                                            </div>
                                            <button type="submit" :disabled="completeForm.processing" class="px-3 py-2 text-xs font-medium text-white bg-green-600 rounded-lg hover:bg-green-700 transition">Done</button>
                                            <button type="button" @click="completingFollowUp = null" class="px-3 py-2 text-xs text-gray-500">{{ isRtl ? 'إلغاء' : 'Cancel' }}</button>
                                        </form>
                                    </div>
                                </div>
                                <div v-if="!followUps?.length" class="py-8 text-center text-sm text-gray-400">No follow-ups scheduled</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column -->
                <div class="space-y-6">
                    <!-- Quick Actions (Contact) -->
                    <div v-if="lead.phone && lead.status !== 'converted'" class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                        <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">Quick Actions</h3>
                        <div class="grid grid-cols-2 gap-2">
                            <a :href="`tel:${lead.phone}`" class="flex items-center justify-center gap-1.5 px-3 py-2 text-xs font-medium text-green-700 bg-green-50 rounded-lg border border-green-100 hover:bg-green-100 transition">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" /></svg>
                                Call
                            </a>
                            <a :href="`https://wa.me/${lead.phone.replace(/[^0-9]/g, '')}`" target="_blank" class="flex items-center justify-center gap-1.5 px-3 py-2 text-xs font-medium text-emerald-700 bg-emerald-50 rounded-lg border border-emerald-100 hover:bg-emerald-100 transition">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" /></svg>
                                WhatsApp
                            </a>
                            <a v-if="lead.email" :href="`mailto:${lead.email}`" class="flex items-center justify-center gap-1.5 px-3 py-2 text-xs font-medium text-blue-700 bg-blue-50 rounded-lg border border-blue-100 hover:bg-blue-100 transition col-span-2">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>
                                Email
                            </a>
                        </div>
                    </div>

                    <!-- Contact Info -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                        <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-4">Contact Information</h3>
                        <div class="space-y-3">
                            <div class="flex justify-between">
                                <span class="text-sm text-gray-500">{{ isRtl ? 'الهاتف' : 'Phone' }}</span>
                                <a v-if="lead.phone" :href="`tel:${lead.phone}`" class="text-sm font-medium text-gray-800 hover:underline">{{ lead.phone }}</a>
                                <span v-else class="text-sm text-gray-400">-</span>
                            </div>
                            <div v-if="lead.phone2" class="flex justify-between">
                                <span class="text-sm text-gray-500">Phone 2</span>
                                <a :href="`tel:${lead.phone2}`" class="text-sm font-medium text-gray-800 hover:underline">{{ lead.phone2 }}</a>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-sm text-gray-500">{{ isRtl ? 'البريد' : 'Email' }}</span>
                                <a v-if="lead.email" :href="`mailto:${lead.email}`" class="text-sm font-medium text-gray-800 hover:underline">{{ lead.email }}</a>
                                <span v-else class="text-sm text-gray-400">-</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-sm text-gray-500">{{ isRtl ? 'الجنس' : 'Gender' }}</span>
                                <span class="text-sm font-medium text-gray-800 capitalize">{{ lead.gender || '-' }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-sm text-gray-500">City</span>
                                <span class="text-sm font-medium text-gray-800">{{ lead.city || '-' }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-sm text-gray-500">{{ isRtl ? 'الجنسية' : 'Nationality' }}</span>
                                <span class="text-sm font-medium text-gray-800">{{ lead.nationality || '-' }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Lead Details -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                        <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-4">{{ isRtl ? 'تفاصيل العميل' : 'Lead Details' }}</h3>
                        <div class="space-y-3">
                            <div class="flex justify-between">
                                <span class="text-sm text-gray-500">{{ isRtl ? 'المصدر' : 'Source' }}</span>
                                <span v-if="lead.source" class="text-xs px-2 py-0.5 rounded-full" :style="{ backgroundColor: lead.source.color + '18', color: lead.source.color }">{{ lead.source.name_en }}</span>
                                <span v-else class="text-sm text-gray-400">-</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-sm text-gray-500">Campaign</span>
                                <span class="text-sm font-medium text-gray-800">{{ lead.campaign?.name || '-' }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-sm text-gray-500">Created</span>
                                <span class="text-sm font-medium text-gray-800">{{ formatDate(lead.created_at) }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-sm text-gray-500">Last Contact</span>
                                <span class="text-sm font-medium text-gray-800">{{ lead.last_contacted_at ? formatDate(lead.last_contacted_at) : 'Never' }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-sm text-gray-500">Follow-ups</span>
                                <span class="text-sm font-medium text-gray-800">{{ lead.follow_up_count }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Notes -->
                    <div v-if="lead.notes" class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                        <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">{{ isRtl ? 'ملاحظات' : 'Notes' }}</h3>
                        <p class="text-sm text-gray-600 whitespace-pre-wrap">{{ lead.notes }}</p>
                    </div>
                </div>
            </div>
        </div>
    </SecretaryLayout>
</template>
