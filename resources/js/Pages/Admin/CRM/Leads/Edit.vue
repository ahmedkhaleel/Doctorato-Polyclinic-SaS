<script setup>
import { ref, computed, onMounted } from 'vue';
import { useForm, Link , usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    lead: Object,
    sources: Array,
    campaigns: Array,
    assignees: Array,
    services: Array,
});

const page = usePage();
const locale = computed(() => page.props.locale || 'ar');
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const form = useForm({
    full_name: props.lead.full_name || '',
    phone: props.lead.phone || '',
    phone2: props.lead.phone2 || '',
    email: props.lead.email || '',
    gender: props.lead.gender || '',
    date_of_birth: props.lead.date_of_birth?.split('T')[0] || '',
    city: props.lead.city || '',
    nationality: props.lead.nationality || '',
    lead_source_id: props.lead.lead_source_id || '',
    campaign_id: props.lead.campaign_id || '',
    assigned_to: props.lead.assigned_to || '',
    priority: String(props.lead.priority || 2),
    status: props.lead.status || 'new',
    interested_services: props.lead.interested_services || [],
    notes: props.lead.notes || '',
    loss_reason: props.lead.loss_reason || '',
});

function submit() {
    form.post(`/admin/leads/${props.lead.id}`);
}

function toggleService(id) {
    const idx = form.interested_services.indexOf(id);
    if (idx > -1) form.interested_services.splice(idx, 1);
    else form.interested_services.push(id);
}

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
    new: { bg: 'bg-slate-50', border: 'border-slate-400', ring: 'ring-slate-100', text: 'text-[#1B365D]', dot: 'bg-[#1B365D]' },
    contacted: { bg: 'bg-slate-50', border: 'border-slate-400', ring: 'ring-slate-100', text: 'text-[#1B365D]', dot: 'bg-[#1B365D]' },
    qualified: { bg: 'bg-slate-50', border: 'border-slate-400', ring: 'ring-slate-100', text: 'text-[#1B365D]', dot: 'bg-[#1B365D]' },
    appointment_booked: { bg: 'bg-slate-50', border: 'border-slate-400', ring: 'ring-slate-100', text: 'text-[#1B365D]', dot: 'bg-[#1B365D]' },
    consultation_done: { bg: 'bg-teal-50', border: 'border-teal-400', ring: 'ring-teal-100', text: 'text-teal-700', dot: 'bg-teal-500' },
    negotiation: { bg: 'bg-amber-50', border: 'border-amber-400', ring: 'ring-amber-100', text: 'text-amber-700', dot: 'bg-amber-500' },
    converted: { bg: 'bg-emerald-50', border: 'border-emerald-400', ring: 'ring-emerald-100', text: 'text-emerald-700', dot: 'bg-emerald-500' },
    lost: { bg: 'bg-red-50', border: 'border-red-400', ring: 'ring-red-100', text: 'text-red-700', dot: 'bg-red-500' },
    dormant: { bg: 'bg-gray-50', border: 'border-gray-400', ring: 'ring-gray-100', text: 'text-gray-700', dot: 'bg-gray-500' },
};

// Searchable select state - Source
const sourceSearchOpen = ref(false);
const sourceSearchText = ref('');
const sourceDropdownRef = ref(null);
const filteredSources = computed(() => {
    if (!props.sources) return [];
    if (!sourceSearchText.value) return props.sources;
    const q = sourceSearchText.value.toLowerCase();
    return props.sources.filter(s => (s.name_en || '').toLowerCase().includes(q) || (s.name_ar || '').toLowerCase().includes(q));
});
const selectedSourceLabel = computed(() => {
    if (!form.lead_source_id) return '';
    const found = props.sources?.find(s => s.id === form.lead_source_id);
    return found ? (isRtl.value ? found.name_ar || found.name_en : found.name_en) : '';
});
function selectSource(s) {
    form.lead_source_id = s.id;
    sourceSearchOpen.value = false;
    sourceSearchText.value = '';
}
function clearSource() {
    form.lead_source_id = '';
    sourceSearchText.value = '';
}

