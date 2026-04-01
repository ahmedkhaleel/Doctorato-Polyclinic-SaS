<script setup>
import { computed, ref, watch } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import SecretaryLayout from '@/Layouts/SecretaryLayout.vue';

const page = usePage();
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const props = defineProps({
    leads: Object,
    filters: Object,
});

const search = ref(props.filters?.search || '');
const statusFilter = ref(props.filters?.status || '');

let searchTimeout = null;

function applyFilters() {
    router.get('/secretary/crm/leads', {
        search: search.value || undefined,
        status: statusFilter.value || undefined,
    }, { preserveState: true, replace: true });
}

watch(search, () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(applyFilters, 400);
});

watch(statusFilter, applyFilters);

function clearFilters() {
    search.value = '';
    statusFilter.value = '';
    router.get('/secretary/crm/leads', {}, { preserveState: true, replace: true });
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
const priorityBadges = {
    1: 'bg-red-100 text-red-700',
    2: 'bg-amber-100 text-amber-700',
    3: 'bg-blue-100 text-blue-700',
};

function formatDate(date) {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
}

function timeAgo(date) {
    if (!date) return '';
    const diff = Math.floor((new Date() - new Date(date)) / 1000);
    if (diff < 60) return 'just now';
    if (diff < 3600) return Math.floor(diff / 60) + 'm ago';
    if (diff < 86400) return Math.floor(diff / 3600) + 'h ago';
    return Math.floor(diff / 86400) + 'd ago';
}

const hasActiveFilters = () => search.value || statusFilter.value;
</script>

<template>
    <SecretaryLayout title="My Leads">
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">{{ isRtl ? 'العملاء المحتملين' : 'My Leads' }}</h1>
                    <p class="text-sm text-gray-500 mt-1">All leads assigned to you</p>
                </div>
                <Link href="/secretary/crm" class="inline-flex items-center px-4 py-2 rounded-lg text-white text-sm font-medium transition shadow-sm" style="background-color: #0d9488;">
                    <svg class="w-4 h-4 ltr:mr-1.5 rtl:ml-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" /></svg>
                    Dashboard
                </Link>
            </div>

            <!-- Filters -->
            <div class="bg-white rounded-xl shadow-sm p-4 border border-gray-100">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <div class="relative md:col-span-2">
                        <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                        <input v-model="search" type="text" placeholder="Search by name, phone, email..."
                            class="w-full pl-10 pr-4 py-2.5 text-sm border border-gray-200 rounded-lg focus:ring-2 focus:ring-teal-500/30 focus:border-teal-500 transition" />
                    </div>
                    <select v-model="statusFilter" class="text-sm border border-gray-200 rounded-lg py-2.5 px-3 focus:ring-2 focus:ring-teal-500/30 focus:border-teal-500">
                        <option value="">{{ isRtl ? 'جميع الحالات' : 'All Statuses' }}</option>
                        <option v-for="(label, key) in statusLabels" :key="key" :value="key">{{ label }}</option>
                    </select>
                </div>
                <div class="flex items-center justify-between mt-3" v-if="hasActiveFilters()">
                    <span class="text-xs text-gray-500">{{ leads.total }} results</span>
                    <button @click="clearFilters" class="text-xs text-red-500 hover:underline">Clear all filters</button>
                </div>
            </div>

            <!-- Table -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="ltr:text-left rtl:ltr:text-right rtl:text-left text-xs text-gray-400 uppercase border-b border-gray-100 bg-gray-50/50">
                                <th class="px-5 py-3 font-medium">Lead</th>
                                <th class="px-5 py-3 font-medium">Contact</th>
                                <th class="px-5 py-3 font-medium">{{ isRtl ? 'المصدر' : 'Source' }}</th>
                                <th class="px-5 py-3 font-medium text-center">Priority</th>
                                <th class="px-5 py-3 font-medium text-center">{{ isRtl ? 'الحالة' : 'Status' }}</th>
                                <th class="px-5 py-3 font-medium text-center">Score</th>
                                <th class="px-5 py-3 font-medium">Next Follow-up</th>
                                <th class="px-5 py-3 font-medium">Created</th>
                                <th class="px-5 py-3 font-medium"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <tr v-for="lead in leads.data" :key="lead.id" class="hover:bg-gray-50/50 transition">
                                <td class="px-5 py-3">
                                    <Link :href="`/secretary/crm/leads/${lead.id}`" class="font-medium text-gray-800 hover:underline">
                                        {{ lead.full_name }}
                                    </Link>
                                    <p class="text-xs text-gray-400 mt-0.5" v-if="lead.city">{{ lead.city }}</p>
                                </td>
                                <td class="px-5 py-3">
                                    <p class="text-gray-700">{{ lead.phone || '-' }}</p>
                                    <p class="text-xs text-gray-400" v-if="lead.email">{{ lead.email }}</p>
                                </td>
                                <td class="px-5 py-3">
                                    <span v-if="lead.source" class="inline-flex items-center gap-1 text-xs px-2 py-0.5 rounded-full"
                                        :style="{ backgroundColor: lead.source.color + '18', color: lead.source.color }"
                                    >
                                        {{ lead.source.name_en }}
                                    </span>
                                    <span v-else class="text-gray-400">-</span>
                                </td>
                                <td class="px-5 py-3 text-center">
                                    <span :class="priorityBadges[lead.priority]" class="px-2 py-0.5 text-[10px] font-bold rounded-full uppercase">
                                        {{ priorityLabels[lead.priority] }}
                                    </span>
                                </td>
                                <td class="px-5 py-3 text-center">
                                    <span :class="statusColors[lead.status]" class="px-2 py-0.5 text-[10px] font-semibold rounded-full whitespace-nowrap">
                                        {{ statusLabels[lead.status] || lead.status }}
                                    </span>
                                </td>
                                <td class="px-5 py-3 text-center">
                                    <span class="text-sm font-mono font-semibold" :class="lead.score >= 50 ? 'text-green-600' : lead.score >= 20 ? 'text-amber-600' : 'text-gray-400'">
                                        {{ lead.score }}
                                    </span>
                                </td>
                                <td class="px-5 py-3">
                                    <span v-if="lead.next_follow_up_at" class="text-xs"
                                        :class="new Date(lead.next_follow_up_at) < new Date() ? 'text-red-600 font-semibold' : 'text-gray-500'"
                                    >
                                        {{ formatDate(lead.next_follow_up_at) }}
                                    </span>
                                    <span v-else class="text-xs text-gray-400">-</span>
                                </td>
                                <td class="px-5 py-3">
                                    <span class="text-xs text-gray-400">{{ timeAgo(lead.created_at) }}</span>
                                </td>
                                <td class="px-5 py-3">
                                    <Link :href="`/secretary/crm/leads/${lead.id}`"
                                        class="text-xs font-medium hover:underline" style="color: #0d9488;"
                                    >{{ isRtl ? 'عرض' : 'View' }}</Link>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Empty State -->
                <div v-if="!leads.data?.length" class="px-6 py-16 text-center">
                    <svg class="w-12 h-12 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                    <p class="text-gray-500 text-sm">No leads found</p>
                </div>

                <!-- Pagination -->
                <div v-if="leads.last_page > 1" class="px-6 py-4 border-t border-gray-100 flex items-center justify-between">
                    <p class="text-xs text-gray-500">Showing {{ leads.from }} to {{ leads.to }} of {{ leads.total }}</p>
                    <div class="flex gap-1">
                        <Link v-for="link in leads.links" :key="link.label"
                            :href="link.url || '#'"
                            :class="[link.active ? 'bg-teal-600 text-white' : 'text-gray-600 hover:bg-gray-100', !link.url ? 'opacity-40 pointer-events-none' : '']"
                            class="px-3 py-1.5 text-xs rounded-md transition"
                            v-html="link.label"
                        />
                    </div>
                </div>
            </div>
        </div>
    </SecretaryLayout>
</template>
