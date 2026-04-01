<script setup>
import { computed, ref } from 'vue';
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
    }, {
        preserveState: true,
        replace: true,
    });
}

function formatDate(date) {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('en-GB', {
        weekday: 'short',
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    });
}

function formatTime(time) {
    if (!time) return '-';
    if (time.includes('T') || time.includes(' ')) {
        const d = new Date(time);
        return d.toLocaleTimeString('en-GB', { hour: '2-digit', minute: '2-digit', hour12: false });
    }
    return time.substring(0, 5);
}

const statusConfig = {
    present: { label: 'Present', bg: 'bg-green-50', text: 'text-green-700', dot: 'bg-green-500' },
    absent:  { label: 'Absent',  bg: 'bg-red-50',   text: 'text-red-700',   dot: 'bg-red-500' },
    late:    { label: 'Late',    bg: 'bg-amber-50',  text: 'text-amber-700', dot: 'bg-amber-500' },
    leave:   { label: 'On Leave', bg: 'bg-blue-50',  text: 'text-blue-700',  dot: 'bg-blue-500' },
};

function getStatus(status) {
    return statusConfig[status] || { label: status, bg: 'bg-gray-50', text: 'text-gray-700', dot: 'bg-gray-500' };
}

const summaryCards = [
    { key: 'present', label: 'Present', color: 'from-green-500 to-green-600', lightBg: 'bg-green-50', iconColor: 'text-green-600' },
    { key: 'absent', label: 'Absent', color: 'from-red-500 to-red-600', lightBg: 'bg-red-50', iconColor: 'text-red-600' },
    { key: 'late', label: 'Late', color: 'from-amber-500 to-amber-600', lightBg: 'bg-amber-50', iconColor: 'text-amber-600' },
    { key: 'leave', label: 'On Leave', color: 'from-blue-500 to-blue-600', lightBg: 'bg-blue-50', iconColor: 'text-blue-600' },
    { key: 'overtime_hours', label: 'Overtime Hours', color: 'from-teal-500 to-teal-600', lightBg: 'bg-teal-50', iconColor: 'text-teal-600' },
];
</script>

<template>
    <div class="space-y-6">
        <!-- Header -->
        <div>
            <h1 class="text-2xl font-bold text-gray-900">{{ isRtl ? 'الحضور والانصراف' : 'My Attendance' }}</h1>
            <p class="text-sm text-gray-500 mt-1">View your attendance records and summary</p>
        </div>

        <!-- Month/Year Filter -->
        <div class="flex flex-wrap items-center gap-3">
            <select
                v-model="selectedMonth"
                class="px-4 py-2.5 bg-white border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-teal-500/30 focus:border-teal-500"
            >
                <option v-for="m in months" :key="m.value" :value="m.value">{{ m.label }}</option>
            </select>
            <select
                v-model="selectedYear"
                class="px-4 py-2.5 bg-white border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-teal-500/30 focus:border-teal-500"
            >
                <option v-for="y in years" :key="y" :value="y">{{ y }}</option>
            </select>
            <button
                @click="applyFilter"
                class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm font-semibold text-white bg-[#0d9488] hover:bg-[#0b8278] shadow-sm transition-all duration-200"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                </svg>
                {{ isRtl ? 'تطبيق' : 'Apply' }}
            </button>
        </div>

        <!-- Summary Cards -->
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-4">
            <div
                v-for="card in summaryCards"
                :key="card.key"
                class="group relative bg-white rounded-2xl p-5 shadow-sm hover:shadow-lg border border-gray-100/80 hover:border-gray-200/80 transition-all duration-300 overflow-hidden"
            >
                <div :class="`absolute top-0 left-0 right-0 h-1 bg-gradient-to-r ${card.color} opacity-80`"></div>
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-[13px] font-medium text-gray-500">{{ card.label }}</p>
                        <p class="text-3xl font-bold text-gray-900 mt-2">{{ summary?.[card.key] ?? 0 }}</p>
                    </div>
                    <div :class="card.lightBg" class="w-10 h-10 rounded-xl flex items-center justify-center group-hover:scale-110 transition-transform duration-300">
                        <!-- Present -->
                        <svg v-if="card.key === 'present'" :class="card.iconColor" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <!-- Absent -->
                        <svg v-else-if="card.key === 'absent'" :class="card.iconColor" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <!-- Late -->
                        <svg v-else-if="card.key === 'late'" :class="card.iconColor" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <!-- Leave -->
                        <svg v-else-if="card.key === 'leave'" :class="card.iconColor" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        <!-- Overtime -->
                        <svg v-else :class="card.iconColor" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <!-- Attendance Table -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100/80 overflow-hidden">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-gray-50/80">
                        <th class="ltr:text-left rtl:ltr:text-right rtl:text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">{{ isRtl ? 'التاريخ' : 'Date' }}</th>
                        <th class="ltr:text-left rtl:ltr:text-right rtl:text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Check In</th>
                        <th class="ltr:text-left rtl:ltr:text-right rtl:text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Check Out</th>
                        <th class="ltr:text-left rtl:ltr:text-right rtl:text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">{{ isRtl ? 'الحالة' : 'Status' }}</th>
                        <th class="ltr:text-left rtl:ltr:text-right rtl:text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Overtime</th>
                        <th class="ltr:text-left rtl:ltr:text-right rtl:text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">{{ isRtl ? 'ملاحظات' : 'Notes' }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <tr v-for="record in records" :key="record.id" class="hover:bg-gray-50/50">
                        <td class="px-6 py-3 text-gray-700 font-medium">{{ formatDate(record.date) }}</td>
                        <td class="px-6 py-3 text-gray-600 font-mono">{{ formatTime(record.check_in) }}</td>
                        <td class="px-6 py-3 text-gray-600 font-mono">{{ formatTime(record.check_out) }}</td>
                        <td class="px-6 py-3">
                            <span
                                :class="[getStatus(record.status).bg, getStatus(record.status).text]"
                                class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-semibold"
                            >
                                <span :class="getStatus(record.status).dot" class="w-1.5 h-1.5 rounded-full"></span>
                                {{ getStatus(record.status).label }}
                            </span>
                        </td>
                        <td class="px-6 py-3 text-gray-600">{{ record.overtime || '-' }}</td>
                        <td class="px-6 py-3 text-gray-500 max-w-[200px] truncate">{{ record.notes || '-' }}</td>
                    </tr>
                </tbody>
            </table>

            <div v-if="!records || records.length === 0" class="py-16 text-center">
                <div class="flex flex-col items-center">
                    <div class="w-16 h-16 rounded-2xl bg-teal-50 flex items-center justify-center mb-3">
                        <svg class="w-8 h-8 text-teal-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <p class="text-sm font-medium text-gray-400">No attendance records found</p>
                    <p class="text-xs text-gray-300 mt-1">Try selecting a different month or year</p>
                </div>
            </div>
        </div>
    </div>
</template>
