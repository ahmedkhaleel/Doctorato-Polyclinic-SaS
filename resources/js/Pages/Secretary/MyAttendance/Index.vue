<script setup>
import { computed, ref, onMounted } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import SecretaryLayout from '@/Layouts/SecretaryLayout.vue';

defineOptions({ layout: SecretaryLayout });

const page = usePage();
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const props = defineProps({
    records: Array,
    summary: Object,
    filters: Object,
});

const months = [
    { value: 1, label: isRtl.value ? 'يناير' : 'January' },
    { value: 2, label: isRtl.value ? 'فبراير' : 'February' },
    { value: 3, label: isRtl.value ? 'مارس' : 'March' },
    { value: 4, label: isRtl.value ? 'أبريل' : 'April' },
    { value: 5, label: isRtl.value ? 'مايو' : 'May' },
    { value: 6, label: isRtl.value ? 'يونيو' : 'June' },
    { value: 7, label: isRtl.value ? 'يوليو' : 'July' },
    { value: 8, label: isRtl.value ? 'أغسطس' : 'August' },
    { value: 9, label: isRtl.value ? 'سبتمبر' : 'September' },
    { value: 10, label: isRtl.value ? 'أكتوبر' : 'October' },
    { value: 11, label: isRtl.value ? 'نوفمبر' : 'November' },
    { value: 12, label: isRtl.value ? 'ديسمبر' : 'December' },
];

const currentYear = new Date().getFullYear();
const years = Array.from({ length: 5 }, (_, i) => currentYear - i);

const selectedMonth = ref(props.filters?.month || new Date().getMonth() + 1);
const selectedYear = ref(props.filters?.year || currentYear);

function applyFilter() {
    router.get('/secretary/my-attendance', {
        month: selectedMonth.value,
        year: selectedYear.value,
    }, { preserveState: true, replace: true });
}

function formatDate(date) {
    if (!date) return '-';
    const d = new Date(date);
    const today = new Date();
    const yesterday = new Date(); yesterday.setDate(yesterday.getDate() - 1);
    if (d.toDateString() === today.toDateString()) return isRtl.value ? 'اليوم' : 'Today';
    if (d.toDateString() === yesterday.toDateString()) return isRtl.value ? 'أمس' : 'Yesterday';
    return d.toLocaleDateString(isRtl.value ? 'ar-EG' : 'en-GB', { weekday: 'short', day: '2-digit', month: 'short' });
}

function formatTime(time) {
    if (!time) return '-';
    if (time.includes('T') || time.includes(' ')) {
        return new Date(time).toLocaleTimeString('en-GB', { hour: '2-digit', minute: '2-digit', hour12: false });
    }
    return time.substring(0, 5);
}

const statusConfig = {
    present: { label: 'Present', labelAr: 'حاضر',   bg: 'bg-green-50',  text: 'text-green-700', dot: 'bg-green-500', border: 'border-green-200' },
    absent:  { label: 'Absent',  labelAr: 'غائب',    bg: 'bg-red-50',    text: 'text-red-700',   dot: 'bg-red-500',   border: 'border-red-200' },
    late:    { label: 'Late',    labelAr: 'متأخر',   bg: 'bg-amber-50',  text: 'text-amber-700', dot: 'bg-amber-500', border: 'border-amber-200' },
    leave:   { label: 'On Leave', labelAr: 'إجازة',  bg: 'bg-blue-50',   text: 'text-blue-700',  dot: 'bg-blue-500',  border: 'border-blue-200' },
};

function getStatus(status) {
    return statusConfig[status] || { label: status, labelAr: status, bg: 'bg-gray-50', text: 'text-gray-700', dot: 'bg-gray-500', border: 'border-gray-200' };
}

