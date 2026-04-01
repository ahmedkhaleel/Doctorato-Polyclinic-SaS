<script setup>
import { computed, ref, watch } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import SecretaryLayout from '@/Layouts/SecretaryLayout.vue';

defineOptions({ layout: SecretaryLayout });

const page = usePage();
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const props = defineProps({
    visits: Object,
    filters: Object,
});

const modules = computed(() => page.props.modules || {});
const activeModules = computed(() => {
    return Object.entries(modules.value)
        .filter(([, m]) => m.is_enabled !== false)
        .map(([slug, m]) => ({ slug, name: isRtl.value ? m.name_ar : m.name_en }));
});

const search = ref(props.filters?.search || '');
const statusFilter = ref(props.filters?.status || '');
const visitTypeFilter = ref(props.filters?.visit_type || '');
const dateFrom = ref(props.filters?.date_from || '');
const dateTo = ref(props.filters?.date_to || '');
const moduleFilter = ref(props.filters?.module || '');
let searchTimeout = null;

function buildParams() {
    return {
        search: search.value || undefined,
        status: statusFilter.value || undefined,
        visit_type: visitTypeFilter.value || undefined,
        date_from: dateFrom.value || undefined,
        date_to: dateTo.value || undefined,
        module: moduleFilter.value || undefined,
    };
}

function applyFilters() {
    router.get('/secretary/visits', buildParams(), {
        preserveState: true,
        replace: true,
    });
}

watch(search, () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        applyFilters();
    }, 400);
});

watch([statusFilter, visitTypeFilter, dateFrom, dateTo, moduleFilter], () => {
    applyFilters();
});

function clearFilters() {
    search.value = '';
    statusFilter.value = '';
    visitTypeFilter.value = '';
    dateFrom.value = '';
    dateTo.value = '';
    moduleFilter.value = '';
    router.get('/secretary/visits', {}, { preserveState: true, replace: true });
}

const statusLabels = {
    waiting: 'Waiting',
    in_progress: 'In Progress',
    completed: 'Completed',
    cancelled: 'Cancelled',
};

const statusColors = {
    waiting: 'bg-yellow-100 text-yellow-800',
    in_progress: 'bg-blue-100 text-blue-800',
    completed: 'bg-green-100 text-green-800',
    cancelled: 'bg-red-100 text-red-800',
};

const visitTypeLabels = {
    consultation: isRtl.value ? 'استشارة' : 'Consultation',
    session: isRtl.value ? 'جلسة' : 'Session',
    follow_up: isRtl.value ? 'متابعة' : 'Follow Up',
};

const visitTypeBadgeColors = {
    consultation: 'bg-teal-100 text-teal-700',
    session: 'bg-cyan-100 text-cyan-700',
    follow_up: 'bg-sky-100 text-sky-700',
};

function formatDate(date) {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('en-GB', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    });
}

const hasActiveFilters = ref(false);
watch([search, statusFilter, visitTypeFilter, dateFrom, dateTo, moduleFilter], () => {
    hasActiveFilters.value = !!(search.value || statusFilter.value || visitTypeFilter.value || dateFrom.value || dateTo.value || moduleFilter.value);
}, { immediate: true });
</script>

