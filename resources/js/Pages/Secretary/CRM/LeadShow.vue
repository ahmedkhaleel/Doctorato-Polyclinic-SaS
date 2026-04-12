<script setup>
import { ref, computed, watch, onMounted } from 'vue';
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

// Entrance animation
const mounted = ref(false);
onMounted(() => {
    setTimeout(() => { mounted.value = true; }, 50);
});

// Tabs
const activeTab = ref('activity');
const tabTransition = ref(false);
function switchTab(tab) {
    if (tab === activeTab.value) return;
    tabTransition.value = true;
    setTimeout(() => {
        activeTab.value = tab;
        setTimeout(() => { tabTransition.value = false; }, 30);
    }, 200);
}

// Pipeline
const pipelineStatuses = ['new', 'contacted', 'qualified', 'appointment_booked', 'consultation_done', 'negotiation'];
const allowedStatuses = ['contacted', 'qualified', 'appointment_booked', 'consultation_done', 'negotiation'];

const statusLabels = {
    new: { en: 'New', ar: 'جديد' },
    contacted: { en: 'Contacted', ar: 'تم التواصل' },
    qualified: { en: 'Qualified', ar: 'مؤهل' },
    appointment_booked: { en: 'Booked', ar: 'تم الحجز' },
    consultation_done: { en: 'Consulted', ar: 'تم الاستشارة' },
    negotiation: { en: 'Negotiation', ar: 'تفاوض' },
    converted: { en: 'Converted', ar: 'محوّل' },
    lost: { en: 'Lost', ar: 'خسارة' },
    dormant: { en: 'Dormant', ar: 'خامد' },
};

const statusColors = {
    new: 'bg-blue-100 text-blue-800',
    contacted: 'bg-cyan-100 text-cyan-800',
    qualified: 'bg-teal-100 text-teal-800',
    appointment_booked: 'bg-emerald-100 text-emerald-800',
    consultation_done: 'bg-green-100 text-green-800',
    negotiation: 'bg-amber-100 text-amber-800',
    converted: 'bg-purple-100 text-purple-800',
    lost: 'bg-red-100 text-red-800',
    dormant: 'bg-gray-100 text-gray-800',
};

const priorityDisplay = {
    hot: { emoji: '🔥', color: 'bg-red-100 text-red-700', label: { en: 'Hot', ar: 'ساخن' } },
    warm: { emoji: '☀️', color: 'bg-amber-100 text-amber-700', label: { en: 'Warm', ar: 'دافئ' } },
    cold: { emoji: '❄️', color: 'bg-blue-100 text-blue-700', label: { en: 'Cold', ar: 'بارد' } },
};

// Status form
const statusForm = useForm({ status: '' });
function changeStatus(newStatus) {
    statusForm.status = newStatus;
    statusForm.post(`/secretary/crm/leads/${props.lead.id}/status`, {
        preserveScroll: true,
    });
}

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

const activityTypes = [
    { value: 'note', label: { en: 'Note', ar: 'ملاحظة' } },
    { value: 'call', label: { en: 'Call', ar: 'مكالمة' } },
    { value: 'whatsapp', label: { en: 'WhatsApp', ar: 'واتساب' } },
    { value: 'email', label: { en: 'Email', ar: 'بريد' } },
    { value: 'sms', label: { en: 'SMS', ar: 'رسالة' } },
    { value: 'meeting', label: { en: 'Meeting', ar: 'اجتماع' } },
];

const activityTypeColors = {
    note: 'text-gray-500 bg-gray-100',
    call: 'text-green-600 bg-green-100',
    whatsapp: 'text-emerald-600 bg-emerald-100',
    email: 'text-blue-600 bg-blue-100',
    sms: 'text-purple-600 bg-purple-100',
    meeting: 'text-amber-600 bg-amber-100',
    status_change: 'text-indigo-600 bg-indigo-100',
    assignment: 'text-pink-600 bg-pink-100',
    system: 'text-gray-500 bg-gray-100',
    follow_up_scheduled: 'text-cyan-600 bg-cyan-100',
    follow_up_completed: 'text-green-600 bg-green-100',
    booking_created: 'text-teal-600 bg-teal-100',
    visit_completed: 'text-emerald-600 bg-emerald-100',
    payment_received: 'text-amber-600 bg-amber-100',
};

function getActivityIcon(type) {
    const icons = {
        note: 'M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z',
        call: 'M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z',
        whatsapp: 'M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z M12 2C6.477 2 2 6.477 2 12c0 1.89.525 3.66 1.438 5.168L2 22l4.832-1.438A9.955 9.955 0 0012 22c5.523 0 10-4.477 10-10S17.523 2 12 2z',
        email: 'M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z',
        sms: 'M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z',
        meeting: 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z',
        status_change: 'M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15',
        assignment: 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z',
        system: 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z M15 12a3 3 0 11-6 0 3 3 0 016 0z',
        follow_up_scheduled: 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z',
        follow_up_completed: 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
        booking_created: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4',
        visit_completed: 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4',
        payment_received: 'M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z',
    };
    return icons[type] || icons.note;
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
    if (confirm(isRtl.value ? 'هل تريد تحديد هذه المتابعة كفائتة؟' : 'Mark this follow-up as missed?')) {
        router.post(`/secretary/crm/follow-ups/${fuId}/miss`, {}, { preserveScroll: true });
    }
}

const followUpStatusColors = {
    pending: 'bg-amber-100 text-amber-800 border-amber-200',
    completed: 'bg-green-100 text-green-800 border-green-200',
    missed: 'bg-red-100 text-red-800 border-red-200',
    cancelled: 'bg-gray-100 text-gray-700 border-gray-200',
    rescheduled: 'bg-blue-100 text-blue-800 border-blue-200',
};

const followUpStatusLabels = {
    pending: { en: 'Pending', ar: 'قيد الانتظار' },
    completed: { en: 'Completed', ar: 'مكتمل' },
    missed: { en: 'Missed', ar: 'فائت' },
    cancelled: { en: 'Cancelled', ar: 'ملغي' },
    rescheduled: { en: 'Rescheduled', ar: 'مُعاد جدولته' },
};

