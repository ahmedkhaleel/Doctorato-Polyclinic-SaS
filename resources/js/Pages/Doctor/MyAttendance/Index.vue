<script setup>
import { ref, computed, onMounted } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import DoctorLayout from '@/Layouts/DoctorLayout.vue';

defineOptions({ layout: DoctorLayout });

const page = usePage();
const locale = computed(() => page.props.locale || 'ar');
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const props = defineProps({
    records: Array,
    summary: Object,
    filters: Object,
});

const headerLoaded = ref(false);
const cardsLoaded = ref(false);

onMounted(() => {
    setTimeout(() => headerLoaded.value = true, 50);
    setTimeout(() => cardsLoaded.value = true, 200);
});

const monthsEn = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
const monthsAr = ['يناير', 'فبراير', 'مارس', 'أبريل', 'مايو', 'يونيو', 'يوليو', 'أغسطس', 'سبتمبر', 'أكتوبر', 'نوفمبر', 'ديسمبر'];

const months = computed(() => monthsEn.map((en, i) => ({ value: i + 1, label: isRtl.value ? monthsAr[i] : en })));

const currentYear = new Date().getFullYear();
const years = Array.from({ length: 5 }, (_, i) => currentYear - i);

const selectedMonth = ref(props.filters?.month || new Date().getMonth() + 1);
const selectedYear = ref(props.filters?.year || currentYear);

// Dropdown states
const openDropdown = ref(null);

function toggleDropdown(key) {
    openDropdown.value = openDropdown.value === key ? null : key;
}

function selectMonth(val) {
    selectedMonth.value = val;
    openDropdown.value = null;
    applyFilter();
}

function selectYear(val) {
    selectedYear.value = val;
    openDropdown.value = null;
    applyFilter();
}

function handleClickOutside(e) {
    if (!e.target.closest('.att-dd')) openDropdown.value = null;
}

onMounted(() => {
    document.addEventListener('click', handleClickOutside);
});

function applyFilter() {
    router.get('/doctor/my-attendance', {
        month: selectedMonth.value,
        year: selectedYear.value,
    }, { preserveState: true, replace: true });
}

function formatDate(dateStr) {
    if (!dateStr) return '-';
    return new Date(dateStr).toLocaleDateString(isRtl.value ? 'ar-EG' : 'en-GB', {
        weekday: 'short', day: 'numeric', month: 'short',
    });
}

function formatTime(timeStr) {
    if (!timeStr) return '-';
    const parts = timeStr.split(':');
    if (parts.length >= 2) return `${parts[0]}:${parts[1]}`;
    return timeStr;
}

const statusConfig = {
    present: { label: isRtl.value ? 'حاضر' : 'Present', bg: 'bg-emerald-50', text: 'text-emerald-700', dot: 'bg-emerald-500', icon: '✓' },
    absent: { label: isRtl.value ? 'غائب' : 'Absent', bg: 'bg-red-50', text: 'text-red-700', dot: 'bg-red-500', icon: '✕' },
    late: { label: isRtl.value ? 'متأخر' : 'Late', bg: 'bg-amber-50', text: 'text-amber-700', dot: 'bg-amber-500', icon: '⏰' },
    leave: { label: isRtl.value ? 'إجازة' : 'On Leave', bg: 'bg-blue-50', text: 'text-blue-700', dot: 'bg-blue-500', icon: '📋' },
};

const selectedMonthLabel = computed(() => months.value.find(m => m.value === selectedMonth.value)?.label || '');

const attendanceRate = computed(() => {
    const total = (props.summary?.present || 0) + (props.summary?.absent || 0) + (props.summary?.late || 0);
    if (!total) return 0;
    return Math.round(((props.summary?.present || 0) + (props.summary?.late || 0)) / total * 100);
});
</script>

