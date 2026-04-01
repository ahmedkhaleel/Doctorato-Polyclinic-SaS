<script setup>
import { ref, computed } from 'vue';
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

const months = [
    { value: 1, label: 'January' },
    { value: 2, label: 'February' },
    { value: 3, label: 'March' },
    { value: 4, label: 'April' },
    { value: 5, label: 'May' },
    { value: 6, label: 'June' },
    { value: 7, label: 'July' },
    { value: 8, label: 'August' },
    { value: 9, label: 'September' },
    { value: 10, label: 'October' },
    { value: 11, label: 'November' },
    { value: 12, label: 'December' },
];

const currentYear = new Date().getFullYear();
const years = Array.from({ length: 5 }, (_, i) => currentYear - i);

const selectedMonth = ref(props.filters?.month || new Date().getMonth() + 1);
const selectedYear = ref(props.filters?.year || currentYear);

function applyFilter() {
    router.get('/doctor/my-attendance', {
        month: selectedMonth.value,
        year: selectedYear.value,
    }, { preserveState: true, replace: true });
}

function formatDate(dateStr) {
    if (!dateStr) return '-';
    return new Date(dateStr).toLocaleDateString('en-GB', {
        weekday: 'short',
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    });
}

function formatTime(timeStr) {
    if (!timeStr) return '-';
    const parts = timeStr.split(':');
    if (parts.length >= 2) return `${parts[0]}:${parts[1]}`;
    return timeStr;
}

const statusConfig = {
    present: { label: 'Present', bg: 'bg-emerald-50', text: 'text-emerald-700', dot: 'bg-emerald-500' },
    absent: { label: 'Absent', bg: 'bg-red-50', text: 'text-red-700', dot: 'bg-red-500' },
    late: { label: 'Late', bg: 'bg-amber-50', text: 'text-amber-700', dot: 'bg-amber-500' },
    leave: { label: 'On Leave', bg: 'bg-blue-50', text: 'text-blue-700', dot: 'bg-blue-500' },
};
</script>