// Quick Send
const quickSendForm = useForm({
    template_id: '',
    channel: 'whatsapp',
    language: 'ar',
});

watch(() => quickSendForm.channel, () => {
    quickSendForm.template_id = '';
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
    const today = new Date().toLocaleDateString(isRtl.value ? 'ar-SA' : 'en-US');
    return body
        .replace(/{name}/g, props.lead.full_name || '')
        .replace(/{first_name}/g, (props.lead.full_name || '').split(' ')[0])
        .replace(/{phone}/g, props.lead.phone || '')
        .replace(/{email}/g, props.lead.email || '')
        .replace(/{clinic_name}/g, isRtl.value ? 'عيادة أورا ديرما' : 'Aura Derma Clinic')
        .replace(/{date}/g, today);
});

function submitQuickSend() {
    quickSendForm.post(`/secretary/crm/leads/${props.lead.id}/send-message`, {
        preserveScroll: true,
        onSuccess: () => quickSendForm.reset(),
    });
}

// Helpers
function timeAgo(dateStr) {
    if (!dateStr) return '';
    const diff = Date.now() - new Date(dateStr).getTime();
    const mins = Math.floor(diff / 60000);
    if (mins < 1) return isRtl.value ? 'الآن' : 'Just now';
    if (mins < 60) return isRtl.value ? `منذ ${mins} دقيقة` : `${mins}m ago`;
    const hrs = Math.floor(mins / 60);
    if (hrs < 24) return isRtl.value ? `منذ ${hrs} ساعة` : `${hrs}h ago`;
    const days = Math.floor(hrs / 24);
    if (days < 30) return isRtl.value ? `منذ ${days} يوم` : `${days}d ago`;
    const months = Math.floor(days / 30);
    return isRtl.value ? `منذ ${months} شهر` : `${months}mo ago`;
}

function formatDate(dateStr) {
    if (!dateStr) return '-';
    return new Date(dateStr).toLocaleDateString(isRtl.value ? 'ar-SA' : 'en-US', {
        year: 'numeric', month: 'short', day: 'numeric',
    });
}

function formatDateTime(dateStr) {
    if (!dateStr) return '-';
    return new Date(dateStr).toLocaleString(isRtl.value ? 'ar-SA' : 'en-US', {
        year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit',
    });
}

const initial = computed(() => {
    return (props.lead.full_name || '?').charAt(0).toUpperCase();
});

const currentPipelineIndex = computed(() => {
    return pipelineStatuses.indexOf(props.lead.status);
});

const scoreAngle = computed(() => {
    return ((props.lead.score || 0) / 100) * 283;
});
</script>