<template>
    <div class="space-y-6">
        <!-- Hero Header -->
        <div
            class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-gray-900 via-gray-800 to-gray-900 p-6 sm:p-8 transition-all duration-700"
            :class="headerLoaded ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
        >
            <div class="absolute top-0 right-0 w-96 h-96 bg-gradient-radial from-emerald-500/10 to-transparent rounded-full -translate-y-1/2 translate-x-1/2"></div>
            <div class="absolute bottom-0 left-0 w-64 h-64 bg-gradient-radial from-[#C4A265]/5 to-transparent rounded-full translate-y-1/2 -translate-x-1/4"></div>

            <div class="relative z-10">
                <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                    <div class="flex items-center gap-4">
                        <div class="w-14 h-14 rounded-xl bg-gradient-to-br from-emerald-400 to-emerald-600 flex items-center justify-center shadow-lg shadow-emerald-500/20">
                            <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9h.01M9 16h.01M12 12h3m-3 4h3" /></svg>
                        </div>
                        <div>
                            <h1 class="text-2xl font-bold text-white">{{ $t('a_my_attendance') }}</h1>
                            <p class="text-sm text-gray-400 mt-0.5">{{ isRtl ? 'تتبع سجلات الحضور والعمل الإضافي' : 'Track your attendance records' }}</p>
                        </div>
                    </div>

                    <!-- Styled Filter -->
                    <div class="flex items-center gap-2">
                        <!-- Month Dropdown -->
                        <div class="relative att-dd">
                            <button @click.stop="toggleDropdown('month')" class="flex items-center gap-2 px-4 py-2.5 bg-white/10 backdrop-blur-sm border border-white/20 rounded-xl text-sm text-white hover:bg-white/15 transition">
                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                                <span>{{ selectedMonthLabel }}</span>
                                <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                            </button>
                            <div v-if="openDropdown === 'month'" class="absolute z-40 mt-1 bg-white rounded-xl shadow-xl border border-gray-200 overflow-hidden min-w-[160px]" :class="isRtl ? 'right-0' : 'left-0'">
                                <div class="max-h-48 overflow-y-auto">
                                    <button v-for="m in months" :key="m.value" @click="selectMonth(m.value)" class="w-full px-4 py-2 text-sm text-start hover:bg-gray-50 transition" :class="selectedMonth === m.value ? 'bg-emerald-50 text-emerald-700 font-semibold' : 'text-gray-700'">
                                        {{ m.label }}
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Year Dropdown -->
                        <div class="relative att-dd">
                            <button @click.stop="toggleDropdown('year')" class="flex items-center gap-2 px-4 py-2.5 bg-white/10 backdrop-blur-sm border border-white/20 rounded-xl text-sm text-white hover:bg-white/15 transition">
                                <span>{{ selectedYear }}</span>
                                <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                            </button>
                            <div v-if="openDropdown === 'year'" class="absolute z-40 mt-1 bg-white rounded-xl shadow-xl border border-gray-200 overflow-hidden min-w-[100px]" :class="isRtl ? 'right-0' : 'left-0'">
                                <button v-for="y in years" :key="y" @click="selectYear(y)" class="w-full px-4 py-2 text-sm text-start hover:bg-gray-50 transition" :class="selectedYear === y ? 'bg-emerald-50 text-emerald-700 font-semibold' : 'text-gray-700'">
                                    {{ y }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Summary Stats -->
                <div class="grid grid-cols-3 sm:grid-cols-5 gap-3 mt-6">
                    <div class="bg-white/5 backdrop-blur-sm rounded-xl px-4 py-3 border border-white/10">
                        <div class="flex items-center gap-2 mb-1">
                            <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
                            <p class="text-[10px] text-gray-400 uppercase font-semibold">{{ isRtl ? 'حاضر' : 'Present' }}</p>
                        </div>
                        <p class="text-xl font-bold text-emerald-400">{{ summary?.present ?? 0 }}</p>
                    </div>
                    <div class="bg-white/5 backdrop-blur-sm rounded-xl px-4 py-3 border border-white/10">
                        <div class="flex items-center gap-2 mb-1">
                            <span class="w-2 h-2 rounded-full bg-red-400"></span>
                            <p class="text-[10px] text-gray-400 uppercase font-semibold">{{ isRtl ? 'غائب' : 'Absent' }}</p>
                        </div>
                        <p class="text-xl font-bold text-red-400">{{ summary?.absent ?? 0 }}</p>
                    </div>
                    <div class="bg-white/5 backdrop-blur-sm rounded-xl px-4 py-3 border border-white/10">
                        <div class="flex items-center gap-2 mb-1">
                            <span class="w-2 h-2 rounded-full bg-amber-400"></span>
                            <p class="text-[10px] text-gray-400 uppercase font-semibold">{{ isRtl ? 'متأخر' : 'Late' }}</p>
                        </div>
                        <p class="text-xl font-bold text-amber-400">{{ summary?.late ?? 0 }}</p>
                    </div>
                    <div class="bg-white/5 backdrop-blur-sm rounded-xl px-4 py-3 border border-white/10">
                        <div class="flex items-center gap-2 mb-1">
                            <span class="w-2 h-2 rounded-full bg-blue-400"></span>
                            <p class="text-[10px] text-gray-400 uppercase font-semibold">{{ isRtl ? 'إجازة' : 'Leave' }}</p>
                        </div>
                        <p class="text-xl font-bold text-blue-400">{{ summary?.leave ?? 0 }}</p>
                    </div>
                    <div class="bg-white/5 backdrop-blur-sm rounded-xl px-4 py-3 border border-white/10 hidden sm:block">
                        <div class="flex items-center gap-2 mb-1">
                            <span class="w-2 h-2 rounded-full bg-purple-400"></span>
                            <p class="text-[10px] text-gray-400 uppercase font-semibold">{{ isRtl ? 'إضافي' : 'Overtime' }}</p>
                        </div>
                        <p class="text-xl font-bold text-purple-400">{{ summary?.overtime_hours ?? 0 }}<span class="text-xs text-gray-500 font-normal">h</span></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Attendance Cards -->
        <div
            class="transition-all duration-700"
            :class="cardsLoaded ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-6'"
        >
            <div v-if="records && records.length" class="space-y-2">
                <div
                    v-for="record in records"
                    :key="record.id || record.date"
                    class="bg-white rounded-xl border border-gray-100 shadow-sm hover:shadow-md transition-all duration-200 p-4"
                >
                    <div class="flex items-center gap-4">
                        <!-- Date Circle -->
                        <div class="w-12 h-12 rounded-xl flex flex-col items-center justify-center flex-shrink-0"
                            :class="statusConfig[record.status]?.bg || 'bg-gray-50'">
                            <span class="text-sm font-bold leading-none" :class="statusConfig[record.status]?.text || 'text-gray-600'">
                                {{ record.date ? new Date(record.date).getDate() : '-' }}
                            </span>
                            <span class="text-[9px] font-medium mt-0.5" :class="(statusConfig[record.status]?.text || 'text-gray-400') + ' opacity-70'">
                                {{ record.date ? new Date(record.date).toLocaleDateString(isRtl ? 'ar-EG' : 'en', { weekday: 'short' }) : '' }}
                            </span>
                        </div>

                        <!-- Check In/Out -->
                        <div class="flex-1 grid grid-cols-2 sm:grid-cols-3 gap-3">
                            <div>
                                <p class="text-[10px] text-gray-400 font-semibold uppercase">{{ isRtl ? 'حضور' : 'Check In' }}</p>
                                <p class="text-sm font-bold text-gray-700 tabular-nums mt-0.5">{{ formatTime(record.check_in) }}</p>
                            </div>
                            <div>
                                <p class="text-[10px] text-gray-400 font-semibold uppercase">{{ isRtl ? 'انصراف' : 'Check Out' }}</p>
                                <p class="text-sm font-bold text-gray-700 tabular-nums mt-0.5">{{ formatTime(record.check_out) }}</p>
                            </div>
                            <div v-if="record.overtime" class="hidden sm:block">
                                <p class="text-[10px] text-gray-400 font-semibold uppercase">{{ isRtl ? 'إضافي' : 'Overtime' }}</p>
                                <p class="text-sm font-bold text-purple-600 mt-0.5">{{ record.overtime }}</p>
                            </div>
                        </div>

                        <!-- Status Badge -->
                        <span v-if="statusConfig[record.status]"
                            class="inline-flex items-center gap-1.5 text-[11px] font-semibold px-3 py-1.5 rounded-full"
                            :class="[statusConfig[record.status].bg, statusConfig[record.status].text]"
                        >
                            <span class="w-1.5 h-1.5 rounded-full" :class="statusConfig[record.status].dot"></span>
                            {{ statusConfig[record.status].label }}
                        </span>
                        <span v-else class="text-xs text-gray-400 px-3 py-1.5">{{ record.status || '-' }}</span>
                    </div>

                    <!-- Notes -->
                    <p v-if="record.notes" class="text-xs text-gray-500 mt-2 ltr:ml-16 rtl:mr-16 bg-gray-50 px-3 py-1.5 rounded-lg">{{ record.notes }}</p>
                </div>
            </div>

            <!-- Empty State -->
            <div v-else class="text-center py-16 bg-white rounded-xl border border-gray-100">
                <div class="w-16 h-16 mx-auto bg-gray-100 rounded-full flex items-center justify-center mb-4">
                    <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                </div>
                <p class="text-sm text-gray-500">{{ isRtl ? 'لا توجد سجلات حضور لهذه الفترة' : 'No attendance records for this period' }}</p>
            </div>
        </div>
    </div>
</template>
