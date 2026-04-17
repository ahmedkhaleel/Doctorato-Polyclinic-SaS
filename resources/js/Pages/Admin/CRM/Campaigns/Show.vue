<script setup>
import { ref, computed, onMounted } from 'vue';
import { Link , usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { usePermissions } from '@/Composables/usePermissions.js';
import { useCurrency } from '@/Composables/useCurrency.js';

const { can } = usePermissions();
const { formatCurrency } = useCurrency();

const props = defineProps({ campaign: Object, leads: Object });

const page = usePage();
const locale = computed(() => page.props.locale || 'ar');
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const statusColors = {
    draft: 'bg-gray-100 text-gray-600 border-gray-200',
    active: 'bg-emerald-50 text-emerald-700 border-emerald-200',
    paused: 'bg-amber-50 text-amber-700 border-amber-200',
    completed: 'bg-slate-50 text-[#1B365D] border-slate-200',
};

const statusDotColors = {
    draft: 'bg-gray-400',
    active: 'bg-emerald-500',
    paused: 'bg-amber-500',
    completed: 'bg-[#1B365D]',
};

const leadStatusColors = {
    new: 'bg-slate-50 text-[#1B365D] border-slate-200',
    contacted: 'bg-slate-50 text-[#1B365D] border-slate-200',
    qualified: 'bg-slate-50 text-[#1B365D] border-slate-200',
    converted: 'bg-emerald-50 text-emerald-700 border-emerald-200',
    lost: 'bg-red-50 text-red-700 border-red-200',
};

const leadStatusDotColors = {
    new: 'bg-[#1B365D]',
    contacted: 'bg-[#1B365D]',
    qualified: 'bg-[#1B365D]',
    converted: 'bg-emerald-500',
    lost: 'bg-red-500',
};

function formatDate(d) {
    if (!d) return '-';
    return new Date(d).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
}

const conversionRate = computed(() => {
    if (!props.campaign.leads_count || props.campaign.leads_count === 0) return 0;
    return ((props.campaign.conversions_count / props.campaign.leads_count) * 100).toFixed(1);
});

const conversionCircumference = 2 * Math.PI * 40;
const conversionOffset = computed(() => {
    return conversionCircumference - (conversionRate.value / 100) * conversionCircumference;
});

function getInitials(name) {
    if (!name) return '??';
    return name.split(' ').map(n => n[0]).slice(0, 2).join('').toUpperCase();
}

const initialsColors = [
    'bg-slate-100 text-[#1B365D]',
    'bg-emerald-100 text-emerald-700',
    'bg-slate-100 text-[#1B365D]',
    'bg-amber-100 text-amber-700',
    'bg-amber-100 text-[#C4A265]',
    'bg-slate-100 text-[#1B365D]',
    'bg-teal-100 text-teal-700',
];

function getInitialsColor(name) {
    if (!name) return initialsColors[0];
    let hash = 0;
    for (let i = 0; i < name.length; i++) { hash = name.charCodeAt(i) + ((hash << 5) - hash); }
    return initialsColors[Math.abs(hash) % initialsColors.length];
}

const showHeader = ref(false);
const showStats = ref(false);
const showConversion = ref(false);
const showLeads = ref(false);

onMounted(() => {
    setTimeout(() => { showHeader.value = true; }, 50);
    setTimeout(() => { showStats.value = true; }, 150);
    setTimeout(() => { showConversion.value = true; }, 250);
    setTimeout(() => { showLeads.value = true; }, 350);
});
</script>

<template>
    <AdminLayout :title="`Campaign: ${campaign.name}`">
        <div class="space-y-6">
            <!-- Header -->
            <div
                :class="showHeader ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
                class="transition-all duration-500 ease-out"
            >
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="h-1.5 w-full" style="background: linear-gradient(to right, #C4A265, #D4B87A, #C4A265);"></div>
                    <div class="p-6">
                        <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
                            <div class="flex items-start gap-4">
                                <Link href="/admin/campaigns" class="mt-1 inline-flex items-center justify-center w-10 h-10 rounded-xl bg-gray-50 border border-gray-200 text-gray-500 hover:text-gray-700 hover:border-gray-300 hover:shadow-sm transition-all duration-200 flex-shrink-0">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
                                </Link>
                                <div>
                                    <div class="flex items-center gap-3 flex-wrap">
                                        <h1 class="text-xl md:text-2xl font-bold text-gray-900 tracking-tight">{{ campaign.name }}</h1>
                                        <span :class="statusColors[campaign.status]" class="inline-flex items-center gap-1.5 px-2.5 py-1 text-[11px] font-semibold rounded-full capitalize border">
                                            <span :class="statusDotColors[campaign.status]" class="w-1.5 h-1.5 rounded-full"></span>
                                            {{ campaign.status }}
                                        </span>
                                    </div>
                                    <div class="flex items-center gap-4 mt-2 flex-wrap">
                                        <span v-if="campaign.lead_source?.name_en" class="inline-flex items-center text-xs text-gray-500">
                                            <svg class="w-3.5 h-3.5 ltr:mr-1 rtl:ml-1 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" /></svg>
                                            {{ campaign.lead_source.name_en }}
                                        </span>
                                        <span class="inline-flex items-center text-xs text-gray-500">
                                            <svg class="w-3.5 h-3.5 ltr:mr-1 rtl:ml-1 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                                            {{ formatDate(campaign.start_date) }} to {{ formatDate(campaign.end_date) }}
                                        </span>
                                    </div>
                                    <p v-if="campaign.description" class="text-sm text-gray-600 mt-3 leading-relaxed max-w-2xl">{{ campaign.description }}</p>
                                </div>
                            </div>
                            <Link
                                v-if="can('crm_campaigns.update')"
                                :href="`/admin/campaigns/${campaign.id}/edit`"
                                class="group inline-flex items-center px-5 py-2.5 rounded-xl text-white text-sm font-semibold transition-all duration-200 shadow-lg shadow-[#C4A265]/25 hover:shadow-xl hover:shadow-[#C4A265]/30 hover:-translate-y-0.5 flex-shrink-0"
                                style="background: linear-gradient(135deg, #C4A265, #D4B87A);"
                            >
                                <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>{{ $t('a_edit_campaign') }}</Link>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Stats + Conversion Rate -->
            <div
                :class="showStats ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
                class="grid grid-cols-1 lg:grid-cols-4 gap-4 transition-all duration-500 ease-out"
            >
                <!-- Leads -->
                <div class="relative bg-white rounded-xl shadow-sm p-5 border border-gray-100 overflow-hidden group hover:shadow-md hover:-translate-y-0.5 transition-all duration-300">
                    <div class="absolute top-0 ltr:left-0 rtl:right-0 ltr:right-0 rtl:left-0 h-1 bg-gradient-to-r from-slate-400 to-[#1B365D]"></div>
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-[11px] text-gray-500 uppercase tracking-wider font-semibold mb-1">{{ $t('a_total_leads') }}</p>
                            <p class="text-2xl md:text-3xl font-bold text-gray-900">{{ campaign.leads_count }}</p>
                        </div>
                        <div class="w-12 h-12 rounded-xl bg-slate-50 flex items-center justify-center">
                            <svg class="w-6 h-6 text-[#1B365D]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
                        </div>
                    </div>
                </div>

                <!-- Conversions -->
                <div class="relative bg-white rounded-xl shadow-sm p-5 border border-gray-100 overflow-hidden group hover:shadow-md hover:-translate-y-0.5 transition-all duration-300">
                    <div class="absolute top-0 ltr:left-0 rtl:right-0 ltr:right-0 rtl:left-0 h-1 bg-gradient-to-r from-emerald-400 to-emerald-500"></div>
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-[11px] text-gray-500 uppercase tracking-wider font-semibold mb-1">{{ $t('a_conversions') }}</p>
                            <p class="text-2xl md:text-3xl font-bold text-emerald-600">{{ campaign.conversions_count }}</p>
                        </div>
                        <div class="w-12 h-12 rounded-xl bg-emerald-50 flex items-center justify-center">
                            <svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        </div>
                    </div>
                </div>

                <!-- Budget -->
                <div class="relative bg-white rounded-xl shadow-sm p-5 border border-gray-100 overflow-hidden group hover:shadow-md hover:-translate-y-0.5 transition-all duration-300">
                    <div class="absolute top-0 ltr:left-0 rtl:right-0 ltr:right-0 rtl:left-0 h-1" style="background: linear-gradient(to right, #C4A265, #D4B87A);"></div>
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-[11px] text-gray-500 uppercase tracking-wider font-semibold mb-1">{{ $t('a_budget') }}</p>
                            <p class="text-2xl md:text-3xl font-bold text-gray-900">{{ formatCurrency(campaign.budget) }}</p>
                        </div>
                        <div class="w-12 h-12 rounded-xl flex items-center justify-center" style="background-color: rgba(196, 162, 101, 0.1);">
                            <svg class="w-6 h-6" style="color: #C4A265;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        </div>
                    </div>
                </div>

                <!-- Actual Cost -->
                <div class="relative bg-white rounded-xl shadow-sm p-5 border border-gray-100 overflow-hidden group hover:shadow-md hover:-translate-y-0.5 transition-all duration-300">
                    <div class="absolute top-0 ltr:left-0 rtl:right-0 ltr:right-0 rtl:left-0 h-1 bg-gradient-to-r from-gray-300 to-gray-400"></div>
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-[11px] text-gray-500 uppercase tracking-wider font-semibold mb-1">{{ $t('a_actual_cost') }}</p>
                            <p class="text-2xl md:text-3xl font-bold text-gray-900">{{ formatCurrency(campaign.actual_cost) }}</p>
                        </div>
                        <div class="w-12 h-12 rounded-xl bg-gray-50 flex items-center justify-center">
                            <svg class="w-6 h-6 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2zM10 8.5a.5.5 0 11-1 0 .5.5 0 011 0zm5 5a.5.5 0 11-1 0 .5.5 0 011 0z" /></svg>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Conversion Rate Arc -->
            <div
                :class="showConversion ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
                class="transition-all duration-500 ease-out"
            >
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                    <div class="flex flex-col sm:flex-row items-center gap-6">
                        <!-- SVG Arc -->
                        <div class="relative flex-shrink-0">
                            <svg width="120" height="120" viewBox="0 0 100 100" class="transform -rotate-90">
                                <circle cx="50" cy="50" r="40" stroke="#f3f4f6" stroke-width="8" fill="none" />
                                <circle
                                    cx="50" cy="50" r="40"
                                    stroke="#C4A265"
                                    stroke-width="8"
                                    fill="none"
                                    stroke-linecap="round"
                                    :stroke-dasharray="conversionCircumference"
                                    :stroke-dashoffset="conversionOffset"
                                    class="transition-all duration-1000 ease-out"
                                />
                            </svg>
                            <div class="absolute inset-0 flex items-center justify-center">
                                <span class="text-xl font-bold text-gray-900">{{ conversionRate }}%</span>
                            </div>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-gray-900">{{ $t('a_conversion_rate') }}</h3>
                            <p class="text-sm text-gray-500 mt-1">
                                {{ campaign.conversions_count }} out of {{ campaign.leads_count }} leads converted to customers
                            </p>
                            <div class="flex items-center gap-4 mt-3">
                                <div class="flex items-center gap-2">
                                    <div class="w-3 h-3 rounded-full" style="background-color: #C4A265;"></div>
                                    <span class="text-xs text-gray-500">{{ $t('a_converted') }}</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <div class="w-3 h-3 rounded-full bg-gray-200"></div>
                                    <span class="text-xs text-gray-500">{{ $t('a_remaining') }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Campaign Leads Table -->
            <div
                :class="showLeads ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
                class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden transition-all duration-500 ease-out"
            >
                <div class="px-4 md:px-6 py-4 border-b border-gray-100 bg-gray-50/50 flex items-center justify-between">
                    <div class="flex items-center gap-2.5">
                        <div class="w-8 h-8 rounded-lg bg-slate-50 flex items-center justify-center">
                            <svg class="w-4 h-4 text-[#1B365D]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
                        </div>
                        <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wider">{{ $t('a_campaign_leads') }}</h3>
                    </div>
                    <span class="text-xs font-medium text-gray-400 bg-gray-100 px-2.5 py-1 rounded-full">{{ leads.data?.length || 0 }} leads</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="ltr:text-left rtl:text-right text-[11px] text-gray-500 uppercase tracking-wider border-b border-gray-100 bg-gray-50/30">
                                <th class="px-5 py-3.5 font-semibold">{{ $t('a_lead') }}</th>
                                <th class="px-5 py-3.5 font-semibold">{{ $t('a_contact') }}</th>
                                <th class="px-5 py-3.5 font-semibold text-center">{{ $t('a_status') }}</th>
                                <th class="px-5 py-3.5 font-semibold text-center">{{ $t('a_score') }}</th>
                                <th class="px-5 py-3.5 font-semibold">{{ $t('a_created') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <tr
                                v-for="(lead, index) in leads.data"
                                :key="lead.id"
                                :class="index % 2 === 0 ? 'bg-white' : 'bg-gray-50/30'"
                                class="hover:bg-[#C4A265]/[0.03] transition-colors duration-150"
                            >
                                <td class="px-5 py-3.5">
                                    <div class="flex items-center gap-3">
                                        <div :class="getInitialsColor(lead.full_name)" class="w-9 h-9 rounded-full flex items-center justify-center flex-shrink-0">
                                            <span class="text-xs font-bold">{{ getInitials(lead.full_name) }}</span>
                                        </div>
                                        <Link :href="`/admin/leads/${lead.id}`" class="font-semibold text-gray-900 hover:text-[#C4A265] transition-colors duration-150">
                                            {{ lead.full_name }}
                                        </Link>
                                    </div>
                                </td>
                                <td class="px-5 py-3.5">
                                    <div class="text-gray-600">
                                        <div v-if="lead.phone" class="flex items-center gap-1.5 text-xs">
                                            <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" /></svg>
                                            {{ lead.phone }}
                                        </div>
                                        <div v-else-if="lead.email" class="flex items-center gap-1.5 text-xs">
                                            <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>
                                            {{ lead.email }}
                                        </div>
                                        <span v-else class="text-xs text-gray-400">-</span>
                                    </div>
                                </td>
                                <td class="px-5 py-3.5 text-center">
                                    <span :class="leadStatusColors[lead.status] || 'bg-gray-100 text-gray-600 border-gray-200'" class="inline-flex items-center gap-1.5 px-2.5 py-1 text-[11px] font-semibold rounded-full capitalize border">
                                        <span :class="leadStatusDotColors[lead.status] || 'bg-gray-400'" class="w-1.5 h-1.5 rounded-full"></span>
                                        {{ lead.status }}
                                    </span>
                                </td>
                                <td class="px-5 py-3.5 text-center">
                                    <div class="flex items-center justify-center gap-2">
                                        <div class="w-16 h-1.5 bg-gray-100 rounded-full overflow-hidden">
                                            <div
                                                class="h-full rounded-full transition-all duration-300"
                                                :style="{
                                                    width: `${Math.min(lead.score || 0, 100)}%`,
                                                    background: (lead.score || 0) >= 70 ? 'linear-gradient(to right, #10b981, #059669)' : (lead.score || 0) >= 40 ? 'linear-gradient(to right, #C4A265, #D4B87A)' : 'linear-gradient(to right, #f59e0b, #d97706)'
                                                }"
                                            ></div>
                                        </div>
                                        <span class="text-xs font-bold tabular-nums text-gray-700 min-w-[24px]">{{ lead.score }}</span>
                                    </div>
                                </td>
                                <td class="px-5 py-3.5 text-xs text-gray-500">
                                    {{ formatDate(lead.created_at) }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Empty State -->
                <div v-if="!leads.data?.length" class="px-4 md:px-6 py-20 text-center">
                    <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-gray-100 mb-4">
                        <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
                    </div>
                    <h3 class="text-sm font-semibold text-gray-700 mb-1">{{ $t('a_no_leads_yet') }}</h3>
                    <p class="text-sm text-gray-400">{{ $t('a_campaign_leads_hint') }}</p>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
