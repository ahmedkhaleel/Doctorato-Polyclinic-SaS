<script setup>
import { ref, computed, watch } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import SecretaryLayout from '@/Layouts/SecretaryLayout.vue';

defineOptions({ layout: SecretaryLayout });

const page = usePage();
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const props = defineProps({
    followups: Object,
    filters: Object,
    stats: Object,
});

const search = ref(props.filters?.search || '');
const statusFilter = ref(props.filters?.status || 'pending');

function applyFilters() {
    router.get('/secretary/dental/followups', {
        search: search.value || undefined,
        status: statusFilter.value || undefined,
    }, { preserveState: true, preserveScroll: true });
}

let searchTimeout = null;
watch(search, () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(applyFilters, 400);
});
watch(statusFilter, applyFilters);

function $localized(obj, field) {
    if (!obj) return '';
    return obj[field + '_' + (isRtl.value ? 'ar' : 'en')] || obj[field + '_en'] || obj[field] || '';
}

function daysUntil(dateStr) {
    if (!dateStr) return null;
    return Math.ceil((new Date(dateStr) - new Date()) / (1000 * 60 * 60 * 24));
}

function formatDate(date) {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
}

const statusConfig = {
    pending: { label: isRtl.value ? 'بانتظار الحجز' : 'Needs Booking', bg: 'bg-amber-50', text: 'text-amber-700', dot: 'bg-amber-500' },
    sms_sent: { label: isRtl.value ? 'تم إرسال SMS' : 'SMS Sent', bg: 'bg-blue-50', text: 'text-blue-700', dot: 'bg-blue-500' },
    booking_created: { label: isRtl.value ? 'تم الحجز' : 'Booked', bg: 'bg-green-50', text: 'text-green-700', dot: 'bg-green-500' },
    completed: { label: isRtl.value ? 'مكتمل' : 'Completed', bg: 'bg-gray-50', text: 'text-gray-600', dot: 'bg-gray-400' },
    cancelled: { label: isRtl.value ? 'ملغي' : 'Cancelled', bg: 'bg-red-50', text: 'text-red-600', dot: 'bg-red-400' },
};

function getStatus(status) {
    return statusConfig[status] || { label: status, bg: 'bg-gray-50', text: 'text-gray-500', dot: 'bg-gray-300' };
}

function isOverdue(f) {
    return f.status === 'pending' && !f.booking_id && daysUntil(f.scheduled_date) < 0;
}

const statCards = computed(() => [
    { label: isRtl.value ? 'تحتاج حجز' : 'Needs Booking', value: props.stats?.pending ?? 0, color: 'amber', filter: 'pending' },
    { label: isRtl.value ? 'متأخرة' : 'Overdue', value: props.stats?.overdue ?? 0, color: 'red', filter: 'overdue' },
    { label: isRtl.value ? 'هذا الأسبوع' : 'This Week', value: props.stats?.upcoming_week ?? 0, color: 'cyan', filter: 'pending' },
    { label: isRtl.value ? 'تم حجزها' : 'Booked', value: props.stats?.booked ?? 0, color: 'green', filter: 'booked' },
]);

const colorMap = {
    amber: { bg: 'bg-amber-50', text: 'text-amber-700', gradient: 'from-amber-500 to-amber-600' },
    red: { bg: 'bg-red-50', text: 'text-red-700', gradient: 'from-red-500 to-red-600' },
    cyan: { bg: 'bg-cyan-50', text: 'text-cyan-700', gradient: 'from-cyan-500 to-cyan-600' },
    green: { bg: 'bg-green-50', text: 'text-green-700', gradient: 'from-green-500 to-green-600' },
};
</script>

