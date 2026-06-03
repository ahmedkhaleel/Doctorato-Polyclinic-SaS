<script setup>
import { ref, computed, onMounted } from 'vue';
import { Link, router , usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { useConfirm } from '@/Composables/useConfirm.js';

const { confirm } = useConfirm();

const props = defineProps({
    primary: Object,
    secondary: Object,
});

const page = usePage();
const locale = computed(() => page.props.locale || 'ar');
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const mounted = ref(false);
onMounted(() => {
    setTimeout(() => mounted.value = true, 50);
});

// Track which fields to take from the secondary lead
const selectedFields = ref([]);

const mergeableFields = [
    { key: 'phone', label: 'Phone', icon: 'M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z' },
    { key: 'phone2', label: 'Phone 2', icon: 'M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z' },
    { key: 'email', label: 'Email', icon: 'M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z' },
    { key: 'gender', label: 'Gender', icon: 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z' },
    { key: 'age', label: 'Age', icon: 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z' },
    { key: 'city', label: 'City', icon: 'M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z' },
    { key: 'nationality', label: 'Nationality', icon: 'M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z' },
    { key: 'notes', label: 'Notes', icon: 'M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z' },
    { key: 'priority', label: 'Priority', icon: 'M3 21v-4m0 0V5a2 2 0 012-2h6.5l1 1H21l-3 6 3 6h-8.5l-1-1H5a2 2 0 00-2 2zm9-13.5V9' },
    { key: 'lead_source_id', label: 'Lead Source', icon: 'M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1' },
    { key: 'campaign_id', label: 'Campaign', icon: 'M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z' },
    { key: 'assigned_to', label: 'Assigned To', icon: 'M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z' },
    { key: 'interested_services', label: 'Interested Services', icon: 'M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z' },
    { key: 'utm_source', label: 'UTM Source', icon: 'M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4' },
    { key: 'utm_medium', label: 'UTM Medium', icon: 'M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4' },
    { key: 'utm_campaign', label: 'UTM Campaign', icon: 'M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4' },
];

// Only show fields where secondary has data and primary doesn't (or both have different values)
const relevantFields = computed(() => {
    return mergeableFields.filter(f => {
        const pVal = props.primary[f.key];
        const sVal = props.secondary[f.key];
        return sVal && sVal !== pVal;
    });
});

function toggleField(key) {
    const idx = selectedFields.value.indexOf(key);
    if (idx >= 0) {
        selectedFields.value.splice(idx, 1);
    } else {
        selectedFields.value.push(key);
    }
}

const priorityLabels = { 1: 'Hot', 2: 'Warm', 3: 'Cold' };
const submitting = ref(false);

function submitMerge() {
    if (submitting.value) return;
    confirm(isRtl.value ? 'هل أنت متأكد من دمج هذين العميلين؟ سيتم أرشفة العميل الثانوي. لا يمكن التراجع.' : 'Are you sure you want to merge these leads? The secondary lead will be archived. This cannot be undone.', () => {
        submitting.value = true;
        router.post('/admin/leads-merge', {
            primary_id: props.primary.id,
            secondary_id: props.secondary.id,
            fields: selectedFields.value,
        }, {
            onFinish: () => submitting.value = false,
        });
    });
}

function displayValue(lead, key) {
    const val = lead[key];
    if (val === null || val === undefined || val === '') return '-';
    if (key === 'priority') return priorityLabels[val] || val;
    if (key === 'lead_source_id') return lead.source?.name_en || `#${val}`;
    if (key === 'campaign_id') return lead.campaign?.name || `#${val}`;
    if (key === 'assigned_to') return lead.assigned_user?.name || `#${val}`;
    if (key === 'interested_services' && Array.isArray(val)) return val.length + ' service(s)';
    return val;
}

function isDifferent(key) {
    const pVal = displayValue(props.primary, key);
    const sVal = displayValue(props.secondary, key);
    return pVal !== sVal;
}
</script>

<template>
    <AdminLayout :title="isRtl ? 'دمج العملاء' : 'Merge Leads'">
        <div class="space-y-6">
            <!-- Header Card -->
            <div
                :class="mounted ? 'translate-y-0 opacity-100' : 'translate-y-4 opacity-0'"
                class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden transition-all duration-700 ease-out"
            >
                <div class="h-1 bg-gradient-to-r from-[#C4A265] via-[#D4B87A] to-[#C4A265]"></div>
                <div class="px-4 md:px-6 py-5 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 rounded-2xl flex items-center justify-center shadow-sm" style="background: linear-gradient(135deg, #C4A265, #D4B87A);">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" /></svg>
                        </div>
                        <div>
                            <h1 class="text-xl md:text-2xl font-bold text-gray-900">{{ $t('a_merge_leads') }}</h1>
                            <p class="text-sm text-gray-500 mt-0.5">Select which data to keep from the secondary lead, then merge into the primary</p>
                        </div>
                    </div>
                    <Link href="/admin/leads" class="inline-flex items-center px-4 py-2.5 rounded-xl border border-gray-200 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50 hover:shadow-sm hover:-translate-y-0.5 transition-all duration-300">
                        <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>{{ $t('a_back_to_leads') }}</Link>
                </div>
            </div>

            <!-- Lead Cards Side by Side -->
            <div class="relative grid grid-cols-1 lg:grid-cols-2 gap-8">
                <!-- VS Badge -->
                <div class="hidden lg:flex absolute inset-y-0 ltr:left-1 rtl:right-1/2 -translate-x-1/2 z-10 items-center justify-center">
                    <div class="w-14 h-14 rounded-full bg-white border-2 border-gray-200 shadow-lg flex items-center justify-center">
                        <span class="text-sm font-black text-gray-400 tracking-wider">VS</span>
                    </div>
                </div>

                <!-- Primary Lead -->
                <div
                    :class="mounted ? 'translate-y-0 opacity-100' : 'translate-y-4 opacity-0'"
                    class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden hover:-translate-y-0.5 hover:shadow-md transition-all duration-500 ease-out"
                    style="transition-delay: 100ms;"
                >
                    <div class="h-1 bg-gradient-to-r from-emerald-400 via-emerald-500 to-emerald-400"></div>
                    <div class="px-4 md:px-6 py-4 bg-emerald-50/50 border-b border-emerald-100/50 flex items-center gap-3">
                        <div class="w-9 h-9 rounded-xl bg-emerald-500 flex items-center justify-center shadow-sm">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        </div>
                        <div>
                            <h3 class="font-bold text-emerald-800 text-sm">{{ $t('a_primary_lead') }}</h3>
                            <p class="text-[10px] text-emerald-600">{{ $t('a_lead_kept_after_merge') }}</p>
                        </div>
                        <span class="ml-auto text-xs text-emerald-600 font-mono bg-emerald-100 px-2.5 py-1 rounded-lg">#{{ primary.id }}</span>
                    </div>
                    <div class="p-4 md:p-6 space-y-4">
                        <div>
                            <p class="text-xl font-bold text-gray-900">{{ primary.full_name }}</p>
                            <div class="flex items-center gap-3 mt-2">
                                <span class="text-sm text-gray-500 flex items-center gap-1.5">
                                    <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" /></svg>
                                    {{ primary.phone || 'No phone' }}
                                </span>
                                <span class="text-gray-300">|</span>
                                <span class="text-sm text-gray-500 flex items-center gap-1.5">
                                    <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>
                                    {{ primary.email || 'No email' }}
                                </span>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div class="bg-emerald-50/50 rounded-xl p-3 text-center">
                                <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wider mb-0.5">{{ $t('a_status') }}</p>
                                <p class="text-sm font-bold text-gray-800 capitalize">{{ primary.status }}</p>
                            </div>
                            <div class="bg-emerald-50/50 rounded-xl p-3 text-center">
                                <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wider mb-0.5">{{ $t('a_score') }}</p>
                                <p class="text-sm font-bold text-gray-800">{{ primary.score }}</p>
                            </div>
                            <div class="bg-emerald-50/50 rounded-xl p-3 text-center">
                                <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wider mb-0.5">Activities</p>
                                <p class="text-sm font-bold text-gray-800">{{ primary.activities?.length || 0 }}</p>
                            </div>
                            <div class="bg-emerald-50/50 rounded-xl p-3 text-center">
                                <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wider mb-0.5">Follow-ups</p>
                                <p class="text-sm font-bold text-gray-800">{{ primary.follow_ups?.length || 0 }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Secondary Lead -->
                <div
                    :class="mounted ? 'translate-y-0 opacity-100' : 'translate-y-4 opacity-0'"
                    class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden hover:-translate-y-0.5 hover:shadow-md transition-all duration-500 ease-out"
                    style="transition-delay: 200ms;"
                >
                    <div class="h-1 bg-gradient-to-r from-red-400 via-red-500 to-red-400"></div>
                    <div class="px-4 md:px-6 py-4 bg-red-50/50 border-b border-red-100/50 flex items-center gap-3">
                        <div class="w-9 h-9 rounded-xl bg-red-500 flex items-center justify-center shadow-sm">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                        </div>
                        <div>
                            <h3 class="font-bold text-red-800 text-sm">{{ $t('a_secondary_lead') }}</h3>
                            <p class="text-[10px] text-red-600">{{ $t('a_lead_archived_after_merge') }}</p>
                        </div>
                        <span class="ml-auto text-xs text-red-600 font-mono bg-red-100 px-2.5 py-1 rounded-lg">#{{ secondary.id }}</span>
                    </div>
                    <div class="p-4 md:p-6 space-y-4">
                        <div>
                            <p class="text-xl font-bold text-gray-900">{{ secondary.full_name }}</p>
                            <div class="flex items-center gap-3 mt-2">
                                <span class="text-sm text-gray-500 flex items-center gap-1.5">
                                    <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" /></svg>
                                    {{ secondary.phone || 'No phone' }}
                                </span>
                                <span class="text-gray-300">|</span>
                                <span class="text-sm text-gray-500 flex items-center gap-1.5">
                                    <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>
                                    {{ secondary.email || 'No email' }}
                                </span>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div class="bg-red-50/30 rounded-xl p-3 text-center">
                                <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wider mb-0.5">{{ $t('a_status') }}</p>
                                <p class="text-sm font-bold text-gray-800 capitalize">{{ secondary.status }}</p>
                            </div>
                            <div class="bg-red-50/30 rounded-xl p-3 text-center">
                                <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wider mb-0.5">{{ $t('a_score') }}</p>
                                <p class="text-sm font-bold text-gray-800">{{ secondary.score }}</p>
                            </div>
                            <div class="bg-red-50/30 rounded-xl p-3 text-center">
                                <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wider mb-0.5">Activities</p>
                                <p class="text-sm font-bold text-gray-800">{{ secondary.activities?.length || 0 }}</p>
                            </div>
                            <div class="bg-red-50/30 rounded-xl p-3 text-center">
                                <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wider mb-0.5">Follow-ups</p>
                                <p class="text-sm font-bold text-gray-800">{{ secondary.follow_ups?.length || 0 }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Field Selection -->
            <div
                v-if="relevantFields.length"
                :class="mounted ? 'translate-y-0 opacity-100' : 'translate-y-4 opacity-0'"
                class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden transition-all duration-700 ease-out"
                style="transition-delay: 300ms;"
            >
                <div class="h-1 bg-gradient-to-r from-[#C4A265] via-[#D4B87A] to-[#C4A265]"></div>
                <div class="px-4 md:px-6 py-4 bg-gray-50/50 border-b border-gray-100 flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl flex items-center justify-center" style="background: linear-gradient(135deg, #C4A265, #D4B87A);">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" /></svg>
                    </div>
                    <div>
                        <h3 class="font-bold text-gray-800">{{ $t('a_select_fields_copy') }}</h3>
                        <p class="text-xs text-gray-500 mt-0.5">{{ $t('a_toggle_fields_hint') }}</p>
                    </div>
                    <span v-if="selectedFields.length" class="ml-auto text-xs font-bold px-3 py-1 rounded-full text-white" style="background: linear-gradient(135deg, #C4A265, #D4B87A);">
                        {{ selectedFields.length }} selected
                    </span>
                </div>

                <div class="divide-y divide-gray-50">
                    <div
                        v-for="(field, fIdx) in relevantFields"
                        :key="field.key"
                        @click="toggleField(field.key)"
                        :class="selectedFields.includes(field.key) ? 'bg-[#C4A265]/5 border-l-[#C4A265]' : 'hover:bg-gray-50/80 border-l-transparent'"
                        class="flex items-center px-4 md:px-6 py-4 cursor-pointer transition-all duration-200 border-l-[3px]"
                    >
                        <!-- Toggle Switch -->
                        <div class="relative shrink-0">
                            <div :class="selectedFields.includes(field.key) ? 'bg-[#C4A265]' : 'bg-gray-200'"
                                class="w-11 h-6 rounded-full transition-all duration-300">
                            </div>
                            <div :class="selectedFields.includes(field.key) ? 'ltr:translate-x-5 rtl:-translate-x-5' : 'translate-x-0.5'"
                                class="absolute top-0.5 w-5 h-5 bg-white rounded-full shadow-sm transition-all duration-300">
                            </div>
                        </div>

                        <!-- Field info -->
                        <div class="ltr:ml-4 rtl:mr-4 flex items-center gap-2.5 min-w-[160px]">
                            <div class="w-8 h-8 rounded-xl bg-gray-100 flex items-center justify-center shrink-0">
                                <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="field.icon" /></svg>
                            </div>
                            <span class="text-sm font-semibold text-gray-700">{{ field.label }}</span>
                        </div>

                        <!-- Values comparison -->
                        <div class="flex-1 flex items-center justify-end gap-3">
                            <div class="ltr:text-right rtl:text-left min-w-0">
                                <p class="text-[10px] text-emerald-500 font-bold uppercase tracking-wider">Primary</p>
                                <p class="text-sm text-emerald-700 font-medium truncate max-w-[180px]" :title="String(displayValue(primary, field.key))">
                                    {{ displayValue(primary, field.key) }}
                                </p>
                            </div>
                            <div class="w-10 flex items-center justify-center shrink-0">
                                <div class="w-8 h-8 rounded-lg bg-gray-50 flex items-center justify-center">
                                    <svg class="w-4 h-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" /></svg>
                                </div>
                            </div>
                            <div class="ltr:text-left rtl:text-right min-w-0">
                                <p class="text-[10px] text-red-500 font-bold uppercase tracking-wider">Secondary</p>
                                <p :class="selectedFields.includes(field.key) ? 'text-[#C4A265]' : 'text-red-700'"
                                    class="text-sm font-medium truncate max-w-[180px] transition-colors duration-200" :title="String(displayValue(secondary, field.key))">
                                    {{ displayValue(secondary, field.key) }}
                                </p>
                            </div>
                            <div v-if="isDifferent(field.key)" class="shrink-0 ltr:ml-2 rtl:mr-2">
                                <span class="w-6 h-6 rounded-full bg-amber-50 flex items-center justify-center" :title="$t('a_values_differ')">
                                    <svg class="w-3.5 h-3.5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z" /></svg>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- No Differences -->
            <div
                v-if="!relevantFields.length"
                :class="mounted ? 'translate-y-0 opacity-100' : 'translate-y-4 opacity-0'"
                class="bg-white rounded-2xl shadow-sm border border-gray-100 p-10 text-center transition-all duration-700 ease-out"
                style="transition-delay: 300ms;"
            >
                <div class="w-16 h-16 rounded-2xl bg-gray-50 border border-gray-100 flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                </div>
                <p class="text-sm font-semibold text-gray-600">{{ $t('a_no_differing_fields') }}</p>
                <p class="text-xs text-gray-400 mt-1">{{ $t('a_no_unique_data_merge') }}</p>
            </div>

            <!-- Auto-merged Info -->
            <div
                :class="mounted ? 'translate-y-0 opacity-100' : 'translate-y-4 opacity-0'"
                class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden transition-all duration-700 ease-out"
                style="transition-delay: 400ms;"
            >
                <div class="h-1 bg-gradient-to-r from-amber-400 via-amber-500 to-amber-400"></div>
                <div class="px-4 md:px-6 py-4 flex items-start gap-4">
                    <div class="w-10 h-10 rounded-xl bg-amber-50 flex items-center justify-center shrink-0 mt-0.5">
                        <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    </div>
                    <div class="flex-1">
                        <h4 class="font-bold text-amber-800 text-sm mb-3">{{ $t('a_auto_merged_data') }}</h4>
                        <div class="space-y-2.5">
                            <div class="flex items-center gap-2.5 text-sm text-amber-700">
                                <svg class="w-4 h-4 text-amber-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                                All activities from the secondary lead will be moved to the primary
                            </div>
                            <div class="flex items-center gap-2.5 text-sm text-amber-700">
                                <svg class="w-4 h-4 text-amber-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                                All follow-ups from the secondary lead will be moved to the primary
                            </div>
                            <div class="flex items-center gap-2.5 text-sm text-amber-700">
                                <svg class="w-4 h-4 text-amber-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                                All linked bookings and contact messages will be reassigned
                            </div>
                            <div class="flex items-center gap-2.5 text-sm text-amber-700">
                                <svg class="w-4 h-4 text-amber-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                                The higher score between the two leads will be kept
                            </div>
                            <div class="flex items-center gap-2.5 text-sm text-amber-700">
                                <svg class="w-4 h-4 text-amber-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                                The secondary lead will be soft-deleted (archived)
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div
                :class="mounted ? 'translate-y-0 opacity-100' : 'translate-y-4 opacity-0'"
                class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden transition-all duration-700 ease-out"
                style="transition-delay: 500ms;"
            >
                <div class="px-4 md:px-6 py-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <p class="text-sm text-gray-500">
                        <span class="font-semibold text-gray-700">{{ primary.full_name }}</span> will be the surviving lead record
                    </p>
                    <div class="flex items-center gap-3">
                        <Link href="/admin/leads" class="inline-flex items-center px-5 py-2.5 rounded-xl border border-gray-200 bg-white text-sm font-medium text-gray-600 hover:bg-gray-50 hover:-translate-y-0.5 transition-all duration-300">{{ $t('a_cancel') }}</Link>
                        <button
                            @click="submitMerge"
                            :disabled="submitting"
                            class="inline-flex items-center gap-2 px-4 md:px-6 py-2.5 rounded-xl text-white text-sm font-semibold transition-all duration-300 shadow-sm hover:shadow-md hover:-translate-y-0.5 disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:translate-y-0"
                            style="background: linear-gradient(135deg, #C4A265, #D4B87A);"
                        >
                            <svg v-if="submitting" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                            <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" /></svg>
                            {{ submitting ? 'Merging...' : 'Merge Leads' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