<template>
    <div class="space-y-6">
        <!-- Header -->
        <div>
            <h1 class="text-2xl font-bold text-gray-900">{{ $t('a_my_attendance') }}</h1>
            <p class="text-sm text-gray-500 mt-1">{{ isRtl ? 'تتبع سجلات الحضور والعمل الإضافي' : 'Track your attendance records and overtime' }}</p>
        </div>

        <!-- Month/Year Filter -->
        <div class="flex flex-wrap items-end gap-3">
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">{{ isRtl ? 'الشهر' : 'Month' }}</label>
                <select v-model="selectedMonth" class="px-4 py-2.5 bg-white border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-[#4f46e5]/20 focus:border-[#4f46e5]">
                    <option v-for="m in months" :key="m.value" :value="m.value">{{ m.label }}</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">{{ isRtl ? 'السنة' : 'Year' }}</label>
                <select v-model="selectedYear" class="px-4 py-2.5 bg-white border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-[#4f46e5]/20 focus:border-[#4f46e5]">
                    <option v-for="y in years" :key="y" :value="y">{{ y }}</option>
                </select>
            </div>
            <button @click="applyFilter" class="px-5 py-2.5 bg-[#4f46e5] text-white text-sm font-semibold rounded-xl hover:bg-[#4338ca] transition-colors">
                {{ isRtl ? 'تطبيق' : 'Apply' }}
            </button>
        </div>

        <!-- Summary Cards -->
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-4">
            <div class="bg-white rounded-2xl border border-emerald-100 p-5 hover:shadow-sm transition-all">
                <div class="flex items-center gap-2 mb-2">
                    <div class="w-8 h-8 rounded-lg bg-emerald-50 flex items-center justify-center">
                        <svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    </div>
                    <span class="text-[10px] uppercase tracking-wider font-semibold text-emerald-600">{{ isRtl ? 'حاضر' : 'Present' }}</span>
                </div>
                <p class="text-2xl font-bold text-emerald-700">{{ summary?.present ?? 0 }}</p>
            </div>

            <div class="bg-white rounded-2xl border border-red-100 p-5 hover:shadow-sm transition-all">
                <div class="flex items-center gap-2 mb-2">
                    <div class="w-8 h-8 rounded-lg bg-red-50 flex items-center justify-center">
                        <svg class="w-4 h-4 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    </div>
                    <span class="text-[10px] uppercase tracking-wider font-semibold text-red-600">{{ isRtl ? 'غائب' : 'Absent' }}</span>
                </div>
                <p class="text-2xl font-bold text-red-700">{{ summary?.absent ?? 0 }}</p>
            </div>

            <div class="bg-white rounded-2xl border border-amber-100 p-5 hover:shadow-sm transition-all">
                <div class="flex items-center gap-2 mb-2">
                    <div class="w-8 h-8 rounded-lg bg-amber-50 flex items-center justify-center">
                        <svg class="w-4 h-4 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    </div>
                    <span class="text-[10px] uppercase tracking-wider font-semibold text-amber-600">{{ isRtl ? 'متأخر' : 'Late' }}</span>
                </div>
                <p class="text-2xl font-bold text-amber-700">{{ summary?.late ?? 0 }}</p>
            </div>

            <div class="bg-white rounded-2xl border border-blue-100 p-5 hover:shadow-sm transition-all">
                <div class="flex items-center gap-2 mb-2">
                    <div class="w-8 h-8 rounded-lg bg-blue-50 flex items-center justify-center">
                        <svg class="w-4 h-4 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                    </div>
                    <span class="text-[10px] uppercase tracking-wider font-semibold text-blue-600">{{ isRtl ? 'إجازة' : 'On Leave' }}</span>
                </div>
                <p class="text-2xl font-bold text-blue-700">{{ summary?.leave ?? 0 }}</p>
            </div>

            <div class="bg-white rounded-2xl border border-indigo-100 p-5 hover:shadow-sm transition-all">
                <div class="flex items-center gap-2 mb-2">
                    <div class="w-8 h-8 rounded-lg bg-indigo-50 flex items-center justify-center">
                        <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
                    </div>
                    <span class="text-[10px] uppercase tracking-wider font-semibold text-indigo-600">{{ isRtl ? 'ساعات إضافية' : 'Overtime Hrs' }}</span>
                </div>
                <p class="text-2xl font-bold text-indigo-700">{{ summary?.overtime_hours ?? 0 }}</p>
            </div>
        </div>

        <!-- Attendance Table -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100/80 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-gray-50/80">
                            <th class="ltr:text-left rtl:text-right px-6 py-3 text-[10px] font-semibold text-gray-400 uppercase">{{ isRtl ? 'التاريخ' : 'Date' }}</th>
                            <th class="ltr:text-left rtl:text-right px-6 py-3 text-[10px] font-semibold text-gray-400 uppercase">{{ isRtl ? 'وقت الحضور' : 'Check In' }}</th>
                            <th class="ltr:text-left rtl:text-right px-6 py-3 text-[10px] font-semibold text-gray-400 uppercase">{{ isRtl ? 'وقت الانصراف' : 'Check Out' }}</th>
                            <th class="text-center px-6 py-3 text-[10px] font-semibold text-gray-400 uppercase">{{ isRtl ? 'الحالة' : 'Status' }}</th>
                            <th class="ltr:text-left rtl:text-right px-6 py-3 text-[10px] font-semibold text-gray-400 uppercase">{{ isRtl ? 'عمل إضافي' : 'Overtime' }}</th>
                            <th class="ltr:text-left rtl:text-right px-6 py-3 text-[10px] font-semibold text-gray-400 uppercase">{{ isRtl ? 'ملاحظات' : 'Notes' }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="record in records" :key="record.id || record.date" class="hover:bg-gray-50/50 transition-colors">
                            <td class="px-6 py-3 font-medium text-gray-800">{{ formatDate(record.date) }}</td>
                            <td class="px-6 py-3 text-gray-600 tabular-nums">{{ formatTime(record.check_in) }}</td>
                            <td class="px-6 py-3 text-gray-600 tabular-nums">{{ formatTime(record.check_out) }}</td>
                            <td class="px-6 py-3 text-center">
                                <span v-if="statusConfig[record.status]"
                                    class="inline-flex items-center gap-1.5 text-[11px] font-semibold px-3 py-1 rounded-full"
                                    :class="[statusConfig[record.status].bg, statusConfig[record.status].text]"
                                >
                                    <span class="w-1.5 h-1.5 rounded-full" :class="statusConfig[record.status].dot"></span>
                                    {{ statusConfig[record.status].label }}
                                </span>
                                <span v-else class="text-xs text-gray-400">{{ record.status || '-' }}</span>
                            </td>
                            <td class="px-6 py-3 text-gray-600 tabular-nums">
                                <span v-if="record.overtime" class="text-indigo-600 font-medium">{{ record.overtime }}</span>
                                <span v-else class="text-gray-300">-</span>
                            </td>
                            <td class="px-6 py-3 text-gray-500 max-w-[200px] truncate">{{ record.notes || '-' }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="!records || records.length === 0" class="py-16 text-center">
                <div class="w-16 h-16 mx-auto bg-gray-50 rounded-2xl flex items-center justify-center mb-3 border border-gray-100">
                    <svg class="w-8 h-8 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                </div>
                <p class="text-sm text-gray-400">{{ isRtl ? 'لا توجد سجلات حضور لهذه الفترة' : 'No attendance records for this period' }}</p>
            </div>
        </div>
    </div>
</template>