<template>
    <div class="space-y-6">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">{{ isRtl ? 'متابعات الأسنان المعلقة' : 'Pending Dental Follow-ups' }}</h1>
                <p class="text-sm text-gray-500 mt-1">{{ isRtl ? 'مواعيد المتابعة التي تحتاج لجدولة حجز' : 'Follow-up appointments that need booking' }}</p>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 sm:gap-4">
            <button
                v-for="card in statCards"
                :key="card.label"
                @click="statusFilter = card.filter"
                class="relative bg-white rounded-2xl p-4 shadow-sm border border-gray-100/80 hover:shadow-md transition-all duration-200 text-left"
                :class="statusFilter === card.filter ? 'ring-2 ring-offset-1 ring-cyan-400' : ''"
            >
                <div :class="`absolute top-0 left-0 right-0 h-1 bg-gradient-to-r ${colorMap[card.color].gradient} rounded-t-2xl opacity-80`"></div>
                <p class="text-[13px] font-medium text-gray-500">{{ card.label }}</p>
                <p class="text-2xl font-bold mt-1" :class="colorMap[card.color].text">{{ card.value }}</p>
            </button>
        </div>

        <!-- Filters -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100/80 p-4">
            <div class="flex flex-col sm:flex-row gap-3">
                <div class="flex-1">
                    <input
                        v-model="search"
                        type="text"
                        :placeholder="isRtl ? 'بحث بالاسم أو رقم الملف أو الهاتف...' : 'Search by name, file # or phone...'"
                        class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:ring-2 focus:ring-cyan-400 focus:border-transparent"
                    />
                </div>
                <select
                    v-model="statusFilter"
                    class="px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:ring-2 focus:ring-cyan-400 focus:border-transparent"
                >
                    <option value="pending">{{ isRtl ? 'تحتاج حجز' : 'Needs Booking' }}</option>
                    <option value="overdue">{{ isRtl ? 'متأخرة' : 'Overdue' }}</option>
                    <option value="booked">{{ isRtl ? 'تم الحجز' : 'Booked' }}</option>
                    <option value="all">{{ isRtl ? 'الكل' : 'All' }}</option>
                </select>
            </div>
        </div>

        <!-- Followups Table -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100/80 overflow-hidden">
            <div class="overflow-x-auto">
                <table v-if="followups.data?.length" class="min-w-full">
                    <thead>
                        <tr class="bg-gray-50/50">
                            <th class="px-6 py-3 ltr:text-left rtl:text-right text-[11px] font-semibold text-gray-500 uppercase tracking-wider">{{ isRtl ? 'المريض' : 'Patient' }}</th>
                            <th class="px-6 py-3 ltr:text-left rtl:text-right text-[11px] font-semibold text-gray-500 uppercase tracking-wider hidden sm:table-cell">{{ isRtl ? 'الطبيب' : 'Doctor' }}</th>
                            <th class="px-6 py-3 ltr:text-left rtl:text-right text-[11px] font-semibold text-gray-500 uppercase tracking-wider hidden md:table-cell">{{ isRtl ? 'العلاج' : 'Treatment' }}</th>
                            <th class="px-6 py-3 ltr:text-left rtl:text-right text-[11px] font-semibold text-gray-500 uppercase tracking-wider">{{ isRtl ? 'تاريخ المتابعة' : 'Follow-up Date' }}</th>
                            <th class="px-6 py-3 ltr:text-left rtl:text-right text-[11px] font-semibold text-gray-500 uppercase tracking-wider">{{ isRtl ? 'الحالة' : 'Status' }}</th>
                            <th class="px-6 py-3 ltr:text-left rtl:text-right text-[11px] font-semibold text-gray-500 uppercase tracking-wider">{{ isRtl ? 'إجراء' : 'Action' }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="f in followups.data" :key="f.id"
                            class="hover:bg-gray-50/50 transition-colors duration-150"
                            :class="isOverdue(f) ? 'bg-red-50/30' : ''">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-lg bg-cyan-50 flex items-center justify-center text-cyan-600 text-xs font-bold flex-shrink-0">
                                        {{ (f.patient?.full_name || '?').charAt(0).toUpperCase() }}
                                    </div>
                                    <div>
                                        <p class="text-sm font-semibold text-gray-900">{{ f.patient?.full_name || '-' }}</p>
                                        <p class="text-[11px] text-gray-400">{{ f.patient?.file_number }} &middot; {{ f.patient?.phone }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 hidden sm:table-cell">
                                {{ $localized(f.doctor, 'name') || '-' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap hidden md:table-cell">
                                <div>
                                    <p class="text-sm text-gray-700">{{ f.treatment?.treatment_type?.replace('_', ' ') || '-' }}</p>
                                    <p v-if="f.treatment?.tooth_number" class="text-[11px] text-gray-400">
                                        {{ isRtl ? 'سن' : 'Tooth' }} #{{ f.treatment.tooth_number }}
                                    </p>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div>
                                    <p class="text-sm font-semibold text-gray-800">{{ formatDate(f.scheduled_date) }}</p>
                                    <p v-if="daysUntil(f.scheduled_date) !== null" class="text-[11px] mt-0.5"
                                        :class="daysUntil(f.scheduled_date) < 0 ? 'text-red-600 font-semibold' : daysUntil(f.scheduled_date) <= 3 ? 'text-amber-600' : 'text-gray-400'">
                                        <template v-if="daysUntil(f.scheduled_date) < 0">
                                            {{ isRtl ? `متأخر ${Math.abs(daysUntil(f.scheduled_date))} يوم` : `${Math.abs(daysUntil(f.scheduled_date))} days overdue` }}
                                        </template>
                                        <template v-else-if="daysUntil(f.scheduled_date) === 0">
                                            {{ isRtl ? 'اليوم!' : 'Today!' }}
                                        </template>
                                        <template v-else>
                                            {{ isRtl ? `بعد ${daysUntil(f.scheduled_date)} يوم` : `in ${daysUntil(f.scheduled_date)} days` }}
                                        </template>
                                    </p>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span v-if="isOverdue(f)" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-semibold bg-red-50 text-red-700">
                                    <span class="w-1.5 h-1.5 rounded-full bg-red-500"></span>
                                    {{ isRtl ? 'متأخرة' : 'Overdue' }}
                                </span>
                                <span v-else :class="[getStatus(f.status).bg, getStatus(f.status).text]"
                                    class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-semibold">
                                    <span :class="getStatus(f.status).dot" class="w-1.5 h-1.5 rounded-full"></span>
                                    {{ getStatus(f.status).label }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <Link v-if="f.status === 'pending' && !f.booking_id"
                                    href="/secretary/bookings/create"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium text-white bg-gradient-to-r from-cyan-500 to-teal-500 hover:from-cyan-600 hover:to-teal-600 shadow-sm transition-all">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                    {{ isRtl ? 'حجز موعد' : 'Book' }}
                                </Link>
                                <span v-else-if="f.status === 'booking_created'" class="text-xs text-green-600 font-medium">
                                    {{ isRtl ? 'تم الحجز' : 'Booked' }}
                                </span>
                                <span v-else class="text-xs text-gray-400">-</span>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <!-- Empty state -->
                <div v-else class="py-16 text-center">
                    <div class="flex flex-col items-center">
                        <div class="w-16 h-16 rounded-2xl bg-cyan-50 flex items-center justify-center mb-3">
                            <svg class="w-8 h-8 text-cyan-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <p class="text-sm font-medium text-gray-400">{{ isRtl ? 'لا توجد متابعات معلقة' : 'No pending follow-ups' }}</p>
                        <p class="text-xs text-gray-300 mt-1">{{ isRtl ? 'ستظهر المتابعات المجدولة هنا' : 'Scheduled follow-ups will appear here' }}</p>
                    </div>
                </div>
            </div>

            <!-- Pagination -->
            <div v-if="followups.last_page > 1" class="px-6 py-4 border-t border-gray-100 flex justify-center gap-1">
                <Link v-for="link in followups.links" :key="link.label"
                    :href="link.url || '#'"
                    :class="['px-3 py-1.5 text-xs rounded-lg border', link.active ? 'bg-cyan-600 text-white border-cyan-600' : 'bg-white text-gray-600 border-gray-200 hover:bg-gray-50']"
                    v-html="link.label" preserve-state />
            </div>
        </div>
    </div>
</template>
