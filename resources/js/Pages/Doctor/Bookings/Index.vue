<script setup>
import { ref, watch, computed } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import DoctorLayout from '@/Layouts/DoctorLayout.vue';

defineOptions({ layout: DoctorLayout });

const page = usePage();
const locale = computed(() => page.props.locale || 'ar');
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');
const modules = computed(() => page.props.modules || {});
const activeModules = computed(() => {
    return Object.entries(modules.value)
        .filter(([, m]) => m.is_enabled !== false)
        .map(([slug, m]) => ({ slug, name: isRtl.value ? m.name_ar : m.name_en }));
});

const props = defineProps({
    bookings: Object,
    filters: Object,
});

const search = ref(props.filters?.search || '');
const status = ref(props.filters?.status || '');
const moduleFilter = ref(props.filters?.module || '');
let debounce = null;

watch(search, (val) => {
    clearTimeout(debounce);
    debounce = setTimeout(() => applyFilters(), 300);
});
watch(status, () => applyFilters());
watch(moduleFilter, () => applyFilters());

function applyFilters() {
    router.get('/doctor/bookings', {
        search: search.value || undefined,
        status: status.value || undefined,
        module: moduleFilter.value || undefined,
    }, { preserveState: true, replace: true });
}

const statusConfig = {
    confirmed: { label: 'Confirmed', bg: 'bg-blue-50', text: 'text-blue-700', dot: 'bg-blue-500' },
    in_progress: { label: 'In Progress', bg: 'bg-indigo-50', text: 'text-indigo-700', dot: 'bg-indigo-500' },
    completed: { label: 'Completed', bg: 'bg-emerald-50', text: 'text-emerald-700', dot: 'bg-emerald-500' },
    cancelled: { label: 'Cancelled', bg: 'bg-red-50', text: 'text-red-700', dot: 'bg-red-500' },
};

function formatTime(time) {
    if (!time) return '-';
    const [h, m] = time.split(':');
    const hour = parseInt(h);
    const ampm = hour >= 12 ? 'PM' : 'AM';
    const h12 = hour % 12 || 12;
    return `${h12}:${m} ${ampm}`;
}

function sessionsText(completed, total) {
    if (!total) return '-';
    return `${completed}/${total}`;
}
</script>

