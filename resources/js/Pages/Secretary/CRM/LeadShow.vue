<script setup>
import { ref, computed, watch, onMounted, onBeforeUnmount } from 'vue';
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

/* ── Prev/Next lead navigation ────────────────────── */
const prevLeadId = ref(null);
const nextLeadId = ref(null);
const leadListPosition = ref('');

(() => {
    try {
        const stored = JSON.parse(localStorage.getItem('crm_lead_list') || '[]');
        if (!stored.length) return;
        const currentIdx = stored.indexOf(props.lead.id);
        if (currentIdx === -1) return;
        if (currentIdx > 0) prevLeadId.value = stored[currentIdx - 1];
        if (currentIdx < stored.length - 1) nextLeadId.value = stored[currentIdx + 1];
        leadListPosition.value = `${currentIdx + 1} / ${stored.length}`;
    } catch {}
})();

function goToPrevLead() {
    if (prevLeadId.value) router.get(`/secretary/crm/leads/${prevLeadId.value}`);
}
function goToNextLead() {
    if (nextLeadId.value) router.get(`/secretary/crm/leads/${nextLeadId.value}`);
}

// Tabs — persisted to localStorage
const savedTab = (() => { try { return localStorage.getItem('crm_leadshow_tab'); } catch { return null; } })();
const activeTab = ref(savedTab || 'activity');
const tabTransition = ref(false);
function switchTab(tab) {
    if (tab === activeTab.value) return;
    tabTransition.value = true;
    setTimeout(() => {
        activeTab.value = tab;
        try { localStorage.setItem('crm_leadshow_tab', tab); } catch {}
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
    1: { color: 'bg-red-100 text-red-700', label: { en: 'Hot', ar: 'ساخن' }, icon: 'M17.657 18.657A8 8 0 016.343 7.343S7 9 9 10c0-2 .5-5 2.986-7C14 5 16.09 5.777 17.656 7.343A7.975 7.975 0 0120 13a7.975 7.975 0 01-2.343 5.657z' },
    2: { color: 'bg-amber-100 text-amber-700', label: { en: 'Warm', ar: 'دافئ' }, icon: 'M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z' },
    3: { color: 'bg-blue-100 text-blue-700', label: { en: 'Cold', ar: 'بارد' }, icon: 'M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z' },
    hot: { color: 'bg-red-100 text-red-700', label: { en: 'Hot', ar: 'ساخن' }, icon: 'M17.657 18.657A8 8 0 016.343 7.343S7 9 9 10c0-2 .5-5 2.986-7C14 5 16.09 5.777 17.656 7.343A7.975 7.975 0 0120 13a7.975 7.975 0 01-2.343 5.657z' },
    warm: { color: 'bg-amber-100 text-amber-700', label: { en: 'Warm', ar: 'دافئ' }, icon: 'M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z' },
    cold: { color: 'bg-blue-100 text-blue-700', label: { en: 'Cold', ar: 'بارد' }, icon: 'M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z' },
};

// Status form
const statusForm = useForm({ status: '' });
function changeStatus(newStatus) {
    statusForm.status = newStatus;
    statusForm.post(`/secretary/crm/leads/${props.lead.id}/status`, {
        preserveScroll: true,
    });
}

// Priority change inline
const showPriorityPicker = ref(false);
const priorityChanging = ref(false);
const priorityOptions = [
    { value: 1, en: 'Hot', ar: '\u0633\u0627\u062E\u0646', color: 'bg-red-100 text-red-700 border-red-300 hover:bg-red-200' },
    { value: 2, en: 'Warm', ar: '\u062F\u0627\u0641\u0626', color: 'bg-amber-100 text-amber-700 border-amber-300 hover:bg-amber-200' },
    { value: 3, en: 'Cold', ar: '\u0628\u0627\u0631\u062F', color: 'bg-blue-100 text-blue-700 border-blue-300 hover:bg-blue-200' },
];

function changePriority(newPriority) {
    if (priorityChanging.value) return;
    priorityChanging.value = true;
    router.post(`/secretary/crm/leads/${props.lead.id}/priority`, {
        priority: newPriority,
    }, {
        preserveScroll: true,
        onFinish: () => { priorityChanging.value = false; showPriorityPicker.value = false; },
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
    quickSendForm.post(`/secretary/crm/leads/${props.lead.id}/quick-send`, {
        preserveScroll: true,
        onSuccess: () => quickSendForm.reset(),
    });
}

// Convert to Patient
const showConvertModal = ref(false);
const convertForm = useForm({
    patient_id: '',
    booking_notes: '',
});

function submitConvert() {
    convertForm.post(`/secretary/crm/leads/${props.lead.id}/convert`, {
        preserveScroll: true,
        onSuccess: () => {
            showConvertModal.value = false;
            convertForm.reset();
        },
    });
}

// Mark as Lost
const showLostModal = ref(false);
const lostForm = useForm({
    loss_reason: '',
});

function submitLost() {
    lostForm.post(`/secretary/crm/leads/${props.lead.id}/lost`, {
        preserveScroll: true,
        onSuccess: () => {
            showLostModal.value = false;
            lostForm.reset();
        },
    });
}

// Reschedule Follow-up
const reschedulingFollowUp = ref(null);
const rescheduleForm = useForm({
    scheduled_at: '',
    notes: '',
});

function openReschedule(fu) {
    reschedulingFollowUp.value = fu.id;
    rescheduleForm.scheduled_at = '';
    rescheduleForm.notes = fu.notes || '';
}

function submitReschedule(fuId) {
    rescheduleForm.post(`/secretary/crm/follow-ups/${fuId}/reschedule`, {
        preserveScroll: true,
        onSuccess: () => {
            reschedulingFollowUp.value = null;
            rescheduleForm.reset();
        },
    });
}

// Snooze follow-up
const snoozeOpen = ref(null);
const snoozeOptions = [
    { key: '1h', en: '1 Hour', ar: 'ساعة واحدة' },
    { key: 'tomorrow', en: 'Tomorrow 9 AM', ar: 'غداً 9 صباحاً' },
    { key: '1w', en: '1 Week', ar: 'أسبوع واحد' },
];

function toggleSnooze(fuId) {
    snoozeOpen.value = snoozeOpen.value === fuId ? null : fuId;
}

function snoozeFollowUp(fuId, key) {
    let scheduledAt;
    const now = new Date();
    if (key === '1h') {
        scheduledAt = new Date(now.getTime() + 60 * 60 * 1000);
    } else if (key === 'tomorrow') {
        scheduledAt = new Date(now);
        scheduledAt.setDate(scheduledAt.getDate() + 1);
        scheduledAt.setHours(9, 0, 0, 0);
    } else if (key === '1w') {
        scheduledAt = new Date(now.getTime() + 7 * 24 * 60 * 60 * 1000);
        scheduledAt.setHours(9, 0, 0, 0);
    }

    const isoStr = scheduledAt.getFullYear() + '-' +
        String(scheduledAt.getMonth() + 1).padStart(2, '0') + '-' +
        String(scheduledAt.getDate()).padStart(2, '0') + 'T' +
        String(scheduledAt.getHours()).padStart(2, '0') + ':' +
        String(scheduledAt.getMinutes()).padStart(2, '0');

    router.post(`/secretary/crm/follow-ups/${fuId}/reschedule`, {
        scheduled_at: isoStr,
        notes: isRtl.value ? 'تم التأجيل' : 'Snoozed',
    }, {
        preserveScroll: true,
        onSuccess: () => { snoozeOpen.value = null; },
    });
}

// Quick note templates
const quickNoteTemplates = [
    { en: 'Called, no answer', ar: 'اتصلت، لا رد' },
    { en: 'Left voicemail', ar: 'تركت رسالة صوتية' },
    { en: 'Interested, will follow up', ar: 'مهتم، سيتم المتابعة' },
    { en: 'Requested callback', ar: 'طلب معاودة الاتصال' },
    { en: 'Sent price list', ar: 'تم إرسال قائمة الأسعار' },
    { en: 'Booked appointment', ar: 'تم حجز موعد' },
    { en: 'Not interested', ar: 'غير مهتم' },
    { en: 'Number not working', ar: 'الرقم لا يعمل' },
];

function useQuickNote(template) {
    activityForm.description = isRtl.value ? template.ar : template.en;
}

/* Quick-log: one-click submit a note instantly */
const quickLogSaving = ref(null);
function quickLogNote(template) {
    if (quickLogSaving.value) return;
    const text = isRtl.value ? template.ar : template.en;
    quickLogSaving.value = text;
    router.post(`/secretary/crm/leads/${props.lead.id}/activity`, {
        type: 'note',
        description: text,
        direction: '',
        outcome: '',
    }, {
        preserveScroll: true,
        onSuccess: () => { quickLogSaving.value = null; },
        onError: () => { quickLogSaving.value = null; },
    });
}

/* Contact auto-log: opens the link AND logs an activity */
const contactAutoLogSaving = ref(null);
function contactAutoLog(type) {
    if (contactAutoLogSaving.value) return;
    contactAutoLogSaving.value = type;
    const descriptions = {
        call: isRtl.value ? 'تم إجراء مكالمة' : 'Initiated a call',
        whatsapp: isRtl.value ? 'تم فتح محادثة واتساب' : 'Opened WhatsApp chat',
        email: isRtl.value ? 'تم إرسال بريد إلكتروني' : 'Sent an email',
    };
    router.post(`/secretary/crm/leads/${props.lead.id}/activity`, {
        type: type === 'whatsapp' ? 'whatsapp' : type === 'email' ? 'email' : 'call',
        description: descriptions[type] || '',
        direction: 'outbound',
        outcome: '',
    }, {
        preserveScroll: true,
        onFinish: () => { contactAutoLogSaving.value = null; },
    });
}

// Activity filter
const activitySearchQuery = ref('');
const activityTypeFilter = ref('all');

const filteredGroupedActivities = computed(() => {
    if (!groupedActivities.value?.length) return [];
    const q = activitySearchQuery.value.trim().toLowerCase();
    const typeF = activityTypeFilter.value;

    return groupedActivities.value.map(group => {
        let items = group.items;
        if (typeF !== 'all') items = items.filter(a => a.type === typeF);
        if (q) items = items.filter(a =>
            (a.subject || '').toLowerCase().includes(q) ||
            (a.description || '').toLowerCase().includes(q)
        );
        return { ...group, items };
    }).filter(g => g.items.length > 0);
});

const lossReasons = [
    { en: 'Price too high', ar: 'السعر مرتفع' },
    { en: 'Chose competitor', ar: 'اختار منافس' },
    { en: 'Not interested anymore', ar: 'لم يعد مهتماً' },
    { en: 'Cannot be reached', ar: 'لا يمكن الوصول إليه' },
    { en: 'Wrong contact info', ar: 'معلومات اتصال خاطئة' },
    { en: 'Other', ar: 'سبب آخر' },
];

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

/* ---------- Copy to clipboard ---------- */
const copiedField = ref(null);
let copiedTimeout = null;
function copyToClipboard(text, field) {
    if (!text) return;
    navigator.clipboard.writeText(text).then(() => {
        copiedField.value = field;
        clearTimeout(copiedTimeout);
        copiedTimeout = setTimeout(() => { copiedField.value = null; }, 2000);
    }).catch(() => {});
}

const currentPipelineIndex = computed(() => {
    return pipelineStatuses.indexOf(props.lead.status);
});

const scoreAngle = computed(() => {
    return ((props.lead.score || 0) / 100) * 283;
});

/* ---------- Score breakdown ---------- */
const showScoreBreakdown = ref(false);
const scoreBreakdown = computed(() => {
    const lead = props.lead;
    const acts = props.activities || [];
    const fups = props.followUps || [];
    const factors = [];
    // Has phone
    factors.push({ en: 'Phone provided', ar: '\u0631\u0642\u0645 \u0627\u0644\u0647\u0627\u062A\u0641', pts: lead.phone ? 10 : 0, max: 10 });
    // Has email
    factors.push({ en: 'Email provided', ar: '\u0627\u0644\u0628\u0631\u064A\u062F \u0627\u0644\u0625\u0644\u0643\u062A\u0631\u0648\u0646\u064A', pts: lead.email ? 5 : 0, max: 5 });
    // Activity count (up to 30 pts)
    const actPts = Math.min(acts.length * 3, 30);
    factors.push({ en: 'Activities (' + acts.length + ')', ar: '\u0627\u0644\u0646\u0634\u0627\u0637\u0627\u062A (' + acts.length + ')', pts: actPts, max: 30 });
    // Follow-ups completed
    const completedFU = fups.filter(f => f.status === 'completed').length;
    const fuPts = Math.min(completedFU * 5, 20);
    factors.push({ en: 'Follow-ups done (' + completedFU + ')', ar: '\u0645\u062A\u0627\u0628\u0639\u0627\u062A \u0645\u0643\u062A\u0645\u0644\u0629 (' + completedFU + ')', pts: fuPts, max: 20 });
    // Pipeline progress
    const pIdx = pipelineStatuses.indexOf(lead.status);
    const pipePts = pIdx >= 0 ? Math.min((pIdx + 1) * 5, 25) : 0;
    factors.push({ en: 'Pipeline stage', ar: '\u0645\u0631\u062D\u0644\u0629 \u0627\u0644\u0642\u0645\u0639', pts: pipePts, max: 25 });
    // Priority bonus
    const prioPts = lead.priority === 1 ? 10 : lead.priority === 2 ? 5 : 0;
    factors.push({ en: 'Priority bonus', ar: '\u0645\u0643\u0627\u0641\u0623\u0629 \u0627\u0644\u0623\u0648\u0644\u0648\u064A\u0629', pts: prioPts, max: 10 });
    return factors;
});

// Grouped activities by date
const groupedActivities = computed(() => {
    if (!props.activities?.length) return [];
    const groups = {};
    props.activities.forEach(act => {
        const d = new Date(act.created_at);
        const key = d.toISOString().split('T')[0];
        if (!groups[key]) {
            groups[key] = { date: key, label: formatDateLabel(key), items: [] };
        }
        groups[key].items.push(act);
    });
    return Object.values(groups);
});

function formatDateLabel(dateStr) {
    const d = new Date(dateStr + 'T00:00:00');
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    const diff = Math.floor((today - d) / 86400000);
    if (diff === 0) return isRtl.value ? 'اليوم' : 'Today';
    if (diff === 1) return isRtl.value ? 'أمس' : 'Yesterday';
    if (diff < 7) return isRtl.value ? `منذ ${diff} أيام` : `${diff} days ago`;
    return d.toLocaleDateString(isRtl.value ? 'ar-SA' : 'en-US', { month: 'short', day: 'numeric', year: d.getFullYear() !== today.getFullYear() ? 'numeric' : undefined });
}

// Activity summary stats
const activityStats = computed(() => {
    if (!props.activities?.length) return { total: 0, calls: 0, whatsapp: 0, emails: 0 };
    const acts = props.activities;
    return {
        total: acts.length,
        calls: acts.filter(a => a.type === 'call').length,
        whatsapp: acts.filter(a => a.type === 'whatsapp').length,
        emails: acts.filter(a => a.type === 'email').length,
    };
});

/* ---------- Activity type mini-summary ---------- */
const activityTypeSummary = computed(() => {
    const acts = props.activities || [];
    if (!acts.length) return [];
    const types = [
        { key: 'call', en: 'Calls', ar: '\u0645\u0643\u0627\u0644\u0645\u0627\u062A', color: 'bg-green-100 text-green-700', iconColor: 'text-green-500' },
        { key: 'whatsapp', en: 'WhatsApp', ar: '\u0648\u0627\u062A\u0633\u0627\u0628', color: 'bg-emerald-100 text-emerald-700', iconColor: 'text-emerald-500' },
        { key: 'email', en: 'Email', ar: '\u0628\u0631\u064A\u062F', color: 'bg-blue-100 text-blue-700', iconColor: 'text-blue-500' },
        { key: 'sms', en: 'SMS', ar: '\u0631\u0633\u0627\u0626\u0644', color: 'bg-cyan-100 text-cyan-700', iconColor: 'text-cyan-500' },
        { key: 'meeting', en: 'Meetings', ar: '\u0627\u062C\u062A\u0645\u0627\u0639\u0627\u062A', color: 'bg-amber-100 text-amber-700', iconColor: 'text-amber-500' },
        { key: 'note', en: 'Notes', ar: '\u0645\u0644\u0627\u062D\u0638\u0627\u062A', color: 'bg-gray-100 text-gray-700', iconColor: 'text-gray-500' },
    ];
    return types.map(t => ({ ...t, count: acts.filter(a => a.type === t.key).length })).filter(t => t.count > 0);
});

function isRecentActivity(act) {
    if (!act.created_at) return false;
    return (Date.now() - new Date(act.created_at).getTime()) < 86400000;
}

// Quick stats
const leadAge = computed(() => {
    if (!props.lead.created_at) return 0;
    return Math.floor((Date.now() - new Date(props.lead.created_at).getTime()) / 86400000);
});

const pendingFollowUps = computed(() => {
    return (props.followUps || []).filter(f => f.status === 'pending').length;
});

const completedFollowUps = computed(() => {
    return (props.followUps || []).filter(f => f.status === 'completed').length;
});

/* ── Next follow-up countdown ────────────────────────── */
const nextFollowUp = computed(() => {
    const pending = (props.followUps || []).filter(f => f.status === 'pending' && f.scheduled_at);
    if (!pending.length) return null;
    pending.sort((a, b) => new Date(a.scheduled_at) - new Date(b.scheduled_at));
    const fu = pending[0];
    const diff = new Date(fu.scheduled_at).getTime() - Date.now();
    const isOverdue = diff < 0;
    const absDiff = Math.abs(diff);
    const mins = Math.floor(absDiff / 60000);
    const hrs = Math.floor(mins / 60);
    const days = Math.floor(hrs / 24);
    let label;
    if (days > 0) label = isRtl.value ? `${days} يوم` : `${days}d`;
    else if (hrs > 0) label = isRtl.value ? `${hrs} ساعة` : `${hrs}h`;
    else label = isRtl.value ? `${mins} دقيقة` : `${mins}m`;
    return { ...fu, isOverdue, label, type: fu.type };
});

// Print lead profile
function printLeadProfile() {
    const lead = props.lead;
    const statusText = isRtl.value ? (statusLabels[lead.status]?.ar || lead.status) : (statusLabels[lead.status]?.en || lead.status);
    const prioObj = priorityDisplay[lead.priority];
    const prioText = prioObj ? (isRtl.value ? prioObj.label.ar : prioObj.label.en) : '-';
    const printFrame = document.createElement('iframe');
    printFrame.style.display = 'none';
    document.body.appendChild(printFrame);
    const doc = printFrame.contentDocument || printFrame.contentWindow.document;
    doc.open();
    const activitiesRows = (props.activities || []).slice(0, 20).map(a => {
        const tr = `<tr><td style="padding:6px 10px;border-bottom:1px solid #eee;font-size:13px">${(a.type || '').replace(/</g, '&lt;')}</td><td style="padding:6px 10px;border-bottom:1px solid #eee;font-size:13px">${(a.subject || '-').replace(/</g, '&lt;')}</td><td style="padding:6px 10px;border-bottom:1px solid #eee;font-size:13px">${formatDateTime(a.created_at)}</td></tr>`;
        return tr;
    }).join('');
    const html = [
        '<!DOCTYPE html><html dir="' + (isRtl.value ? 'rtl' : 'ltr') + '"><head><title>' + (lead.full_name || '').replace(/</g, '&lt;') + '</title>',
        '<style>body{font-family:system-ui,sans-serif;padding:30px;color:#333}table{width:100%;border-collapse:collapse}th{background:#0d9488;color:white;text-align:start;padding:8px 10px;font-size:13px}.ig{display:grid;grid-template-columns:1fr 1fr;gap:12px;margin:20px 0}.ii{padding:10px;background:#f9fafb;border-radius:8px;border:1px solid #e5e7eb}.il{font-size:11px;color:#6b7280;text-transform:uppercase}.iv{font-size:15px;font-weight:600;margin-top:2px}h1{color:#0d9488;margin:0}</style></head><body>',
        '<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px"><div><h1>' + (lead.full_name || '').replace(/</g, '&lt;') + '</h1>',
        '<p style="color:#6b7280;margin:4px 0">' + statusText + ' | ' + prioText + ' | Score: ' + (lead.score || 0) + '</p></div>',
        '<div style="font-size:12px;color:#9ca3af">Aura Derma Clinic CRM</div></div>',
        '<div class="ig"><div class="ii"><div class="il">Phone</div><div class="iv">' + (lead.phone || '-') + '</div></div>',
        '<div class="ii"><div class="il">Email</div><div class="iv">' + (lead.email || '-').replace(/</g, '&lt;') + '</div></div>',
        '<div class="ii"><div class="il">City</div><div class="iv">' + (lead.city || '-').replace(/</g, '&lt;') + '</div></div>',
        '<div class="ii"><div class="il">Source</div><div class="iv">' + ((lead.source?.name_en || lead.source?.name_ar || '-')).replace(/</g, '&lt;') + '</div></div></div>',
        '<h3 style="margin-top:20px;color:#0d9488">' + (isRtl.value ? 'آخر النشاطات' : 'Recent Activities') + '</h3>',
        '<table><thead><tr><th>' + (isRtl.value ? 'النوع' : 'Type') + '</th><th>' + (isRtl.value ? 'الموضوع' : 'Subject') + '</th><th>' + (isRtl.value ? 'التاريخ' : 'Date') + '</th></tr></thead>',
        '<tbody>' + (activitiesRows || '<tr><td colspan="3" style="padding:20px;text-align:center;color:#9ca3af">-</td></tr>') + '</tbody></table>',
        '</body></html>',
    ].join('');
    doc.write(html);
    doc.close();
    setTimeout(() => {
        printFrame.contentWindow.print();
        setTimeout(() => document.body.removeChild(printFrame), 1000);
    }, 300);
}

// Copy phone
const phoneCopied = ref(false);
function copyPhone() {
    if (!props.lead.phone) return;
    navigator.clipboard.writeText(props.lead.phone).then(() => {
        phoneCopied.value = true;
        setTimeout(() => { phoneCopied.value = false; }, 2000);
    });
}

// WhatsApp link
function whatsappUrl(phone) {
    if (!phone) return '#';
    const clean = phone.replace(/[^0-9+]/g, '');
    return `https://wa.me/${clean.replace('+', '')}`;
}

/* ── Sticky header on scroll ────────────────────────── */
const showStickyHeader = ref(false);
function handleScroll() {
    showStickyHeader.value = window.scrollY > 260;
}
onMounted(() => {
    window.addEventListener('scroll', handleScroll, { passive: true });
    document.addEventListener('keydown', handleLeadShowKey);
});
onBeforeUnmount(() => {
    window.removeEventListener('scroll', handleScroll);
    document.removeEventListener('keydown', handleLeadShowKey);
});

/* ── Keyboard shortcuts ────────────────────────────── */
function handleLeadShowKey(e) {
    if (['INPUT', 'TEXTAREA', 'SELECT'].includes(document.activeElement?.tagName)) return;
    if (showConvertModal.value || showLostModal.value || showFollowUpForm.value) return;
    if (e.key === 'a' || e.key === 'A') {
        e.preventDefault();
        switchTab('activity');
        setTimeout(() => { document.querySelector('[data-activity-desc]')?.focus(); }, 300);
    }
    if (e.key === 'f' || e.key === 'F') { e.preventDefault(); showFollowUpForm.value = true; }
    if (e.key === 'p' || e.key === 'P') { e.preventDefault(); printLeadProfile(); }
    if (e.key === 'b' || e.key === 'B' || e.key === 'Backspace') {
        if (e.key === 'Backspace') return; // only B, not backspace
        e.preventDefault(); router.get('/secretary/crm/leads');
    }
    if (e.key === 'ArrowLeft' && !isRtl.value || e.key === 'ArrowRight' && isRtl.value) {
        if (prevLeadId.value) { e.preventDefault(); goToPrevLead(); }
    }
    if (e.key === 'ArrowRight' && !isRtl.value || e.key === 'ArrowLeft' && isRtl.value) {
        if (nextLeadId.value) { e.preventDefault(); goToNextLead(); }
    }
    if (e.key === 'Escape') {
        if (showScoreBreakdown.value) { showScoreBreakdown.value = false; return; }
        if (showPriorityPicker.value) { showPriorityPicker.value = false; return; }
    }
}
</script>

<template>
    <SecretaryLayout>
        <div class="min-h-screen bg-gray-50 pb-12" :dir="isRtl ? 'rtl' : 'ltr'">
            <!-- Sticky compact header -->
            <Teleport to="body">
                <Transition
                    enter-active-class="transition-all duration-300 ease-out"
                    enter-from-class="opacity-0 -translate-y-full"
                    enter-to-class="opacity-100 translate-y-0"
                    leave-active-class="transition-all duration-200 ease-in"
                    leave-from-class="opacity-100 translate-y-0"
                    leave-to-class="opacity-0 -translate-y-full"
                >
                    <div v-if="showStickyHeader" class="fixed top-0 inset-x-0 z-50 bg-white/95 backdrop-blur-md border-b border-gray-200 shadow-sm" :dir="isRtl ? 'rtl' : 'ltr'">
                        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-2.5 flex items-center justify-between gap-4">
                            <div class="flex items-center gap-3 min-w-0">
                                <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-teal-500 to-teal-700 flex items-center justify-center text-sm font-bold text-white flex-shrink-0">
                                    {{ initial }}
                                </div>
                                <div class="min-w-0">
                                    <h2 class="text-sm font-bold text-gray-900 truncate">{{ lead.full_name }}</h2>
                                    <div class="flex items-center gap-2">
                                        <span class="text-[10px] font-semibold px-1.5 py-0.5 rounded-full" :class="statusColors[lead.status] || 'bg-gray-100 text-gray-700'">
                                            {{ isRtl ? (statusLabels[lead.status]?.ar || lead.status) : (statusLabels[lead.status]?.en || lead.status) }}
                                        </span>
                                        <span class="text-[10px] text-gray-400 font-medium">{{ isRtl ? 'النقاط:' : 'Score:' }} {{ lead.score || 0 }}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <a v-if="lead.phone" :href="`tel:${lead.phone}`"
                                   class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold bg-teal-50 text-teal-700 hover:bg-teal-100 border border-teal-200 transition-all">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                                    <span class="hidden sm:inline">{{ isRtl ? 'اتصال' : 'Call' }}</span>
                                </a>
                                <a v-if="lead.phone" :href="whatsappUrl(lead.phone)" target="_blank"
                                   class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold bg-green-50 text-green-700 hover:bg-green-100 border border-green-200 transition-all">
                                    <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/></svg>
                                    <span class="hidden sm:inline">WA</span>
                                </a>
                                <button @click="showFollowUpForm = true"
                                   class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold bg-amber-50 text-amber-700 hover:bg-amber-100 border border-amber-200 transition-all">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                    <span class="hidden sm:inline">{{ isRtl ? 'متابعة' : 'Follow-up' }}</span>
                                </button>
                                <Link href="/secretary/crm/leads"
                                   class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold bg-gray-50 text-gray-600 hover:bg-gray-100 border border-gray-200 transition-all">
                                    <svg class="w-3.5 h-3.5" :class="isRtl ? 'rotate-180' : ''" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                                    <span class="hidden sm:inline">{{ isRtl ? 'رجوع' : 'Back' }}</span>
                                </Link>
                            </div>
                        </div>
                    </div>
                </Transition>
            </Teleport>

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
                        <!-- Back button + Prev/Next nav -->
                        <div class="flex items-center justify-between mb-6">
                            <div class="flex items-center gap-3">
                                <Link
                                    href="/secretary/crm/leads"
                                    class="inline-flex items-center gap-1.5 text-teal-100 hover:text-white text-sm transition-colors"
                                >
                                    <svg class="w-4 h-4" :class="isRtl ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                                    </svg>
                                    {{ isRtl ? 'العودة للعملاء المحتملين' : 'Back to Leads' }}
                                </Link>
                                <!-- Prev/Next lead navigation -->
                                <div v-if="prevLeadId || nextLeadId" class="hidden sm:flex items-center gap-1 ms-2 ps-3 border-s border-teal-500/30">
                                    <button @click="goToPrevLead" :disabled="!prevLeadId"
                                        class="w-7 h-7 rounded-lg flex items-center justify-center transition-all"
                                        :class="prevLeadId ? 'bg-white/10 hover:bg-white/20 text-teal-100 hover:text-white' : 'bg-white/5 text-teal-500/30 cursor-not-allowed'"
                                        :title="isRtl ? 'العميل السابق' : 'Previous lead'">
                                        <svg class="w-3.5 h-3.5" :class="isRtl ? 'rotate-180' : ''" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                                    </button>
                                    <span v-if="leadListPosition" class="text-[10px] text-teal-300/60 font-mono tabular-nums px-1">{{ leadListPosition }}</span>
                                    <button @click="goToNextLead" :disabled="!nextLeadId"
                                        class="w-7 h-7 rounded-lg flex items-center justify-center transition-all"
                                        :class="nextLeadId ? 'bg-white/10 hover:bg-white/20 text-teal-100 hover:text-white' : 'bg-white/5 text-teal-500/30 cursor-not-allowed'"
                                        :title="isRtl ? 'العميل التالي' : 'Next lead'">
                                        <svg class="w-3.5 h-3.5" :class="isRtl ? '' : 'rotate-180'" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                                    </button>
                                </div>
                            </div>
                            <!-- Keyboard hints -->
                            <div class="hidden lg:flex items-center gap-2 text-teal-200/50 text-[10px]">
                                <span class="flex items-center gap-1"><kbd class="px-1 py-0.5 bg-white/10 rounded text-[9px] font-mono">A</kbd> {{ isRtl ? 'نشاط' : 'Activity' }}</span>
                                <span class="flex items-center gap-1"><kbd class="px-1 py-0.5 bg-white/10 rounded text-[9px] font-mono">F</kbd> {{ isRtl ? 'متابعة' : 'Follow-up' }}</span>
                                <span class="flex items-center gap-1"><kbd class="px-1 py-0.5 bg-white/10 rounded text-[9px] font-mono">P</kbd> {{ isRtl ? 'طباعة' : 'Print' }}</span>
                                <span class="flex items-center gap-1"><kbd class="px-1 py-0.5 bg-white/10 rounded text-[9px] font-mono">B</kbd> {{ isRtl ? 'رجوع' : 'Back' }}</span>
                            </div>
                        </div>

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
                                        <!-- Priority badge (clickable) -->
                                        <div class="relative">
                                            <button
                                                v-if="lead.priority && priorityDisplay[lead.priority]"
                                                @click="showPriorityPicker = !showPriorityPicker"
                                                class="px-2.5 py-0.5 rounded-full text-xs font-semibold cursor-pointer transition-all duration-200 hover:ring-2 hover:ring-white/40"
                                                :class="priorityDisplay[lead.priority].color"
                                                :title="isRtl ? '\u0627\u0646\u0642\u0631 \u0644\u062A\u063A\u064A\u064A\u0631 \u0627\u0644\u0623\u0648\u0644\u0648\u064A\u0629' : 'Click to change priority'"
                                            >
                                                <svg class="w-3.5 h-3.5 inline-block" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="priorityDisplay[lead.priority].icon"/></svg>
                                                {{ isRtl ? priorityDisplay[lead.priority].label.ar : priorityDisplay[lead.priority].label.en }}
                                                <svg class="w-3 h-3 inline-block ms-0.5 opacity-60" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                                            </button>
                                            <Transition
                                                enter-active-class="transition-all duration-200 ease-out"
                                                enter-from-class="opacity-0 scale-95 -translate-y-1"
                                                enter-to-class="opacity-100 scale-100 translate-y-0"
                                                leave-active-class="transition-all duration-150 ease-in"
                                                leave-from-class="opacity-100 scale-100"
                                                leave-to-class="opacity-0 scale-95">
                                                <div v-if="showPriorityPicker" class="absolute top-full mt-1 z-30 bg-white rounded-xl shadow-xl border border-gray-200 p-1.5 min-w-[120px]">
                                                    <button v-for="po in priorityOptions" :key="po.value"
                                                        @click="changePriority(po.value)"
                                                        :disabled="priorityChanging || lead.priority == po.value"
                                                        :class="['w-full flex items-center gap-2 px-3 py-2 rounded-lg text-xs font-semibold border transition-all duration-150 mb-0.5 last:mb-0',
                                                            lead.priority == po.value ? po.color + ' ring-1 ring-offset-1' : 'border-transparent hover:' + po.color.split(' ')[0],
                                                            priorityChanging ? 'opacity-50 cursor-not-allowed' : 'cursor-pointer']">
                                                        <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" :d="priorityDisplay[po.value]?.icon"/></svg>
                                                        {{ isRtl ? po.ar : po.en }}
                                                        <svg v-if="lead.priority == po.value" class="w-3.5 h-3.5 ms-auto text-current" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                                    </button>
                                                </div>
                                            </Transition>
                                        </div>
                                    </div>

                                    <!-- Contact details -->
                                    <div class="flex flex-wrap items-center gap-x-5 gap-y-1.5 text-teal-100 text-sm">
                                        <span v-if="lead.phone" class="flex items-center gap-1.5">
                                            <a :href="'tel:' + lead.phone" class="flex items-center gap-1.5 hover:text-white transition-colors">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                                                {{ lead.phone }}
                                            </a>
                                            <button @click.prevent="copyToClipboard(lead.phone, 'phone')" class="p-0.5 rounded hover:bg-white/20 transition-colors" :title="isRtl ? 'نسخ' : 'Copy'">
                                                <svg v-if="copiedField === 'phone'" class="w-3.5 h-3.5 text-emerald-300" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                                <svg v-else class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                                            </button>
                                        </span>
                                        <span v-if="lead.email" class="flex items-center gap-1.5">
                                            <a :href="'mailto:' + lead.email" class="flex items-center gap-1.5 hover:text-white transition-colors">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                                {{ lead.email }}
                                            </a>
                                            <button @click.prevent="copyToClipboard(lead.email, 'email')" class="p-0.5 rounded hover:bg-white/20 transition-colors" :title="isRtl ? 'نسخ' : 'Copy'">
                                                <svg v-if="copiedField === 'email'" class="w-3.5 h-3.5 text-emerald-300" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                                <svg v-else class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                                            </button>
                                        </span>
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
                                <button
                                    v-if="!['converted', 'lost'].includes(lead.status)"
                                    @click="showConvertModal = true"
                                    class="h-11 px-4 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white flex items-center justify-center gap-2 shadow-lg hover:shadow-xl transition-all duration-200 hover:-translate-y-0.5 text-sm font-medium"
                                    :title="isRtl ? 'تحويل لمريض' : 'Convert to Patient'"
                                >
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                                    <span class="hidden sm:inline">{{ isRtl ? 'تحويل لمريض' : 'Convert' }}</span>
                                </button>
                                <button
                                    v-if="!['converted', 'lost'].includes(lead.status)"
                                    @click="showLostModal = true"
                                    class="h-11 px-4 rounded-xl bg-red-500/80 hover:bg-red-500 text-white flex items-center justify-center gap-2 shadow-lg hover:shadow-xl transition-all duration-200 hover:-translate-y-0.5 text-sm font-medium"
                                    :title="isRtl ? 'تسجيل خسارة' : 'Mark as Lost'"
                                >
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                                    <span class="hidden sm:inline">{{ isRtl ? 'خسارة' : 'Lost' }}</span>
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

            <!-- Quick Stats Strip -->
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4">
                <div :class="['grid grid-cols-2 md:grid-cols-5 gap-3 transition-all duration-700 delay-150', mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4']"
                     :style="{ transitionTimingFunction: 'cubic-bezier(0.16, 1, 0.3, 1)' }">
                    <div class="bg-white rounded-xl border border-gray-100 p-3 flex items-center gap-3 shadow-sm">
                        <div class="w-9 h-9 rounded-lg bg-teal-50 flex items-center justify-center flex-shrink-0">
                            <svg class="w-4.5 h-4.5 text-teal-600" style="width:18px;height:18px" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                        <div>
                            <div class="text-lg font-bold text-gray-800">{{ leadAge }}</div>
                            <div class="text-[10px] text-gray-400 font-medium uppercase">{{ isRtl ? 'يوم في النظام' : 'Days in CRM' }}</div>
                        </div>
                    </div>
                    <div class="bg-white rounded-xl border border-gray-100 p-3 flex items-center gap-3 shadow-sm">
                        <div class="w-9 h-9 rounded-lg bg-blue-50 flex items-center justify-center flex-shrink-0">
                            <svg class="w-4.5 h-4.5 text-blue-600" style="width:18px;height:18px" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/></svg>
                        </div>
                        <div>
                            <div class="text-lg font-bold text-gray-800">{{ activityStats.total }}</div>
                            <div class="text-[10px] text-gray-400 font-medium uppercase">{{ isRtl ? 'نشاط' : 'Activities' }}</div>
                        </div>
                    </div>
                    <div class="bg-white rounded-xl border border-gray-100 p-3 flex items-center gap-3 shadow-sm">
                        <div class="w-9 h-9 rounded-lg bg-amber-50 flex items-center justify-center flex-shrink-0">
                            <svg class="w-4.5 h-4.5 text-amber-600" style="width:18px;height:18px" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        </div>
                        <div>
                            <div class="text-lg font-bold text-gray-800">{{ pendingFollowUps }}</div>
                            <div class="text-[10px] text-gray-400 font-medium uppercase">{{ isRtl ? 'متابعة معلقة' : 'Pending F/U' }}</div>
                        </div>
                    </div>
                    <div class="bg-white rounded-xl border border-gray-100 p-3 flex items-center gap-3 shadow-sm">
                        <div class="w-9 h-9 rounded-lg bg-green-50 flex items-center justify-center flex-shrink-0">
                            <svg class="w-4.5 h-4.5 text-green-600" style="width:18px;height:18px" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                        <div>
                            <div class="text-lg font-bold text-gray-800">{{ completedFollowUps }}</div>
                            <div class="text-[10px] text-gray-400 font-medium uppercase">{{ isRtl ? 'متابعة مكتملة' : 'Completed F/U' }}</div>
                        </div>
                    </div>
                    <!-- Print button -->
                    <button @click="printLeadProfile"
                        class="bg-white rounded-xl border border-gray-100 p-3 flex items-center gap-3 shadow-sm hover:bg-gray-50 hover:border-teal-200 transition-all duration-200 group cursor-pointer">
                        <div class="w-9 h-9 rounded-lg bg-gray-50 group-hover:bg-teal-50 flex items-center justify-center flex-shrink-0 transition-colors">
                            <svg class="w-4.5 h-4.5 text-gray-400 group-hover:text-teal-600 transition-colors" style="width:18px;height:18px" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                        </div>
                        <div class="text-xs font-medium text-gray-500 group-hover:text-teal-700 transition-colors">{{ isRtl ? 'طباعة الملف' : 'Print Profile' }}</div>
                    </button>
                </div>
            </div>

            <!-- Next Follow-up Countdown -->
            <div v-if="nextFollowUp" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-3">
                <div :class="['rounded-xl border p-3 flex items-center justify-between transition-all duration-500',
                    nextFollowUp.isOverdue
                        ? 'bg-red-50 border-red-200'
                        : 'bg-amber-50 border-amber-200',
                    mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4']"
                     :style="{ transitionTimingFunction: 'cubic-bezier(0.16, 1, 0.3, 1)', transitionDelay: '180ms' }">
                    <div class="flex items-center gap-3">
                        <div :class="['w-9 h-9 rounded-lg flex items-center justify-center flex-shrink-0',
                            nextFollowUp.isOverdue ? 'bg-red-100' : 'bg-amber-100']">
                            <svg :class="['w-[18px] h-[18px]', nextFollowUp.isOverdue ? 'text-red-600' : 'text-amber-600']" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <div>
                            <div class="text-xs font-semibold" :class="nextFollowUp.isOverdue ? 'text-red-700' : 'text-amber-700'">
                                {{ nextFollowUp.isOverdue
                                    ? (isRtl ? 'متابعة متأخرة!' : 'Overdue follow-up!')
                                    : (isRtl ? 'المتابعة القادمة' : 'Next follow-up') }}
                            </div>
                            <div class="text-[10px] mt-0.5" :class="nextFollowUp.isOverdue ? 'text-red-500' : 'text-amber-500'">
                                {{ nextFollowUp.isOverdue ? (isRtl ? 'منذ ' : '') : (isRtl ? 'خلال ' : 'in ') }}{{ nextFollowUp.label }}{{ nextFollowUp.isOverdue && !isRtl ? ' ago' : '' }}
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <span :class="['text-lg font-bold tabular-nums', nextFollowUp.isOverdue ? 'text-red-600 animate-pulse' : 'text-amber-600']">
                            {{ nextFollowUp.label }}
                        </span>
                        <button @click="showFollowUpForm = true"
                            :class="['px-3 py-1.5 rounded-lg text-xs font-semibold transition-all',
                                nextFollowUp.isOverdue
                                    ? 'bg-red-100 text-red-700 hover:bg-red-200 border border-red-300'
                                    : 'bg-amber-100 text-amber-700 hover:bg-amber-200 border border-amber-300']">
                            {{ isRtl ? 'إعادة جدولة' : 'Reschedule' }}
                        </button>
                    </div>
                </div>
            </div>

            <!-- Engagement Score Breakdown -->
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-3">
                <button @click="showScoreBreakdown = !showScoreBreakdown"
                    :class="['w-full bg-white rounded-xl border border-gray-100 shadow-sm p-3 flex items-center justify-between hover:border-teal-200 transition-all duration-300 group', mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4']"
                    :style="{ transitionTimingFunction: 'cubic-bezier(0.16, 1, 0.3, 1)', transitionDelay: '200ms' }">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-lg bg-teal-50 flex items-center justify-center flex-shrink-0">
                            <svg class="w-[18px] h-[18px] text-teal-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/></svg>
                        </div>
                        <div class="text-start">
                            <span class="text-sm font-semibold text-gray-700 group-hover:text-teal-700 transition-colors">{{ isRtl ? '\u062A\u0641\u0627\u0635\u064A\u0644 \u0627\u0644\u0646\u0642\u0627\u0637' : 'Score Breakdown' }}</span>
                            <span class="text-xs text-gray-400 block">{{ isRtl ? '\u0643\u064A\u0641 \u062A\u0645 \u062D\u0633\u0627\u0628 \u0646\u0642\u0627\u0637 \u0627\u0644\u0639\u0645\u064A\u0644' : 'How this lead score was calculated' }}</span>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="text-lg font-bold text-teal-700">{{ lead.score || 0 }}</span>
                        <svg :class="['w-4 h-4 text-gray-400 transition-transform duration-300', showScoreBreakdown ? 'rotate-180' : '']" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                    </div>
                </button>
                <Transition
                    enter-active-class="transition-all duration-300 ease-out"
                    enter-from-class="opacity-0 -translate-y-2 max-h-0"
                    enter-to-class="opacity-100 translate-y-0 max-h-96"
                    leave-active-class="transition-all duration-200 ease-in"
                    leave-from-class="opacity-100 max-h-96"
                    leave-to-class="opacity-0 max-h-0"
                >
                    <div v-if="showScoreBreakdown" class="bg-white rounded-b-xl border border-t-0 border-gray-100 shadow-sm p-4 -mt-1 overflow-hidden">
                        <div class="space-y-2.5">
                            <div v-for="factor in scoreBreakdown" :key="factor.en" class="flex items-center gap-3">
                                <span class="text-xs text-gray-500 w-36 truncate">{{ isRtl ? factor.ar : factor.en }}</span>
                                <div class="flex-1 h-4 bg-gray-50 rounded-full overflow-hidden">
                                    <div class="h-full rounded-full transition-all duration-700 ease-out"
                                         :style="{ width: (factor.pts / factor.max * 100) + '%', background: factor.pts >= factor.max ? '#10b981' : factor.pts > 0 ? '#0d9488' : '#e5e7eb' }"></div>
                                </div>
                                <span :class="['text-xs font-bold tabular-nums w-14 text-end', factor.pts > 0 ? 'text-teal-700' : 'text-gray-300']">{{ factor.pts }}/{{ factor.max }}</span>
                            </div>
                        </div>
                        <div class="mt-3 pt-3 border-t border-gray-100 flex items-center justify-between">
                            <span class="text-xs text-gray-400">{{ isRtl ? '\u0627\u0644\u0646\u0642\u0627\u0637 \u0627\u0644\u0641\u0639\u0644\u064A\u0629 \u0642\u062F \u062A\u062E\u062A\u0644\u0641 \u0628\u0646\u0627\u0621 \u0639\u0644\u0649 \u0627\u0644\u062E\u0648\u0627\u0631\u0632\u0645\u064A\u0629' : 'Actual score may vary based on algorithm' }}</span>
                            <span class="text-sm font-bold text-teal-700">{{ scoreBreakdown.reduce((s, f) => s + f.pts, 0) }} {{ isRtl ? '\u0646\u0642\u0637\u0629 \u062A\u0642\u0631\u064A\u0628\u064A\u0629' : 'pts est.' }}</span>
                        </div>
                    </div>
                </Transition>
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
                                                data-activity-desc
                                                class="w-full rounded-lg border-gray-300 text-sm focus:ring-teal-500 focus:border-teal-500 resize-none"
                                                :placeholder="isRtl ? 'أضف وصفاً...' : 'Add description...'"
                                            ></textarea>
                                            <!-- Quick note templates -->
                                            <div class="flex flex-wrap gap-1.5 mt-2">
                                                <div v-for="qn in quickNoteTemplates" :key="qn.en" class="inline-flex items-center rounded-lg border border-gray-200 hover:border-teal-300 hover:bg-teal-50 transition-all group/qn">
                                                    <button type="button" @click="useQuickNote(qn)"
                                                        class="text-[11px] px-2 py-1 text-gray-500 group-hover/qn:text-teal-600 transition-colors"
                                                        :title="isRtl ? 'انقر للتعبئة' : 'Click to fill'">
                                                        {{ isRtl ? qn.ar : qn.en }}
                                                    </button>
                                                    <button type="button" @click="quickLogNote(qn)"
                                                        :disabled="quickLogSaving === (isRtl ? qn.ar : qn.en)"
                                                        class="px-1.5 py-1 border-s border-gray-200 text-gray-300 hover:text-teal-600 transition-colors"
                                                        :title="isRtl ? 'حفظ فوري' : 'Quick log'">
                                                        <svg v-if="quickLogSaving === (isRtl ? qn.ar : qn.en)" class="w-3 h-3 animate-spin text-teal-500" fill="none" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" class="opacity-25"/><path fill="currentColor" class="opacity-75" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                                                        <svg v-else class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                                                    </button>
                                                </div>
                                            </div>
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

                                    <!-- Activity Timeline Filter -->
                                    <div v-if="groupedActivities.length > 0" class="flex items-center gap-2 mb-4 flex-wrap">
                                        <div class="relative flex-1 min-w-[140px]">
                                            <svg class="w-3.5 h-3.5 text-gray-400 absolute top-1/2 -translate-y-1/2 pointer-events-none" :class="isRtl ? 'right-2.5' : 'left-2.5'" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path stroke-linecap="round" d="m21 21-4.35-4.35"/></svg>
                                            <input v-model="activitySearchQuery" type="text"
                                                :placeholder="isRtl ? 'بحث في النشاطات...' : 'Search activities...'"
                                                class="w-full rounded-lg border-gray-200 text-xs py-2 focus:ring-teal-500 focus:border-teal-500"
                                                :class="isRtl ? 'pr-8 pl-2' : 'pl-8 pr-2'"/>
                                        </div>
                                        <select v-model="activityTypeFilter" class="text-xs rounded-lg border-gray-200 py-2 px-2.5 focus:ring-teal-500 focus:border-teal-500">
                                            <option value="all">{{ isRtl ? 'كل الأنواع' : 'All types' }}</option>
                                            <option v-for="at in activityTypes" :key="at.value" :value="at.value">{{ isRtl ? at.label.ar : at.label.en }}</option>
                                            <option value="status_change">{{ isRtl ? 'تغيير حالة' : 'Status change' }}</option>
                                        </select>
                                        <button v-if="activitySearchQuery || activityTypeFilter !== 'all'"
                                            @click="activitySearchQuery = ''; activityTypeFilter = 'all'"
                                            class="text-[11px] text-red-500 hover:text-red-700 underline">
                                            {{ isRtl ? 'مسح' : 'Clear' }}
                                        </button>
                                    </div>

                                    <!-- Activity Type Mini-Summary Bar -->
                                    <div v-if="activityTypeSummary.length > 0" class="flex flex-wrap gap-2 mb-4">
                                        <div v-for="ts in activityTypeSummary" :key="ts.key"
                                             :class="['inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg text-xs font-semibold', ts.color]">
                                            <span class="text-sm font-bold">{{ ts.count }}</span>
                                            <span>{{ isRtl ? ts.ar : ts.en }}</span>
                                        </div>
                                        <div class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg text-xs font-semibold bg-teal-50 text-teal-700">
                                            <span class="text-sm font-bold">{{ (activities || []).length }}</span>
                                            <span>{{ isRtl ? '\u0625\u062C\u0645\u0627\u0644\u064A' : 'Total' }}</span>
                                        </div>
                                    </div>

                                    <!-- Activity Timeline (grouped by date) -->
                                    <div v-if="filteredGroupedActivities.length > 0" class="space-y-5">
                                        <div v-for="group in filteredGroupedActivities" :key="group.date">
                                            <!-- Date Header -->
                                            <div class="flex items-center gap-3 mb-3">
                                                <div class="h-px flex-1 bg-gray-200"></div>
                                                <span class="text-xs font-semibold text-gray-400 uppercase tracking-wider whitespace-nowrap px-2">{{ group.label }}</span>
                                                <div class="h-px flex-1 bg-gray-200"></div>
                                            </div>

                                            <!-- Activities in this group -->
                                            <div class="relative">
                                                <div class="absolute top-0 bottom-0 w-px bg-gray-200" :class="isRtl ? 'right-5' : 'left-5'"></div>
                                                <div
                                                    v-for="act in group.items"
                                                    :key="act.id"
                                                    class="relative flex gap-4 pb-4 last:pb-0"
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
                                                    <div :class="['flex-1 rounded-xl border p-3.5 hover:shadow-sm transition-shadow',
                                                        isRecentActivity(act) ? 'bg-teal-50/40 border-teal-200/60 ring-1 ring-teal-100' : 'bg-white border-gray-100']">
                                                        <div class="flex items-start justify-between gap-2 mb-1">
                                                            <div>
                                                                <span class="text-sm font-semibold text-gray-800">{{ act.subject || act.type }}</span>
                                                                <span v-if="act.direction" class="text-xs text-gray-400 mx-1.5">
                                                                    {{ act.direction === 'inbound' ? (isRtl ? '← وارد' : '← Inbound') : (isRtl ? '→ صادر' : '→ Outbound') }}
                                                                </span>
                                                            </div>
                                                            <div class="flex items-center gap-1.5 flex-shrink-0">
                                                                <span v-if="isRecentActivity(act)" class="px-1.5 py-0.5 rounded-full text-[9px] font-bold bg-teal-500 text-white animate-pulse">{{ isRtl ? '\u062C\u062F\u064A\u062F' : 'NEW' }}</span>
                                                                <span class="text-xs text-gray-400 whitespace-nowrap">{{ timeAgo(act.created_at) }}</span>
                                                            </div>
                                                        </div>
                                                        <p v-if="act.description" class="text-sm text-gray-600 mb-1.5">{{ act.description }}</p>
                                                        <div class="flex items-center gap-3 text-xs text-gray-400">
                                                            <span v-if="act.outcome" class="px-2 py-0.5 rounded-full bg-gray-100 text-gray-600">{{ act.outcome }}</span>
                                                            <span v-if="act.performer?.name">{{ act.performer.name }}</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <!-- No filtered results -->
                                    <div v-else-if="groupedActivities.length > 0 && filteredGroupedActivities.length === 0" class="text-center py-8 text-gray-400">
                                        <svg class="w-10 h-10 mx-auto mb-2 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                                        <p class="text-sm">{{ isRtl ? 'لا توجد نتائج للبحث' : 'No matching activities' }}</p>
                                        <button @click="activitySearchQuery = ''; activityTypeFilter = 'all'" class="mt-2 text-xs text-teal-600 hover:underline">{{ isRtl ? 'مسح الفلتر' : 'Clear filter' }}</button>
                                    </div>
                                    <!-- No activities at all -->
                                    <div v-else-if="!groupedActivities.length" class="text-center py-10 text-gray-400">
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
                                                    <button
                                                        @click="openReschedule(fu)"
                                                        class="px-2.5 py-1.5 bg-blue-50 text-blue-700 text-xs font-medium rounded-lg hover:bg-blue-100 transition-colors border border-blue-200"
                                                    >
                                                        {{ isRtl ? 'إعادة جدولة' : 'Reschedule' }}
                                                    </button>
                                                    <!-- Snooze dropdown -->
                                                    <div class="relative">
                                                        <button
                                                            @click="toggleSnooze(fu.id)"
                                                            class="px-2.5 py-1.5 bg-purple-50 text-purple-700 text-xs font-medium rounded-lg hover:bg-purple-100 transition-colors border border-purple-200 flex items-center gap-1"
                                                        >
                                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                                            {{ isRtl ? 'تأجيل' : 'Snooze' }}
                                                        </button>
                                                        <Transition
                                                            enter-active-class="transition-all duration-200 ease-out"
                                                            enter-from-class="opacity-0 scale-95 -translate-y-1"
                                                            enter-to-class="opacity-100 scale-100 translate-y-0"
                                                            leave-active-class="transition-all duration-150 ease-in"
                                                            leave-from-class="opacity-100 scale-100"
                                                            leave-to-class="opacity-0 scale-95"
                                                        >
                                                            <div
                                                                v-if="snoozeOpen === fu.id"
                                                                class="absolute top-full mt-1 z-20 bg-white rounded-xl shadow-xl border border-gray-200 py-1 min-w-[140px]"
                                                                :class="isRtl ? 'right-0' : 'left-0'"
                                                            >
                                                                <button
                                                                    v-for="opt in snoozeOptions"
                                                                    :key="opt.key"
                                                                    @click="snoozeFollowUp(fu.id, opt.key)"
                                                                    class="w-full text-start px-3 py-2 text-xs text-gray-700 hover:bg-purple-50 hover:text-purple-700 transition-colors flex items-center gap-2"
                                                                >
                                                                    <svg class="w-3.5 h-3.5 text-purple-400 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                                                    {{ isRtl ? opt.ar : opt.en }}
                                                                </button>
                                                            </div>
                                                        </Transition>
                                                    </div>
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

                                            <!-- Reschedule form inline -->
                                            <Transition
                                                enter-active-class="transition-all duration-200 ease-out"
                                                enter-from-class="opacity-0 scale-95"
                                                enter-to-class="opacity-100 scale-100"
                                                leave-active-class="transition-all duration-150 ease-in"
                                                leave-from-class="opacity-100 scale-100"
                                                leave-to-class="opacity-0 scale-95"
                                            >
                                                <div v-if="reschedulingFollowUp === fu.id" class="mt-3 pt-3 border-t border-gray-100">
                                                    <form @submit.prevent="submitReschedule(fu.id)" class="space-y-2">
                                                        <div class="flex gap-2">
                                                            <input
                                                                v-model="rescheduleForm.scheduled_at"
                                                                type="datetime-local"
                                                                class="flex-1 rounded-lg border-gray-300 text-sm focus:ring-teal-500 focus:border-teal-500"
                                                            />
                                                        </div>
                                                        <input
                                                            v-model="rescheduleForm.notes"
                                                            type="text"
                                                            class="w-full rounded-lg border-gray-300 text-sm focus:ring-teal-500 focus:border-teal-500"
                                                            :placeholder="isRtl ? 'ملاحظات...' : 'Notes...'"
                                                        />
                                                        <div class="flex gap-2 justify-end">
                                                            <button
                                                                type="button"
                                                                @click="reschedulingFollowUp = null"
                                                                class="px-3 py-1.5 text-xs text-gray-500 hover:text-gray-700 transition-colors"
                                                            >
                                                                {{ isRtl ? 'إلغاء' : 'Cancel' }}
                                                            </button>
                                                            <button
                                                                type="submit"
                                                                :disabled="rescheduleForm.processing || !rescheduleForm.scheduled_at"
                                                                class="px-4 py-1.5 bg-blue-600 text-white text-xs font-medium rounded-lg hover:bg-blue-700 disabled:opacity-50 transition-colors"
                                                            >
                                                                {{ isRtl ? 'إعادة جدولة' : 'Reschedule' }}
                                                            </button>
                                                        </div>
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
                        <!-- Quick Contact Actions -->
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-5">
                            <h3 class="text-sm font-semibold text-gray-800 mb-3 flex items-center gap-2">
                                <svg class="w-4 h-4 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                                {{ isRtl ? 'تواصل سريع' : 'Quick Contact' }}
                            </h3>
                            <div class="grid grid-cols-2 gap-2">
                                <a v-if="lead.phone" :href="whatsappUrl(lead.phone)" target="_blank"
                                   @click="contactAutoLog('whatsapp')"
                                   class="flex items-center gap-2 px-3 py-2.5 rounded-xl bg-emerald-50 text-emerald-700 hover:bg-emerald-100 transition-colors border border-emerald-200 text-sm font-medium relative group/btn">
                                    <svg v-if="contactAutoLogSaving !== 'whatsapp'" class="w-4 h-4 flex-shrink-0" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347zM12 2C6.477 2 2 6.477 2 12c0 1.89.525 3.66 1.438 5.168L2 22l4.832-1.438A9.955 9.955 0 0012 22c5.523 0 10-4.477 10-10S17.523 2 12 2z"/></svg>
                                    <svg v-else class="w-4 h-4 flex-shrink-0 animate-spin" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                    {{ isRtl ? 'واتساب' : 'WhatsApp' }}
                                    <span class="absolute -top-1 -end-1 w-3.5 h-3.5 rounded-full bg-emerald-500 text-white flex items-center justify-center opacity-0 group-hover/btn:opacity-100 transition-opacity" :title="isRtl ? 'تسجيل تلقائي' : 'Auto-log'">
                                        <svg class="w-2 h-2" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                    </span>
                                </a>
                                <a v-if="lead.phone" :href="'tel:' + lead.phone"
                                   @click="contactAutoLog('call')"
                                   class="flex items-center gap-2 px-3 py-2.5 rounded-xl bg-green-50 text-green-700 hover:bg-green-100 transition-colors border border-green-200 text-sm font-medium relative group/btn">
                                    <svg v-if="contactAutoLogSaving !== 'call'" class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                                    <svg v-else class="w-4 h-4 flex-shrink-0 animate-spin" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                    {{ isRtl ? 'اتصال' : 'Call' }}
                                    <span class="absolute -top-1 -end-1 w-3.5 h-3.5 rounded-full bg-green-500 text-white flex items-center justify-center opacity-0 group-hover/btn:opacity-100 transition-opacity" :title="isRtl ? 'تسجيل تلقائي' : 'Auto-log'">
                                        <svg class="w-2 h-2" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                    </span>
                                </a>
                                <a v-if="lead.email" :href="'mailto:' + lead.email"
                                   @click="contactAutoLog('email')"
                                   class="flex items-center gap-2 px-3 py-2.5 rounded-xl bg-blue-50 text-blue-700 hover:bg-blue-100 transition-colors border border-blue-200 text-sm font-medium relative group/btn">
                                    <svg v-if="contactAutoLogSaving !== 'email'" class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                    <svg v-else class="w-4 h-4 flex-shrink-0 animate-spin" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                    {{ isRtl ? 'بريد' : 'Email' }}
                                    <span class="absolute -top-1 -end-1 w-3.5 h-3.5 rounded-full bg-blue-500 text-white flex items-center justify-center opacity-0 group-hover/btn:opacity-100 transition-opacity" :title="isRtl ? 'تسجيل تلقائي' : 'Auto-log'">
                                        <svg class="w-2 h-2" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                    </span>
                                </a>
                                <button v-if="lead.phone" @click="copyPhone"
                                    class="flex items-center gap-2 px-3 py-2.5 rounded-xl transition-colors border text-sm font-medium"
                                    :class="phoneCopied ? 'bg-teal-50 text-teal-700 border-teal-200' : 'bg-gray-50 text-gray-600 hover:bg-gray-100 border-gray-200'">
                                    <svg v-if="!phoneCopied" class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"/></svg>
                                    <svg v-else class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                    {{ phoneCopied ? (isRtl ? 'تم النسخ' : 'Copied') : (isRtl ? 'نسخ الهاتف' : 'Copy Phone') }}
                                </button>
                            </div>
                            <!-- Auto-log hint -->
                            <p class="mt-2 text-[10px] text-gray-400 flex items-center gap-1">
                                <svg class="w-3 h-3 text-teal-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                {{ isRtl ? 'الضغط على الأزرار يسجل النشاط تلقائياً' : 'Clicking logs activity automatically' }}
                            </p>
                        </div>

                        <!-- Activity Summary -->
                        <div v-if="activityStats.total > 0" class="bg-white rounded-2xl shadow-sm border border-gray-200 p-5">
                            <h3 class="text-sm font-semibold text-gray-800 mb-3 flex items-center gap-2">
                                <svg class="w-4 h-4 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                                {{ isRtl ? 'ملخص النشاطات' : 'Activity Summary' }}
                            </h3>
                            <div class="grid grid-cols-2 gap-2">
                                <div class="bg-gray-50 rounded-xl p-3 text-center">
                                    <div class="text-xl font-bold text-gray-800">{{ activityStats.total }}</div>
                                    <div class="text-[10px] text-gray-400 mt-0.5">{{ isRtl ? 'إجمالي' : 'Total' }}</div>
                                </div>
                                <div class="bg-green-50 rounded-xl p-3 text-center">
                                    <div class="text-xl font-bold text-green-700">{{ activityStats.calls }}</div>
                                    <div class="text-[10px] text-green-500 mt-0.5">{{ isRtl ? 'مكالمات' : 'Calls' }}</div>
                                </div>
                                <div class="bg-emerald-50 rounded-xl p-3 text-center">
                                    <div class="text-xl font-bold text-emerald-700">{{ activityStats.whatsapp }}</div>
                                    <div class="text-[10px] text-emerald-500 mt-0.5">{{ isRtl ? 'واتساب' : 'WhatsApp' }}</div>
                                </div>
                                <div class="bg-blue-50 rounded-xl p-3 text-center">
                                    <div class="text-xl font-bold text-blue-700">{{ activityStats.emails }}</div>
                                    <div class="text-[10px] text-blue-500 mt-0.5">{{ isRtl ? 'بريد' : 'Emails' }}</div>
                                </div>
                            </div>
                        </div>

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
        <!-- Convert to Patient Modal -->
        <Teleport to="body">
            <Transition
                enter-active-class="transition-all duration-300 ease-out"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="transition-all duration-200 ease-in"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div v-if="showConvertModal" class="fixed inset-0 z-50 flex items-center justify-center p-4" :dir="isRtl ? 'rtl' : 'ltr'">
                    <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="showConvertModal = false"></div>
                    <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md p-6 transform transition-all duration-300">
                        <div class="flex items-center gap-3 mb-5">
                            <div class="w-12 h-12 bg-emerald-100 rounded-xl flex items-center justify-center">
                                <svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                            </div>
                            <div>
                                <h3 class="text-lg font-bold text-gray-900">{{ isRtl ? 'تحويل إلى مريض' : 'Convert to Patient' }}</h3>
                                <p class="text-sm text-gray-500">{{ isRtl ? 'سيتم إنشاء ملف مريض جديد' : 'A new patient file will be created' }}</p>
                            </div>
                        </div>

                        <form @submit.prevent="submitConvert" class="space-y-4">
                            <div class="bg-gray-50 rounded-xl p-4 border border-gray-100">
                                <div class="space-y-2 text-sm">
                                    <div class="flex justify-between">
                                        <span class="text-gray-500">{{ isRtl ? 'الاسم' : 'Name' }}</span>
                                        <span class="font-medium text-gray-800">{{ lead.full_name }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-500">{{ isRtl ? 'الهاتف' : 'Phone' }}</span>
                                        <span class="font-medium text-gray-800" dir="ltr">{{ lead.phone }}</span>
                                    </div>
                                    <div v-if="lead.email" class="flex justify-between">
                                        <span class="text-gray-500">{{ isRtl ? 'البريد' : 'Email' }}</span>
                                        <span class="font-medium text-gray-800">{{ lead.email }}</span>
                                    </div>
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">{{ isRtl ? 'ملاحظات' : 'Notes' }}</label>
                                <textarea
                                    v-model="convertForm.booking_notes"
                                    rows="3"
                                    class="w-full rounded-xl border-gray-300 text-sm focus:ring-teal-500 focus:border-teal-500 resize-none"
                                    :placeholder="isRtl ? 'ملاحظات إضافية عن التحويل...' : 'Additional conversion notes...'"
                                ></textarea>
                            </div>

                            <div class="flex gap-3 pt-2">
                                <button
                                    type="button"
                                    @click="showConvertModal = false"
                                    class="flex-1 px-4 py-2.5 border border-gray-200 text-gray-600 text-sm font-medium rounded-xl hover:bg-gray-50 transition-colors"
                                >
                                    {{ isRtl ? 'إلغاء' : 'Cancel' }}
                                </button>
                                <button
                                    type="submit"
                                    :disabled="convertForm.processing"
                                    class="flex-1 px-4 py-2.5 bg-emerald-600 text-white text-sm font-semibold rounded-xl hover:bg-emerald-700 disabled:opacity-50 transition-all duration-200 flex items-center justify-center gap-2"
                                >
                                    <svg v-if="convertForm.processing" class="animate-spin w-4 h-4" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                                    {{ convertForm.processing ? (isRtl ? 'جارٍ التحويل...' : 'Converting...') : (isRtl ? 'تحويل لمريض' : 'Convert to Patient') }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </Transition>
        </Teleport>

        <!-- Mark as Lost Modal -->
        <Teleport to="body">
            <Transition
                enter-active-class="transition-all duration-300 ease-out"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="transition-all duration-200 ease-in"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div v-if="showLostModal" class="fixed inset-0 z-50 flex items-center justify-center p-4" :dir="isRtl ? 'rtl' : 'ltr'">
                    <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="showLostModal = false"></div>
                    <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md p-6 transform transition-all duration-300">
                        <div class="flex items-center gap-3 mb-5">
                            <div class="w-12 h-12 bg-red-100 rounded-xl flex items-center justify-center">
                                <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                            </div>
                            <div>
                                <h3 class="text-lg font-bold text-gray-900">{{ isRtl ? 'تسجيل خسارة العميل' : 'Mark Lead as Lost' }}</h3>
                                <p class="text-sm text-gray-500">{{ lead.full_name }}</p>
                            </div>
                        </div>

                        <form @submit.prevent="submitLost" class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">{{ isRtl ? 'سبب الخسارة' : 'Reason for Loss' }}</label>
                                <div class="grid grid-cols-2 gap-2 mb-3">
                                    <button
                                        v-for="reason in lossReasons"
                                        :key="reason.en"
                                        type="button"
                                        @click="lostForm.loss_reason = isRtl ? reason.ar : reason.en"
                                        class="px-3 py-2 rounded-xl border text-xs font-medium transition-all duration-200"
                                        :class="lostForm.loss_reason === (isRtl ? reason.ar : reason.en)
                                            ? 'border-red-400 bg-red-50 text-red-700'
                                            : 'border-gray-200 text-gray-500 hover:border-gray-300'"
                                    >
                                        {{ isRtl ? reason.ar : reason.en }}
                                    </button>
                                </div>
                                <textarea
                                    v-model="lostForm.loss_reason"
                                    rows="2"
                                    class="w-full rounded-xl border-gray-300 text-sm focus:ring-red-400 focus:border-red-400 resize-none"
                                    :placeholder="isRtl ? 'أو اكتب سبباً آخر...' : 'Or type a custom reason...'"
                                ></textarea>
                                <p v-if="lostForm.errors.loss_reason" class="mt-1 text-xs text-red-500">{{ lostForm.errors.loss_reason }}</p>
                            </div>

                            <div class="flex gap-3 pt-2">
                                <button
                                    type="button"
                                    @click="showLostModal = false"
                                    class="flex-1 px-4 py-2.5 border border-gray-200 text-gray-600 text-sm font-medium rounded-xl hover:bg-gray-50 transition-colors"
                                >
                                    {{ isRtl ? 'إلغاء' : 'Cancel' }}
                                </button>
                                <button
                                    type="submit"
                                    :disabled="lostForm.processing || !lostForm.loss_reason"
                                    class="flex-1 px-4 py-2.5 bg-red-600 text-white text-sm font-semibold rounded-xl hover:bg-red-700 disabled:opacity-50 transition-all duration-200 flex items-center justify-center gap-2"
                                >
                                    <svg v-if="lostForm.processing" class="animate-spin w-4 h-4" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                                    {{ lostForm.processing ? (isRtl ? 'جارٍ الحفظ...' : 'Saving...') : (isRtl ? 'تسجيل الخسارة' : 'Mark as Lost') }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </Transition>
        </Teleport>
    </SecretaryLayout>
</template>