// Searchable select state - Campaign
const campaignSearchOpen = ref(false);
const campaignSearchText = ref('');
const campaignDropdownRef = ref(null);
const filteredCampaigns = computed(() => {
    if (!props.campaigns) return [];
    if (!campaignSearchText.value) return props.campaigns;
    const q = campaignSearchText.value.toLowerCase();
    return props.campaigns.filter(c => c.name.toLowerCase().includes(q));
});
const selectedCampaignLabel = computed(() => {
    if (!form.campaign_id) return '';
    const found = props.campaigns?.find(c => c.id === form.campaign_id);
    return found ? found.name : '';
});
function selectCampaign(c) {
    form.campaign_id = c.id;
    campaignSearchOpen.value = false;
    campaignSearchText.value = '';
}
function clearCampaign() {
    form.campaign_id = '';
    campaignSearchText.value = '';
}

// Searchable select state - Assignee
const assigneeSearchOpen = ref(false);
const assigneeSearchText = ref('');
const assigneeDropdownRef = ref(null);
const filteredAssignees = computed(() => {
    if (!props.assignees) return [];
    if (!assigneeSearchText.value) return props.assignees;
    const q = assigneeSearchText.value.toLowerCase();
    return props.assignees.filter(u => u.name.toLowerCase().includes(q));
});
const selectedAssigneeLabel = computed(() => {
    if (!form.assigned_to) return '';
    const found = props.assignees?.find(u => u.id === form.assigned_to);
    return found ? found.name : '';
});
function selectAssignee(u) {
    form.assigned_to = u.id;
    assigneeSearchOpen.value = false;
    assigneeSearchText.value = '';
}
function clearAssignee() {
    form.assigned_to = '';
    assigneeSearchText.value = '';
}

// Close dropdowns on outside click
function handleClickOutside(e) {
    if (sourceDropdownRef.value && !sourceDropdownRef.value.contains(e.target)) {
        sourceSearchOpen.value = false;
    }
    if (campaignDropdownRef.value && !campaignDropdownRef.value.contains(e.target)) {
        campaignSearchOpen.value = false;
    }
    if (assigneeDropdownRef.value && !assigneeDropdownRef.value.contains(e.target)) {
        assigneeSearchOpen.value = false;
    }
}

// Staggered entrance animation
const sectionsVisible = ref([false, false, false, false, false, false, false]);

onMounted(() => {
    document.addEventListener('click', handleClickOutside);
    sectionsVisible.value.forEach((_, i) => {
        setTimeout(() => {
            sectionsVisible.value[i] = true;
        }, 80 * (i + 1));
    });
});

const notesMaxLength = 1000;
const notesLength = computed(() => form.notes.length);
</script>