<template>
    <div>
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-900">{{ $t('a_bookings') }}</h1>
            <p class="text-sm text-gray-500 mt-1">{{ isRtl ? 'الحجوزات المؤكدة المسندة إليك' : 'Confirmed bookings assigned to you' }}</p>
        </div>

        <!-- Filters -->
        <div class="flex flex-wrap gap-3 mb-4">
            <div class="relative">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <input v-model="search" type="text" :placeholder="isRtl ? 'بحث بالاسم، الهاتف، رقم الحجز...' : 'Search name, phone, booking #...'" class="ltr:pl-10 rtl:pr-10 ltr:pr-4 rtl:pl-4 py-2.5 bg-white border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-[#C4A265]/30 focus:border-[#C4A265] w-72" />
            </div>
            <select v-if="activeModules.length > 1" v-model="moduleFilter" class="px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-yellow-200 focus:border-transparent">
                <option value="">{{ isRtl ? 'كل الأقسام' : 'All Departments' }}</option>
                <option v-for="mod in activeModules" :key="mod.slug" :value="mod.slug">{{ mod.name }}</option>
            </select>
            <select v-model="status" class="px-4 py-2.5 bg-white border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-[#C4A265]/30 focus:border-[#C4A265]">
                <option value="">{{ isRtl ? 'جميع الحالات' : 'All Statuses' }}</option>
                <option value="confirmed">{{ isRtl ? 'مؤكد' : 'Confirmed' }}</option>
                <option value="in_progress">{{ isRtl ? 'قيد التنفيذ' : 'In Progress' }}</option>
                <option value="completed">{{ isRtl ? 'مكتمل' : 'Completed' }}</option>
                <option value="cancelled">{{ isRtl ? 'ملغي' : 'Cancelled' }}</option>
            </select>
        </div>

        <!-- Bookings Table -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100/80 overflow-hidden">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-gray-50/80">
                        <th class="ltr:text-left rtl:text-right px-6 py-3 text-xs font-semibold text-gray-500 uppercase">{{ isRtl ? 'رقم الحجز' : 'Booking #' }}</th>
                        <th class="ltr:text-left rtl:text-right px-6 py-3 text-xs font-semibold text-gray-500 uppercase">{{ isRtl ? 'المريض' : 'Patient' }}</th>
                        <th class="ltr:text-left rtl:text-right px-6 py-3 text-xs font-semibold text-gray-500 uppercase">{{ isRtl ? 'الخدمة' : 'Service' }}</th>
                        <th class="ltr:text-left rtl:text-right px-6 py-3 text-xs font-semibold text-gray-500 uppercase">{{ isRtl ? 'الموعد القادم' : 'Next Appointment' }}</th>
                        <th class="text-center px-6 py-3 text-xs font-semibold text-gray-500 uppercase">{{ isRtl ? 'الجلسات' : 'Sessions' }}</th>
                        <th class="text-center px-6 py-3 text-xs font-semibold text-gray-500 uppercase">{{ isRtl ? 'الحالة' : 'Status' }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <tr v-for="booking in bookings.data" :key="booking.id" class="hover:bg-gray-50/50 transition-colors">
                        <td class="px-6 py-3">
                            <span class="text-xs font-mono text-[#C4A265] font-semibold">{{ booking.booking_number || '-' }}</span>
                        </td>
                        <td class="px-6 py-3">
                            <p class="font-semibold text-gray-800">{{ booking.patient_name }}</p>
                            <p class="text-xs text-gray-400">{{ booking.patient_phone }}</p>
                        </td>
                        <td class="px-6 py-3 text-gray-600">{{ booking.doctor_service_name }}</td>
                        <td class="px-6 py-3">
                            <template v-if="booking.next_appointment_date">
                                <p class="text-gray-700">{{ booking.next_appointment_date }}</p>
                                <p class="text-xs text-gray-400">{{ formatTime(booking.next_appointment_time) }}</p>
                            </template>
                            <span v-else class="text-xs text-gray-400">{{ isRtl ? 'لا يوجد قادم' : 'No upcoming' }}</span>
                        </td>
                        <td class="px-6 py-3 text-center">
                            <span class="text-sm font-medium text-gray-700">{{ sessionsText(booking.doctor_completed_sessions, booking.doctor_sessions_count) }}</span>
                        </td>
                        <td class="px-6 py-3 text-center">
                            <span class="inline-flex items-center gap-1.5 text-[11px] font-semibold px-3 py-1 rounded-full" :class="[statusConfig[booking.status]?.bg, statusConfig[booking.status]?.text]">
                                <span class="w-1.5 h-1.5 rounded-full" :class="statusConfig[booking.status]?.dot"></span>
                                {{ statusConfig[booking.status]?.label || booking.status }}
                            </span>
                        </td>
                    </tr>
                </tbody>
            </table>

            <div v-if="bookings.data?.length === 0" class="py-16 text-center">
                <svg class="mx-auto w-12 h-12 text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                <p class="text-sm text-gray-400">{{ isRtl ? 'لا توجد حجوزات' : 'No bookings found' }}</p>
                <p class="text-xs text-gray-300 mt-1">{{ isRtl ? 'الحجوزات المؤكدة فقط تظهر هنا' : 'Only confirmed bookings appear here' }}</p>
            </div>

            <div v-if="bookings.links?.length > 3" class="flex items-center justify-center gap-1 px-6 py-4 border-t border-gray-100">
                <template v-for="link in bookings.links" :key="link.label">
                    <Link v-if="link.url" :href="link.url" class="px-3 py-1.5 rounded-lg text-xs font-medium transition-colors" :class="link.active ? 'bg-[#C4A265] text-white' : 'text-gray-500 hover:bg-gray-100'" v-html="link.label" preserve-state />
                    <span v-else class="px-3 py-1.5 text-xs text-gray-300" v-html="link.label" />
                </template>
            </div>
        </div>
    </div>
</template>
