<script setup>
import { ref, computed, watch } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import DoctorLayout from '@/Layouts/DoctorLayout.vue';

defineOptions({ layout: DoctorLayout });

const page = usePage();
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const props = defineProps({
    followups: Object,
    filters: Object,
    stats: Object,
});

const search = ref(props.filters?.search || '');
const statusFilter = ref(props.filters?.status || 'pending');

const isFiltering = ref(false);

function applyFilters() {
    isFiltering.value = true;
    router.get('/doctor/dental/followups', {
        search: search.value || undefined,
        status: statusFilter.value || undefined,
    }, {
        preserveState: true,
        preserveScroll: true,
        onFinish: () => { isFiltering.value = false; },
    });
}

let searchTimeout = null;
watch(search, () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(applyFilters, 400);
});
watch(statusFilter, applyFilters);

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
    { label: isRtl.value ? 'هذا الأسبوع' : 'This Week', value: props.stats?.this_week ?? 0, color: 'cyan', filter: 'pending' },
    { label: isRtl.value ? 'مكتملة هذا الشهر' : 'Completed (Month)', value: props.stats?.completed_month ?? 0, color: 'green', filter: 'completed' },
]);

const colorMap = {
    amber: { text: 'text-amber-700', gradient: 'from-amber-500 to-amber-600' },
    red: { text: 'text-red-700', gradient: 'from-red-500 to-red-600' },
    cyan: { text: 'text-cyan-700', gradient: 'from-cyan-500 to-cyan-600' },
    green: { text: 'text-green-700', gradient: 'from-green-500 to-green-600' },
};
</script>