<template>
    <SecretaryLayout>
        <div class="min-h-screen bg-gray-50 pb-12" :dir="isRtl ? 'rtl' : 'ltr'">
            <!-- Hero Card -->
            <div
                class="relative overflow-hidden transition-all duration-700 ease-out"
                :class="mounted ? 'opacity-100 translate-y-0' : 'opacity-0 -translate-y-6'"
            >
                <div class="bg-gradient-to-br from-teal-600 via-teal-700 to-teal-900 px-4 sm:px-6 lg:px-8 pt-8 pb-20">
                    <!-- Decorative circles -->
                    <div class="absolute top-0 right-0 w-64 h-64 bg-white/5 rounded-full -translate-y-1/2 translate-x-1/2"></div>
                    <div class="absolute bottom-0 left-0 w-48 h-48 bg-white/5 rounded-full translate-y-1/2 -translate-x-1/4"></div>

                    <div class="max-w-7xl mx-auto relative z-10">
                        <!-- Back button -->
                        <Link
                            href="/secretary/crm/leads"
                            class="inline-flex items-center gap-1.5 text-teal-100 hover:text-white text-sm mb-6 transition-colors"
                        >
                            <svg class="w-4 h-4" :class="isRtl ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                            </svg>
                            {{ isRtl ? 'العودة للعملاء المحتملين' : 'Back to Leads' }}
                        </Link>

                        <div class="flex flex-col lg:flex-row lg:items-start gap-6">
                            <!-- Avatar + Info -->
                            <div class="flex items-start gap-5 flex-1">
                                <!-- Avatar with score gauge -->
                                <div class="relative flex-shrink-0">
                                    <div class="w-20 h-20 rounded-2xl bg-gradient-to-br from-white/30 to-white/10 backdrop-blur-sm flex items-center justify-center text-3xl font-bold text-white shadow-lg">
                                        {{ initial }}
                                    </div>
                                    <!-- Score circle -->
                                    <div class="absolute -bottom-2 -right-2 w-10 h-10" :title="(isRtl ? 'النقاط: ' : 'Score: ') + (lead.score || 0)">
                                        <svg viewBox="0 0 100 100" class="w-full h-full -rotate-90">
                                            <circle cx="50" cy="50" r="45" fill="white" stroke="none"/>
                                            <circle cx="50" cy="50" r="40" fill="none" stroke="#e5e7eb" stroke-width="8"/>
                                            <circle
                                                cx="50" cy="50" r="40" fill="none"
                                                stroke="#0d9488" stroke-width="8"
                                                stroke-linecap="round"
                                                :stroke-dasharray="scoreAngle + ' 283'"
                                                class="transition-all duration-1000 ease-out"
                                            />
                                        </svg>
                                        <span class="absolute inset-0 flex items-center justify-center text-xs font-bold text-teal-700">
                                            {{ lead.score || 0 }}
                                        </span>
                                    </div>
                                </div>

                                <div class="flex-1 min-w-0">
                                    <div class="flex flex-wrap items-center gap-3 mb-2">
                                        <h1 class="text-2xl font-bold text-white truncate">{{ lead.full_name }}</h1>
                                        <!-- Status badge -->
                                        <span
                                            class="px-2.5 py-0.5 rounded-full text-xs font-semibold"
                                            :class="statusColors[lead.status] || 'bg-gray-100 text-gray-700'"
                                        >
                                            {{ isRtl ? (statusLabels[lead.status]?.ar || lead.status) : (statusLabels[lead.status]?.en || lead.status) }}
                                        </span>
                                        <!-- Priority badge -->
                                        <span
                                            v-if="lead.priority && priorityDisplay[lead.priority]"
                                            class="px-2.5 py-0.5 rounded-full text-xs font-semibold"
                                            :class="priorityDisplay[lead.priority].color"
                                        >
                                            {{ priorityDisplay[lead.priority].emoji }}
                                            {{ isRtl ? priorityDisplay[lead.priority].label.ar : priorityDisplay[lead.priority].label.en }}
                                        </span>
                                    </div>

                                    <!-- Contact details -->
                                    <div class="flex flex-wrap items-center gap-x-5 gap-y-1.5 text-teal-100 text-sm">
                                        <a v-if="lead.phone" :href="'tel:' + lead.phone" class="flex items-center gap-1.5 hover:text-white transition-colors">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                                            {{ lead.phone }}
                                        </a>
                                        <a v-if="lead.email" :href="'mailto:' + lead.email" class="flex items-center gap-1.5 hover:text-white transition-colors">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                            {{ lead.email }}
                                        </a>
                                        <span v-if="lead.city" class="flex items-center gap-1.5">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                            {{ lead.city }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- Quick Action Buttons -->
                            <div class="flex items-center gap-2 flex-shrink-0">
                                <!-- Edit Lead -->
                                <Link
                                    :href="`/secretary/crm/leads/${lead.id}/edit`"
                                    class="w-11 h-11 rounded-xl bg-white/20 hover:bg-white/30 backdrop-blur-sm text-white flex items-center justify-center shadow-lg hover:shadow-xl transition-all duration-200 hover:-translate-y-0.5 border border-white/10"
                                    :title="isRtl ? 'تعديل' : 'Edit'"
                                >
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                </Link>
                            </div>
                            <div class="flex items-center gap-2 flex-shrink-0">
                                <button
                                    v-if="lead.phone"
                                    @click="window.open('tel:' + lead.phone)"
                                    class="w-11 h-11 rounded-xl bg-green-500 hover:bg-green-400 text-white flex items-center justify-center shadow-lg hover:shadow-xl transition-all duration-200 hover:-translate-y-0.5"
                                    :title="isRtl ? 'اتصال' : 'Call'"
                                >
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                                </button>
                                <button
                                    v-if="lead.phone"
                                    @click="window.open('https://wa.me/' + lead.phone.replace(/[^0-9]/g, ''))"
                                    class="w-11 h-11 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-white flex items-center justify-center shadow-lg hover:shadow-xl transition-all duration-200 hover:-translate-y-0.5"
                                    :title="isRtl ? 'واتساب' : 'WhatsApp'"
                                >
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347zM12 2C6.477 2 2 6.477 2 12c0 1.89.525 3.66 1.438 5.168L2 22l4.832-1.438A9.955 9.955 0 0012 22c5.523 0 10-4.477 10-10S17.523 2 12 2z"/></svg>
                                </button>
                                <button
                                    v-if="lead.email"
                                    @click="window.open('mailto:' + lead.email)"
                                    class="w-11 h-11 rounded-xl bg-blue-500 hover:bg-blue-400 text-white flex items-center justify-center shadow-lg hover:shadow-xl transition-all duration-200 hover:-translate-y-0.5"
                                    :title="isRtl ? 'بريد إلكتروني' : 'Email'"
                                >
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                </button>
                                <button
                                    v-if="lead.phone"
                                    @click="window.open('sms:' + lead.phone)"
                                    class="w-11 h-11 rounded-xl bg-purple-500 hover:bg-purple-400 text-white flex items-center justify-center shadow-lg hover:shadow-xl transition-all duration-200 hover:-translate-y-0.5"
                                    :title="isRtl ? 'رسالة نصية' : 'SMS'"
                                >
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Pipeline Progress Bar -->
                <div class="bg-white border-b border-gray-200 -mt-10 pt-12 pb-4 px-4 sm:px-6 lg:px-8">
                    <div class="max-w-7xl mx-auto">
                        <p class="text-xs font-medium text-gray-500 mb-3 uppercase tracking-wider">
                            {{ isRtl ? 'مسار التحويل' : 'Conversion Pipeline' }}
                        </p>
                        <div class="flex items-center gap-1">
                            <template v-for="(status, idx) in pipelineStatuses" :key="status">
                                <div class="flex-1 relative group">
                                    <div
                                        class="h-2.5 rounded-full transition-all duration-500"
                                        :class="idx <= currentPipelineIndex
                                            ? 'bg-teal-500 shadow-sm'
                                            : 'bg-gray-200'"
                                    ></div>
                                    <div
                                        class="absolute -top-1 left-1/2 -translate-x-1/2 w-4.5 h-4.5 rounded-full border-2 transition-all duration-500"
                                        :class="idx === currentPipelineIndex
                                            ? 'bg-teal-500 border-white shadow-md scale-125'
                                            : idx < currentPipelineIndex
                                                ? 'bg-teal-400 border-teal-300 scale-100'
                                                : 'bg-gray-200 border-gray-100 scale-75'"
                                        style="width: 18px; height: 18px;"
                                    ></div>
                                    <!-- Tooltip -->
                                    <div class="absolute top-6 left-1/2 -translate-x-1/2 opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none z-10">
                                        <div class="bg-gray-800 text-white text-xs rounded-md px-2 py-1 whitespace-nowrap">
                                            {{ isRtl ? statusLabels[status]?.ar : statusLabels[status]?.en }}
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Content: 2-col layout -->
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 -mt-2 pt-6">
                <div class="flex flex-col lg:flex-row gap-6">

                    <!-- Main Content Area (2/3) -->
                    <div
                        class="flex-1 lg:w-2/3 transition-all duration-700 delay-200 ease-out"
                        :class="mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-6'"
                    >
                        <!-- Tabs -->
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                            <div class="flex border-b border-gray-200">
                                <button
                                    v-for="tab in [
                                        { key: 'activity', en: 'Activities', ar: 'النشاطات', icon: 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z' },
                                        { key: 'followups', en: 'Follow-ups', ar: 'المتابعات', icon: 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z' },
                                        { key: 'communication', en: 'Communication', ar: 'التواصل', icon: 'M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z' },
                                    ]"
                                    :key="tab.key"
                                    @click="switchTab(tab.key)"
                                    class="flex-1 flex items-center justify-center gap-2 px-4 py-3.5 text-sm font-medium transition-all duration-200 border-b-2 -mb-px"
                                    :class="activeTab === tab.key
                                        ? 'text-teal-700 border-teal-500 bg-teal-50/50'
                                        : 'text-gray-500 border-transparent hover:text-gray-700 hover:border-gray-300'"
                                >
                                    <svg class="w-4.5 h-4.5" style="width:18px;height:18px" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="tab.icon"/>
                                    </svg>
                                    {{ isRtl ? tab.ar : tab.en }}
                                </button>
                            </div>

                            <!-- Tab Content with animation -->
                            <div class="transition-all duration-200" :class="tabTransition ? 'opacity-0 translate-y-2' : 'opacity-100 translate-y-0'">

                                <!-- ACTIVITY TAB -->
                                <div v-if="activeTab === 'activity'" class="p-5">
                                    <!-- Activity Log Form -->
                                    <form @submit.prevent="submitActivity" class="bg-gray-50 rounded-xl p-4 mb-6 border border-gray-100">
                                        <h3 class="text-sm font-semibold text-gray-700 mb-3">
                                            {{ isRtl ? 'تسجيل نشاط جديد' : 'Log New Activity' }}
                                        </h3>
                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-3">
                                            <div>
                                                <label class="block text-xs font-medium text-gray-600 mb-1">{{ isRtl ? 'النوع' : 'Type' }}</label>
                                                <select v-model="activityForm.type" class="w-full rounded-lg border-gray-300 text-sm focus:ring-teal-500 focus:border-teal-500">
                                                    <option v-for="at in activityTypes" :key="at.value" :value="at.value">
                                                        {{ isRtl ? at.label.ar : at.label.en }}
                                                    </option>
                                                </select>
                                            </div>
                                            <div>
                                                <label class="block text-xs font-medium text-gray-600 mb-1">{{ isRtl ? 'الاتجاه' : 'Direction' }}</label>
                                                <div class="flex gap-3 mt-1.5">
                                                    <label class="flex items-center gap-1.5 text-sm text-gray-600 cursor-pointer">
                                                        <input type="radio" v-model="activityForm.direction" value="outbound" class="text-teal-600 focus:ring-teal-500"/>
                                                        {{ isRtl ? 'صادر' : 'Outbound' }}
                                                    </label>
                                                    <label class="flex items-center gap-1.5 text-sm text-gray-600 cursor-pointer">
                                                        <input type="radio" v-model="activityForm.direction" value="inbound" class="text-teal-600 focus:ring-teal-500"/>
                                                        {{ isRtl ? 'وارد' : 'Inbound' }}
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <label class="block text-xs font-medium text-gray-600 mb-1">{{ isRtl ? 'الوصف' : 'Description' }}</label>
                                            <textarea
                                                v-model="activityForm.description"
                                                rows="2"
                                                class="w-full rounded-lg border-gray-300 text-sm focus:ring-teal-500 focus:border-teal-500 resize-none"
                                                :placeholder="isRtl ? 'أضف وصفاً...' : 'Add description...'"
                                            ></textarea>
                                        </div>
                                        <div class="mb-3">
                                            <label class="block text-xs font-medium text-gray-600 mb-1">{{ isRtl ? 'النتيجة' : 'Outcome' }}</label>
                                            <select v-model="activityForm.outcome" class="w-full rounded-lg border-gray-300 text-sm focus:ring-teal-500 focus:border-teal-500">
                                                <option value="">{{ isRtl ? '-- اختر --' : '-- Select --' }}</option>
                                                <option value="answered">{{ isRtl ? 'تم الرد' : 'Answered' }}</option>
                                                <option value="no_answer">{{ isRtl ? 'لا رد' : 'No Answer' }}</option>
                                                <option value="busy">{{ isRtl ? 'مشغول' : 'Busy' }}</option>
                                                <option value="voicemail">{{ isRtl ? 'بريد صوتي' : 'Voicemail' }}</option>
                                                <option value="interested">{{ isRtl ? 'مهتم' : 'Interested' }}</option>
                                                <option value="not_interested">{{ isRtl ? 'غير مهتم' : 'Not Interested' }}</option>
                                                <option value="callback">{{ isRtl ? 'إعادة اتصال' : 'Callback' }}</option>
                                            </select>
                                        </div>
                                        <div class="flex justify-end">
                                            <button
                                                type="submit"
                                                :disabled="activityForm.processing || !activityForm.description"
                                                class="px-5 py-2 bg-teal-600 text-white text-sm font-medium rounded-lg hover:bg-teal-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                                            >
                                                {{ activityForm.processing
                                                    ? (isRtl ? 'جارٍ الحفظ...' : 'Saving...')
                                                    : (isRtl ? 'حفظ النشاط' : 'Save Activity') }}
                                            </button>
                                        </div>
                                    </form>

                                    <!-- Activity Timeline -->
                                    <div v-if="activities && activities.length > 0" class="relative">
                                        <div class="absolute top-0 bottom-0 w-px bg-gray-200" :class="isRtl ? 'right-5' : 'left-5'"></div>
                                        <div
                                            v-for="(act, idx) in activities"
                                            :key="act.id"
                                            class="relative flex gap-4 pb-5 last:pb-0"
                                            :class="isRtl ? 'flex-row-reverse' : ''"
                                        >
                                            <!-- Icon -->
                                            <div
                                                class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0 z-10"
                                                :class="activityTypeColors[act.type] || 'text-gray-500 bg-gray-100'"
                                            >
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="getActivityIcon(act.type)"/>
                                                </svg>
                                            </div>
                                            <!-- Content -->
                                            <div class="flex-1 bg-white rounded-xl border border-gray-100 p-3.5 hover:shadow-sm transition-shadow">
                                                <div class="flex items-start justify-between gap-2 mb-1">
                                                    <div>
                                                        <span class="text-sm font-semibold text-gray-800">{{ act.subject || act.type }}</span>
                                                        <span v-if="act.direction" class="text-xs text-gray-400 mx-1.5">
                                                            {{ act.direction === 'inbound' ? (isRtl ? '← وارد' : '← Inbound') : (isRtl ? '→ صادر' : '→ Outbound') }}
                                                        </span>
                                                    </div>
                                                    <span class="text-xs text-gray-400 whitespace-nowrap">{{ timeAgo(act.created_at) }}</span>
                                                </div>
                                                <p v-if="act.description" class="text-sm text-gray-600 mb-1.5">{{ act.description }}</p>
                                                <div class="flex items-center gap-3 text-xs text-gray-400">
                                                    <span v-if="act.outcome" class="px-2 py-0.5 rounded-full bg-gray-100 text-gray-600">{{ act.outcome }}</span>
                                                    <span v-if="act.performer?.name">{{ act.performer.name }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div v-else class="text-center py-10 text-gray-400">
                                        <svg class="w-12 h-12 mx-auto mb-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                        <p class="text-sm">{{ isRtl ? 'لا توجد نشاطات بعد' : 'No activities yet' }}</p>
                                    </div>
                                </div>

                                <!-- FOLLOW-UPS TAB -->
                                <div v-if="activeTab === 'followups'" class="p-5">
                                    <!-- Add Follow-up button -->
                                    <div class="flex justify-between items-center mb-4">
                                        <h3 class="text-sm font-semibold text-gray-700">
                                            {{ isRtl ? 'المتابعات المجدولة' : 'Scheduled Follow-ups' }}
                                        </h3>
                                        <button
                                            @click="showFollowUpForm = !showFollowUpForm"
                                            class="px-3.5 py-1.5 bg-teal-600 text-white text-xs font-medium rounded-lg hover:bg-teal-700 transition-colors flex items-center gap-1.5"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                            {{ isRtl ? 'جدولة متابعة' : 'Schedule Follow-up' }}
                                        </button>
                                    </div>

                                    <!-- Follow-up Form -->
                                    <Transition
                                        enter-active-class="transition-all duration-300 ease-out"
                                        enter-from-class="opacity-0 -translate-y-3"
                                        enter-to-class="opacity-100 translate-y-0"
                                        leave-active-class="transition-all duration-200 ease-in"
                                        leave-from-class="opacity-100 translate-y-0"
                                        leave-to-class="opacity-0 -translate-y-3"
                                    >
                                        <form v-if="showFollowUpForm" @submit.prevent="submitFollowUp" class="bg-teal-50 rounded-xl p-4 mb-5 border border-teal-100">
                                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-3">
                                                <div>
                                                    <label class="block text-xs font-medium text-gray-600 mb-1">{{ isRtl ? 'النوع' : 'Type' }}</label>
                                                    <select v-model="followUpForm.type" class="w-full rounded-lg border-gray-300 text-sm focus:ring-teal-500 focus:border-teal-500">
                                                        <option value="call">{{ isRtl ? 'مكالمة' : 'Call' }}</option>
                                                        <option value="whatsapp">{{ isRtl ? 'واتساب' : 'WhatsApp' }}</option>
                                                        <option value="email">{{ isRtl ? 'بريد' : 'Email' }}</option>
                                                        <option value="sms">{{ isRtl ? 'رسالة' : 'SMS' }}</option>
                                                        <option value="meeting">{{ isRtl ? 'اجتماع' : 'Meeting' }}</option>
                                                    </select>
                                                </div>
                                                <div>
                                                    <label class="block text-xs font-medium text-gray-600 mb-1">{{ isRtl ? 'الموعد' : 'Scheduled At' }}</label>
                                                    <input
                                                        type="datetime-local"
                                                        v-model="followUpForm.scheduled_at"
                                                        class="w-full rounded-lg border-gray-300 text-sm focus:ring-teal-500 focus:border-teal-500"
                                                    />
                                                </div>
                                            </div>
                                            <div class="mb-3">
                                                <label class="block text-xs font-medium text-gray-600 mb-1">{{ isRtl ? 'ملاحظات' : 'Notes' }}</label>
                                                <textarea
                                                    v-model="followUpForm.notes"
                                                    rows="2"
                                                    class="w-full rounded-lg border-gray-300 text-sm focus:ring-teal-500 focus:border-teal-500 resize-none"
                                                    :placeholder="isRtl ? 'ملاحظات اختيارية...' : 'Optional notes...'"
                                                ></textarea>
                                            </div>
                                            <div class="flex justify-end gap-2">
                                                <button type="button" @click="showFollowUpForm = false" class="px-4 py-1.5 text-sm text-gray-600 hover:text-gray-800 transition-colors">
                                                    {{ isRtl ? 'إلغاء' : 'Cancel' }}
                                                </button>
                                                <button
                                                    type="submit"
                                                    :disabled="followUpForm.processing || !followUpForm.scheduled_at"
                                                    class="px-5 py-1.5 bg-teal-600 text-white text-sm font-medium rounded-lg hover:bg-teal-700 disabled:opacity-50 transition-colors"
                                                >
                                                    {{ followUpForm.processing ? (isRtl ? 'جارٍ الحفظ...' : 'Saving...') : (isRtl ? 'حفظ' : 'Save') }}
                                                </button>
                                            </div>
                                        </form>
                                    </Transition>

                                    <!-- Follow-ups List -->
                                    <div v-if="followUps && followUps.length > 0" class="space-y-3">
                                        <div
                                            v-for="fu in followUps"
                                            :key="fu.id"
                                            class="bg-white rounded-xl border border-gray-100 p-4 hover:shadow-sm transition-shadow"
                                        >
                                            <div class="flex items-start justify-between gap-3">
                                                <div class="flex-1">
                                                    <div class="flex items-center gap-2 mb-1.5">
                                                        <span
                                                            class="px-2 py-0.5 rounded-full text-xs font-medium border"
                                                            :class="followUpStatusColors[fu.status] || 'bg-gray-100 text-gray-700'"
                                                        >
                                                            {{ isRtl ? (followUpStatusLabels[fu.status]?.ar || fu.status) : (followUpStatusLabels[fu.status]?.en || fu.status) }}
                                                        </span>
                                                        <span class="text-xs font-medium text-gray-500 capitalize">{{ fu.type }}</span>
                                                    </div>
                                                    <p class="text-sm text-gray-700 mb-1">
                                                        <span class="text-gray-400">{{ isRtl ? 'مجدولة: ' : 'Scheduled: ' }}</span>
                                                        {{ formatDateTime(fu.scheduled_at) }}
                                                    </p>
                                                    <p v-if="fu.completed_at" class="text-sm text-gray-500 mb-1">
                                                        <span class="text-gray-400">{{ isRtl ? 'مكتملة: ' : 'Completed: ' }}</span>
                                                        {{ formatDateTime(fu.completed_at) }}
                                                    </p>
                                                    <p v-if="fu.notes" class="text-sm text-gray-600 mt-1">{{ fu.notes }}</p>
                                                    <p v-if="fu.result" class="text-sm text-teal-700 mt-1 font-medium">{{ fu.result }}</p>
                                                    <p v-if="fu.assigned_user?.name" class="text-xs text-gray-400 mt-1.5">
                                                        {{ isRtl ? 'المسؤول: ' : 'Assigned: ' }}{{ fu.assigned_user.name }}
                                                    </p>
                                                </div>

                                                <!-- Actions for pending follow-ups -->
                                                <div v-if="fu.status === 'pending'" class="flex items-center gap-1.5 flex-shrink-0">
                                                    <button
                                                        @click="openComplete(fu)"
                                                        class="px-2.5 py-1.5 bg-green-50 text-green-700 text-xs font-medium rounded-lg hover:bg-green-100 transition-colors border border-green-200"
                                                    >
                                                        {{ isRtl ? 'إكمال' : 'Complete' }}
                                                    </button>
                                                    <button
                                                        @click="missFollowUp(fu.id)"
                                                        class="px-2.5 py-1.5 bg-red-50 text-red-700 text-xs font-medium rounded-lg hover:bg-red-100 transition-colors border border-red-200"
                                                    >
                                                        {{ isRtl ? 'فائت' : 'Miss' }}
                                                    </button>
                                                </div>
                                            </div>

                                            <!-- Complete form inline -->
                                            <Transition
                                                enter-active-class="transition-all duration-200 ease-out"
                                                enter-from-class="opacity-0 scale-95"
                                                enter-to-class="opacity-100 scale-100"
                                                leave-active-class="transition-all duration-150 ease-in"
                                                leave-from-class="opacity-100 scale-100"
                                                leave-to-class="opacity-0 scale-95"
                                            >
                                                <div v-if="completingFollowUp === fu.id" class="mt-3 pt-3 border-t border-gray-100">
                                                    <form @submit.prevent="submitComplete(fu.id)" class="flex gap-2">
                                                        <input
                                                            v-model="completeForm.result"
                                                            type="text"
                                                            class="flex-1 rounded-lg border-gray-300 text-sm focus:ring-teal-500 focus:border-teal-500"
                                                            :placeholder="isRtl ? 'نتيجة المتابعة...' : 'Follow-up result...'"
                                                        />
                                                        <button
                                                            type="submit"
                                                            :disabled="completeForm.processing"
                                                            class="px-4 py-2 bg-green-600 text-white text-xs font-medium rounded-lg hover:bg-green-700 disabled:opacity-50 transition-colors"
                                                        >
                                                            {{ isRtl ? 'تأكيد' : 'Confirm' }}
                                                        </button>
                                                        <button
                                                            type="button"
                                                            @click="completingFollowUp = null"
                                                            class="px-3 py-2 text-xs text-gray-500 hover:text-gray-700 transition-colors"
                                                        >
                                                            {{ isRtl ? 'إلغاء' : 'Cancel' }}
                                                        </button>
                                                    </form>
                                                </div>
                                            </Transition>
                                        </div>
                                    </div>
                                    <div v-else class="text-center py-10 text-gray-400">
                                        <svg class="w-12 h-12 mx-auto mb-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                        </svg>
                                        <p class="text-sm">{{ isRtl ? 'لا توجد متابعات مجدولة' : 'No follow-ups scheduled' }}</p>
                                    </div>
                                </div>

                                <!-- COMMUNICATION TAB -->
                                <div v-if="activeTab === 'communication'" class="p-5">
                                    <h3 class="text-sm font-semibold text-gray-700 mb-4">
                                        {{ isRtl ? 'إرسال سريع' : 'Quick Send' }}
                                    </h3>

                                    <form @submit.prevent="submitQuickSend" class="space-y-4">
                                        <!-- Channel Selector -->
                                        <div>
                                            <label class="block text-xs font-medium text-gray-600 mb-2">{{ isRtl ? 'القناة' : 'Channel' }}</label>
                                            <div class="flex gap-2">
                                                <button
                                                    v-for="ch in [
                                                        { value: 'whatsapp', label: 'WhatsApp', color: 'emerald' },
                                                        { value: 'sms', label: 'SMS', color: 'purple' },
                                                        { value: 'email', label: 'Email', color: 'blue' },
                                                    ]"
                                                    :key="ch.value"
                                                    type="button"
                                                    @click="quickSendForm.channel = ch.value"
                                                    class="flex-1 py-2.5 px-3 rounded-xl text-sm font-medium border-2 transition-all duration-200"
                                                    :class="quickSendForm.channel === ch.value
                                                        ? `border-${ch.color}-500 bg-${ch.color}-50 text-${ch.color}-700 shadow-sm`
                                                        : 'border-gray-200 text-gray-500 hover:border-gray-300'"
                                                >
                                                    {{ ch.label }}
                                                </button>
                                            </div>
                                        </div>

                                        <!-- Language Toggle -->
                                        <div>
                                            <label class="block text-xs font-medium text-gray-600 mb-2">{{ isRtl ? 'اللغة' : 'Language' }}</label>
                                            <div class="flex bg-gray-100 rounded-lg p-0.5 w-fit">
                                                <button
                                                    type="button"
                                                    @click="quickSendForm.language = 'ar'"
                                                    class="px-4 py-1.5 rounded-md text-sm font-medium transition-all duration-200"
                                                    :class="quickSendForm.language === 'ar' ? 'bg-white text-teal-700 shadow-sm' : 'text-gray-500'"
                                                >
                                                    العربية
                                                </button>
                                                <button
                                                    type="button"
                                                    @click="quickSendForm.language = 'en'"
                                                    class="px-4 py-1.5 rounded-md text-sm font-medium transition-all duration-200"
                                                    :class="quickSendForm.language === 'en' ? 'bg-white text-teal-700 shadow-sm' : 'text-gray-500'"
                                                >
                                                    English
                                                </button>
                                            </div>
                                        </div>

                                        <!-- Template Selector -->
                                        <div>
                                            <label class="block text-xs font-medium text-gray-600 mb-1">{{ isRtl ? 'القالب' : 'Template' }}</label>
                                            <select
                                                v-model="quickSendForm.template_id"
                                                class="w-full rounded-lg border-gray-300 text-sm focus:ring-teal-500 focus:border-teal-500"
                                            >
                                                <option value="">{{ isRtl ? '-- اختر قالب --' : '-- Select Template --' }}</option>
                                                <option v-for="tmpl in filteredTemplates" :key="tmpl.id" :value="tmpl.id">
                                                    {{ tmpl.name }}
                                                    <template v-if="tmpl.category"> ({{ tmpl.category }})</template>
                                                </option>
                                            </select>
                                        </div>

                                        <!-- Message Preview -->
                                        <Transition
                                            enter-active-class="transition-all duration-300 ease-out"
                                            enter-from-class="opacity-0 translate-y-2"
                                            enter-to-class="opacity-100 translate-y-0"
                                            leave-active-class="transition-all duration-200 ease-in"
                                            leave-from-class="opacity-100"
                                            leave-to-class="opacity-0"
                                        >
                                            <div v-if="previewMessage" class="bg-gray-50 rounded-xl p-4 border border-gray-200">
                                                <div class="flex items-center justify-between mb-2">
                                                    <span class="text-xs font-medium text-gray-500">{{ isRtl ? 'معاينة الرسالة' : 'Message Preview' }}</span>
                                                    <span v-if="selectedTemplate?.subject" class="text-xs text-gray-400">
                                                        {{ isRtl ? 'الموضوع: ' : 'Subject: ' }}{{ selectedTemplate.subject }}
                                                    </span>
                                                </div>
                                                <div
                                                    class="text-sm text-gray-700 whitespace-pre-wrap leading-relaxed rounded-lg bg-white p-3 border border-gray-100"
                                                    :dir="quickSendForm.language === 'ar' ? 'rtl' : 'ltr'"
                                                >
                                                    {{ previewMessage }}
                                                </div>
                                            </div>
                                        </Transition>

                                        <!-- Send Button -->
                                        <div class="flex justify-end pt-2">
                                            <button
                                                type="submit"
                                                :disabled="quickSendForm.processing || !quickSendForm.template_id"
                                                class="px-6 py-2.5 bg-teal-600 text-white text-sm font-medium rounded-xl hover:bg-teal-700 disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-200 flex items-center gap-2 shadow-sm hover:shadow-md"
                                            >
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                                                </svg>
                                                {{ quickSendForm.processing
                                                    ? (isRtl ? 'جارٍ الإرسال...' : 'Sending...')
                                                    : (isRtl ? 'إرسال' : 'Send') }}
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Side Panel (1/3) -->
                    <div
                        class="lg:w-1/3 space-y-5 transition-all duration-700 delay-300 ease-out"
                        :class="mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-6'"
                    >
                        <!-- Lead Info Card -->
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-5">
                            <h3 class="text-sm font-semibold text-gray-800 mb-4 flex items-center gap-2">
                                <svg class="w-4 h-4 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                {{ isRtl ? 'معلومات العميل المحتمل' : 'Lead Information' }}
                            </h3>

                            <div class="space-y-3.5">
                                <!-- Source -->
                                <div class="flex items-center justify-between text-sm">
                                    <span class="text-gray-500">{{ isRtl ? 'المصدر' : 'Source' }}</span>
                                    <span v-if="lead.source" class="flex items-center gap-1.5 font-medium text-gray-700">
                                        <span v-if="lead.source.icon" class="text-base" v-html="lead.source.icon"></span>
                                        {{ isRtl ? lead.source.name_ar : lead.source.name_en }}
                                    </span>
                                    <span v-else class="text-gray-400">-</span>
                                </div>

                                <!-- Campaign -->
                                <div class="flex items-center justify-between text-sm">
                                    <span class="text-gray-500">{{ isRtl ? 'الحملة' : 'Campaign' }}</span>
                                    <span class="font-medium text-gray-700">{{ lead.campaign?.name || '-' }}</span>
                                </div>

                                <!-- Gender -->
                                <div v-if="lead.gender" class="flex items-center justify-between text-sm">
                                    <span class="text-gray-500">{{ isRtl ? 'الجنس' : 'Gender' }}</span>
                                    <span class="font-medium text-gray-700">
                                        {{ lead.gender === 'male' ? (isRtl ? 'ذكر' : 'Male') : (isRtl ? 'أنثى' : 'Female') }}
                                    </span>
                                </div>

                                <!-- DOB -->
                                <div v-if="lead.date_of_birth" class="flex items-center justify-between text-sm">
                                    <span class="text-gray-500">{{ isRtl ? 'تاريخ الميلاد' : 'Date of Birth' }}</span>
                                    <span class="font-medium text-gray-700">{{ formatDate(lead.date_of_birth) }}</span>
                                </div>

                                <!-- Nationality -->
                                <div v-if="lead.nationality" class="flex items-center justify-between text-sm">
                                    <span class="text-gray-500">{{ isRtl ? 'الجنسية' : 'Nationality' }}</span>
                                    <span class="font-medium text-gray-700">{{ lead.nationality }}</span>
                                </div>

                                <!-- Phone 2 -->
                                <div v-if="lead.phone2" class="flex items-center justify-between text-sm">
                                    <span class="text-gray-500">{{ isRtl ? 'هاتف آخر' : 'Alt Phone' }}</span>
                                    <a :href="'tel:' + lead.phone2" class="font-medium text-teal-700 hover:underline">{{ lead.phone2 }}</a>
                                </div>

                                <div class="border-t border-gray-100 pt-3 mt-3 space-y-3">
                                    <!-- Created -->
                                    <div class="flex items-center justify-between text-sm">
                                        <span class="text-gray-500">{{ isRtl ? 'تاريخ الإنشاء' : 'Created' }}</span>
                                        <span class="font-medium text-gray-700">{{ formatDate(lead.created_at) }}</span>
                                    </div>

                                    <!-- Last contacted -->
                                    <div class="flex items-center justify-between text-sm">
                                        <span class="text-gray-500">{{ isRtl ? 'آخر تواصل' : 'Last Contact' }}</span>
                                        <span class="font-medium text-gray-700">{{ lead.last_contacted_at ? timeAgo(lead.last_contacted_at) : '-' }}</span>
                                    </div>

                                    <!-- Next follow-up -->
                                    <div class="flex items-center justify-between text-sm">
                                        <span class="text-gray-500">{{ isRtl ? 'المتابعة القادمة' : 'Next Follow-up' }}</span>
                                        <span class="font-medium" :class="lead.next_follow_up_at ? 'text-amber-600' : 'text-gray-400'">
                                            {{ lead.next_follow_up_at ? formatDateTime(lead.next_follow_up_at) : '-' }}
                                        </span>
                                    </div>

                                    <!-- Follow-up count -->
                                    <div class="flex items-center justify-between text-sm">
                                        <span class="text-gray-500">{{ isRtl ? 'عدد المتابعات' : 'Follow-up Count' }}</span>
                                        <span class="font-medium text-gray-700">{{ lead.follow_up_count || 0 }}</span>
                                    </div>

                                    <!-- Assigned to -->
                                    <div v-if="lead.assigned_to" class="flex items-center justify-between text-sm">
                                        <span class="text-gray-500">{{ isRtl ? 'مسؤول' : 'Assigned To' }}</span>
                                        <span class="font-medium text-gray-700">{{ lead.assigned_to }}</span>
                                    </div>
                                </div>

                                <!-- Notes -->
                                <div v-if="lead.notes" class="border-t border-gray-100 pt-3 mt-3">
                                    <p class="text-xs font-medium text-gray-500 mb-1">{{ isRtl ? 'ملاحظات' : 'Notes' }}</p>
                                    <p class="text-sm text-gray-700 whitespace-pre-wrap">{{ lead.notes }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Patient Link Card -->
                        <div v-if="lead.patient" class="bg-gradient-to-br from-purple-50 to-indigo-50 rounded-2xl shadow-sm border border-purple-200 p-5">
                            <h3 class="text-sm font-semibold text-purple-800 mb-3 flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                {{ isRtl ? 'تم التحويل لمريض' : 'Converted to Patient' }}
                            </h3>
                            <div class="space-y-2 text-sm">
                                <div class="flex justify-between">
                                    <span class="text-purple-600">{{ isRtl ? 'الاسم' : 'Name' }}</span>
                                    <span class="font-medium text-purple-900">{{ lead.patient.full_name }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-purple-600">{{ isRtl ? 'رقم الملف' : 'File #' }}</span>
                                    <span class="font-medium text-purple-900">{{ lead.patient.file_number }}</span>
                                </div>
                            </div>
                            <Link
                                :href="'/secretary/patients/' + lead.patient.id"
                                class="mt-3 inline-flex items-center gap-1.5 text-xs font-medium text-purple-700 hover:text-purple-900 transition-colors"
                            >
                                {{ isRtl ? 'عرض ملف المريض' : 'View Patient File' }}
                                <svg class="w-3.5 h-3.5" :class="isRtl ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                            </Link>
                        </div>

                        <!-- Quick Status Change -->
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-5">
                            <h3 class="text-sm font-semibold text-gray-800 mb-3 flex items-center gap-2">
                                <svg class="w-4 h-4 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                {{ isRtl ? 'تغيير الحالة' : 'Change Status' }}
                            </h3>
                            <div class="grid grid-cols-1 gap-2">
                                <button
                                    v-for="s in allowedStatuses"
                                    :key="s"
                                    @click="changeStatus(s)"
                                    :disabled="lead.status === s || statusForm.processing"
                                    class="w-full text-start px-3.5 py-2.5 rounded-xl text-sm font-medium transition-all duration-200 border"
                                    :class="lead.status === s
                                        ? 'bg-teal-50 border-teal-300 text-teal-800 cursor-default'
                                        : 'border-gray-200 text-gray-600 hover:border-teal-300 hover:bg-teal-50 hover:text-teal-700'"
                                >
                                    <div class="flex items-center justify-between">
                                        <span>{{ isRtl ? statusLabels[s]?.ar : statusLabels[s]?.en }}</span>
                                        <svg v-if="lead.status === s" class="w-4 h-4 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                        </svg>
                                    </div>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </SecretaryLayout>
</template>