<template>
    <div class="space-y-6">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">{{ isRtl ? 'الزيارات' : 'Visits' }}</h1>
                <p class="text-sm text-gray-500 mt-1">Manage and track all patient visits</p>
            </div>
            <Link
                href="/secretary/bookings/create"
                class="inline-flex items-center px-5 py-2.5 rounded-lg text-white text-sm font-medium transition-all duration-200 shadow-md hover:shadow-lg"
                style="background: linear-gradient(135deg, #0d9488, #06b6d4);"
            >
                <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                {{ isRtl ? 'حجز جديد' : 'New Booking' }}
            </Link>
        </div>

        <!-- Filters -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <div class="flex flex-wrap items-center gap-3">
                <!-- Search -->
                <div class="relative flex-1 min-w-[260px]">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    <input
                        v-model="search"
                        type="text"
                        placeholder="Search by patient name or phone..."
                        class="w-full pl-10 pr-4 py-2.5 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-teal-200 focus:border-teal-400 transition"
                    />
                </div>

                <!-- Status -->
                <select
                    v-model="statusFilter"
                    class="px-4 py-2.5 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-teal-200 focus:border-teal-400 transition bg-white"
                >
                    <option value="">{{ isRtl ? 'جميع الحالات' : 'All Status' }}</option>
                    <option value="waiting">{{ isRtl ? 'انتظار' : 'Waiting' }}</option>
                    <option value="in_progress">{{ isRtl ? 'جاري' : 'In Progress' }}</option>
                    <option value="completed">{{ isRtl ? 'مكتمل' : 'Completed' }}</option>
                    <option value="cancelled">Cancelled</option>
                </select>

                <!-- Module / Department -->
                <select
                    v-if="activeModules.length > 1"
                    v-model="moduleFilter"
                    class="px-4 py-2.5 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-teal-200 focus:border-teal-400 transition bg-white"
                >
                    <option value="">{{ isRtl ? 'كل الأقسام' : 'All Departments' }}</option>
                    <option v-for="mod in activeModules" :key="mod.slug" :value="mod.slug">{{ mod.name }}</option>
                </select>

                <!-- Visit Type -->
                <select
                    v-model="visitTypeFilter"
                    class="px-4 py-2.5 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-teal-200 focus:border-teal-400 transition bg-white"
                >
                    <option value="">{{ isRtl ? 'جميع الأنواع' : 'All Types' }}</option>
                    <option value="consultation">Consultation</option>
                    <option value="session">Session</option>
                    <option value="follow_up">{{ isRtl ? 'متابعة' : 'Follow Up' }}</option>
                </select>

                <!-- Date Range -->
                <div class="flex items-center gap-2">
                    <input
                        v-model="dateFrom"
                        type="date"
                        :max="dateTo || undefined"
                        class="px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-teal-200 focus:border-teal-400 transition"
                        title="From date"
                    />
                    <span class="text-gray-400 text-xs">to</span>
                    <input
                        v-model="dateTo"
                        type="date"
                        :min="dateFrom || undefined"
                        class="px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-teal-200 focus:border-teal-400 transition"
                        title="To date"
                    />
                </div>

                <!-- Clear -->
                <button
                    v-if="hasActiveFilters"
                    @click="clearFilters"
                    class="px-3 py-2.5 text-sm text-gray-500 hover:text-red-500 hover:bg-red-50 rounded-lg transition"
                    title="Clear all filters"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>

        <!-- Table -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead>
                        <tr class="bg-gradient-to-r from-teal-50 to-cyan-50">
                            <th class="px-6 py-3.5 ltr:text-left rtl:ltr:text-right rtl:text-left text-xs font-semibold text-teal-700 uppercase tracking-wider">{{ isRtl ? 'المريض' : 'Patient' }}</th>
                            <th class="px-6 py-3.5 ltr:text-left rtl:ltr:text-right rtl:text-left text-xs font-semibold text-teal-700 uppercase tracking-wider">{{ isRtl ? 'الطبيب' : 'Doctor' }}</th>
                            <th class="px-6 py-3.5 ltr:text-left rtl:ltr:text-right rtl:text-left text-xs font-semibold text-teal-700 uppercase tracking-wider">{{ isRtl ? 'الخدمة' : 'Service' }}</th>
                            <th class="px-6 py-3.5 ltr:text-left rtl:ltr:text-right rtl:text-left text-xs font-semibold text-teal-700 uppercase tracking-wider">{{ isRtl ? 'النوع' : 'Type' }}</th>
                            <th class="px-6 py-3.5 ltr:text-left rtl:ltr:text-right rtl:text-left text-xs font-semibold text-teal-700 uppercase tracking-wider">{{ isRtl ? 'حجز' : 'Booking' }}</th>
                            <th class="px-6 py-3.5 ltr:text-left rtl:ltr:text-right rtl:text-left text-xs font-semibold text-teal-700 uppercase tracking-wider">Visit Date</th>
                            <th class="px-6 py-3.5 ltr:text-left rtl:ltr:text-right rtl:text-left text-xs font-semibold text-teal-700 uppercase tracking-wider">{{ isRtl ? 'الحالة' : 'Status' }}</th>
                            <th class="px-6 py-3.5 ltr:text-right rtl:text-left text-xs font-semibold text-teal-700 uppercase tracking-wider">{{ isRtl ? 'إجراءات' : 'Actions' }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="visit in visits.data" :key="visit.id" class="hover:bg-teal-50/30 transition-colors">
                            <!-- Patient -->
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="w-9 h-9 rounded-full flex-shrink-0 flex items-center justify-center text-white text-xs font-bold bg-gradient-to-br from-teal-500 to-cyan-500">
                                        {{ visit.patient?.full_name?.charAt(0) || '?' }}
                                    </div>
                                    <div class="ml-3">
                                        <div class="text-sm font-medium text-gray-900">{{ visit.patient?.full_name || '-' }}</div>
                                        <div class="text-xs text-gray-400">{{ visit.patient?.phone || '' }}</div>
                                    </div>
                                </div>
                            </td>

                            <!-- Doctor -->
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                {{ visit.doctor?.name_en || visit.doctor?.name || '-' }}
                            </td>

                            <!-- Service -->
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                {{ visit.service?.name_en || visit.service?.name || '-' }}
                            </td>

                            <!-- Visit Type -->
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span
                                    :class="visitTypeBadgeColors[visit.visit_type] || 'bg-gray-100 text-gray-600'"
                                    class="px-2.5 py-0.5 inline-flex text-xs leading-5 font-semibold rounded-full"
                                >
                                    {{ visitTypeLabels[visit.visit_type] || visit.visit_type }}
                                </span>
                            </td>

                            <!-- Booking -->
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <Link v-if="visit.booking" :href="`/secretary/bookings/${visit.booking.id}`" class="font-medium text-teal-600 hover:text-teal-800 hover:underline">{{ visit.booking.booking_number || `#${visit.booking.id}` }}</Link>
                                <span v-else class="text-gray-300">-</span>
                            </td>

                            <!-- Visit Date -->
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                {{ formatDate(visit.visit_date) }}
                            </td>

                            <!-- Status -->
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span
                                    :class="statusColors[visit.status]"
                                    class="px-2.5 py-0.5 inline-flex text-xs leading-5 font-semibold rounded-full"
                                >
                                    {{ statusLabels[visit.status] || visit.status }}
                                </span>
                            </td>

                            <!-- Actions -->
                            <td class="px-6 py-4 whitespace-nowrap ltr:text-right rtl:text-left">
                                <Link
                                    :href="`/secretary/visits/${visit.id}`"
                                    class="inline-flex items-center px-3 py-1.5 text-xs font-medium rounded-lg text-teal-700 bg-teal-50 hover:bg-teal-100 transition-colors"
                                >
                                    <svg class="w-3.5 h-3.5 ltr:mr-1 rtl:ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                    View
                                </Link>
                            </td>
                        </tr>

                        <!-- Empty State -->
                        <tr v-if="!visits.data || visits.data.length === 0">
                            <td colspan="8" class="px-6 py-16 text-center">
                                <svg class="w-12 h-12 mx-auto text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                </svg>
                                <p class="text-sm text-gray-500 font-medium">No visits found</p>
                                <p class="text-xs text-gray-400 mt-1">Try adjusting your filters or create a new visit.</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div v-if="visits.links && visits.links.length > 3" class="px-6 py-4 border-t border-gray-100 flex flex-col sm:flex-row items-center justify-between gap-3 bg-gray-50/50">
                <p class="text-sm text-gray-500">
                    Showing <span class="font-medium text-gray-700">{{ visits.from }}</span> to <span class="font-medium text-gray-700">{{ visits.to }}</span> of <span class="font-medium text-gray-700">{{ visits.total }}</span> results
                </p>
                <nav class="flex space-x-1">
                    <template v-for="link in visits.links" :key="link.label">
                        <Link
                            v-if="link.url"
                            :href="link.url"
                            v-html="link.label"
                            class="px-3 py-1.5 text-sm rounded-lg border transition-all duration-200"
                            :class="link.active
                                ? 'text-white border-transparent shadow-sm'
                                : 'text-gray-600 border-gray-200 hover:bg-teal-50 hover:text-teal-700 hover:border-teal-200'"
                            :style="link.active ? 'background: linear-gradient(135deg, #0d9488, #06b6d4);' : ''"
                            preserve-state
                        />
                        <span v-else v-html="link.label" class="px-3 py-1.5 text-sm text-gray-300" />
                    </template>
                </nav>
            </div>
        </div>
    </div>
</template>