<template>
    <div class="space-y-6">
        <!-- Hero Header -->
        <div class="dental-hero-enter relative overflow-hidden rounded-2xl bg-gradient-to-br from-gray-900 via-gray-800 to-gray-900 p-6 sm:p-7">
            <div class="absolute -top-12 ltr:-right-12 rtl:-left-12 w-48 h-48 bg-[#C4A265]/10 rounded-full blur-3xl"></div>
            <div class="absolute -bottom-8 ltr:left-20 rtl:right-20 w-32 h-32 bg-purple-500/10 rounded-full blur-2xl"></div>

            <div class="relative z-10">
                <div class="mb-5">
                    <p class="text-[#C4A265] text-xs font-semibold tracking-wider uppercase mb-1">{{ isRtl ? 'طب الأسنان' : 'Dental' }}</p>
                    <h1 class="text-2xl sm:text-3xl font-bold text-white">{{ isRtl ? 'متابعات مرضاي' : 'My Patient Follow-ups' }}</h1>
                    <p class="text-gray-400 text-sm mt-1">{{ isRtl ? 'مواعيد المتابعة المجدولة لمرضاك' : 'Scheduled follow-up appointments for your patients' }}</p>
                </div>

                <!-- Stats inside hero -->
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 sm:gap-3">
                    <button
                        v-for="(card, idx) in statCards"
                        :key="card.label"
                        @click="statusFilter = card.filter"
                        class="dental-card-enter relative bg-white/5 backdrop-blur-sm rounded-xl p-3 border text-left transition-all duration-200 hover:bg-white/10"
                        :class="statusFilter === card.filter ? 'border-[#C4A265]/50 ring-1 ring-[#C4A265]/30' : 'border-white/10'"
                        :style="{ animationDelay: (0.15 + idx * 0.05) + 's' }"
                    >
                        <p class="text-[11px] font-medium text-gray-400">{{ card.label }}</p>
                        <p class="text-xl font-bold mt-0.5 text-white">{{ card.value }}</p>
                    </button>
                </div>

                <!-- Filters inside hero -->
                <div class="dental-card-enter flex flex-col sm:flex-row gap-3 mt-4" style="animation-delay:0.35s">
                    <div class="relative flex-1">
                        <svg v-if="!isFiltering" class="absolute ltr:left-4 rtl:right-4 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        <svg v-else class="absolute ltr:left-4 rtl:right-4 top-1/2 -translate-y-1/2 w-4 h-4 text-[#C4A265] animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                        <input
                            v-model="search"
                            type="text"
                            :placeholder="isRtl ? 'بحث بالاسم أو رقم الملف...' : 'Search by name or file #...'"
                            class="w-full ltr:pl-11 rtl:pr-11 px-4 py-2.5 bg-white/10 backdrop-blur-sm border border-white/15 rounded-xl text-sm text-white placeholder-gray-400 focus:ring-2 focus:ring-[#C4A265]/30 focus:border-[#C4A265]/50 transition"
                        />
                    </div>
                    <select
                        v-model="statusFilter"
                        class="px-4 py-2.5 bg-white/10 backdrop-blur-sm border border-white/15 rounded-xl text-sm text-white focus:ring-2 focus:ring-[#C4A265]/30 focus:border-[#C4A265]/50 transition"
                    >
                        <option value="pending" class="text-gray-900">{{ isRtl ? 'تحتاج حجز' : 'Needs Booking' }}</option>
                        <option value="overdue" class="text-gray-900">{{ isRtl ? 'متأخرة' : 'Overdue' }}</option>
                        <option value="booked" class="text-gray-900">{{ isRtl ? 'تم الحجز' : 'Booked' }}</option>
                        <option value="completed" class="text-gray-900">{{ isRtl ? 'مكتمل' : 'Completed' }}</option>
                        <option value="all" class="text-gray-900">{{ isRtl ? 'الكل' : 'All' }}</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Followups List -->
        <div class="dental-card-enter bg-white rounded-2xl shadow-sm border border-gray-100/80 overflow-hidden" style="animation-delay:0.4s">
            <div v-if="followups.data?.length" class="divide-y divide-gray-50">
                <div v-for="f in followups.data" :key="f.id"
                    class="px-4 sm:px-6 py-4 hover:bg-gray-50/50 transition-colors"
                    :class="isOverdue(f) ? 'bg-red-50/30' : ''">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 sm:gap-4">
                        <div class="flex items-center gap-3 sm:gap-4 min-w-0 flex-1">
                            <div class="w-10 h-10 rounded-xl flex items-center justify-center text-sm font-bold flex-shrink-0"
                                :class="isOverdue(f) ? 'bg-red-100 text-red-700' : 'bg-gradient-to-br from-cyan-50 to-teal-50 text-cyan-700'">
                                {{ (f.patient?.full_name || '?').charAt(0).toUpperCase() }}
                            </div>
                            <div class="min-w-0">
                                <div class="flex items-center gap-2">
                                    <p class="text-sm font-semibold text-gray-900">{{ f.patient?.full_name || '-' }}</p>
                                    <span class="text-[10px] text-gray-400 font-mono">{{ f.patient?.file_number }}</span>
                                </div>
                                <div class="flex items-center gap-2 mt-0.5 text-[11px] text-gray-500">
                                    <span v-if="f.treatment?.treatment_type">{{ f.treatment.treatment_type.replace(/_/g, ' ') }}</span>
                                    <span v-if="f.treatment?.tooth_number" class="text-gray-400">
                                        &middot; {{ isRtl ? 'سن' : 'Tooth' }} #{{ f.treatment.tooth_number }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center gap-3 sm:gap-4 flex-shrink-0 flex-wrap sm:flex-nowrap ps-0 sm:ps-0">
                            <!-- Date & countdown -->
                            <div class="text-right">
                                <p class="text-xs font-semibold text-gray-700">{{ formatDate(f.scheduled_date) }}</p>
                                <p class="text-[10px] mt-0.5"
                                    :class="daysUntil(f.scheduled_date) < 0 ? 'text-red-600 font-bold' : daysUntil(f.scheduled_date) <= 3 ? 'text-amber-600' : 'text-gray-400'">
                                    <template v-if="daysUntil(f.scheduled_date) < 0">
                                        {{ isRtl ? `متأخر ${Math.abs(daysUntil(f.scheduled_date))} يوم` : `${Math.abs(daysUntil(f.scheduled_date))}d overdue` }}
                                    </template>
                                    <template v-else-if="daysUntil(f.scheduled_date) === 0">{{ isRtl ? 'اليوم' : 'Today' }}</template>
                                    <template v-else>{{ isRtl ? `بعد ${daysUntil(f.scheduled_date)} يوم` : `in ${daysUntil(f.scheduled_date)}d` }}</template>
                                </p>
                            </div>

                            <!-- Status -->
                            <span v-if="isOverdue(f)"
                                class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-semibold bg-red-50 text-red-700">
                                <span class="w-1.5 h-1.5 rounded-full bg-red-500"></span>
                                {{ isRtl ? 'متأخرة' : 'Overdue' }}
                            </span>
                            <span v-else :class="[getStatus(f.status).bg, getStatus(f.status).text]"
                                class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-semibold">
                                <span :class="getStatus(f.status).dot" class="w-1.5 h-1.5 rounded-full"></span>
                                {{ getStatus(f.status).label }}
                            </span>

                            <!-- Patient link -->
                            <Link v-if="f.patient"
                                :href="`/doctor/patients/${f.patient.id}`"
                                class="inline-flex items-center p-1.5 rounded-lg text-gray-400 hover:text-[#C4A265] hover:bg-amber-50 transition"
                                :title="isRtl ? 'عرض المريض' : 'View Patient'">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                            </Link>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Empty state -->
            <div v-else class="py-16 text-center">
                <div class="flex flex-col items-center">
                    <div class="w-16 h-16 rounded-2xl bg-amber-50 flex items-center justify-center mb-3">
                        <svg class="w-8 h-8 text-amber-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <p class="text-sm font-medium text-gray-400">{{ isRtl ? 'لا توجد متابعات' : 'No follow-ups found' }}</p>
                </div>
            </div>

            <!-- Pagination -->
            <div v-if="followups.last_page > 1" class="px-4 sm:px-6 py-4 border-t border-gray-100 flex justify-center gap-1 flex-wrap">
                <Link v-for="link in followups.links" :key="link.label"
                    :href="link.url || '#'"
                    :class="['px-3 py-1.5 text-xs rounded-lg border', link.active ? 'bg-[#C4A265] text-white border-[#C4A265]' : 'bg-white text-gray-600 border-gray-200 hover:bg-gray-50']"
                    v-html="link.label" preserve-state />
            </div>
        </div>
    </div>
</template>

<style>
@keyframes dentalHeroEnter {
    from { opacity: 0; transform: translateY(16px); }
    to   { opacity: 1; transform: translateY(0); }
}
@keyframes dentalCardEnter {
    from { opacity: 0; transform: translateY(20px); }
    to   { opacity: 1; transform: translateY(0); }
}
.dental-hero-enter { animation: dentalHeroEnter 0.6s cubic-bezier(0.16, 1, 0.3, 1) both; }
.dental-card-enter { animation: dentalCardEnter 0.6s cubic-bezier(0.16, 1, 0.3, 1) both; }
</style>
