<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import { Link, useForm, router, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { usePermissions } from '@/Composables/usePermissions.js';
import { useCurrency } from '@/Composables/useCurrency.js';

const { can } = usePermissions();
const { formatCurrency } = useCurrency();

const props = defineProps({
    lead: Object,
    activities: Array,
    followUps: Array,
    assignees: Array,
    sources: Array,
    services: Array,
    doctors: Array,
    templates: Array,
    smartContact: Object,
});

const page = usePage();
const locale = computed(() => page.props.locale || 'ar');
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const activeTab = ref('activity');

// Entrance animation
const mounted = ref(false);
onMounted(() => {
    setTimeout(() => mounted.value = true, 50);
});

// Activity form
const activityForm = useForm({
    type: 'note',
    subject: '',
    description: '',
    direction: '',
    duration_seconds: '',
    outcome: '',
});

function submitActivity() {
    activityForm.post(`/admin/leads/${props.lead.id}/activity`, {
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
    assigned_to: '',
});

function submitFollowUp() {
    followUpForm.post(`/admin/leads/${props.lead.id}/follow-up`, {
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
    completeForm.post(`/admin/follow-ups/${fuId}/complete`, {
        preserveScroll: true,
        onSuccess: () => {
            completingFollowUp.value = null;
            completeForm.reset();
        },
    });
}

function missFollowUp(fuId) {
    if (confirm(isRtl.value ? 'هل تريد تحديد هذه المتابعة كفائتة؟' : 'Mark this follow-up as missed?')) {
        router.post(`/admin/follow-ups/${fuId}/miss`, {}, { preserveScroll: true });
    }
}

const rescheduleForm = useForm({ scheduled_at: '' });
const reschedulingFollowUp = ref(null);

function openReschedule(fu) {
    reschedulingFollowUp.value = fu.id;
    rescheduleForm.scheduled_at = '';
}

function submitReschedule(fuId) {
    rescheduleForm.post(`/admin/follow-ups/${fuId}/reschedule`, {
        preserveScroll: true,
        onSuccess: () => {
            reschedulingFollowUp.value = null;
            rescheduleForm.reset();
        },
    });
}

// Quick Send (WhatsApp/SMS/Email templates)
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
        .replace(/{clinic_name}/g, 'Doctorato Polyclinic')
        .replace(/{date}/g, new Date().toLocaleDateString('en-GB'));
});

function submitQuickSend() {
    quickSendForm.post(`/admin/leads/${props.lead.id}/quick-send`, {
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

// Reset template when channel changes
watch(() => quickSendForm.channel, () => {
    quickSendForm.template_id = '';
});

// Template search for searchable select
const templateSearch = ref('');
const showTemplateDropdown = ref(false);
const filteredTemplateOptions = computed(() => {
    const list = filteredTemplates.value;
    if (!templateSearch.value) return list;
    const q = templateSearch.value.toLowerCase();
    return list.filter(t => t.name.toLowerCase().includes(q));
});

function selectTemplate(t) {
    quickSendForm.template_id = t.id;
    templateSearch.value = t.name;
    showTemplateDropdown.value = false;
}

// Assignee search for follow-up form
const assigneeSearch = ref('');
const showAssigneeDropdown = ref(false);
const filteredAssigneeOptions = computed(() => {
    const list = props.assignees || [];
    if (!assigneeSearch.value) return list;
    const q = assigneeSearch.value.toLowerCase();
    return list.filter(u => u.name.toLowerCase().includes(q));
});

function selectAssignee(u) {
    followUpForm.assigned_to = u.id;
    assigneeSearch.value = u.name;
    showAssigneeDropdown.value = false;
}

// Status update
const showStatusModal = ref(false);
const statusForm = useForm({ status: '', loss_reason: '' });

function changeStatus(newStatus) {
    statusForm.status = newStatus;
    if (newStatus === 'lost') {
        showStatusModal.value = true;
    } else {
        statusForm.post(`/admin/leads/${props.lead.id}/status`, {
            preserveScroll: true,
            onSuccess: () => statusForm.reset(),
        });
    }
}

function confirmLostStatus() {
    statusForm.post(`/admin/leads/${props.lead.id}/status`, {
        preserveScroll: true,
        onSuccess: () => {
            statusForm.reset();
            showStatusModal.value = false;
        },
    });
}

// Reactivate lead (lost/dormant → new)
const reactivating = ref(false);
function deleteLead() {
    const msg = isRtl.value
        ? `هل أنت متأكد من حذف العميل "${props.lead.full_name}"؟ لا يمكن التراجع عن هذا الإجراء.`
        : `Are you sure you want to delete "${props.lead.full_name}"? This action cannot be undone.`;
    if (!confirm(msg)) return;
    router.post(`/admin/leads/${props.lead.id}/delete`, {}, {
        onSuccess: () => router.visit('/admin/leads'),
    });
}

function reactivateLead() {
    const msg = isRtl.value ? 'هل تريد إعادة تنشيط هذا العميل المحتمل؟' : 'Reactivate this lead back to New status?';
    if (!confirm(msg)) return;
    reactivating.value = true;
    router.post(`/admin/leads/${props.lead.id}/reactivate`, {}, {
        preserveScroll: true,
        onFinish: () => reactivating.value = false,
    });
}

// Convert modal
const showConvertModal = ref(false);
const selectedDepartment = ref('');
const convertForm = useForm({
    create_booking: false,
    department: '',
    booking_type: 'service',
    service_id: '',
    doctor_id: '',
    appointment_date: '',
    start_time: '',
    end_time: '',
    booking_notes: '',
});

// Filter services by selected department
const filteredServices = computed(() => {
    if (!selectedDepartment.value) return [];
    return (props.services || []).filter(s => s.module === selectedDepartment.value);
});

// Filter doctors by department
const filteredDoctors = computed(() => {
    if (!selectedDepartment.value) return props.doctors || [];
    return (props.doctors || []).filter(d => d.department === selectedDepartment.value || !d.department);
});

// Reset dependent fields when department changes
watch(selectedDepartment, (val) => {
    convertForm.department = val;
    convertForm.service_id = '';
    convertForm.doctor_id = '';
    convertForm.start_time = '';
    convertForm.end_time = '';
    availableSlots.value = [];
});
const availableSlots = ref([]);
const availableDates = ref([]);
const datesLoading = ref(false);
const slotsLoading = ref(false);
const slotsError = ref('');

function openConvertModal() {
    convertForm.reset();
    convertForm.create_booking = false;
    selectedDepartment.value = '';
    availableSlots.value = [];
    availableDates.value = [];
    slotsError.value = '';
    showConvertModal.value = true;
}

// Fetch available dates for the next 60 days when doctor is selected
function fetchAvailableDates() {
    if (!convertForm.doctor_id) {
        availableDates.value = [];
        return;
    }
    datesLoading.value = true;
    const from = new Date().toISOString().split('T')[0];
    const to = new Date(Date.now() + 60 * 86400000).toISOString().split('T')[0];
    const service = (props.services || []).find(s => s.id == convertForm.service_id);
    const duration = service?.session_duration_minutes || 30;

    fetch(`/api/available-dates?doctor_id=${convertForm.doctor_id}&from=${from}&to=${to}&duration=${duration}`)
        .then(r => r.json())
        .then(data => {
            const dates = data.dates || [];
            // API returns [{date, available_slots_count}] — extract date strings
            availableDates.value = dates.map(d => typeof d === 'string' ? d : d.date);
        })
        .catch(() => { availableDates.value = []; })
        .finally(() => { datesLoading.value = false; });
}

function fetchTimeSlots() {
    if (!convertForm.doctor_id || !convertForm.appointment_date) {
        availableSlots.value = [];
        return;
    }
    slotsLoading.value = true;
    slotsError.value = '';
    convertForm.start_time = '';
    convertForm.end_time = '';

    const service = (props.services || []).find(s => s.id == convertForm.service_id);
    const duration = service?.session_duration_minutes || 30;

    fetch(`/api/time-slots?doctor_id=${convertForm.doctor_id}&date=${convertForm.appointment_date}&duration=${duration}`)
        .then(r => r.json())
        .then(data => {
            const slots = data.slots || data;
            availableSlots.value = Array.isArray(slots) ? slots : [];
            if (!availableSlots.value.length) slotsError.value = isRtl.value ? 'لا توجد مواعيد متاحة لهذا اليوم.' : 'No available slots for this date.';
        })
        .catch(() => { slotsError.value = isRtl.value ? 'فشل تحميل المواعيد.' : 'Failed to load time slots.'; })
        .finally(() => { slotsLoading.value = false; });
}

function selectSlot(slot) {
    convertForm.start_time = slot.start;
    convertForm.end_time = slot.end;
}

function submitConvert() {
    convertForm.post(`/admin/leads/${props.lead.id}/convert`, {
        onSuccess: () => { showConvertModal.value = false; },
    });
}

// Watch doctor change to fetch available dates + slots
watch(() => convertForm.doctor_id, () => {
    if (convertForm.create_booking) {
        fetchAvailableDates();
        fetchTimeSlots();
    }
});
watch(() => convertForm.appointment_date, () => { if (convertForm.create_booking) fetchTimeSlots(); });

// Smart Contact - late hour detection
const currentHour = new Date().getHours();
const isLateHour = currentHour >= 21 || currentHour < 8; // 9 PM to 8 AM
const lateHourDismissed = ref(false);

const channelIcons = {
    call: 'M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z',
    whatsapp: 'M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z',
    email: 'M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z',
    sms: 'M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z',
};

const statusLabels = computed(() => ({
    new: isRtl.value ? 'جديد' : 'New',
    contacted: isRtl.value ? 'تم التواصل' : 'Contacted',
    qualified: isRtl.value ? 'مؤهل' : 'Qualified',
    appointment_booked: isRtl.value ? 'تم الحجز' : 'Appt. Booked',
    consultation_done: isRtl.value ? 'تم الاستشارة' : 'Consultation',
    negotiation: isRtl.value ? 'تفاوض' : 'Negotiation',
    converted: isRtl.value ? 'تم التحويل' : 'Converted',
    lost: isRtl.value ? 'خسارة' : 'Lost',
    dormant: isRtl.value ? 'خامل' : 'Dormant',
}));
const statusColors = {
    new: 'bg-slate-100 text-[#1B365D] ring-[#1B365D]/10',
    contacted: 'bg-slate-100 text-[#1B365D] ring-[#1B365D]/10',
    qualified: 'bg-slate-100 text-[#1B365D] ring-[#1B365D]/10',
    appointment_booked: 'bg-amber-100 text-amber-700 ring-amber-600/10',
    consultation_done: 'bg-teal-100 text-teal-700 ring-teal-600/10',
    negotiation: 'bg-amber-100 text-[#C4A265] ring-[#C4A265]/10',
    converted: 'bg-emerald-100 text-emerald-700 ring-emerald-600/10',
    lost: 'bg-red-100 text-red-700 ring-red-600/10',
    dormant: 'bg-gray-100 text-gray-600 ring-gray-500/10',
};
const priorityLabels = computed(() => ({ 1: isRtl.value ? 'ساخن' : 'Hot', 2: isRtl.value ? 'دافئ' : 'Warm', 3: isRtl.value ? 'بارد' : 'Cold' }));
const priorityColors = { 1: 'bg-red-100 text-red-700', 2: 'bg-amber-100 text-amber-700', 3: 'bg-slate-100 text-[#1B365D]' };
const priorityDotColors = { 1: '#ef4444', 2: '#f59e0b', 3: '#3b82f6' };

// Only show enabled medical departments (derma/dental) in convert modal
const enabledDepartments = computed(() => {
    const allModules = page.props.modules || {};
    const deptIcons = {
        derma: 'M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z',
        dental: 'M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
    };
    return ['derma', 'dental', 'pediatric']
        .filter(slug => allModules[slug]?.enabled || allModules[slug]?.is_core)
        .map(slug => ({
            slug,
            name_en: allModules[slug]?.name_en || slug,
            name_ar: allModules[slug]?.name_ar || slug,
            icon: deptIcons[slug] || allModules[slug]?.icon,
        }));
});

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
    call: 'bg-emerald-100 text-emerald-600',
    whatsapp: 'bg-emerald-100 text-emerald-600',
    email: 'bg-slate-100 text-[#1B365D]',
    sms: 'bg-slate-100 text-[#1B365D]',
    meeting: 'bg-amber-100 text-amber-600',
    status_change: 'bg-slate-100 text-[#1B365D]',
    assignment: 'bg-amber-100 text-[#C4A265]',
    system: 'bg-gray-100 text-gray-500',
    follow_up_scheduled: 'bg-amber-100 text-amber-600',
    follow_up_completed: 'bg-emerald-100 text-emerald-600',
    booking_created: 'bg-slate-100 text-[#1B365D]',
    visit_completed: 'bg-teal-100 text-teal-600',
    payment_received: 'bg-emerald-100 text-emerald-700',
};

const activityTypeBorderColors = {
    note: 'border-gray-300',
    call: 'border-emerald-300',
    whatsapp: 'border-emerald-300',
    email: 'border-slate-300',
    sms: 'border-slate-300',
    meeting: 'border-amber-300',
    status_change: 'border-slate-300',
    assignment: 'border-amber-300',
    system: 'border-gray-200',
    follow_up_scheduled: 'border-amber-300',
    follow_up_completed: 'border-emerald-300',
    booking_created: 'border-slate-300',
    visit_completed: 'border-teal-300',
    payment_received: 'border-emerald-400',
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
    if (diff < 60) return isRtl.value ? 'الآن' : 'just now';
    if (diff < 3600) return Math.floor(diff / 60) + (isRtl.value ? ' د' : 'm ago');
    if (diff < 86400) return Math.floor(diff / 3600) + (isRtl.value ? ' س' : 'h ago');
    return Math.floor(diff / 86400) + (isRtl.value ? ' ي' : 'd ago');
}

function getInitials(name) {
    if (!name) return '?';
    return name.split(' ').map(w => w[0]).slice(0, 2).join('').toUpperCase();
}

const pipelineStatuses = ['new', 'contacted', 'qualified', 'appointment_booked', 'consultation_done', 'negotiation'];
const currentStepIndex = pipelineStatuses.indexOf(props.lead.status);

const activityTypes = computed(() => [
    { value: 'note', label: isRtl.value ? 'ملاحظة' : 'Note', icon: activityTypeIcons.note },
    { value: 'call', label: isRtl.value ? 'مكالمة' : 'Call', icon: activityTypeIcons.call },
    { value: 'whatsapp', label: 'WhatsApp', icon: activityTypeIcons.whatsapp },
    { value: 'email', label: isRtl.value ? 'بريد' : 'Email', icon: activityTypeIcons.email },
    { value: 'sms', label: 'SMS', icon: activityTypeIcons.sms },
    { value: 'meeting', label: isRtl.value ? 'اجتماع' : 'Meeting', icon: activityTypeIcons.meeting },
]);

// Translation maps for raw DB values
const directionLabels = computed(() => ({
    inbound: isRtl.value ? 'وارد' : 'Inbound',
    outbound: isRtl.value ? 'صادر' : 'Outbound',
}));

const outcomeLabels = computed(() => ({
    successful: isRtl.value ? 'ناجح' : 'Successful',
    no_answer: isRtl.value ? 'لا إجابة' : 'No Answer',
    busy: isRtl.value ? 'مشغول' : 'Busy',
    voicemail: isRtl.value ? 'بريد صوتي' : 'Voicemail',
    callback_requested: isRtl.value ? 'طلب معاودة الاتصال' : 'Callback Requested',
    not_interested: isRtl.value ? 'غير مهتم' : 'Not Interested',
}));

const followUpTypeLabels = computed(() => ({
    call: isRtl.value ? 'مكالمة' : 'Call',
    whatsapp: 'WhatsApp',
    email: isRtl.value ? 'بريد إلكتروني' : 'Email',
    sms: 'SMS',
    meeting: isRtl.value ? 'اجتماع' : 'Meeting',
    other: isRtl.value ? 'أخرى' : 'Other',
}));

const followUpStatusLabels = computed(() => ({
    pending: isRtl.value ? 'معلّق' : 'Pending',
    completed: isRtl.value ? 'مكتمل' : 'Completed',
    missed: isRtl.value ? 'فائت' : 'Missed',
    cancelled: isRtl.value ? 'ملغي' : 'Cancelled',
    rescheduled: isRtl.value ? 'أعيد جدولته' : 'Rescheduled',
}));

const channelLabels = computed(() => ({
    call: isRtl.value ? 'مكالمة' : 'Call',
    whatsapp: 'WhatsApp',
    email: isRtl.value ? 'بريد إلكتروني' : 'Email',
    sms: 'SMS',
}));

const genderLabels = computed(() => ({
    male: isRtl.value ? 'ذكر' : 'Male',
    female: isRtl.value ? 'أنثى' : 'Female',
}));

const activityTypeLabels = computed(() => ({
    note: isRtl.value ? 'ملاحظة' : 'Note',
    call: isRtl.value ? 'مكالمة' : 'Call',
    whatsapp: 'WhatsApp',
    email: isRtl.value ? 'بريد' : 'Email',
    sms: 'SMS',
    meeting: isRtl.value ? 'اجتماع' : 'Meeting',
    status_change: isRtl.value ? 'تغيير حالة' : 'Status Change',
    assignment: isRtl.value ? 'تعيين' : 'Assignment',
    system: isRtl.value ? 'نظام' : 'System',
    follow_up_scheduled: isRtl.value ? 'جدولة متابعة' : 'Follow-up Scheduled',
    follow_up_completed: isRtl.value ? 'متابعة مكتملة' : 'Follow-up Completed',
    booking_created: isRtl.value ? 'إنشاء حجز' : 'Booking Created',
    visit_completed: isRtl.value ? 'زيارة مكتملة' : 'Visit Completed',
    payment_received: isRtl.value ? 'دفعة مستلمة' : 'Payment Received',
}));

// Translate system-generated activity subjects & descriptions from DB
function translateSubject(act) {
    if (!isRtl.value) return act.subject || activityTypeLabels.value[act.type] || act.type?.replace(/_/g, ' ');
    const s = act.subject || '';

    // Status change: "Status changed from X to Y"
    const statusMatch = s.match(/^Status changed from (\w+) to (\w+)$/);
    if (statusMatch) {
        const from = statusLabels.value[statusMatch[1]] || statusMatch[1];
        const to = statusLabels.value[statusMatch[2]] || statusMatch[2];
        return `تم تغيير الحالة من ${from} إلى ${to}`;
    }

    // Known static subjects
    const subjectMap = {
        'Visit completed': 'تمت الزيارة',
        'Visit cancelled / no-show': 'إلغاء الزيارة / لم يحضر',
        'Payment received': 'تم استلام دفعة',
        'Lead auto-created from website booking': 'تم إنشاء العميل تلقائياً من حجز الموقع',
        'Lead auto-created from website contact form': 'تم إنشاء العميل تلقائياً من نموذج التواصل',
        'Lead created': 'تم إنشاء العميل المحتمل',
        'Lead reassigned': 'تم إعادة تعيين العميل',
        'Lead converted to patient': 'تم تحويل العميل إلى مريض',
        'Lead merged': 'تم دمج العميل المحتمل',
        'New contact form submission': 'نموذج تواصل جديد',
        'Website booking submitted': 'تم تقديم حجز من الموقع',
        'Booking created': 'تم إنشاء حجز',
    };
    if (subjectMap[s]) return subjectMap[s];

    // Follow-up patterns
    const fuScheduled = s.match(/^Follow-up scheduled: (.+?) at (.+)$/);
    if (fuScheduled) return `تم جدولة متابعة: ${followUpTypeLabels.value[fuScheduled[1]] || fuScheduled[1]}`;
    const fuCompleted = s.match(/^Follow-up completed: (.+)$/);
    if (fuCompleted) return `تم إكمال متابعة: ${followUpTypeLabels.value[fuCompleted[1]] || fuCompleted[1]}`;
    const fuMissed = s.match(/^Follow-up missed: (.+)$/);
    if (fuMissed) return `متابعة فائتة: ${followUpTypeLabels.value[fuMissed[1]] || fuMissed[1]}`;
    const fuRescheduled = s.match(/^Follow-up rescheduled to (.+)$/);
    if (fuRescheduled) return `تم إعادة جدولة المتابعة إلى ${fuRescheduled[1]}`;

    // Auto-assign
    if (s.startsWith('Auto-assigned via rule:')) return `تعيين تلقائي عبر قاعدة: ${s.replace('Auto-assigned via rule: ', '')}`;
    if (s.startsWith('Auto-enrolled in sequence:')) return `تسجيل تلقائي في تسلسل: ${s.replace('Auto-enrolled in sequence: ', '')}`;

    // Auto messages
    if (s === 'Auto WhatsApp (Sequence)') return 'واتساب تلقائي (تسلسل)';
    if (s === 'Auto Email (Sequence)') return 'بريد تلقائي (تسلسل)';
    if (s === 'Auto SMS (Sequence)') return 'رسالة نصية تلقائية (تسلسل)';
    if (s.startsWith('Sequence step executed:')) return `تنفيذ خطوة تسلسل: ${s.replace('Sequence step executed: ', '')}`;
    if (s === 'Dormant lead detected') return 'تم اكتشاف عميل خامل';

    // Fallback to type label
    return activityTypeLabels.value[act.type] || s || act.type?.replace(/_/g, ' ');
}

function translateDescription(desc) {
    if (!isRtl.value || !desc) return desc;

    // "Completed: ServiceName"
    const completedMatch = desc.match(/^Completed: (.+)$/);
    if (completedMatch) return `مكتمل: ${completedMatch[1]}`;

    // Known descriptions
    const descMap = {
        'Visit completed at the clinic': 'تمت الزيارة في العيادة',
        'Booked via website booking form': 'تم الحجز عبر نموذج الموقع',
        'Service interest via website booking form': 'اهتمام بخدمة عبر نموذج حجز الموقع',
        'Patient did not attend or visit was cancelled.': 'لم يحضر المريض أو تم إلغاء الزيارة.',
    };
    if (descMap[desc]) return descMap[desc];

    // Amount pattern
    const amountMatch = desc.match(/^Amount: (.+)$/);
    if (amountMatch) return `المبلغ: ${amountMatch[1]}`;

    // Subject/Message pattern from contact form
    const subjMsgMatch = desc.match(/^Subject: (.+)\nMessage: (.+)$/s);
    if (subjMsgMatch) return `الموضوع: ${subjMsgMatch[1]}\nالرسالة: ${subjMsgMatch[2]}`;

    // Merged description
    if (desc.startsWith('Merged with lead #')) return desc.replace(/Merged with lead #(\d+) \((.+)\)\. Data absorbed into this lead\./, 'تم الدمج مع العميل #$1 ($2). تم استيعاب البيانات.');

    return desc;
}
</script>

<template>
    <AdminLayout :title="`${$t('a_lead')}: ${lead.full_name}`">
        <div class="space-y-6 pb-8">

            <!-- ===================== HEADER CARD ===================== -->
            <div
                :class="mounted ? 'translate-y-0 opacity-100' : 'translate-y-4 opacity-0'"
                class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden transition-all duration-700 ease-out hover:-translate-y-0.5 hover:shadow-md"
            >
                <!-- Gold gradient top bar -->
                <div class="h-1 bg-gradient-to-r from-[#C4A265] via-[#D4B87A] to-[#C4A265]"></div>

                <div class="p-4 md:p-6 lg:p-8">
                    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
                        <!-- Left: Avatar + Info -->
                        <div class="flex items-start gap-4">
                            <Link href="/admin/leads" class="mt-2 p-2 rounded-xl text-gray-400 hover:text-[#C4A265] hover:bg-[#C4A265]/5 transition-all duration-200 shrink-0">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
                            </Link>
                            <!-- Avatar Initials -->
                            <div class="w-14 h-14 rounded-2xl flex items-center justify-center text-lg font-bold text-white shrink-0 shadow-lg"
                                 style="background: linear-gradient(135deg, #C4A265, #D4B87A);">
                                {{ getInitials(lead.full_name) }}
                            </div>
                            <div class="min-w-0">
                                <div class="flex items-center gap-3 flex-wrap">
                                    <h1 class="text-xl md:text-2xl font-bold text-gray-900 tracking-tight">{{ lead.full_name }}</h1>
                                    <span :class="statusColors[lead.status]" class="inline-flex items-center px-3 py-1 text-xs font-semibold rounded-full ring-1 ring-inset">
                                        {{ statusLabels[lead.status] }}
                                    </span>
                                    <span :class="priorityColors[lead.priority]" class="inline-flex items-center gap-1 px-2.5 py-0.5 text-[10px] font-bold rounded-full uppercase tracking-wide">
                                        <span class="w-1.5 h-1.5 rounded-full" :style="{ backgroundColor: priorityDotColors[lead.priority] }"></span>
                                        {{ priorityLabels[lead.priority] }}
                                    </span>
                                </div>
                                <div class="flex items-center gap-2 mt-2 text-sm text-gray-500 flex-wrap">
                                    <span v-if="lead.source" class="inline-flex items-center gap-1.5">
                                        <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" /></svg>
                                        {{ (isRtl ? lead.source?.name_ar : lead.source?.name_en) || $t('a_unknown_source') }}
                                    </span>
                                    <span v-if="lead.campaign" class="text-gray-300">|</span>
                                    <span v-if="lead.campaign" class="inline-flex items-center gap-1.5">
                                        <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z" /></svg>
                                        {{ lead.campaign.name }}
                                    </span>
                                    <span v-if="lead.assigned_user" class="text-gray-300">|</span>
                                    <span v-if="lead.assigned_user" class="inline-flex items-center gap-1.5">
                                        <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                                        {{ $t('a_assigned_to') }} <span class="font-medium text-gray-700">{{ lead.assigned_user.name }}</span>
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Right: Score + Actions -->
                        <div class="flex items-center gap-4 flex-wrap">
                            <!-- Score Badge -->
                            <div class="flex items-center gap-3 px-5 py-3 rounded-2xl bg-gradient-to-br from-gray-50 to-gray-100/80 border border-gray-200/60 shadow-sm">
                                <div class="relative w-12 h-12">
                                    <svg class="w-12 h-12 -rotate-90" viewBox="0 0 36 36">
                                        <path d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" fill="none" stroke="#e5e7eb" stroke-width="2.5" />
                                        <path d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"
                                            fill="none" stroke="url(#scoreGradient)" stroke-width="2.5" stroke-dasharray="100"
                                            :stroke-dashoffset="100 - Math.min(lead.score || 0, 100)"
                                            stroke-linecap="round"
                                            class="transition-all duration-1000 ease-out" />
                                        <defs>
                                            <linearGradient id="scoreGradient" x1="0%" y1="0%" x2="100%" y2="0%">
                                                <stop offset="0%" stop-color="#C4A265" />
                                                <stop offset="100%" stop-color="#D4B87A" />
                                            </linearGradient>
                                        </defs>
                                    </svg>
                                    <span class="absolute inset-0 flex items-center justify-center text-sm font-bold text-gray-800">{{ lead.score }}</span>
                                </div>
                                <div class="ltr:text-left rtl:text-right">
                                    <p class="text-[10px] uppercase tracking-widest text-gray-400 font-semibold">{{ $t('a_lead_score') }}</p>
                                    <p class="text-xs font-medium text-gray-500">{{ $t('a_out_of_100') }}</p>
                                </div>
                            </div>

                            <!-- Action Buttons -->
                            <div class="flex items-center gap-2.5 flex-wrap">
                                <button v-if="can('leads.update') && templates?.length && lead.status !== 'converted'"
                                    @click="showQuickSend = !showQuickSend"
                                    class="inline-flex items-center px-4 py-2.5 rounded-xl text-sm font-medium transition-all duration-200 shadow-sm bg-emerald-600 hover:bg-emerald-700 hover:shadow-md hover:-translate-y-0.5 text-white"
                                >
                                    <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" /></svg>
                                    {{ $t('a_quick_send') }}
                                </button>
                                <button v-if="can('leads.convert') && lead.status !== 'converted' && lead.status !== 'lost'"
                                    @click="openConvertModal"
                                    class="inline-flex items-center px-4 py-2.5 rounded-xl text-white text-sm font-medium transition-all duration-200 shadow-sm bg-emerald-600 hover:bg-emerald-700 hover:shadow-md hover:-translate-y-0.5"
                                >
                                    <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                    {{ $t('a_convert_to_patient') }}
                                </button>
                                <Link v-if="can('leads.update')" :href="`/admin/leads/${lead.id}/edit`"
                                    class="inline-flex items-center px-4 py-2.5 rounded-xl text-white text-sm font-medium transition-all duration-200 shadow-sm hover:shadow-md hover:-translate-y-0.5"
                                    style="background: linear-gradient(135deg, #C4A265, #D4B87A);"
                                >
                                    <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>{{ $t('a_edit_lead') }}</Link>
                                <!-- Delete lead -->
                                <button v-if="can('leads.delete')"
                                    @click="deleteLead"
                                    class="inline-flex items-center px-4 py-2.5 rounded-xl text-red-600 border border-red-200 bg-white text-sm font-medium transition-all duration-200 hover:bg-red-50 hover:border-red-300"
                                >
                                    <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M1 7h22M9 7V4a2 2 0 012-2h2a2 2 0 012 2v3" /></svg>
                                    {{ isRtl ? 'حذف' : 'Delete' }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ===================== QUICK SEND PANEL ===================== -->
            <transition enter-active-class="transition-all duration-300 ease-out" leave-active-class="transition-all duration-200 ease-in"
                enter-from-class="opacity-0 -translate-y-3 scale-[0.98]" enter-to-class="opacity-100 translate-y-0 scale-100"
                leave-from-class="opacity-100 translate-y-0 scale-100" leave-to-class="opacity-0 -translate-y-3 scale-[0.98]">
                <div v-if="showQuickSend" class="bg-white rounded-2xl shadow-lg border border-gray-100 overflow-hidden">
                    <div class="h-1 bg-gradient-to-r from-emerald-400 via-emerald-400 to-emerald-400"></div>
                    <div class="border-b border-gray-100 px-4 md:px-6 py-4 flex items-center justify-between bg-gradient-to-r from-gray-50 to-white">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg bg-emerald-100 flex items-center justify-center">
                                <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" /></svg>
                            </div>
                            <h3 class="text-sm font-bold text-gray-800 uppercase tracking-wider">{{ $t('a_quick_send_message') }}</h3>
                        </div>
                        <button @click="showQuickSend = false" class="p-2 rounded-xl text-gray-400 hover:text-gray-600 hover:bg-gray-100 transition-all duration-200">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                        </button>
                    </div>
                    <div class="p-6">
                        <form @submit.prevent="submitQuickSend" class="space-y-5">
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                                <!-- Channel Tabs -->
                                <div>
                                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">{{ $t('a_channel') }}</label>
                                    <div class="flex gap-1.5 bg-gray-100 p-1 rounded-xl">
                                        <button type="button" @click="quickSendForm.channel = 'whatsapp'"
                                            :class="quickSendForm.channel === 'whatsapp' ? 'bg-white text-emerald-700 shadow-sm' : 'text-gray-500 hover:text-gray-700'"
                                            class="flex-1 px-3 py-2.5 rounded-lg text-xs font-semibold transition-all duration-200">
                                            WhatsApp
                                        </button>
                                        <button type="button" @click="quickSendForm.channel = 'sms'"
                                            :class="quickSendForm.channel === 'sms' ? 'bg-white text-[#1B365D] shadow-sm' : 'text-gray-500 hover:text-gray-700'"
                                            class="flex-1 px-3 py-2.5 rounded-lg text-xs font-semibold transition-all duration-200">
                                            SMS
                                        </button>
                                        <button type="button" @click="quickSendForm.channel = 'email'"
                                            :class="quickSendForm.channel === 'email' ? 'bg-white text-[#1B365D] shadow-sm' : 'text-gray-500 hover:text-gray-700'"
                                            class="flex-1 px-3 py-2.5 rounded-lg text-xs font-semibold transition-all duration-200">{{ $t('a_email') }}</button>
                                    </div>
                                </div>
                                <!-- Template (Searchable) -->
                                <div class="relative">
                                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">{{ $t('a_template') }}</label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 ltr:left-0 rtl:right-0 ltr:pl-3 rtl:pr-3 flex items-center pointer-events-none">
                                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                                        </div>
                                        <input type="text"
                                            v-model="templateSearch"
                                            @focus="showTemplateDropdown = true"
                                            @blur="setTimeout(() => showTemplateDropdown = false, 200)"
                                            :placeholder="$t('a_search_templates')"
                                            class="doctorato-input w-full ltr:pl-10 rtl:pr-10 ltr:pr-4 rtl:pl-4 py-2.5 text-sm border border-gray-200 rounded-xl bg-white focus:ring-2 focus:ring-[#C4A265]/30 focus:border-[#C4A265] transition-all duration-200"
                                        />
                                    </div>
                                    <div v-if="showTemplateDropdown && filteredTemplateOptions.length" class="absolute z-20 mt-1 w-full bg-white border border-gray-200 rounded-xl shadow-lg max-h-48 overflow-y-auto">
                                        <button type="button" v-for="t in filteredTemplateOptions" :key="t.id"
                                            @mousedown.prevent="selectTemplate(t)"
                                            :class="quickSendForm.template_id === t.id ? 'bg-[#C4A265]/10 text-[#C4A265]' : 'text-gray-700 hover:bg-gray-50'"
                                            class="w-full ltr:text-left rtl:text-right px-4 py-2.5 text-sm transition-colors duration-150">
                                            {{ t.name }}
                                        </button>
                                    </div>
                                </div>
                                <!-- Language -->
                                <div>
                                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">{{ $t('a_language') }}</label>
                                    <div class="flex gap-1.5 bg-gray-100 p-1 rounded-xl">
                                        <button type="button" @click="quickSendForm.language = 'en'"
                                            :class="quickSendForm.language === 'en' ? 'bg-white text-[#C4A265] shadow-sm' : 'text-gray-500 hover:text-gray-700'"
                                            class="flex-1 px-3 py-2.5 rounded-lg text-xs font-semibold transition-all duration-200">{{ $t('a_english') }}</button>
                                        <button type="button" @click="quickSendForm.language = 'ar'"
                                            :class="quickSendForm.language === 'ar' ? 'bg-white text-[#C4A265] shadow-sm' : 'text-gray-500 hover:text-gray-700'"
                                            class="flex-1 px-3 py-2.5 rounded-lg text-xs font-semibold transition-all duration-200">{{ $t('a_arabic') }}</button>
                                    </div>
                                </div>
                            </div>
                            <!-- Preview -->
                            <transition enter-active-class="transition-all duration-300" enter-from-class="opacity-0 translate-y-2" enter-to-class="opacity-100 translate-y-0">
                                <div v-if="previewMessage" class="bg-gradient-to-br from-gray-50 to-gray-100/50 rounded-xl p-5 border border-gray-200/60">
                                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-2">{{ $t('a_message_preview') }}</p>
                                    <p class="text-sm text-gray-700 whitespace-pre-wrap leading-relaxed" :dir="quickSendForm.language === 'ar' ? 'rtl' : 'ltr'">{{ previewMessage }}</p>
                                </div>
                            </transition>
                            <div class="flex justify-end">
                                <button type="submit" :disabled="quickSendForm.processing || !quickSendForm.template_id"
                                    class="inline-flex items-center px-4 md:px-6 py-2.5 text-sm font-medium text-white rounded-xl transition-all duration-200 disabled:opacity-50 shadow-sm hover:shadow-md hover:-translate-y-0.5"
                                    :class="quickSendForm.channel === 'whatsapp' ? 'bg-emerald-600 hover:bg-emerald-700' : quickSendForm.channel === 'sms' ? 'bg-[#1B365D] hover:bg-[#1B365D]' : 'bg-[#1B365D] hover:bg-[#1B365D]'"
                                >
                                    <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" /></svg>
                                    {{ quickSendForm.channel === 'whatsapp' ? $t('a_send_via_whatsapp') : quickSendForm.channel === 'sms' ? $t('a_log_sms') : $t('a_log_email') }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </transition>

            <!-- ===================== PIPELINE STEPPER ===================== -->
            <div v-if="currentStepIndex >= 0"
                :class="mounted ? 'translate-y-0 opacity-100' : 'translate-y-4 opacity-0'"
                class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden transition-all duration-700 delay-100 ease-out hover:-translate-y-0.5 hover:shadow-md"
            >
                <div class="h-1 bg-gradient-to-r from-[#C4A265] via-[#D4B87A] to-[#C4A265]"></div>
                <div class="px-4 md:px-6 py-5">
                    <div class="flex items-center">
                        <template v-for="(status, idx) in pipelineStatuses" :key="status">
                            <div class="flex flex-col items-center relative group">
                                <div :class="[
                                    idx < currentStepIndex ? 'text-white shadow-md' : '',
                                    idx === currentStepIndex ? 'text-white shadow-lg ring-4 ring-[#C4A265]/20 scale-110' : '',
                                    idx > currentStepIndex ? 'bg-gray-100 text-gray-400 border-2 border-gray-200' : '',
                                ]"
                                    :style="idx <= currentStepIndex ? 'background: linear-gradient(135deg, #C4A265, #D4B87A)' : ''"
                                    class="w-10 h-10 rounded-full flex items-center justify-center text-xs font-bold shrink-0 transition-all duration-500 relative z-10"
                                >
                                    <svg v-if="idx < currentStepIndex" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" /></svg>
                                    <span v-else>{{ idx + 1 }}</span>
                                </div>
                                <span class="text-[9px] font-medium mt-2.5 text-gray-500 whitespace-nowrap hidden md:block"
                                    :class="idx === currentStepIndex ? 'text-[#C4A265] font-bold' : ''">
                                    {{ statusLabels[status] }}
                                </span>
                            </div>
                            <div v-if="idx < pipelineStatuses.length - 1" class="flex-1 h-0.5 mx-1.5 relative">
                                <div class="absolute inset-0 bg-gray-200 rounded-full"></div>
                                <div :class="idx < currentStepIndex ? 'w-full' : 'w-0'"
                                    class="absolute inset-y-0 ltr:left-0 rtl:right-0 rounded-full transition-all duration-700 ease-out"
                                    style="background: linear-gradient(90deg, #C4A265, #D4B87A);"></div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            <!-- ===================== CONVERTED INFO ===================== -->
            <transition enter-active-class="transition-all duration-500" enter-from-class="opacity-0 scale-95" enter-to-class="opacity-100 scale-100">
                <div v-if="lead.status === 'converted' && lead.patient" class="bg-gradient-to-r from-emerald-50 to-emerald-50 border border-emerald-200 rounded-2xl p-4 md:p-6 flex items-center gap-4">
                    <div class="w-12 h-12 rounded-2xl bg-emerald-100 flex items-center justify-center shrink-0">
                        <svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-bold text-emerald-800">{{ $t('a_lead_converted_patient') }}</p>
                        <p class="text-xs text-emerald-600 mt-0.5">{{ $t('a_patient') }}: {{ lead.patient.full_name }} ({{ lead.patient.file_number }})</p>
                    </div>
                    <Link :href="`/admin/patients/${lead.patient.id}`"
                        class="px-5 py-2.5 text-sm font-medium text-white rounded-xl hover:shadow-md transition-all duration-200 shrink-0"
                        style="background: linear-gradient(135deg, #059669, #10b981);">
                        {{ $t('a_view_patient') }}
                    </Link>
                </div>
            </transition>

            <!-- ===================== LOST INFO ===================== -->
            <div v-if="lead.status === 'lost' || lead.status === 'dormant'" class="bg-gradient-to-r from-red-50 to-amber-50 border border-red-200 rounded-2xl p-6">
                <div class="flex items-center justify-between gap-4">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 rounded-2xl bg-red-100 flex items-center justify-center shrink-0">
                            <svg class="w-6 h-6 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                        </div>
                        <div>
                            <p class="text-sm font-bold text-red-800">{{ lead.status === 'lost' ? $t('a_lead_marked_lost') : (isRtl ? 'العميل المحتمل خامل' : 'Lead is Dormant') }}</p>
                            <p v-if="lead.loss_reason" class="text-xs text-red-600 mt-0.5">{{ lead.loss_reason }}</p>
                            <p v-if="lead.lost_at" class="text-xs text-red-400 mt-0.5">{{ $t('a_lost_on') }} {{ formatDate(lead.lost_at) }}</p>
                        </div>
                    </div>
                    <button v-if="can('leads.update')"
                        @click="reactivateLead"
                        :disabled="reactivating"
                        class="inline-flex items-center gap-2 px-4 py-2.5 text-xs font-semibold rounded-xl border border-emerald-300 bg-white text-emerald-700 hover:bg-emerald-50 hover:shadow-md hover:-translate-y-0.5 transition-all duration-200 shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        {{ isRtl ? 'اعادة تنشيط' : 'Reactivate' }}
                    </button>
                </div>
            </div>

            <!-- ===================== MAIN CONTENT: TWO-COLUMN ===================== -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                <!-- ============ LEFT COLUMN (wider) ============ -->
                <div class="lg:col-span-2 space-y-6">

                    <!-- Quick Status Actions -->
                    <div v-if="can('leads.update') && lead.status !== 'converted'"
                        :class="mounted ? 'translate-y-0 opacity-100' : 'translate-y-4 opacity-0'"
                        class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden transition-all duration-700 delay-200 ease-out hover:-translate-y-0.5 hover:shadow-md"
                    >
                        <div class="h-1 bg-gradient-to-r from-[#C4A265] via-[#D4B87A] to-[#C4A265]"></div>
                        <div class="p-6">
                            <h3 class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-4 flex items-center gap-2">
                                <svg class="w-4 h-4 text-[#C4A265]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M5 5l7 7-7 7" /></svg>
                                {{ $t('a_move_to_stage') }}
                            </h3>
                            <div class="flex flex-wrap gap-2">
                                <button v-for="s in ['new','contacted','qualified','appointment_booked','consultation_done','negotiation','lost']" :key="s"
                                    @click="changeStatus(s)"
                                    :disabled="lead.status === s"
                                    :class="[
                                        lead.status === s ? 'ring-2 ring-[#C4A265] bg-[#C4A265]/5 text-[#C4A265] cursor-not-allowed font-bold' : 'hover:shadow-md hover:border-gray-300 hover:-translate-y-0.5',
                                        s === 'lost' ? 'border-red-200 text-red-600 hover:bg-red-50' : 'border-gray-200 text-gray-700 hover:bg-gray-50'
                                    ]"
                                    class="px-4 py-2.5 text-xs font-semibold rounded-xl border transition-all duration-200"
                                >{{ statusLabels[s] }}</button>
                            </div>
                        </div>
                    </div>

                    <!-- ============ TABS SECTION ============ -->
                    <div
                        :class="mounted ? 'translate-y-0 opacity-100' : 'translate-y-4 opacity-0'"
                        class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden transition-all duration-700 delay-300 ease-out"
                    >
                        <div class="h-1 bg-gradient-to-r from-[#C4A265] via-[#D4B87A] to-[#C4A265]"></div>

                        <!-- Tab Headers -->
                        <div class="px-4 md:px-6 py-3 border-b border-slate-100 bg-slate-50/50">
                            <div class="overflow-x-auto">
                                <div class="inline-flex gap-1 bg-slate-100 p-1 rounded-xl min-w-full sm:min-w-0">
                                    <button @click="activeTab = 'activity'"
                                        :class="[
                                            'px-4 py-2 rounded-lg text-sm font-semibold transition whitespace-nowrap flex items-center gap-2',
                                            activeTab === 'activity'
                                                ? 'bg-gradient-to-r from-[#1B365D] to-[#2C4E7A] text-white shadow-sm'
                                                : 'text-slate-600 hover:bg-white hover:text-[#1B365D]'
                                        ]"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                        {{ $t('a_activity_timeline') }}
                                        <span v-if="activities?.length" class="ms-1 inline-flex items-center rounded-full px-1.5 py-0.5 text-[10px] font-bold"
                                            :class="activeTab === 'activity' ? 'bg-[#C4A265] text-white' : 'bg-slate-200 text-slate-600'">
                                            {{ activities.length }}
                                        </span>
                                    </button>
                                    <button @click="activeTab = 'followups'"
                                        :class="[
                                            'px-4 py-2 rounded-lg text-sm font-semibold transition whitespace-nowrap flex items-center gap-2',
                                            activeTab === 'followups'
                                                ? 'bg-gradient-to-r from-[#1B365D] to-[#2C4E7A] text-white shadow-sm'
                                                : 'text-slate-600 hover:bg-white hover:text-[#1B365D]'
                                        ]"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                                        {{ $t('a_follow_ups') }}
                                        <span v-if="followUps?.length" class="ms-1 inline-flex items-center rounded-full px-1.5 py-0.5 text-[10px] font-bold"
                                            :class="activeTab === 'followups' ? 'bg-[#C4A265] text-white' : 'bg-slate-200 text-slate-600'">
                                            {{ followUps.length }}
                                        </span>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- ======== ACTIVITY TAB ======== -->
                        <div v-show="activeTab === 'activity'" class="p-4 md:p-6 space-y-6">
                            <!-- Log Activity Form -->
                            <form @submit.prevent="submitActivity" class="bg-gradient-to-br from-gray-50 to-gray-100/30 rounded-2xl p-5 space-y-4 border border-gray-100">
                                <p class="text-xs font-bold text-gray-500 uppercase tracking-wider flex items-center gap-2">
                                    <svg class="w-4 h-4 text-[#C4A265]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" /></svg>
                                    {{ $t('a_log_activity') }}
                                </p>
                                <!-- Type selector as tabs -->
                                <div class="flex flex-wrap gap-1.5">
                                    <button v-for="at in activityTypes" :key="at.value" type="button"
                                        @click="activityForm.type = at.value"
                                        :class="activityForm.type === at.value ? 'text-white shadow-sm' : 'bg-white text-gray-600 border border-gray-200 hover:border-gray-300 hover:shadow-sm'"
                                        :style="activityForm.type === at.value ? 'background: linear-gradient(135deg, #C4A265, #D4B87A)' : ''"
                                        class="inline-flex items-center gap-1.5 px-3.5 py-2.5 rounded-xl text-xs font-medium transition-all duration-200"
                                    >
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" :d="at.icon" /></svg>
                                        {{ at.label }}
                                    </button>
                                </div>
                                <div class="grid grid-cols-2 gap-3" v-if="['call','whatsapp','email','sms'].includes(activityForm.type) || activityForm.type === 'call'">
                                    <div v-if="['call','whatsapp','email','sms'].includes(activityForm.type)" class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" /></svg>
                                        </div>
                                        <select v-model="activityForm.direction"
                                            class="doctorato-input w-full pl-10 pr-4 text-sm border border-gray-200 rounded-xl py-2.5 bg-white focus:ring-2 focus:ring-[#C4A265]/30 focus:border-[#C4A265] transition-all duration-200">
                                            <option value="">{{ $t('a_direction') }}</option>
                                            <option value="inbound">{{ $t('a_inbound') }}</option>
                                            <option value="outbound">{{ $t('a_outbound') }}</option>
                                        </select>
                                    </div>
                                    <div v-if="activityForm.type === 'call'" class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                        </div>
                                        <select v-model="activityForm.outcome"
                                            class="doctorato-input w-full pl-10 pr-4 text-sm border border-gray-200 rounded-xl py-2.5 bg-white focus:ring-2 focus:ring-[#C4A265]/30 focus:border-[#C4A265] transition-all duration-200">
                                            <option value="">{{ $t('a_outcome') }}</option>
                                            <option value="successful">{{ $t('a_successful') }}</option>
                                            <option value="no_answer">{{ $t('a_no_answer') }}</option>
                                            <option value="busy">{{ $t('a_busy') }}</option>
                                            <option value="voicemail">{{ $t('a_voicemail') }}</option>
                                            <option value="callback_requested">{{ $t('a_callback_requested') }}</option>
                                            <option value="not_interested">{{ $t('a_not_interested') }}</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="relative">
                                    <div class="absolute top-3 left-4 pointer-events-none">
                                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="activityTypeIcons.note" /></svg>
                                    </div>
                                    <textarea v-model="activityForm.description" rows="2" :placeholder="$t('a_what_happened_placeholder')"
                                        class="doctorato-input w-full text-sm border border-gray-200 rounded-xl py-3 pl-10 pr-4 resize-none bg-white focus:ring-2 focus:ring-[#C4A265]/30 focus:border-[#C4A265] transition-all duration-200 placeholder:text-gray-400"></textarea>
                                </div>
                                <div class="flex justify-end">
                                    <button type="submit" :disabled="activityForm.processing"
                                        class="px-4 md:px-6 py-2.5 text-xs font-semibold text-white rounded-xl transition-all duration-200 shadow-sm hover:shadow-md hover:-translate-y-0.5 disabled:opacity-50"
                                        style="background: linear-gradient(135deg, #C4A265, #D4B87A);">
                                        {{ $t('a_log_activity') }}
                                    </button>
                                </div>
                            </form>

                            <!-- Activity Timeline -->
                            <div class="relative">
                                <div v-if="activities?.length" class="absolute left-[19px] top-4 bottom-4 w-px bg-gradient-to-b from-gray-200 via-gray-200 to-transparent"></div>
                                <div class="space-y-0">
                                    <div v-for="(act, aIdx) in activities" :key="act.id"
                                        class="flex gap-4 py-4 relative group hover:bg-gray-50/50 rounded-xl px-2 -mx-2 transition-all duration-200"
                                    >
                                        <div class="relative z-10">
                                            <div :class="activityTypeColors[act.type] || 'bg-gray-100 text-gray-500'"
                                                class="w-10 h-10 rounded-full flex items-center justify-center shrink-0 ring-4 ring-white transition-all duration-300 group-hover:scale-110 group-hover:shadow-md">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" :d="activityTypeIcons[act.type] || activityTypeIcons.system" /></svg>
                                            </div>
                                        </div>
                                        <div class="flex-1 min-w-0 pt-1">
                                            <div class="flex items-center justify-between gap-2">
                                                <p class="text-sm font-semibold text-gray-800">
                                                    {{ translateSubject(act) }}
                                                </p>
                                                <span class="text-[10px] text-gray-400 whitespace-nowrap bg-gray-100 px-2.5 py-0.5 rounded-full font-medium">{{ timeAgo(act.created_at) }}</span>
                                            </div>
                                            <p v-if="act.description" class="text-xs text-gray-500 mt-1 leading-relaxed line-clamp-2">{{ translateDescription(act.description) }}</p>
                                            <div class="flex items-center gap-2 mt-2 flex-wrap">
                                                <span v-if="act.direction" class="text-[10px] px-2 py-0.5 rounded-full bg-gray-100 text-gray-500 font-medium">{{ directionLabels[act.direction] || act.direction }}</span>
                                                <span v-if="act.outcome" class="text-[10px] px-2 py-0.5 rounded-full bg-gray-100 text-gray-500 font-medium">{{ outcomeLabels[act.outcome] || act.outcome?.replace(/_/g, ' ') }}</span>
                                                <span v-if="act.performer" class="text-[10px] text-gray-400 flex items-center gap-1">
                                                    <span class="w-4 h-4 rounded-full flex items-center justify-center text-[7px] font-bold text-white" style="background: linear-gradient(135deg, #C4A265, #D4B87A);">{{ getInitials(act.performer.name) }}</span>
                                                    {{ act.performer.name }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    <div v-if="!activities?.length" class="py-16 text-center">
                                        <div class="w-20 h-20 mx-auto rounded-2xl bg-gray-50 flex items-center justify-center mb-4 border border-gray-100">
                                            <svg class="w-10 h-10 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                        </div>
                                        <p class="text-sm font-medium text-gray-400">{{ $t('a_no_activity_yet') }}</p>
                                        <p class="text-xs text-gray-300 mt-1">{{ $t('a_log_first_activity_hint') }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- ======== FOLLOW-UPS TAB ======== -->
                        <div v-show="activeTab === 'followups'" class="p-4 md:p-6 space-y-5">
                            <div class="flex justify-between items-center">
                                <p class="text-xs font-bold text-gray-500 uppercase tracking-wider flex items-center gap-2">
                                    <svg class="w-4 h-4 text-[#C4A265]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                                    {{ $t('a_scheduled_follow_ups') }}
                                </p>
                                <button @click="showFollowUpForm = !showFollowUpForm"
                                    class="inline-flex items-center gap-2 text-xs font-semibold px-4 py-2.5 rounded-xl transition-all duration-200 shadow-sm hover:shadow-md hover:-translate-y-0.5 text-white"
                                    style="background: linear-gradient(135deg, #C4A265, #D4B87A);">
                                    <svg v-if="!showFollowUpForm" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" /></svg>
                                    <svg v-else class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                                    {{ showFollowUpForm ? $t('a_cancel') : $t('a_schedule_follow_up') }}
                                </button>
                            </div>

                            <!-- Follow-up Form -->
                            <transition enter-active-class="transition-all duration-300" enter-from-class="opacity-0 -translate-y-2" enter-to-class="opacity-100 translate-y-0"
                                leave-active-class="transition-all duration-200" leave-from-class="opacity-100" leave-to-class="opacity-0">
                                <form v-if="showFollowUpForm" @submit.prevent="submitFollowUp" class="bg-gradient-to-br from-gray-50 to-gray-100/30 rounded-2xl p-5 space-y-4 border border-gray-100">
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                        <div class="relative">
                                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" /></svg>
                                            </div>
                                            <select v-model="followUpForm.type" class="doctorato-input w-full pl-10 pr-4 text-sm border border-gray-200 rounded-xl py-2.5 bg-white focus:ring-2 focus:ring-[#C4A265]/30 focus:border-[#C4A265] transition-all">
                                                <option value="call">{{ $t('a_call') }}</option>
                                                <option value="whatsapp">{{ $t('a_whatsapp') }}</option>
                                                <option value="email">{{ $t('a_email') }}</option>
                                                <option value="sms">{{ $t('a_sms') }}</option>
                                                <option value="meeting">{{ $t('a_meeting') }}</option>
                                                <option value="other">{{ $t('a_other') }}</option>
                                            </select>
                                        </div>
                                        <div class="relative">
                                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                                            </div>
                                            <input v-model="followUpForm.scheduled_at" type="datetime-local" class="doctorato-input w-full pl-10 pr-4 text-sm border border-gray-200 rounded-xl py-2.5 bg-white focus:ring-2 focus:ring-[#C4A265]/30 focus:border-[#C4A265] transition-all" />
                                        </div>
                                        <!-- Assignee (Searchable) -->
                                        <div class="relative">
                                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                                            </div>
                                            <input type="text"
                                                v-model="assigneeSearch"
                                                @focus="showAssigneeDropdown = true"
                                                @blur="setTimeout(() => showAssigneeDropdown = false, 200)"
                                                :placeholder="$t('a_search_assignee')"
                                                class="doctorato-input w-full pl-10 pr-4 text-sm border border-gray-200 rounded-xl py-2.5 bg-white focus:ring-2 focus:ring-[#C4A265]/30 focus:border-[#C4A265] transition-all"
                                            />
                                            <div v-if="showAssigneeDropdown && filteredAssigneeOptions.length" class="absolute z-20 mt-1 w-full bg-white border border-gray-200 rounded-xl shadow-lg max-h-48 overflow-y-auto">
                                                <button type="button"
                                                    @mousedown.prevent="followUpForm.assigned_to = ''; assigneeSearch = ''; showAssigneeDropdown = false"
                                                    class="w-full text-left px-4 py-2.5 text-sm text-gray-400 hover:bg-gray-50 transition-colors">
                                                    {{ $t('a_assign_to_me') }}
                                                </button>
                                                <button type="button" v-for="u in filteredAssigneeOptions" :key="u.id"
                                                    @mousedown.prevent="selectAssignee(u)"
                                                    :class="followUpForm.assigned_to === u.id ? 'bg-[#C4A265]/10 text-[#C4A265]' : 'text-gray-700 hover:bg-gray-50'"
                                                    class="w-full text-left px-4 py-2.5 text-sm transition-colors duration-150">
                                                    {{ u.name }}
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="relative">
                                        <div class="absolute top-3 left-4 pointer-events-none">
                                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="activityTypeIcons.note" /></svg>
                                        </div>
                                        <textarea v-model="followUpForm.notes" rows="2" :placeholder="$t('a_follow_up_notes_placeholder')"
                                            class="doctorato-input w-full text-sm border border-gray-200 rounded-xl py-3 pl-10 pr-4 resize-none bg-white focus:ring-2 focus:ring-[#C4A265]/30 focus:border-[#C4A265] transition-all placeholder:text-gray-400"></textarea>
                                    </div>
                                    <div class="flex justify-end">
                                        <button type="submit" :disabled="followUpForm.processing"
                                            class="px-4 md:px-6 py-2.5 text-xs font-semibold text-white rounded-xl shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-all disabled:opacity-50"
                                            style="background: linear-gradient(135deg, #C4A265, #D4B87A);">
                                            {{ $t('a_schedule') }}
                                        </button>
                                    </div>
                                </form>
                            </transition>

                            <!-- Follow-up Cards -->
                            <div class="grid gap-3">
                                <div v-for="fu in followUps" :key="fu.id"
                                    :class="[
                                        fu.status === 'pending' && new Date(fu.scheduled_at) < new Date() ? 'border-red-200 bg-red-50/30' : 'border-gray-100 bg-white',
                                    ]"
                                    class="rounded-2xl border p-5 transition-all duration-200 hover:shadow-md hover:-translate-y-0.5 group"
                                >
                                    <div class="flex items-start gap-4">
                                        <div :class="{
                                            'bg-amber-100 text-amber-600': fu.status === 'pending',
                                            'bg-emerald-100 text-emerald-600': fu.status === 'completed',
                                            'bg-red-100 text-red-600': fu.status === 'missed',
                                            'bg-gray-100 text-gray-500': fu.status === 'cancelled' || fu.status === 'rescheduled',
                                        }" class="w-11 h-11 rounded-xl flex items-center justify-center shrink-0">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <div class="flex items-center gap-2 flex-wrap">
                                                <span class="text-sm font-bold text-gray-800">{{ followUpTypeLabels[fu.type] || fu.type }}</span>
                                                <span class="text-[10px] px-2.5 py-0.5 rounded-full font-semibold ring-1 ring-inset"
                                                    :class="{
                                                        'bg-amber-100 text-amber-700 ring-amber-600/10': fu.status === 'pending',
                                                        'bg-emerald-100 text-emerald-700 ring-emerald-600/10': fu.status === 'completed',
                                                        'bg-red-100 text-red-700 ring-red-600/10': fu.status === 'missed',
                                                        'bg-gray-100 text-gray-600 ring-gray-500/10': fu.status === 'cancelled' || fu.status === 'rescheduled',
                                                    }"
                                                >{{ followUpStatusLabels[fu.status] || fu.status }}</span>
                                                <span v-if="fu.status === 'pending' && new Date(fu.scheduled_at) < new Date()" class="text-[10px] px-2.5 py-0.5 rounded-full bg-red-100 text-red-700 font-semibold ring-1 ring-inset ring-red-600/10 animate-pulse">{{ $t('a_overdue') }}</span>
                                            </div>
                                            <p class="text-xs text-gray-500 mt-1.5 flex items-center gap-1.5">
                                                <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                                                {{ formatDateTime(fu.scheduled_at) }}
                                            </p>
                                            <p v-if="fu.notes" class="text-xs text-gray-400 mt-1.5 leading-relaxed">{{ fu.notes }}</p>
                                            <p v-if="fu.result" class="text-xs text-emerald-600 mt-1.5 font-medium flex items-center gap-1">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4" /></svg>
                                                {{ $t('a_result') }}: {{ fu.result }}
                                            </p>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <span v-if="fu.assigned_user" class="hidden sm:inline-flex items-center gap-1.5 text-xs text-gray-400 mr-1">
                                                <span class="w-5 h-5 rounded-full flex items-center justify-center text-[8px] font-bold text-white" style="background: linear-gradient(135deg, #C4A265, #D4B87A);">{{ getInitials(fu.assigned_user.name) }}</span>
                                                {{ fu.assigned_user.name }}
                                            </span>
                                            <!-- Action buttons for pending -->
                                            <template v-if="fu.status === 'pending' && can('leads.update')">
                                                <button @click="openComplete(fu)" :title="$t('a_mark_complete')" class="p-2 rounded-xl text-emerald-500 hover:bg-emerald-50 hover:shadow-sm transition-all duration-200">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                                                </button>
                                                <button @click="missFollowUp(fu.id)" :title="$t('a_mark_missed')" class="p-2 rounded-xl text-red-500 hover:bg-red-50 hover:shadow-sm transition-all duration-200">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                                                </button>
                                                <button @click="openReschedule(fu)" :title="$t('a_reschedule')" class="p-2 rounded-xl text-amber-500 hover:bg-amber-50 hover:shadow-sm transition-all duration-200">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                                                </button>
                                            </template>
                                        </div>
                                    </div>

                                    <!-- Complete form (inline) -->
                                    <transition enter-active-class="transition-all duration-200" enter-from-class="opacity-0" enter-to-class="opacity-100">
                                        <div v-if="completingFollowUp === fu.id" class="mt-4 pt-4 border-t border-gray-100">
                                            <form @submit.prevent="submitComplete(fu.id)" class="flex items-end gap-3">
                                                <div class="flex-1">
                                                    <label class="text-xs font-medium text-gray-500 mb-1.5 block">{{ $t('a_result_notes_optional') }}</label>
                                                    <div class="relative">
                                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4" /></svg>
                                                        </div>
                                                        <input v-model="completeForm.result" type="text" :placeholder="$t('a_complete_result_placeholder')"
                                                            class="doctorato-input w-full pl-10 pr-4 text-sm border border-gray-200 rounded-xl py-2.5 focus:ring-2 focus:ring-[#C4A265]/30 focus:border-[#1B365D] transition-all" />
                                                    </div>
                                                </div>
                                                <button type="submit" :disabled="completeForm.processing" class="px-5 py-2.5 text-xs font-semibold text-white bg-emerald-600 rounded-xl hover:bg-emerald-700 hover:-translate-y-0.5 transition-all shadow-sm">{{ $t('a_done') }}</button>
                                                <button type="button" @click="completingFollowUp = null" class="px-4 py-2.5 text-xs text-gray-500 hover:text-gray-700 rounded-xl hover:bg-gray-100 transition-all">{{ $t('a_cancel') }}</button>
                                            </form>
                                        </div>
                                    </transition>

                                    <!-- Reschedule form (inline) -->
                                    <transition enter-active-class="transition-all duration-200" enter-from-class="opacity-0" enter-to-class="opacity-100">
                                        <div v-if="reschedulingFollowUp === fu.id" class="mt-4 pt-4 border-t border-gray-100">
                                            <form @submit.prevent="submitReschedule(fu.id)" class="flex items-end gap-3">
                                                <div class="flex-1">
                                                    <label class="text-xs font-medium text-gray-500 mb-1.5 block">{{ $t('a_new_date_time') }}</label>
                                                    <div class="relative">
                                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                                                        </div>
                                                        <input v-model="rescheduleForm.scheduled_at" type="datetime-local"
                                                            class="doctorato-input w-full pl-10 pr-4 text-sm border border-gray-200 rounded-xl py-2.5 focus:ring-2 focus:ring-[#C4A265]/40 focus:border-[#C4A265] transition-all" />
                                                    </div>
                                                </div>
                                                <button type="submit" :disabled="rescheduleForm.processing || !rescheduleForm.scheduled_at" class="px-5 py-2.5 text-xs font-semibold text-white bg-[#C4A265] rounded-xl hover:bg-[#8B7043] hover:-translate-y-0.5 transition-all shadow-sm">{{ $t('a_reschedule') }}</button>
                                                <button type="button" @click="reschedulingFollowUp = null" class="px-4 py-2.5 text-xs text-gray-500 hover:text-gray-700 rounded-xl hover:bg-gray-100 transition-all">{{ $t('a_cancel') }}</button>
                                            </form>
                                        </div>
                                    </transition>
                                </div>
                                <div v-if="!followUps?.length" class="py-16 text-center">
                                    <div class="w-20 h-20 mx-auto rounded-2xl bg-gray-50 flex items-center justify-center mb-4 border border-gray-100">
                                        <svg class="w-10 h-10 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                                    </div>
                                    <p class="text-sm font-medium text-gray-400">{{ $t('a_no_follow_ups_scheduled') }}</p>
                                    <p class="text-xs text-gray-300 mt-1">{{ $t('a_schedule_follow_up_hint') }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ============ RIGHT COLUMN (Sidebar) ============ -->
                <div class="space-y-6">

                    <!-- Late Hour Warning -->
                    <transition enter-active-class="transition-all duration-300" enter-from-class="opacity-0 -translate-y-2" enter-to-class="opacity-100 translate-y-0"
                        leave-active-class="transition-all duration-200" leave-from-class="opacity-100" leave-to-class="opacity-0">
                        <div v-if="isLateHour && !lateHourDismissed && lead.status !== 'converted'"
                            class="bg-amber-50 rounded-2xl border border-amber-200 p-4 flex items-start gap-3"
                            :class="mounted ? 'translate-y-0 opacity-100' : 'translate-y-4 opacity-0'"
                            :style="{ transitionDelay: '300ms' }"
                        >
                            <div class="w-9 h-9 rounded-xl bg-amber-100 flex items-center justify-center shrink-0 mt-0.5">
                                <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-xs font-bold text-amber-800">{{ $t('a_late_hour_warning') }}</p>
                                <p class="text-[11px] text-amber-700 mt-0.5">{{ $t('a_late_hour_description') }}</p>
                            </div>
                            <button @click="lateHourDismissed = true" class="p-1 rounded-lg text-amber-400 hover:text-amber-600 hover:bg-amber-100 transition-colors shrink-0">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                            </button>
                        </div>
                    </transition>

                    <!-- Quick Actions (Contact) -->
                    <div v-if="lead.phone && lead.status !== 'converted'"
                        :class="mounted ? 'translate-y-0 opacity-100' : 'translate-y-4 opacity-0'"
                        class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden transition-all duration-700 delay-[350ms] ease-out hover:-translate-y-0.5 hover:shadow-md"
                    >
                        <div class="h-1 bg-gradient-to-r from-[#C4A265] via-[#D4B87A] to-[#C4A265]"></div>
                        <div class="p-5">
                            <h3 class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-4 flex items-center gap-2">
                                <svg class="w-4 h-4 text-[#C4A265]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
                                {{ $t('a_quick_actions') }}
                            </h3>
                            <div class="grid grid-cols-2 gap-2.5">
                                <a :href="`tel:${lead.phone}`" class="flex items-center justify-center gap-2 px-4 py-3.5 text-xs font-semibold rounded-xl border hover:shadow-sm hover:-translate-y-0.5 transition-all duration-200"
                                    :class="isLateHour && !lateHourDismissed ? 'text-gray-400 bg-gray-50 border-gray-200' : 'text-emerald-700 bg-emerald-50 border-emerald-100 hover:bg-emerald-100'">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" /></svg>
                                    {{ $t('a_call') }}
                                </a>
                                <a :href="`https://wa.me/${lead.phone.replace(/[^0-9]/g, '')}`" target="_blank" class="flex items-center justify-center gap-2 px-4 py-3.5 text-xs font-semibold text-emerald-700 bg-emerald-50 rounded-xl border border-emerald-100 hover:bg-emerald-100 hover:shadow-sm hover:-translate-y-0.5 transition-all duration-200">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" /></svg>
                                    {{ $t('a_whatsapp') }}
                                </a>
                                <a v-if="lead.email" :href="`mailto:${lead.email}`" class="flex items-center justify-center gap-2 px-4 py-3.5 text-xs font-semibold text-[#1B365D] bg-slate-50 rounded-xl border border-slate-100 hover:bg-slate-100 hover:shadow-sm hover:-translate-y-0.5 transition-all duration-200 col-span-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>
                                    {{ $t('a_email') }}
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Smart Contact Insights -->
                    <div v-if="smartContact?.total_attempts > 0 && lead.status !== 'converted'"
                        :class="mounted ? 'translate-y-0 opacity-100' : 'translate-y-4 opacity-0'"
                        class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden transition-all duration-700 delay-[380ms] ease-out hover:-translate-y-0.5 hover:shadow-md"
                    >
                        <div class="h-1 bg-gradient-to-r from-slate-400 via-slate-400 to-slate-400"></div>
                        <div class="p-5">
                            <h3 class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-4 flex items-center gap-2">
                                <svg class="w-4 h-4 text-[#1B365D]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" /></svg>
                                {{ $t('a_smart_contact') }}
                            </h3>

                            <div class="space-y-3">
                                <!-- Best Hours -->
                                <div v-if="smartContact.best_hours?.length">
                                    <p class="text-[10px] text-gray-400 font-semibold uppercase tracking-wider mb-1.5">{{ $t('a_best_time_to_call') }}</p>
                                    <div class="flex flex-wrap gap-1.5">
                                        <span v-for="h in smartContact.best_hours" :key="h.hour"
                                            class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-semibold rounded-lg bg-slate-50 text-[#1B365D] border border-slate-100">
                                            <svg class="w-3 h-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                            {{ h.label }}
                                            <span class="text-[9px] text-slate-400">({{ h.successful }}x)</span>
                                        </span>
                                    </div>
                                </div>

                                <!-- Best Day -->
                                <div v-if="smartContact.best_day" class="flex items-center gap-2">
                                    <div class="w-7 h-7 rounded-lg bg-slate-50 flex items-center justify-center">
                                        <svg class="w-3.5 h-3.5 text-[#1B365D]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                                    </div>
                                    <div>
                                        <p class="text-[10px] text-gray-400 font-medium">{{ $t('a_best_day') }}</p>
                                        <p class="text-xs font-semibold text-gray-700">{{ smartContact.best_day.name }}</p>
                                    </div>
                                </div>

                                <!-- Preferred Channel -->
                                <div v-if="smartContact.preferred_channel" class="flex items-center gap-2">
                                    <div class="w-7 h-7 rounded-lg bg-emerald-50 flex items-center justify-center">
                                        <svg class="w-3.5 h-3.5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="channelIcons[smartContact.preferred_channel] || channelIcons.call" /></svg>
                                    </div>
                                    <div>
                                        <p class="text-[10px] text-gray-400 font-medium">{{ $t('a_preferred_channel') }}</p>
                                        <p class="text-xs font-semibold text-gray-700">{{ channelLabels[smartContact.preferred_channel] || smartContact.preferred_channel }}</p>
                                    </div>
                                </div>

                                <!-- Stats -->
                                <div class="grid grid-cols-2 gap-2 pt-2 border-t border-gray-100">
                                    <div class="text-center p-2 rounded-lg bg-gray-50">
                                        <p class="text-lg font-bold text-gray-800">{{ smartContact.total_attempts }}</p>
                                        <p class="text-[9px] text-gray-400 font-semibold uppercase">{{ $t('a_attempts') }}</p>
                                    </div>
                                    <div class="text-center p-2 rounded-lg bg-emerald-50">
                                        <p class="text-lg font-bold text-emerald-700">{{ smartContact.successful_attempts }}</p>
                                        <p class="text-[9px] text-emerald-500 font-semibold uppercase">{{ $t('a_successful') }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Contact Info -->
                    <div
                        :class="mounted ? 'translate-y-0 opacity-100' : 'translate-y-4 opacity-0'"
                        class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden transition-all duration-700 delay-[400ms] ease-out hover:-translate-y-0.5 hover:shadow-md"
                    >
                        <div class="h-1 bg-gradient-to-r from-[#C4A265] via-[#D4B87A] to-[#C4A265]"></div>
                        <div class="p-5">
                            <h3 class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-4 flex items-center gap-2">
                                <svg class="w-4 h-4 text-[#C4A265]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                                {{ $t('a_contact_information') }}
                            </h3>
                            <div class="space-y-3.5">
                                <div class="flex items-center gap-3 p-2.5 rounded-xl hover:bg-gray-50 transition-all duration-200 -mx-2.5">
                                    <div class="w-9 h-9 rounded-xl bg-emerald-50 flex items-center justify-center shrink-0">
                                        <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" /></svg>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-[10px] text-gray-400 font-medium uppercase tracking-wider">{{ $t('a_phone') }}</p>
                                        <a v-if="lead.phone" :href="`tel:${lead.phone}`" class="text-sm font-medium text-gray-800 hover:text-[#C4A265] transition-colors">{{ lead.phone }}</a>
                                        <span v-else class="text-sm text-gray-300">--</span>
                                    </div>
                                </div>
                                <div v-if="lead.phone2" class="flex items-center gap-3 p-2.5 rounded-xl hover:bg-gray-50 transition-all duration-200 -mx-2.5">
                                    <div class="w-9 h-9 rounded-xl bg-emerald-50 flex items-center justify-center shrink-0">
                                        <svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" /></svg>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-[10px] text-gray-400 font-medium uppercase tracking-wider">{{ $t('a_phone2') }}</p>
                                        <a :href="`tel:${lead.phone2}`" class="text-sm font-medium text-gray-800 hover:text-[#C4A265] transition-colors">{{ lead.phone2 }}</a>
                                    </div>
                                </div>
                                <div class="flex items-center gap-3 p-2.5 rounded-xl hover:bg-gray-50 transition-all duration-200 -mx-2.5">
                                    <div class="w-9 h-9 rounded-xl bg-slate-50 flex items-center justify-center shrink-0">
                                        <svg class="w-4 h-4 text-[#1B365D]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-[10px] text-gray-400 font-medium uppercase tracking-wider">{{ $t('a_email') }}</p>
                                        <a v-if="lead.email" :href="`mailto:${lead.email}`" class="text-sm font-medium text-gray-800 hover:text-[#C4A265] transition-colors truncate block">{{ lead.email }}</a>
                                        <span v-else class="text-sm text-gray-300">--</span>
                                    </div>
                                </div>
                                <div class="flex items-center gap-3 p-2.5 rounded-xl hover:bg-gray-50 transition-all duration-200 -mx-2.5">
                                    <div class="w-9 h-9 rounded-xl bg-slate-50 flex items-center justify-center shrink-0">
                                        <svg class="w-4 h-4 text-[#1B365D]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-[10px] text-gray-400 font-medium uppercase tracking-wider">{{ $t('a_gender') }}</p>
                                        <p class="text-sm font-medium text-gray-800">{{ genderLabels[lead.gender] || lead.gender || '--' }}</p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-3 p-2.5 rounded-xl hover:bg-gray-50 transition-all duration-200 -mx-2.5">
                                    <div class="w-9 h-9 rounded-xl bg-amber-50 flex items-center justify-center shrink-0">
                                        <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-[10px] text-gray-400 font-medium uppercase tracking-wider">{{ $t('a_city') }}</p>
                                        <p class="text-sm font-medium text-gray-800">{{ lead.city || '--' }}</p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-3 p-2.5 rounded-xl hover:bg-gray-50 transition-all duration-200 -mx-2.5">
                                    <div class="w-9 h-9 rounded-xl bg-slate-50 flex items-center justify-center shrink-0">
                                        <svg class="w-4 h-4 text-[#1B365D]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-[10px] text-gray-400 font-medium uppercase tracking-wider">{{ $t('a_nationality') }}</p>
                                        <p class="text-sm font-medium text-gray-800">{{ lead.nationality || '--' }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Lead Details -->
                    <div
                        :class="mounted ? 'translate-y-0 opacity-100' : 'translate-y-4 opacity-0'"
                        class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden transition-all duration-700 delay-[450ms] ease-out hover:-translate-y-0.5 hover:shadow-md"
                    >
                        <div class="h-1 bg-gradient-to-r from-[#C4A265] via-[#D4B87A] to-[#C4A265]"></div>
                        <div class="p-5">
                            <h3 class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-4 flex items-center gap-2">
                                <svg class="w-4 h-4 text-[#C4A265]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" /></svg>
                                {{ $t('a_lead_details') }}
                            </h3>
                            <div class="space-y-0">
                                <div class="flex justify-between items-center py-3 border-b border-gray-50">
                                    <span class="text-sm text-gray-500">{{ $t('a_source') }}</span>
                                    <span v-if="lead.source" class="text-xs px-2.5 py-1 rounded-full font-medium" :style="{ backgroundColor: lead.source.color + '18', color: lead.source.color }">{{ isRtl ? lead.source.name_ar : lead.source.name_en }}</span>
                                    <span v-else class="text-sm text-gray-300">--</span>
                                </div>
                                <div class="flex justify-between items-center py-3 border-b border-gray-50">
                                    <span class="text-sm text-gray-500">{{ $t('a_campaign') }}</span>
                                    <span class="text-sm font-medium text-gray-800">{{ lead.campaign?.name || '--' }}</span>
                                </div>
                                <div class="flex justify-between items-center py-3 border-b border-gray-50">
                                    <span class="text-sm text-gray-500">{{ $t('a_assigned_to') }}</span>
                                    <span v-if="lead.assigned_user" class="inline-flex items-center gap-1.5 text-sm font-medium text-gray-800">
                                        <span class="w-5 h-5 rounded-full flex items-center justify-center text-[8px] font-bold text-white" style="background: linear-gradient(135deg, #C4A265, #D4B87A);">{{ getInitials(lead.assigned_user.name) }}</span>
                                        {{ lead.assigned_user.name }}
                                    </span>
                                    <span v-else class="text-sm text-gray-300">{{ $t('a_unassigned') }}</span>
                                </div>
                                <div class="flex justify-between items-center py-3 border-b border-gray-50">
                                    <span class="text-sm text-gray-500">{{ $t('a_created_by') }}</span>
                                    <span class="text-sm font-medium text-gray-800">{{ lead.creator?.name || '--' }}</span>
                                </div>
                                <div class="flex justify-between items-center py-3 border-b border-gray-50">
                                    <span class="text-sm text-gray-500">{{ $t('a_created') }}</span>
                                    <span class="text-sm font-medium text-gray-800">{{ formatDate(lead.created_at) }}</span>
                                </div>
                                <div class="flex justify-between items-center py-3 border-b border-gray-50">
                                    <span class="text-sm text-gray-500">{{ $t('a_last_contact') }}</span>
                                    <span class="text-sm font-medium text-gray-800">{{ lead.last_contacted_at ? formatDate(lead.last_contacted_at) : $t('a_never') }}</span>
                                </div>
                                <div class="flex justify-between items-center py-3">
                                    <span class="text-sm text-gray-500">{{ $t('a_follow_ups') }}</span>
                                    <span class="text-sm font-bold px-2.5 py-0.5 rounded-full bg-[#C4A265]/10 text-[#C4A265]">{{ lead.follow_up_count }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Score Progress Bar Card -->
                    <div
                        :class="mounted ? 'translate-y-0 opacity-100' : 'translate-y-4 opacity-0'"
                        class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden transition-all duration-700 delay-[475ms] ease-out hover:-translate-y-0.5 hover:shadow-md"
                    >
                        <div class="h-1 bg-gradient-to-r from-[#C4A265] via-[#D4B87A] to-[#C4A265]"></div>
                        <div class="p-5">
                            <h3 class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-3 flex items-center gap-2">
                                <svg class="w-4 h-4 text-[#C4A265]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" /></svg>
                                {{ $t('a_lead_score_breakdown') }}
                            </h3>
                            <div class="space-y-3">
                                <div class="flex items-center justify-between text-xs text-gray-500">
                                    <span>{{ $t('a_overall_score') }}</span>
                                    <span class="font-bold text-gray-800">{{ lead.score || 0 }} / 100</span>
                                </div>
                                <div class="h-3 bg-gray-100 rounded-full overflow-hidden">
                                    <div class="h-full rounded-full transition-all duration-1000 ease-out"
                                        :style="{ width: Math.min(lead.score || 0, 100) + '%', background: 'linear-gradient(90deg, #C4A265, #D4B87A)' }">
                                    </div>
                                </div>
                                <div class="flex justify-between text-[10px] text-gray-400">
                                    <span>{{ $t('a_cold') }}</span>
                                    <span>{{ $t('a_warm') }}</span>
                                    <span>{{ $t('a_hot') }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Notes -->
                    <div v-if="lead.notes"
                        :class="mounted ? 'translate-y-0 opacity-100' : 'translate-y-4 opacity-0'"
                        class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden transition-all duration-700 delay-[500ms] ease-out hover:-translate-y-0.5 hover:shadow-md"
                    >
                        <div class="h-1 bg-gradient-to-r from-[#C4A265] via-[#D4B87A] to-[#C4A265]"></div>
                        <div class="p-5">
                            <h3 class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-3 flex items-center gap-2">
                                <svg class="w-4 h-4 text-[#C4A265]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="activityTypeIcons.note" /></svg>
                                {{ $t('a_notes') }}
                            </h3>
                            <div class="bg-gradient-to-br from-gray-50 to-gray-100/30 rounded-xl p-4 border border-gray-100">
                                <p class="text-sm text-gray-600 whitespace-pre-wrap leading-relaxed">{{ lead.notes }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Interested Services -->
                    <div v-if="lead.interested_services?.length"
                        :class="mounted ? 'translate-y-0 opacity-100' : 'translate-y-4 opacity-0'"
                        class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden transition-all duration-700 delay-[550ms] ease-out hover:-translate-y-0.5 hover:shadow-md"
                    >
                        <div class="h-1 bg-gradient-to-r from-[#C4A265] via-[#D4B87A] to-[#C4A265]"></div>
                        <div class="p-5">
                            <h3 class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-3 flex items-center gap-2">
                                <svg class="w-4 h-4 text-[#C4A265]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" /></svg>
                                {{ $t('a_interested_services') }}
                            </h3>
                            <div class="flex flex-wrap gap-2">
                                <span v-for="sid in lead.interested_services" :key="sid"
                                    class="text-xs px-3 py-1.5 rounded-full border border-[#C4A265]/30 text-[#C4A265] bg-[#C4A265]/5 font-medium hover:bg-[#C4A265]/10 transition-colors duration-200"
                                >
                                    {{ (isRtl ? services?.find(s => s.id === sid)?.name_ar : services?.find(s => s.id === sid)?.name_en) || `Service #${sid}` }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ===================== LOSS REASON MODAL ===================== -->
        <Teleport to="body">
            <transition enter-active-class="transition-all duration-300" leave-active-class="transition-all duration-200"
                enter-from-class="opacity-0" enter-to-class="opacity-100"
                leave-from-class="opacity-100" leave-to-class="opacity-0">
                <div v-if="showStatusModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4">
                    <transition enter-active-class="transition-all duration-300 delay-100" enter-from-class="opacity-0 scale-95 translate-y-4" enter-to-class="opacity-100 scale-100 translate-y-0">
                        <div v-if="showStatusModal" class="bg-white rounded-2xl shadow-2xl w-full max-w-full sm:max-w-md overflow-hidden max-h-[90vh] overflow-y-auto">
                            <div class="h-1 bg-gradient-to-r from-red-400 via-red-500 to-red-400"></div>
                            <div class="p-7">
                                <div class="flex items-center gap-3 mb-5">
                                    <div class="w-12 h-12 rounded-2xl bg-red-100 flex items-center justify-center shrink-0">
                                        <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                                    </div>
                                    <div>
                                        <h3 class="text-lg font-bold text-gray-800">{{ $t('a_mark_as_lost') }}</h3>
                                        <p class="text-sm text-gray-500">{{ $t('a_loss_reason_prompt') }}</p>
                                    </div>
                                </div>
                                <div class="relative">
                                    <div class="absolute top-3 left-4 pointer-events-none">
                                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="activityTypeIcons.note" /></svg>
                                    </div>
                                    <textarea v-model="statusForm.loss_reason" rows="3"
                                        class="doctorato-input w-full text-sm border border-gray-200 rounded-xl py-3 pl-10 pr-4 resize-none focus:ring-2 focus:ring-[#C4A265]/30 focus:border-red-400 transition-all placeholder:text-gray-400"
                                        :placeholder="$t('a_loss_reason_placeholder')"></textarea>
                                </div>
                                <div class="flex justify-end gap-3 mt-5">
                                    <button @click="showStatusModal = false" class="px-5 py-2.5 text-sm text-gray-600 hover:text-gray-800 transition-all rounded-xl hover:bg-gray-100 font-medium">{{ $t('a_cancel') }}</button>
                                    <button @click="confirmLostStatus" class="px-5 py-2.5 text-sm font-semibold text-white bg-red-600 rounded-xl hover:bg-red-700 hover:-translate-y-0.5 transition-all shadow-sm hover:shadow-md">{{ $t('a_confirm') }}</button>
                                </div>
                            </div>
                        </div>
                    </transition>
                </div>
            </transition>
            <!-- Convert to Patient Modal -->
            <transition enter-active-class="transition-opacity duration-200" leave-active-class="transition-opacity duration-200"
                enter-from-class="opacity-0" enter-to-class="opacity-100"
                leave-from-class="opacity-100" leave-to-class="opacity-0">
                <div v-if="showConvertModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4 overflow-y-auto">
                    <transition enter-active-class="transition-all duration-300 delay-100" enter-from-class="opacity-0 scale-95 translate-y-4" enter-to-class="opacity-100 scale-100 translate-y-0">
                        <div v-if="showConvertModal" class="bg-white rounded-2xl shadow-2xl w-full max-w-lg overflow-hidden my-8">
                            <div class="h-1 bg-gradient-to-r from-emerald-400 via-emerald-500 to-emerald-400"></div>
                            <div class="p-7">
                                <!-- Header -->
                                <div class="flex items-center gap-3 mb-6">
                                    <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-emerald-500 to-emerald-600 flex items-center justify-center">
                                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                    </div>
                                    <div>
                                        <h3 class="text-lg font-bold text-gray-800">{{ $t('a_convert_to_patient') }}</h3>
                                        <p class="text-xs text-gray-500 mt-0.5">{{ lead.full_name }} — {{ lead.phone }}</p>
                                    </div>
                                </div>

                                <!-- Patient Info Preview -->
                                <div class="bg-emerald-50 rounded-xl p-4 mb-5 border border-emerald-100">
                                    <p class="text-xs font-semibold text-emerald-700 uppercase tracking-wider mb-2">{{ $t('a_new_patient_record') }}</p>
                                    <div class="grid grid-cols-2 gap-2 text-xs text-emerald-800">
                                        <div><span class="text-emerald-500">{{ $t('a_full_name') }}:</span> {{ lead.full_name }}</div>
                                        <div><span class="text-emerald-500">{{ $t('a_phone') }}:</span> {{ lead.phone }}</div>
                                        <div v-if="lead.email"><span class="text-emerald-500">{{ $t('a_email') }}:</span> {{ lead.email }}</div>
                                        <div v-if="lead.gender"><span class="text-emerald-500">{{ $t('a_gender') }}:</span> {{ lead.gender }}</div>
                                    </div>
                                </div>

                                <!-- Create Booking Toggle -->
                                <div class="mb-5">
                                    <label class="flex items-center gap-3 cursor-pointer group">
                                        <div class="relative">
                                            <input type="checkbox" v-model="convertForm.create_booking" class="sr-only peer" />
                                            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-emerald-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-600"></div>
                                        </div>
                                        <div>
                                            <span class="text-sm font-semibold text-gray-700 group-hover:text-gray-900">{{ $t('a_create_booking') }}</span>
                                            <p class="text-[10px] text-gray-400">{{ $t('a_schedule_during_conversion') }}</p>
                                        </div>
                                    </label>
                                </div>

                                <!-- Booking Fields (shown when toggle is on) -->
                                <transition enter-active-class="transition-all duration-300" enter-from-class="opacity-0 max-h-0" enter-to-class="opacity-100 max-h-[600px]"
                                    leave-active-class="transition-all duration-200" leave-from-class="opacity-100 max-h-[600px]" leave-to-class="opacity-0 max-h-0">
                                    <div v-if="convertForm.create_booking" class="space-y-4 overflow-hidden">
                                        <div class="border-t border-gray-100 pt-4">
                                            <p class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3">{{ $t('a_booking_details') }}</p>

                                            <!-- Department Selection -->
                                            <div class="mb-4">
                                                <label class="text-xs font-medium text-gray-600 mb-2 block">{{ $t('a_department') }}</label>
                                                <div class="grid gap-2" :class="enabledDepartments.length > 1 ? 'grid-cols-2' : 'grid-cols-1'">
                                                    <button v-for="dept in enabledDepartments" :key="dept.slug" type="button" @click="selectedDepartment = dept.slug"
                                                        class="flex items-center gap-2 px-4 py-3 rounded-xl border-2 text-sm font-medium transition-all"
                                                        :class="selectedDepartment === dept.slug ? 'border-emerald-500 bg-emerald-50 text-emerald-700' : 'border-gray-200 bg-gray-50 text-gray-600 hover:border-gray-300'">
                                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="dept.icon"/></svg>
                                                        {{ isRtl ? dept.name_ar : dept.name_en }}
                                                    </button>
                                                </div>
                                            </div>

                                            <!-- Service (shown after department selected) -->
                                            <div v-if="selectedDepartment" class="mb-3">
                                                <label class="text-xs font-medium text-gray-600 mb-1 block">{{ $t('a_service') }}</label>
                                                <select v-model="convertForm.service_id" class="doctorato-input w-full text-sm border border-gray-200 rounded-xl px-3 py-2.5 focus:ring-2 focus:ring-[#C4A265]/30 focus:border-[#1B365D] bg-gray-50 focus:bg-white transition-all">
                                                    <option value="">{{ $t('a_select_service') }}</option>
                                                    <option v-for="s in filteredServices" :key="s.id" :value="s.id">{{ isRtl ? s.name_ar : s.name_en }}</option>
                                                </select>
                                                <p v-if="convertForm.errors.service_id" class="text-xs text-red-500 mt-1">{{ convertForm.errors.service_id }}</p>
                                            </div>

                                            <!-- Doctor (shown after department selected) -->
                                            <div v-if="selectedDepartment" class="mb-3">
                                                <label class="text-xs font-medium text-gray-600 mb-1 block">{{ $t('a_doctor') }}</label>
                                                <select v-model="convertForm.doctor_id" class="doctorato-input w-full text-sm border border-gray-200 rounded-xl px-3 py-2.5 focus:ring-2 focus:ring-[#C4A265]/30 focus:border-[#1B365D] bg-gray-50 focus:bg-white transition-all">
                                                    <option value="">{{ $t('a_select_doctor') }}</option>
                                                    <option v-for="d in filteredDoctors" :key="d.id" :value="d.id">{{ isRtl ? (d.name_ar || d.name) : (d.name_en || d.name) }}</option>
                                                </select>
                                            </div>

                                            <!-- Available Dates -->
                                            <div class="mb-3">
                                                <label class="text-xs font-medium text-gray-600 mb-1.5 block">{{ $t('a_available_dates') }}</label>
                                                <div v-if="!convertForm.doctor_id" class="text-xs text-gray-400 bg-gray-50 rounded-xl p-4 text-center border border-dashed border-gray-200">
                                                    {{ $t('a_select_doctor_for_dates') }}
                                                </div>
                                                <div v-else-if="datesLoading" class="flex items-center justify-center gap-2 py-4 text-xs text-gray-400">
                                                    <svg class="w-4 h-4 animate-spin text-emerald-500" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                                                    {{ $t('a_loading_dates') }}
                                                </div>
                                                <div v-else-if="availableDates.length === 0" class="text-xs text-amber-600 bg-amber-50 rounded-xl p-4 text-center border border-amber-200">
                                                    {{ $t('a_no_available_dates') }}
                                                </div>
                                                <div v-else class="grid grid-cols-1 md:grid-cols-3 sm:grid-cols-4 gap-2 max-h-52 overflow-y-auto">
                                                    <button v-for="d in availableDates" :key="d" type="button"
                                                        @click="convertForm.appointment_date = d"
                                                        class="px-2 py-2.5 text-xs font-medium rounded-lg border transition-all duration-150 text-center"
                                                        :class="convertForm.appointment_date === d
                                                            ? 'bg-emerald-600 text-white border-emerald-600 shadow-sm'
                                                            : 'bg-white text-gray-700 border-gray-200 hover:border-emerald-300 hover:bg-emerald-50'"
                                                    >
                                                        <div class="font-semibold">{{ new Date(d + 'T00:00:00').toLocaleDateString(isRtl ? 'ar-EG' : 'en-GB', { weekday: 'short' }) }}</div>
                                                        <div class="text-[10px] mt-0.5" :class="convertForm.appointment_date === d ? 'text-emerald-100' : 'text-gray-400'">{{ new Date(d + 'T00:00:00').toLocaleDateString(isRtl ? 'ar-EG' : 'en-GB', { day: 'numeric', month: 'short' }) }}</div>
                                                    </button>
                                                </div>
                                                <p v-if="convertForm.errors.appointment_date" class="text-xs text-red-500 mt-1">{{ convertForm.errors.appointment_date }}</p>
                                            </div>

                                            <!-- Available Time Slots -->
                                            <div v-if="convertForm.appointment_date" class="mb-3">
                                                <label class="text-xs font-medium text-gray-600 mb-1.5 block">{{ $t('a_available_time_slots') }}</label>
                                                <div v-if="slotsLoading" class="flex items-center justify-center gap-2 py-4 text-xs text-gray-400">
                                                    <svg class="w-4 h-4 animate-spin text-emerald-500" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                                                    {{ $t('a_loading_slots') }}
                                                </div>
                                                <div v-else-if="slotsError" class="text-xs text-amber-600 bg-amber-50 rounded-xl p-4 text-center border border-amber-200">
                                                    {{ slotsError }}
                                                </div>
                                                <div v-else class="grid grid-cols-1 md:grid-cols-3 sm:grid-cols-4 gap-2 max-h-48 overflow-y-auto">
                                                    <button v-for="slot in availableSlots" :key="slot.start" type="button"
                                                        @click="selectSlot(slot)"
                                                        class="px-2 py-2 text-xs font-medium rounded-lg border transition-all duration-150 text-center"
                                                        :class="convertForm.start_time === slot.start
                                                            ? 'bg-emerald-600 text-white border-emerald-600 shadow-sm'
                                                            : 'bg-white text-gray-700 border-gray-200 hover:border-emerald-300 hover:bg-emerald-50'"
                                                    >
                                                        {{ slot.start_12h }}
                                                    </button>
                                                </div>
                                                <p v-if="convertForm.start_time" class="text-xs text-emerald-600 font-medium mt-2">
                                                    {{ $t('a_selected') }}: {{ availableSlots.find(s => s.start === convertForm.start_time)?.start_12h }} — {{ availableSlots.find(s => s.start === convertForm.start_time)?.end_12h }}
                                                </p>
                                                <p v-if="convertForm.errors.start_time" class="text-xs text-red-500 mt-1">{{ convertForm.errors.start_time }}</p>
                                            </div>

                                            <!-- Notes -->
                                            <div>
                                                <label class="text-xs font-medium text-gray-600 mb-1 block">{{ $t('a_notes_optional') }}</label>
                                                <textarea v-model="convertForm.booking_notes" rows="2" :placeholder="$t('a_booking_notes_placeholder')"
                                                    class="doctorato-input w-full text-sm border border-gray-200 rounded-xl px-3 py-2.5 focus:ring-2 focus:ring-[#C4A265]/30 focus:border-[#1B365D] bg-gray-50 focus:bg-white transition-all resize-none"></textarea>
                                            </div>
                                        </div>
                                    </div>
                                </transition>

                                <!-- Error messages -->
                                <p v-if="convertForm.errors.create_booking" class="text-xs text-red-500 mt-3">{{ convertForm.errors.create_booking }}</p>

                                <!-- Actions -->
                                <div class="flex justify-end gap-3 mt-6 pt-5 border-t border-gray-100">
                                    <button @click="showConvertModal = false" class="px-5 py-2.5 text-sm text-gray-600 hover:text-gray-800 transition-all rounded-xl hover:bg-gray-100 font-medium">{{ $t('a_cancel') }}</button>
                                    <button @click="submitConvert" :disabled="convertForm.processing || (convertForm.create_booking && !convertForm.start_time)"
                                        class="px-5 py-2.5 text-sm font-semibold text-white rounded-xl transition-all shadow-sm hover:shadow-md hover:-translate-y-0.5 disabled:opacity-50 disabled:cursor-not-allowed"
                                        :class="convertForm.processing ? 'bg-gray-400' : 'bg-emerald-600 hover:bg-emerald-700'"
                                    >
                                        <span v-if="convertForm.processing" class="flex items-center gap-2">
                                            <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                                            {{ $t('a_converting') }}
                                        </span>
                                        <span v-else>{{ convertForm.create_booking ? $t('a_convert_and_book') : $t('a_convert_to_patient') }}</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </transition>
                </div>
            </transition>
        </Teleport>
    </AdminLayout>
</template>
