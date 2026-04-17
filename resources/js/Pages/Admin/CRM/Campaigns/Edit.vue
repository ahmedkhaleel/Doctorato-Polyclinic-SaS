<script setup>
import { ref, onMounted , computed } from 'vue';
import { useForm, Link , usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { useCurrency } from '@/Composables/useCurrency.js';

const { currencyCode } = useCurrency();

const props = defineProps({ campaign: Object, sources: Array });

const page = usePage();
const locale = computed(() => page.props.locale || 'ar');
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const form = useForm({
    name: props.campaign.name || '',
    description: props.campaign.description || '',
    lead_source_id: props.campaign.lead_source_id || '',
    status: props.campaign.status || 'draft',
    budget: props.campaign.budget || '',
    actual_cost: props.campaign.actual_cost || '',
    start_date: props.campaign.start_date?.split('T')[0] || '',
    end_date: props.campaign.end_date?.split('T')[0] || '',
    utm_source: props.campaign.utm_source || '',
    utm_medium: props.campaign.utm_medium || '',
    utm_campaign: props.campaign.utm_campaign || '',
});

function submit() { form.post(`/admin/campaigns/${props.campaign.id}`); }

const showHeader = ref(false);
const showLeft = ref(false);
const showRight = ref(false);
const showActions = ref(false);

onMounted(() => {
    setTimeout(() => { showHeader.value = true; }, 50);
    setTimeout(() => { showLeft.value = true; }, 150);
    setTimeout(() => { showRight.value = true; }, 250);
    setTimeout(() => { showActions.value = true; }, 350);

    // Initialize source search with current value
    const currentSource = props.sources?.find(s => s.id === props.campaign.lead_source_id);
    if (currentSource) {
        sourceSearch.value = currentSource.name_en;
    }
});

const statusOptions = [
    { value: 'draft', label: 'Draft', icon: 'M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z', color: 'gray' },
    { value: 'active', label: 'Active', icon: 'M13 10V3L4 14h7v7l9-11h-7z', color: 'emerald' },
    { value: 'paused', label: 'Paused', icon: 'M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z', color: 'amber' },
    { value: 'completed', label: 'Completed', icon: 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z', color: 'blue' },
];

const sourceSearch = ref('');
const showSourceDropdown = ref(false);
const filteredSources = ref([]);

function filterSources() {
    if (!props.sources) { filteredSources.value = []; return; }
    const q = sourceSearch.value.toLowerCase();
    filteredSources.value = q ? props.sources.filter(s => s.name_en.toLowerCase().includes(q)) : props.sources;
}

function selectSource(source) {
    form.lead_source_id = source ? source.id : '';
    sourceSearch.value = source ? source.name_en : '';
    showSourceDropdown.value = false;
}

function onSourceFocus() {
    filterSources();
    showSourceDropdown.value = true;
}

function onSourceBlur() {
    setTimeout(() => { showSourceDropdown.value = false; }, 200);
}
</script>

<template>
    <AdminLayout :title="`Edit: ${campaign.name}`">
        <form @submit.prevent="submit" class="space-y-6 pb-24">
            <!-- Header -->
            <div
                :class="showHeader ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
                class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 transition-all duration-500 ease-out"
            >
                <div class="flex items-center gap-4">
                    <Link :href="`/admin/campaigns/${campaign.id}`" class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-white border border-gray-200 text-gray-500 hover:text-gray-700 hover:border-gray-300 hover:shadow-md hover:-translate-y-0.5 transition-all duration-200">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
                    </Link>
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl flex items-center justify-center shadow-sm" style="background: linear-gradient(135deg, #C4A265, #D4B87A);">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                        </div>
                        <div>
                            <h1 class="text-xl md:text-2xl font-bold text-gray-900 tracking-tight">{{ $t('a_edit_campaign') }}</h1>
                            <p class="text-sm text-gray-500 mt-0.5">Update campaign details and tracking parameters for <span class="font-medium text-gray-700">{{ campaign.name }}</span></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Full Width Layout -->
            <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
                <!-- Left: Campaign Details (2 cols) -->
                <div
                    :class="showLeft ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
                    class="xl:col-span-2 bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden transition-all duration-500 ease-out"
                >
                    <!-- Gold gradient top bar -->
                    <div class="h-1 w-full" style="background: linear-gradient(135deg, #C4A265, #D4B87A);"></div>
                    <div class="px-4 md:px-6 py-4 border-b border-gray-100 bg-gray-50/50">
                        <div class="flex items-center gap-2.5">
                            <div class="w-8 h-8 rounded-lg flex items-center justify-center" style="background-color: rgba(196, 162, 101, 0.1);">
                                <svg class="w-4 h-4" style="color: #C4A265;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                            </div>
                            <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wider">{{ $t('a_campaign_details') }}</h3>
                        </div>
                    </div>
                    <div class="p-4 md:p-6 space-y-5">
                        <!-- Name + Lead Source Row -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            <!-- Name -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">{{ $t('a_campaign_name') }}<span class="text-red-400">*</span></label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 ltr:left-0 rtl:right-0 ltr:pl-3.5 rtl:pr-3.5 flex items-center pointer-events-none">
                                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z" /></svg>
                                    </div>
                                    <input
                                        v-model="form.name"
                                        type="text"
                                        :placeholder="$t('a_enter_campaign_name')"
                                        class="w-full ltr:pl-10 rtl:pr-10 ltr:pr-4 rtl:pl-4 py-2.5 text-sm bg-gray-50/50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-[#C4A265]/20 focus:border-[#C4A265] focus:bg-white transition-all duration-200 placeholder-gray-400"
                                    />
                                </div>
                                <p v-if="form.errors.name" class="text-xs text-red-500 mt-1.5 flex items-center gap-1">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                    {{ form.errors.name }}
                                </p>
                            </div>

                            <!-- Lead Source (Searchable) -->
                            <div class="relative">
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">{{ $t('a_lead_source') }}</label>
                                <div class="relative">
                                    <svg class="absolute ltr:left-3.5 rtl:right-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                                    <input
                                        v-model="sourceSearch"
                                        @focus="onSourceFocus"
                                        @blur="onSourceBlur"
                                        @input="filterSources"
                                        type="text"
                                        :placeholder="$t('a_search_sources')"
                                        class="w-full ltr:pl-10 rtl:pr-10 ltr:pr-10 rtl:pl-10 py-2.5 text-sm bg-gray-50/50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-[#C4A265]/20 focus:border-[#C4A265] focus:bg-white transition-all duration-200 placeholder-gray-400"
                                    />
                                    <button
                                        v-if="form.lead_source_id"
                                        @click.prevent="selectSource(null); sourceSearch = ''"
                                        type="button"
                                        class="absolute ltr:right-3 rtl:left-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 transition-colors"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                                    </button>
                                </div>
                                <div
                                    v-if="showSourceDropdown && filteredSources.length"
                                    class="absolute z-20 mt-1 w-full bg-white border border-gray-200 rounded-xl shadow-lg max-h-48 overflow-y-auto"
                                >
                                    <button
                                        type="button"
                                        @click="selectSource(null); sourceSearch = ''"
                                        class="w-full ltr:text-left rtl:text-right px-4 py-2.5 text-sm text-gray-500 hover:bg-gray-50 transition-colors"
                                    >{{ $t('a_all_sources') }}</button>
                                    <button
                                        v-for="s in filteredSources"
                                        :key="s.id"
                                        type="button"
                                        @click="selectSource(s)"
                                        :class="form.lead_source_id === s.id ? 'bg-[#C4A265]/5 text-[#C4A265]' : 'text-gray-700 hover:bg-gray-50'"
                                        class="w-full ltr:text-left rtl:text-right px-4 py-2.5 text-sm transition-colors flex items-center justify-between"
                                    >
                                        {{ s.name_en }}
                                        <svg v-if="form.lead_source_id === s.id" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Description -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">{{ $t('a_description') }}</label>
                            <div class="relative">
                                <div class="absolute top-3 ltr:left-3.5 rtl:right-3.5 pointer-events-none">
                                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7" /></svg>
                                </div>
                                <textarea
                                    v-model="form.description"
                                    rows="3"
                                    :placeholder="$t('a_describe_campaign')"
                                    class="w-full ltr:pl-10 rtl:pr-10 ltr:pr-4 rtl:pl-4 py-2.5 text-sm bg-gray-50/50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-[#C4A265]/20 focus:border-[#C4A265] focus:bg-white transition-all duration-200 resize-none placeholder-gray-400"
                                ></textarea>
                            </div>
                        </div>

                        <!-- Status Pills -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2.5">{{ $t('a_status') }}</label>
                            <div class="flex flex-wrap gap-3">
                                <button
                                    v-for="opt in statusOptions"
                                    :key="opt.value"
                                    type="button"
                                    @click="form.status = opt.value"
                                    :class="form.status === opt.value
                                        ? 'border-[#C4A265] bg-[#C4A265]/5 text-[#C4A265] shadow-sm ring-1 ring-[#C4A265]/20'
                                        : 'border-gray-200 text-gray-500 hover:border-gray-300 hover:bg-gray-50 hover:-translate-y-0.5'"
                                    class="flex-1 flex items-center justify-center gap-2 px-3 py-3 text-sm font-medium border rounded-xl transition-all duration-200 min-w-[100px]"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="opt.icon" /></svg>
                                    {{ opt.label }}
                                </button>
                            </div>
                        </div>

                        <!-- Budget / Cost Row -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">{{ $t('a_budget') }}</label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 ltr:left-0 rtl:right-0 ltr:pl-3.5 rtl:pr-3.5 flex items-center pointer-events-none">
                                        <span class="text-xs font-semibold text-gray-400">{{ currencyCode }}</span>
                                    </div>
                                    <input
                                        v-model="form.budget"
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        placeholder="0.00"
                                        class="w-full ltr:pl-14 rtl:pr-14 ltr:pr-4 rtl:pl-4 py-2.5 text-sm bg-gray-50/50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-[#C4A265]/20 focus:border-[#C4A265] focus:bg-white transition-all duration-200 placeholder-gray-400 tabular-nums"
                                    />
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">{{ $t('a_actual_cost') }}</label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 ltr:left-0 rtl:right-0 ltr:pl-3.5 rtl:pr-3.5 flex items-center pointer-events-none">
                                        <span class="text-xs font-semibold text-gray-400">{{ currencyCode }}</span>
                                    </div>
                                    <input
                                        v-model="form.actual_cost"
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        placeholder="0.00"
                                        class="w-full ltr:pl-14 rtl:pr-14 ltr:pr-4 rtl:pl-4 py-2.5 text-sm bg-gray-50/50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-[#C4A265]/20 focus:border-[#C4A265] focus:bg-white transition-all duration-200 placeholder-gray-400 tabular-nums"
                                    />
                                </div>
                            </div>
                        </div>

                        <!-- Dates -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">{{ $t('a_campaign_period') }}</label>
                            <div class="flex items-center gap-3">
                                <div class="relative flex-1">
                                    <div class="absolute inset-y-0 ltr:left-0 rtl:right-0 ltr:pl-3.5 rtl:pr-3.5 flex items-center pointer-events-none">
                                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                                    </div>
                                    <input
                                        v-model="form.start_date"
                                        type="date"
                                        class="w-full ltr:pl-10 rtl:pr-10 ltr:pr-4 rtl:pl-4 py-2.5 text-sm bg-gray-50/50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-[#C4A265]/20 focus:border-[#C4A265] focus:bg-white transition-all duration-200"
                                    />
                                </div>
                                <div class="flex items-center justify-center w-10">
                                    <svg class="w-5 h-5 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" /></svg>
                                </div>
                                <div class="relative flex-1">
                                    <div class="absolute inset-y-0 ltr:left-0 rtl:right-0 ltr:pl-3.5 rtl:pr-3.5 flex items-center pointer-events-none">
                                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                                    </div>
                                    <input
                                        v-model="form.end_date"
                                        type="date"
                                        class="w-full ltr:pl-10 rtl:pr-10 ltr:pr-4 rtl:pl-4 py-2.5 text-sm bg-gray-50/50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-[#C4A265]/20 focus:border-[#C4A265] focus:bg-white transition-all duration-200"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right: UTM Tracking (1 col) -->
                <div
                    :class="showRight ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
                    class="transition-all duration-500 ease-out"
                >
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                        <!-- Gold gradient top bar -->
                        <div class="h-1 w-full" style="background: linear-gradient(135deg, #C4A265, #D4B87A);"></div>
                        <div class="px-4 md:px-6 py-4 border-b border-gray-100 bg-gray-50/50">
                            <div class="flex items-center gap-2.5">
                                <div class="w-8 h-8 rounded-lg flex items-center justify-center bg-slate-50">
                                    <svg class="w-4 h-4 text-[#1B365D]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" /></svg>
                                </div>
                                <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wider">{{ $t('a_utm_tracking') }}</h3>
                            </div>
                        </div>
                        <div class="p-4 md:p-6 space-y-5">
                            <div class="bg-slate-50/50 border border-slate-100 rounded-xl p-3.5">
                                <div class="flex gap-2.5">
                                    <svg class="w-4 h-4 text-[#1B365D] flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                    <p class="text-xs text-[#1B365D] leading-relaxed">
                                        UTM parameters help track the effectiveness of your campaign across different marketing channels. These values will be appended to your campaign URLs.
                                    </p>
                                </div>
                            </div>

                            <!-- UTM Source -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">{{ $t('a_utm_source') }}</label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 ltr:left-0 rtl:right-0 ltr:pl-3.5 rtl:pr-3.5 flex items-center pointer-events-none">
                                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9" /></svg>
                                    </div>
                                    <input
                                        v-model="form.utm_source"
                                        type="text"
                                        placeholder="e.g. facebook, google, newsletter"
                                        class="w-full ltr:pl-10 rtl:pr-10 ltr:pr-4 rtl:pl-4 py-2.5 text-sm bg-gray-50/50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-[#C4A265]/20 focus:border-[#C4A265] focus:bg-white transition-all duration-200 placeholder-gray-400 font-mono"
                                    />
                                </div>
                                <p class="text-[11px] text-gray-400 mt-1.5 font-mono">utm_source={{ form.utm_source || '...' }}</p>
                            </div>

                            <!-- UTM Medium -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">{{ $t('a_utm_medium') }}</label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 ltr:left-0 rtl:right-0 ltr:pl-3.5 rtl:pr-3.5 flex items-center pointer-events-none">
                                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" /></svg>
                                    </div>
                                    <input
                                        v-model="form.utm_medium"
                                        type="text"
                                        placeholder="e.g. cpc, email, social"
                                        class="w-full ltr:pl-10 rtl:pr-10 ltr:pr-4 rtl:pl-4 py-2.5 text-sm bg-gray-50/50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-[#C4A265]/20 focus:border-[#C4A265] focus:bg-white transition-all duration-200 placeholder-gray-400 font-mono"
                                    />
                                </div>
                                <p class="text-[11px] text-gray-400 mt-1.5 font-mono">utm_medium={{ form.utm_medium || '...' }}</p>
                            </div>

                            <!-- UTM Campaign -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">{{ $t('a_utm_campaign') }}</label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 ltr:left-0 rtl:right-0 ltr:pl-3.5 rtl:pr-3.5 flex items-center pointer-events-none">
                                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z" /></svg>
                                    </div>
                                    <input
                                        v-model="form.utm_campaign"
                                        type="text"
                                        placeholder="e.g. summer_promo, launch_2024"
                                        class="w-full ltr:pl-10 rtl:pr-10 ltr:pr-4 rtl:pl-4 py-2.5 text-sm bg-gray-50/50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-[#C4A265]/20 focus:border-[#C4A265] focus:bg-white transition-all duration-200 placeholder-gray-400 font-mono"
                                    />
                                </div>
                                <p class="text-[11px] text-gray-400 mt-1.5 font-mono">utm_campaign={{ form.utm_campaign || '...' }}</p>
                            </div>

                            <!-- Preview URL -->
                            <div v-if="form.utm_source || form.utm_medium || form.utm_campaign" class="mt-4">
                                <label class="block text-[11px] font-semibold text-gray-500 uppercase tracking-wider mb-2">{{ $t('a_url_preview') }}</label>
                                <div class="bg-gray-900 rounded-xl p-3.5 overflow-x-auto">
                                    <code class="text-[11px] text-emerald-400 whitespace-nowrap">
                                        ?{{ form.utm_source ? `utm_source=${form.utm_source}` : '' }}{{ form.utm_medium ? `&utm_medium=${form.utm_medium}` : '' }}{{ form.utm_campaign ? `&utm_campaign=${form.utm_campaign}` : '' }}
                                    </code>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sticky Action Bar -->
            <div
                :class="showActions ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
                class="fixed bottom-0 ltr:left-0 rtl:right-0 ltr:right-0 rtl:left-0 z-30 transition-all duration-500 ease-out"
            >
                <div class="bg-white/95 backdrop-blur-sm border-t border-gray-200 shadow-[0_-4px_20px_rgba(0,0,0,0.06)]">
                    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-2 h-2 rounded-full bg-[#C4A265] animate-pulse"></div>
                            <span class="text-sm text-gray-500">Editing: {{ campaign.name }}</span>
                        </div>
                        <div class="flex items-center gap-3">
                            <Link :href="`/admin/campaigns/${campaign.id}`" class="px-5 py-2.5 text-sm font-medium text-gray-600 hover:text-gray-800 hover:bg-gray-100 rounded-xl transition-all duration-200">{{ $t('a_cancel') }}</Link>
                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="px-4 md:px-6 py-2.5 rounded-xl text-white text-sm font-semibold transition-all duration-200 shadow-lg shadow-[#C4A265]/25 hover:shadow-xl hover:shadow-[#C4A265]/30 hover:-translate-y-0.5 disabled:opacity-50 disabled:hover:translate-y-0 disabled:hover:shadow-lg"
                                style="background: linear-gradient(135deg, #C4A265, #D4B87A);"
                            >
                                <span v-if="form.processing" class="flex items-center gap-2">
                                    <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>{{ $t('a_updating') }}</span>
                                <span v-else class="flex items-center gap-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>{{ $t('a_update_campaign') }}</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </AdminLayout>
</template>
