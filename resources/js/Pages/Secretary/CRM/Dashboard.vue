<script setup>
import { computed } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import SecretaryLayout from '@/Layouts/SecretaryLayout.vue';

const page = usePage();
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const props = defineProps({
    stats: Object,
    todayFollowUps: Array,
    overdueFollowUps: Array,
    recentLeads: Array,
});

const statusLabels = {
    new: isRtl.value ? 'جديد' : 'New', contacted: isRtl.value ? 'تم التواصل' : 'Contacted', qualified: isRtl.value ? 'مؤهل' : 'Qualified',
    appointment_booked: 'Appt. Booked', consultation_done: 'Consultation',
    negotiation: 'Negotiation', converted: isRtl.value ? 'محوّل' : 'Converted', lost: isRtl.value ? 'خسارة' : 'Lost', dormant: isRtl.value ? 'خامد' : 'Dormant',
};
const statusColors = {
    new: 'bg-blue-100 text-blue-700', contacted: 'bg-indigo-100 text-indigo-700',
    qualified: 'bg-purple-100 text-purple-700', appointment_booked: 'bg-amber-100 text-amber-700',
    consultation_done: 'bg-teal-100 text-teal-700', negotiation: 'bg-orange-100 text-orange-700',
    converted: 'bg-green-100 text-green-700', lost: 'bg-red-100 text-red-700', dormant: 'bg-gray-100 text-gray-600',
};

function formatTime(date) {
    if (!date) return '-';
    return new Date(date).toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });
}

function formatDate(date) {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('en-GB', { day: '2-digit', month: 'short' });
}

function completeFollowUp(fuId) {
    router.post(`/secretary/crm/follow-ups/${fuId}/complete`, { result: '' }, { preserveScroll: true });
}

function missFollowUp(fuId) {
    router.post(`/secretary/crm/follow-ups/${fuId}/miss`, {}, { preserveScroll: true });
}
</script>