const summaryCards = [
    { key: 'present', labelAr: 'حاضر', label: 'Present', color: 'from-green-500 to-green-600', lightBg: 'bg-green-50', iconColor: 'text-green-600', icon: 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z' },
    { key: 'absent', labelAr: 'غائب', label: 'Absent', color: 'from-red-500 to-red-600', lightBg: 'bg-red-50', iconColor: 'text-red-600', icon: 'M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z' },
    { key: 'late', labelAr: 'متأخر', label: 'Late', color: 'from-amber-500 to-amber-600', lightBg: 'bg-amber-50', iconColor: 'text-amber-600', icon: 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z' },
    { key: 'leave', labelAr: 'إجازة', label: 'On Leave', color: 'from-blue-500 to-blue-600', lightBg: 'bg-blue-50', iconColor: 'text-blue-600', icon: 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z' },
    { key: 'overtime_hours', labelAr: 'ساعات إضافية', label: 'Overtime Hours', color: 'from-teal-500 to-teal-600', lightBg: 'bg-teal-50', iconColor: 'text-teal-600', icon: 'M13 10V3L4 14h7v7l9-11h-7z' },
];

const headerLoaded = ref(false);
const cardsLoaded = ref(false);
onMounted(() => {
    setTimeout(() => { headerLoaded.value = true; }, 50);
    setTimeout(() => { cardsLoaded.value = true; }, 200);
});
</script>

<template>
    <div>
        <!-- ═══ HERO HEADER ═══ -->
        <div class="relative -mx-4 sm:-mx-6 lg:-mx-8 -mt-4 sm:-mt-6 mb-8 px-4 sm:px-6 lg:px-8 pt-8 pb-10 bg-gradient-to-br from-gray-900 via-gray-800 to-gray-900 overflow-hidden transition-all duration-700" :class="headerLoaded ? 'opacity-100 translate-y-0' : 'opacity-0 -translate-y-4'">
            <div class="absolute inset-0 opacity-10" style="background: radial-gradient(circle at 70% 50%, #0d9488 0%, transparent 60%)"></div>
            <div class="relative z-10">
                <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4">
                    <div>
                        <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-white/10 backdrop-blur-sm mb-3">
                            <span class="w-2 h-2 rounded-full bg-[#0d9488] animate-pulse"></span>
                            <span class="text-xs font-semibold text-gray-300">{{ isRtl ? 'الموارد البشرية' : 'HR Module' }}</span>
                        </div>
                        <h1 class="text-2xl sm:text-3xl font-bold text-white">{{ isRtl ? 'الحضور والانصراف' : 'My Attendance' }}</h1>
                        <p class="text-sm text-gray-400 mt-1.5">{{ isRtl ? 'عرض سجلات الحضور والانصراف والملخص' : 'View your attendance records and summary' }}</p>
                    </div>
                    <!-- Filter -->
                    <div class="flex items-center gap-2">
                        <select v-model="selectedMonth" class="px-3 py-2 bg-white/10 border border-white/20 rounded-xl text-sm text-white focus:ring-2 focus:ring-[#0d9488]/50 focus:border-[#0d9488] [&>option]:text-gray-900">
                            <option v-for="m in months" :key="m.value" :value="m.value">{{ m.label }}</option>
                        </select>
                        <select v-model="selectedYear" class="px-3 py-2 bg-white/10 border border-white/20 rounded-xl text-sm text-white focus:ring-2 focus:ring-[#0d9488]/50 focus:border-[#0d9488] [&>option]:text-gray-900">
                            <option v-for="y in years" :key="y" :value="y">{{ y }}</option>
                        </select>
                        <button @click="applyFilter" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-sm font-semibold text-white bg-[#0d9488] hover:bg-[#0b8278] transition-all">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" /></svg>
                            {{ isRtl ? 'تطبيق' : 'Apply' }}
                        </button>
                    </div>
                </div>

                <!-- Summary Stats -->
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3 mt-6">
                    <div v-for="card in summaryCards" :key="card.key" class="bg-white/5 backdrop-blur-sm rounded-xl p-3.5 border border-white/10">
                        <div class="flex items-center justify-between">
                            <p class="text-xs text-gray-400 font-medium">{{ isRtl ? card.labelAr : card.label }}</p>
                            <div :class="card.lightBg" class="w-7 h-7 rounded-lg flex items-center justify-center">
                                <svg :class="card.iconColor" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="card.icon" /></svg>
                            </div>
                        </div>
                        <p class="text-2xl font-bold text-white mt-1">{{ summary?.[card.key] ?? 0 }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- ═══ ATTENDANCE CARDS ═══ -->
        <div class="space-y-3 transition-all duration-500" :class="cardsLoaded ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'">
            <div
                v-for="record in records"
                :key="record.id"
                class="bg-white rounded-2xl shadow-sm border border-gray-100/80 hover:shadow-md transition-all duration-300 overflow-hidden"
            >
                <div class="flex flex-col sm:flex-row sm:items-center gap-3 p-4">
                    <!-- Date -->
                    <div class="flex items-center gap-3 flex-1 min-w-0">
                        <div :class="[getStatus(record.status).bg, getStatus(record.status).border]" class="w-12 h-12 rounded-xl border flex items-center justify-center flex-shrink-0">
                            <span :class="getStatus(record.status).dot" class="w-3 h-3 rounded-full"></span>
                        </div>
                        <div class="min-w-0">
                            <p class="font-semibold text-gray-900 text-sm">{{ formatDate(record.date) }}</p>
                            <span :class="[getStatus(record.status).bg, getStatus(record.status).text]" class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold mt-0.5">
                                {{ isRtl ? getStatus(record.status).labelAr : getStatus(record.status).label }}
                            </span>
                        </div>
                    </div>

                    <!-- Times -->
                    <div class="flex items-center gap-4 sm:gap-6">
                        <div class="text-center">
                            <p class="text-[10px] text-gray-400 font-semibold uppercase">{{ isRtl ? 'دخول' : 'In' }}</p>
                            <p class="text-sm font-mono font-bold text-gray-700 mt-0.5">{{ formatTime(record.check_in) }}</p>
                        </div>
                        <svg class="w-4 h-4 text-gray-300 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" /></svg>
                        <div class="text-center">
                            <p class="text-[10px] text-gray-400 font-semibold uppercase">{{ isRtl ? 'خروج' : 'Out' }}</p>
                            <p class="text-sm font-mono font-bold text-gray-700 mt-0.5">{{ formatTime(record.check_out) }}</p>
                        </div>
                        <div v-if="record.overtime" class="text-center">
                            <p class="text-[10px] text-teal-500 font-semibold uppercase">{{ isRtl ? 'إضافي' : 'OT' }}</p>
                            <p class="text-sm font-bold text-teal-600 mt-0.5">{{ record.overtime }}</p>
                        </div>
                    </div>

                    <!-- Notes -->
                    <p v-if="record.notes" class="text-xs text-gray-400 max-w-[200px] truncate flex-shrink-0">{{ record.notes }}</p>
                </div>
            </div>
        </div>

        <!-- Empty State -->
        <div v-if="!records || records.length === 0" class="py-16 text-center">
            <div class="w-16 h-16 mx-auto mb-4 rounded-2xl bg-teal-50 flex items-center justify-center">
                <svg class="w-8 h-8 text-teal-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
            </div>
            <p class="text-sm font-semibold text-gray-500">{{ isRtl ? 'لا توجد سجلات حضور' : 'No attendance records found' }}</p>
            <p class="text-xs text-gray-400 mt-1">{{ isRtl ? 'جرب اختيار شهر أو سنة مختلفة' : 'Try selecting a different month or year' }}</p>
        </div>
    </div>
</template>