<template>
    <AdminLayout :title="`Edit Lead: ${lead.full_name}`">
        <div class="space-y-6 pb-28">
            <!-- Header -->
            <div
                class="flex items-center gap-4 transition-all duration-500 ease-out"
                :class="sectionsVisible[0] ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
            >
                <Link :href="`/admin/leads/${lead.id}`" class="flex items-center justify-center w-10 h-10 rounded-xl bg-white border border-gray-200 text-gray-400 hover:text-[#C4A265] hover:border-[#C4A265]/40 transition-all duration-200 shadow-sm">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
                </Link>
                <div class="flex-1">
                    <div class="flex items-center gap-2 text-xs text-gray-400 mb-1">
                        <Link href="/admin/leads" class="hover:text-[#C4A265] transition">{{ $t('a_leads') }}</Link>
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5l7 7-7 7" /></svg>
                        <Link :href="`/admin/leads/${lead.id}`" class="hover:text-[#C4A265] transition">{{ lead.full_name }}</Link>
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5l7 7-7 7" /></svg>
                        <span class="text-gray-600">{{ $t('a_edit') }}</span>
                    </div>
                    <h1 class="text-xl md:text-2xl font-bold text-gray-800">{{ $t('a_edit_lead') }}</h1>
                </div>
            </div>

            <form @submit.prevent="submit" class="space-y-6">
                <!-- Status Section -->
                <div
                    class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 md:p-6 lg:p-8 transition-all duration-500 ease-out"
                    :class="sectionsVisible[1] ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
                >
                    <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-6 flex items-center gap-2">
                        <span class="flex items-center justify-center w-7 h-7 rounded-lg bg-[#C4A265]/10">
                            <svg class="w-4 h-4 text-[#C4A265]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        </span>
                        {{ $t('a_lead_status') }}
                    </h3>
                    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-2">
                        <label
                            v-for="(label, key) in statusLabels"
                            :key="key"
                            :class="[
                                form.status === key
                                    ? `${statusColors[key].border} ${statusColors[key].bg} ring-2 ${statusColors[key].ring}`
                                    : 'border-gray-200 bg-white hover:bg-gray-50 hover:border-gray-300'
                            ]"
                            class="relative flex items-center gap-2 px-3 py-3 rounded-xl border-2 cursor-pointer transition-all duration-200"
                        >
                            <input type="radio" v-model="form.status" :value="key" class="sr-only" />
                            <span class="w-2 h-2 rounded-full shrink-0" :class="form.status === key ? statusColors[key].dot : 'bg-gray-300'"></span>
                            <span class="text-xs font-semibold" :class="form.status === key ? statusColors[key].text : 'text-gray-500'">{{ label }}</span>
                            <div v-if="form.status === key" class="absolute top-1 ltr:right-1 rtl:left-1">
                                <svg class="w-3.5 h-3.5" :class="statusColors[key].text" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" /></svg>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- Loss Reason -->
                <Transition
                    enter-active-class="transition-all duration-400 ease-out"
                    enter-from-class="opacity-0 -translate-y-3 scale-[0.98]"
                    enter-to-class="opacity-100 translate-y-0 scale-100"
                    leave-active-class="transition-all duration-300 ease-in"
                    leave-from-class="opacity-100 translate-y-0 scale-100"
                    leave-to-class="opacity-0 -translate-y-3 scale-[0.98]"
                >
                    <div v-if="form.status === 'lost'" class="bg-gradient-to-r from-red-50 to-amber-50 rounded-2xl shadow-sm border border-red-200 p-4 md:p-6 lg:p-8">
                        <h3 class="text-sm font-semibold text-red-600 uppercase tracking-wider mb-4 flex items-center gap-2">
                            <span class="flex items-center justify-center w-7 h-7 rounded-lg bg-red-100">
                                <svg class="w-4 h-4 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                            </span>
                            {{ $t('a_loss_reason') }}
                        </h3>
                        <textarea
                            v-model="form.loss_reason"
                            rows="3"
                            class="w-full px-4 py-3 text-sm border border-red-200 rounded-xl bg-white/70 focus:bg-white focus:ring-2 focus:ring-red-200 focus:border-red-400 transition-all duration-200 resize-none"
                            :placeholder="isRtl ? 'لماذا تم فقدان هذا العميل؟ هذه المعلومات تساعد في تحسين معدلات التحويل...' : 'Why was this lead lost? This information helps improve future conversion rates...'"
                        ></textarea>
                    </div>
                </Transition>

                <!-- Contact Information -->
                <div
                    class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 md:p-6 lg:p-8 transition-all duration-500 ease-out"
                    :class="sectionsVisible[2] ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
                >
                    <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-6 flex items-center gap-2">
                        <span class="flex items-center justify-center w-7 h-7 rounded-lg bg-[#C4A265]/10">
                            <svg class="w-4 h-4 text-[#C4A265]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                        </span>
                        {{ $t('a_contact_information') }}
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                        <!-- Full Name -->
                        <div class="lg:col-span-3 md:col-span-2">
                            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">{{ $t('a_full_name') }}<span class="text-red-400">*</span></label>
                            <div class="relative">
                                <div class="absolute inset-y-0 ltr:left-0 rtl:right-0 flex items-center ltr:pl-3.5 rtl:pr-3.5 pointer-events-none">
                                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                                </div>
                                <input v-model="form.full_name" type="text" class="w-full ltr:pl-10 rtl:pr-10 ltr:pr-4 rtl:pl-4 py-3 text-sm border border-gray-200 rounded-xl bg-gray-50/50 focus:bg-white focus:ring-2 focus:ring-[#C4A265]/20 focus:border-[#C4A265] transition-all duration-200" :placeholder="isRtl ? 'أدخل الاسم الكامل' : 'Enter full name'" />
                            </div>
                            <p v-if="form.errors.full_name" class="text-xs text-red-500 mt-1.5">{{ form.errors.full_name }}</p>
                        </div>
                        <!-- Phone -->
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">{{ $t('a_phone') }}</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 ltr:left-0 rtl:right-0 flex items-center ltr:pl-3.5 rtl:pr-3.5 pointer-events-none">
                                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" /></svg>
                                </div>
                                <input v-model="form.phone" type="text" class="w-full ltr:pl-10 rtl:pr-10 ltr:pr-4 rtl:pl-4 py-3 text-sm border border-gray-200 rounded-xl bg-gray-50/50 focus:bg-white focus:ring-2 focus:ring-[#C4A265]/20 focus:border-[#C4A265] transition-all duration-200" placeholder="+20 1xx xxx xxxx" />
                            </div>
                            <p v-if="form.errors.phone" class="text-xs text-red-500 mt-1.5">{{ form.errors.phone }}</p>
                        </div>
                        <!-- Phone 2 -->
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">{{ $t('a_phone2') }}</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 ltr:left-0 rtl:right-0 flex items-center ltr:pl-3.5 rtl:pr-3.5 pointer-events-none">
                                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" /></svg>
                                </div>
                                <input v-model="form.phone2" type="text" class="w-full ltr:pl-10 rtl:pr-10 ltr:pr-4 rtl:pl-4 py-3 text-sm border border-gray-200 rounded-xl bg-gray-50/50 focus:bg-white focus:ring-2 focus:ring-[#C4A265]/20 focus:border-[#C4A265] transition-all duration-200" :placeholder="isRtl ? 'هاتف بديل' : 'Alternative phone'" />
                            </div>
                        </div>
                        <!-- Email -->
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">{{ $t('a_email') }}</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 ltr:left-0 rtl:right-0 flex items-center ltr:pl-3.5 rtl:pr-3.5 pointer-events-none">
                                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>
                                </div>
                                <input v-model="form.email" type="email" class="w-full ltr:pl-10 rtl:pr-10 ltr:pr-4 rtl:pl-4 py-3 text-sm border border-gray-200 rounded-xl bg-gray-50/50 focus:bg-white focus:ring-2 focus:ring-[#C4A265]/20 focus:border-[#C4A265] transition-all duration-200" placeholder="email@example.com" />
                            </div>
                            <p v-if="form.errors.email" class="text-xs text-red-500 mt-1.5">{{ form.errors.email }}</p>
                        </div>
                        <!-- Gender -->
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">{{ $t('a_gender') }}</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 ltr:left-0 rtl:right-0 flex items-center ltr:pl-3.5 rtl:pr-3.5 pointer-events-none">
                                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
                                </div>
                                <select v-model="form.gender" class="w-full ltr:pl-10 rtl:pr-10 ltr:pr-4 rtl:pl-4 py-3 text-sm border border-gray-200 rounded-xl bg-gray-50/50 focus:bg-white focus:ring-2 focus:ring-[#C4A265]/20 focus:border-[#C4A265] transition-all duration-200 appearance-none">
                                    <option value="">{{ $t('a_select') }}</option>
                                    <option value="male">{{ $t('a_male') }}</option>
                                    <option value="female">{{ $t('a_female') }}</option>
                                </select>
                                <div class="absolute inset-y-0 ltr:right-0 rtl:left-0 flex items-center ltr:pr-3 rtl:pl-3 pointer-events-none">
                                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                                </div>
                            </div>
                        </div>
                        <!-- Date of Birth -->
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">{{ $t('a_date_of_birth') }}</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 ltr:left-0 rtl:right-0 flex items-center ltr:pl-3.5 rtl:pr-3.5 pointer-events-none">
                                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                                </div>
                                <input v-model="form.date_of_birth" type="date" class="w-full ltr:pl-10 rtl:pr-10 ltr:pr-4 rtl:pl-4 py-3 text-sm border border-gray-200 rounded-xl bg-gray-50/50 focus:bg-white focus:ring-2 focus:ring-[#C4A265]/20 focus:border-[#C4A265] transition-all duration-200" />
                            </div>
                        </div>
                        <!-- City -->
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">{{ $t('a_city') }}</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 ltr:left-0 rtl:right-0 flex items-center ltr:pl-3.5 rtl:pr-3.5 pointer-events-none">
                                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                                </div>
                                <input v-model="form.city" type="text" class="w-full ltr:pl-10 rtl:pr-10 ltr:pr-4 rtl:pl-4 py-3 text-sm border border-gray-200 rounded-xl bg-gray-50/50 focus:bg-white focus:ring-2 focus:ring-[#C4A265]/20 focus:border-[#C4A265] transition-all duration-200" :placeholder="isRtl ? 'المدينة' : 'City'" />
                            </div>
                        </div>
                        <!-- Nationality -->
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">{{ $t('a_nationality') }}</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 ltr:left-0 rtl:right-0 flex items-center ltr:pl-3.5 rtl:pr-3.5 pointer-events-none">
                                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                </div>
                                <input v-model="form.nationality" type="text" class="w-full ltr:pl-10 rtl:pr-10 ltr:pr-4 rtl:pl-4 py-3 text-sm border border-gray-200 rounded-xl bg-gray-50/50 focus:bg-white focus:ring-2 focus:ring-[#C4A265]/20 focus:border-[#C4A265] transition-all duration-200" :placeholder="isRtl ? 'الجنسية' : 'Nationality'" />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Lead Details -->
                <div
                    class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 md:p-6 lg:p-8 transition-all duration-500 ease-out"
                    :class="sectionsVisible[3] ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
                >
                    <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-6 flex items-center gap-2">
                        <span class="flex items-center justify-center w-7 h-7 rounded-lg bg-[#C4A265]/10">
                            <svg class="w-4 h-4 text-[#C4A265]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3c2.755 0 5.455.232 8.083.678.533.09.917.556.917 1.096v1.044a2.25 2.25 0 01-.659 1.591l-5.432 5.432a2.25 2.25 0 00-.659 1.591v2.927a2.25 2.25 0 01-1.244 2.013L9.75 21v-6.568a2.25 2.25 0 00-.659-1.591L3.659 7.409A2.25 2.25 0 013 5.818V4.774c0-.54.384-1.006.917-1.096A48.32 48.32 0 0112 3z" /></svg>
                        </span>
                        {{ $t('a_lead_details') }}
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                        <!-- Source - Searchable Select -->
                        <div ref="sourceDropdownRef" class="relative">
                            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">{{ $t('a_source') }}</label>
                            <div v-if="form.lead_source_id" class="flex items-center gap-2 w-full ltr:pl-4 rtl:pr-4 ltr:pr-2 rtl:pl-2 py-3 text-sm border border-[#C4A265]/40 rounded-xl bg-[#C4A265]/5">
                                <span class="flex-1 text-gray-800">{{ selectedSourceLabel }}</span>
                                <button type="button" @click="clearSource" class="flex items-center justify-center w-6 h-6 rounded-full hover:bg-gray-200 transition">
                                    <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                                </button>
                            </div>
                            <div v-else>
                                <div class="relative">
                                    <div class="absolute inset-y-0 ltr:left-0 rtl:right-0 flex items-center ltr:pl-3.5 rtl:pr-3.5 pointer-events-none">
                                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                                    </div>
                                    <input
                                        v-model="sourceSearchText"
                                        @focus="sourceSearchOpen = true"
                                        type="text"
                                        class="w-full ltr:pl-10 rtl:pr-10 ltr:pr-4 rtl:pl-4 py-3 text-sm border border-gray-200 rounded-xl bg-gray-50/50 focus:bg-white focus:ring-2 focus:ring-[#C4A265]/20 focus:border-[#C4A265] transition-all duration-200"
                                        :placeholder="isRtl ? 'بحث عن المصدر...' : 'Search source...'"
                                    />
                                </div>
                                <Transition
                                    enter-active-class="transition duration-150 ease-out"
                                    enter-from-class="opacity-0 scale-95"
                                    enter-to-class="opacity-100 scale-100"
                                    leave-active-class="transition duration-100 ease-in"
                                    leave-from-class="opacity-100 scale-100"
                                    leave-to-class="opacity-0 scale-95"
                                >
                                    <div v-if="sourceSearchOpen" class="absolute z-30 mt-1 w-full bg-white border border-gray-200 rounded-xl shadow-lg max-h-48 overflow-y-auto">
                                        <button
                                            type="button"
                                            v-for="s in filteredSources"
                                            :key="s.id"
                                            @mousedown.prevent="selectSource(s)"
                                            class="w-full ltr:text-left rtl:text-right px-4 py-2.5 text-sm hover:bg-[#C4A265]/5 hover:text-[#C4A265] transition-colors"
                                        >{{ isRtl ? s.name_ar || s.name_en : s.name_en }}</button>
                                        <div v-if="!filteredSources.length" class="px-4 py-3 text-xs text-gray-400">{{ $t('a_no_sources_found') }}</div>
                                    </div>
                                </Transition>
                            </div>
                        </div>

                        <!-- Campaign - Searchable Select -->
                        <div ref="campaignDropdownRef" class="relative">
                            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">{{ $t('a_campaign') }}</label>
                            <div v-if="form.campaign_id" class="flex items-center gap-2 w-full ltr:pl-4 rtl:pr-4 ltr:pr-2 rtl:pl-2 py-3 text-sm border border-[#C4A265]/40 rounded-xl bg-[#C4A265]/5">
                                <span class="flex-1 text-gray-800">{{ selectedCampaignLabel }}</span>
                                <button type="button" @click="clearCampaign" class="flex items-center justify-center w-6 h-6 rounded-full hover:bg-gray-200 transition">
                                    <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                                </button>
                            </div>
                            <div v-else>
                                <div class="relative">
                                    <div class="absolute inset-y-0 ltr:left-0 rtl:right-0 flex items-center ltr:pl-3.5 rtl:pr-3.5 pointer-events-none">
                                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                                    </div>
                                    <input
                                        v-model="campaignSearchText"
                                        @focus="campaignSearchOpen = true"
                                        type="text"
                                        class="w-full ltr:pl-10 rtl:pr-10 ltr:pr-4 rtl:pl-4 py-3 text-sm border border-gray-200 rounded-xl bg-gray-50/50 focus:bg-white focus:ring-2 focus:ring-[#C4A265]/20 focus:border-[#C4A265] transition-all duration-200"
                                        :placeholder="isRtl ? 'بحث عن الحملة...' : 'Search campaign...'"
                                    />
                                </div>
                                <Transition
                                    enter-active-class="transition duration-150 ease-out"
                                    enter-from-class="opacity-0 scale-95"
                                    enter-to-class="opacity-100 scale-100"
                                    leave-active-class="transition duration-100 ease-in"
                                    leave-from-class="opacity-100 scale-100"
                                    leave-to-class="opacity-0 scale-95"
                                >
                                    <div v-if="campaignSearchOpen" class="absolute z-30 mt-1 w-full bg-white border border-gray-200 rounded-xl shadow-lg max-h-48 overflow-y-auto">
                                        <button
                                            type="button"
                                            v-for="c in filteredCampaigns"
                                            :key="c.id"
                                            @mousedown.prevent="selectCampaign(c)"
                                            class="w-full ltr:text-left rtl:text-right px-4 py-2.5 text-sm hover:bg-[#C4A265]/5 hover:text-[#C4A265] transition-colors"
                                        >{{ c.name }}</button>
                                        <div v-if="!filteredCampaigns.length" class="px-4 py-3 text-xs text-gray-400">{{ $t('a_no_campaigns_found') }}</div>
                                    </div>
                                </Transition>
                            </div>
                        </div>

                        <!-- Assignee - Searchable Select -->
                        <div ref="assigneeDropdownRef" class="relative">
                            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">{{ $t('a_assign_to') }}</label>
                            <div v-if="form.assigned_to" class="flex items-center gap-2 w-full ltr:pl-4 rtl:pr-4 ltr:pr-2 rtl:pl-2 py-3 text-sm border border-[#C4A265]/40 rounded-xl bg-[#C4A265]/5">
                                <span class="flex-1 text-gray-800">{{ selectedAssigneeLabel }}</span>
                                <button type="button" @click="clearAssignee" class="flex items-center justify-center w-6 h-6 rounded-full hover:bg-gray-200 transition">
                                    <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                                </button>
                            </div>
                            <div v-else>
                                <div class="relative">
                                    <div class="absolute inset-y-0 ltr:left-0 rtl:right-0 flex items-center ltr:pl-3.5 rtl:pr-3.5 pointer-events-none">
                                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                                    </div>
                                    <input
                                        v-model="assigneeSearchText"
                                        @focus="assigneeSearchOpen = true"
                                        type="text"
                                        class="w-full ltr:pl-10 rtl:pr-10 ltr:pr-4 rtl:pl-4 py-3 text-sm border border-gray-200 rounded-xl bg-gray-50/50 focus:bg-white focus:ring-2 focus:ring-[#C4A265]/20 focus:border-[#C4A265] transition-all duration-200"
                                        :placeholder="isRtl ? 'بحث عن المسؤول...' : 'Search assignee...'"
                                    />
                                </div>
                                <Transition
                                    enter-active-class="transition duration-150 ease-out"
                                    enter-from-class="opacity-0 scale-95"
                                    enter-to-class="opacity-100 scale-100"
                                    leave-active-class="transition duration-100 ease-in"
                                    leave-from-class="opacity-100 scale-100"
                                    leave-to-class="opacity-0 scale-95"
                                >
                                    <div v-if="assigneeSearchOpen" class="absolute z-30 mt-1 w-full bg-white border border-gray-200 rounded-xl shadow-lg max-h-48 overflow-y-auto">
                                        <button
                                            type="button"
                                            v-for="u in filteredAssignees"
                                            :key="u.id"
                                            @mousedown.prevent="selectAssignee(u)"
                                            class="w-full ltr:text-left rtl:text-right px-4 py-2.5 text-sm hover:bg-[#C4A265]/5 hover:text-[#C4A265] transition-colors"
                                        >{{ u.name }}</button>
                                        <div v-if="!filteredAssignees.length" class="px-4 py-3 text-xs text-gray-400">{{ $t('a_no_assignees_found') }}</div>
                                    </div>
                                </Transition>
                            </div>
                        </div>

                        <!-- Priority - Card Style -->
                        <div class="lg:col-span-3 md:col-span-2">
                            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-3">{{ $t('a_priority') }}</label>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                                <label
                                    v-for="(config, val) in {
                                        '1': { label: isRtl ? 'ساخن' : 'Hot', color: 'red', icon: 'M17.657 18.657A8 8 0 016.343 7.343S7 9 9 10c0-2 .5-5 2.986-7C14 5 16.09 5.777 17.656 7.343A7.975 7.975 0 0120 13a7.975 7.975 0 01-2.343 5.657z' },
                                        '2': { label: isRtl ? 'دافئ' : 'Warm', color: 'amber', icon: 'M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z' },
                                        '3': { label: isRtl ? 'بارد' : 'Cold', color: 'blue', icon: 'M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z' }
                                    }"
                                    :key="val"
                                    :class="[
                                        form.priority === val
                                            ? (val === '1' ? 'border-red-400 bg-red-50 ring-2 ring-red-100' : val === '2' ? 'border-amber-400 bg-amber-50 ring-2 ring-amber-100' : 'border-slate-400 bg-slate-50 ring-2 ring-slate-100')
                                            : 'border-gray-200 bg-white hover:bg-gray-50 hover:border-gray-300'
                                    ]"
                                    class="relative flex flex-col items-center gap-2 p-4 rounded-xl border-2 cursor-pointer transition-all duration-200"
                                >
                                    <input type="radio" v-model="form.priority" :value="val" class="sr-only" />
                                    <svg class="w-5 h-5" :class="val === '1' ? 'text-red-500' : val === '2' ? 'text-amber-500' : 'text-[#1B365D]'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="config.icon" /></svg>
                                    <span class="text-sm font-semibold" :class="val === '1' ? 'text-red-700' : val === '2' ? 'text-amber-700' : 'text-[#1B365D]'">{{ config.label }}</span>
                                    <div v-if="form.priority === val" class="absolute top-2 ltr:right-2 rtl:left-2">
                                        <svg class="w-4 h-4" :class="val === '1' ? 'text-red-500' : val === '2' ? 'text-amber-500' : 'text-[#1B365D]'" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" /></svg>
                                    </div>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Interested Services -->
                <div
                    v-if="services?.length"
                    class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 md:p-6 lg:p-8 transition-all duration-500 ease-out"
                    :class="sectionsVisible[4] ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
                >
                    <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-6 flex items-center gap-2">
                        <span class="flex items-center justify-center w-7 h-7 rounded-lg bg-[#C4A265]/10">
                            <svg class="w-4 h-4 text-[#C4A265]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" /></svg>
                        </span>
                        {{ $t('a_interested_services') }}
                        <span v-if="form.interested_services.length" class="ml-auto text-xs font-medium text-[#C4A265] bg-[#C4A265]/10 px-2.5 py-1 rounded-full">{{ form.interested_services.length }} {{ isRtl ? 'محدد' : 'selected' }}</span>
                    </h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                        <button
                            type="button"
                            v-for="service in services"
                            :key="service.id"
                            @click="toggleService(service.id)"
                            :class="form.interested_services.includes(service.id)
                                ? 'border-[#C4A265] bg-[#C4A265]/5 ring-2 ring-[#C4A265]/20'
                                : 'border-gray-200 bg-white hover:border-gray-300 hover:bg-gray-50'"
                            class="flex items-center gap-3 p-4 rounded-xl border-2 ltr:text-left rtl:text-right transition-all duration-200"
                        >
                            <div
                                :class="form.interested_services.includes(service.id) ? 'bg-[#C4A265] border-[#C4A265]' : 'bg-white border-gray-300'"
                                class="flex items-center justify-center w-5 h-5 rounded border-2 shrink-0 transition-all duration-200"
                            >
                                <svg v-if="form.interested_services.includes(service.id)" class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" /></svg>
                            </div>
                            <span class="text-sm font-medium" :class="form.interested_services.includes(service.id) ? 'text-[#C4A265]' : 'text-gray-600'">{{ isRtl ? service.name_ar || service.name_en : service.name_en }}</span>
                        </button>
                    </div>
                </div>

                <!-- Notes -->
                <div
                    class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 md:p-6 lg:p-8 transition-all duration-500 ease-out"
                    :class="sectionsVisible[5] ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
                >
                    <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-6 flex items-center gap-2">
                        <span class="flex items-center justify-center w-7 h-7 rounded-lg bg-[#C4A265]/10">
                            <svg class="w-4 h-4 text-[#C4A265]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                        </span>
                        {{ $t('a_notes') }}
                    </h3>
                    <textarea
                        v-model="form.notes"
                        rows="5"
                        :maxlength="notesMaxLength"
                        class="w-full px-4 py-3 text-sm border border-gray-200 rounded-xl bg-gray-50/50 focus:bg-white focus:ring-2 focus:ring-[#C4A265]/20 focus:border-[#C4A265] transition-all duration-200 resize-none"
                        :placeholder="isRtl ? 'أي ملاحظات إضافية عن هذا العميل المحتمل...' : 'Any additional notes about this lead...'"
                    ></textarea>
                    <div class="flex justify-end mt-2">
                        <span class="text-xs" :class="notesLength > notesMaxLength * 0.9 ? 'text-amber-500' : 'text-gray-400'">{{ notesLength }} / {{ notesMaxLength }}</span>
                    </div>
                </div>
            </form>
        </div>

        <!-- Sticky Bottom Actions Bar -->
        <div class="fixed bottom-0 ltr:left-0 rtl:right-0 ltr:right-0 rtl:left-0 z-40">
            <div class="backdrop-blur-xl bg-white/80 border-t border-gray-200 shadow-[0_-4px_20px_rgba(0,0,0,0.06)]">
                <div class="flex items-center justify-between px-4 md:px-6 lg:px-8 py-4 max-w-screen-2xl mx-auto">
                    <div class="flex items-center gap-3">
                        <span class="text-sm text-gray-500">{{ isRtl ? 'تعديل:' : 'Editing:' }}</span>
                        <span class="text-sm font-semibold text-gray-800">{{ lead.full_name }}</span>
                        <span
                            class="text-[10px] px-2 py-0.5 rounded-full font-medium capitalize"
                            :class="`${statusColors[form.status]?.bg} ${statusColors[form.status]?.text}`"
                        >{{ statusLabels[form.status] }}</span>
                    </div>
                    <div class="flex items-center gap-3">
                        <Link :href="`/admin/leads/${lead.id}`" class="px-5 py-2.5 text-sm font-medium text-gray-600 hover:text-gray-800 bg-gray-100 hover:bg-gray-200 rounded-xl transition-all duration-200">{{ $t('a_cancel') }}</Link>
                        <button
                            @click="submit"
                            :disabled="form.processing"
                            class="px-8 py-2.5 rounded-xl text-white text-sm font-semibold transition-all duration-200 shadow-sm hover:shadow-md disabled:opacity-50 disabled:cursor-not-allowed"
                            style="background-color: #C4A265;"
                        >
                            <span v-if="form.processing" class="flex items-center gap-2">
                                <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" /><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" /></svg>{{ $t('a_saving') }}</span>
                            <span v-else>{{ $t('a_update_lead') }}</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