<template>
    <SecretaryLayout title="My Leads">
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">{{ isRtl ? 'العملاء المحتملين' : 'My Leads' }}</h1>
                    <p class="text-sm text-gray-500 mt-1">Manage leads assigned to you</p>
                </div>
                <Link href="/secretary/crm/leads" class="inline-flex items-center px-4 py-2 rounded-lg text-white text-sm font-medium transition shadow-sm" style="background-color: #0d9488;">
                    View All Leads
                </Link>
            </div>

            <!-- Stats -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="bg-white rounded-xl shadow-sm p-5 border border-gray-100">
                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">My Active Leads</p>
                    <p class="text-2xl font-bold text-gray-800 mt-2">{{ stats.my_leads }}</p>
                </div>
                <div class="bg-white rounded-xl shadow-sm p-5 border border-gray-100">
                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">New Leads</p>
                    <p class="text-2xl font-bold text-blue-600 mt-2">{{ stats.new_leads }}</p>
                </div>
                <div class="bg-white rounded-xl shadow-sm p-5 border border-gray-100">
                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">{{ isRtl ? 'متابعات اليوم' : 'Today Follow-ups' }}</p>
                    <p class="text-2xl font-bold text-gray-800 mt-2">{{ stats.today_follow_ups }}</p>
                </div>
                <div class="bg-white rounded-xl shadow-sm p-5 border border-gray-100">
                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Overdue</p>
                    <p class="text-2xl font-bold" :class="stats.overdue_follow_ups > 0 ? 'text-red-600' : 'text-gray-800'">{{ stats.overdue_follow_ups }}</p>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Today's Follow-ups -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100">
                    <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                        <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wider">Today's Follow-ups</h3>
                        <span class="text-xs font-mono px-2 py-0.5 rounded-full bg-teal-50 text-teal-600">{{ todayFollowUps?.length || 0 }}</span>
                    </div>
                    <div class="divide-y divide-gray-50">
                        <div v-for="fu in todayFollowUps" :key="fu.id" class="px-6 py-3 flex items-center gap-3 hover:bg-gray-50/50 transition">
                            <div class="flex-1 min-w-0">
                                <Link :href="`/secretary/crm/leads/${fu.lead?.id}`" class="text-sm font-medium text-gray-800 hover:underline truncate block">
                                    {{ fu.lead?.full_name }}
                                </Link>
                                <div class="flex items-center gap-2 mt-0.5">
                                    <span class="text-xs text-gray-400 capitalize">{{ fu.type }}</span>
                                    <span class="text-xs text-gray-400">{{ formatTime(fu.scheduled_at) }}</span>
                                </div>
                            </div>
                            <div class="flex items-center gap-1">
                                <button @click="completeFollowUp(fu.id)" title="Complete" class="p-1 rounded text-green-500 hover:bg-green-50 transition">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                                </button>
                                <button @click="missFollowUp(fu.id)" title="Missed" class="p-1 rounded text-red-500 hover:bg-red-50 transition">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                                </button>
                            </div>
                        </div>
                        <div v-if="!todayFollowUps?.length" class="px-6 py-8 text-sm text-gray-400 text-center">No follow-ups today</div>
                    </div>
                </div>

                <!-- Overdue Follow-ups -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100" v-if="overdueFollowUps?.length">
                    <div class="px-6 py-4 border-b border-red-100 flex items-center justify-between bg-red-50/50 rounded-t-xl">
                        <h3 class="text-sm font-semibold text-red-700 uppercase tracking-wider">{{ isRtl ? 'متابعات متأخرة' : 'Overdue Follow-ups' }}</h3>
                        <span class="text-xs font-mono px-2 py-0.5 rounded-full bg-red-100 text-red-600">{{ overdueFollowUps.length }}</span>
                    </div>
                    <div class="divide-y divide-gray-50">
                        <div v-for="fu in overdueFollowUps" :key="fu.id" class="px-6 py-3 flex items-center gap-3 hover:bg-gray-50/50 transition">
                            <div class="flex-1 min-w-0">
                                <Link :href="`/secretary/crm/leads/${fu.lead?.id}`" class="text-sm font-medium text-gray-800 hover:underline truncate block">
                                    {{ fu.lead?.full_name }}
                                </Link>
                                <span class="text-xs text-red-500">Due {{ formatDate(fu.scheduled_at) }}</span>
                            </div>
                            <div class="flex items-center gap-1">
                                <button @click="completeFollowUp(fu.id)" title="Complete" class="p-1 rounded text-green-500 hover:bg-green-50 transition">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                                </button>
                                <button @click="missFollowUp(fu.id)" title="Missed" class="p-1 rounded text-red-500 hover:bg-red-50 transition">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Leads -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100" :class="overdueFollowUps?.length ? '' : ''">
                    <div class="px-6 py-4 border-b border-gray-100">
                        <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wider">My Recent Leads</h3>
                    </div>
                    <div class="divide-y divide-gray-50">
                        <Link v-for="lead in recentLeads" :key="lead.id" :href="`/secretary/crm/leads/${lead.id}`"
                            class="px-6 py-3 flex items-center gap-3 hover:bg-gray-50/50 transition block"
                        >
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-800 truncate">{{ lead.full_name }}</p>
                                <p class="text-xs text-gray-400">{{ lead.phone || lead.email || '-' }}</p>
                            </div>
                            <span :class="statusColors[lead.status]" class="px-2 py-0.5 text-[10px] font-semibold rounded-full whitespace-nowrap">
                                {{ statusLabels[lead.status] || lead.status }}
                            </span>
                        </Link>
                        <div v-if="!recentLeads?.length" class="px-6 py-8 text-sm text-gray-400 text-center">No leads assigned to you yet</div>
                    </div>
                </div>
            </div>
        </div>
    </SecretaryLayout>
</template>
