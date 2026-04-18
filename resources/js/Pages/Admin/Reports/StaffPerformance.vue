<script setup>
import { ref, computed, watch } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const page = usePage();
const locale = computed(() => page.props.locale || 'ar');
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const props = defineProps({
    staff: Array,
    totals: Object,
    byDepartment: Array,
    dailyTrend: Array,
    filters: Object,
});

const dateFrom = ref(props.filters?.date_from || '');
const dateTo = ref(props.filters?.date_to || '');
const sortBy = ref('attendance');

let filterTimeout = null;
watch([dateFrom, dateTo], () => {
    clearTimeout(filterTimeout);
    filterTimeout = setTimeout(() => {
        router.get('/admin/reports/staff-performance', {
            date_from: dateFrom.value || undefined,
            date_to: dateTo.value || undefined,
        }, { preserveState: true, replace: true });
    }, 400);
});

const sortedStaff = computed(() => {
    const s = [...props.staff];
    switch (sortBy.value) {
        case 'attendance': return s.sort((a, b) => b.attendance_rate - a.attendance_rate);
        case 'activity': return s.sort((a, b) => b.activity_count - a.activity_count);
        case 'overtime': return s.sort((a, b) => b.overtime_hours - a.overtime_hours);
        case 'absent': return s.sort((a, b) => b.absent_days - a.absent_days);
        default: return s;
    }
});

function attendanceColor(rate) {
    if (rate >= 95) return 'text-emerald-600';
    if (rate >= 80) return 'text-[#1B365D]';
    if (rate >= 60) return 'text-amber-600';
    return 'text-red-600';
}

function attendanceBg(rate) {
    if (rate >= 95) return 'bg-emerald-500';
    if (rate >= 80) return 'bg-[#1B365D]';
    if (rate >= 60) return 'bg-amber-500';
    return 'bg-red-500';
}

const maxDailyTotal = computed(() => Math.max(...(props.dailyTrend?.map(d => d.total) || [1]), 1));
</script>

<template>
    <AdminLayout :title="isRtl ? 'أداء الموظفين' : 'Staff Performance'">
        <div class="space-y-6">
            <!-- ═════════ Navy Hero Header ═════════ -->
            <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-[#1B365D] via-[#1B365D] to-[#0F2444] shadow-xl">
                <div class="pointer-events-none absolute -top-16 -end-16 h-56 w-56 rounded-full bg-[#C4A265]/20 blur-3xl"></div>
                <div class="pointer-events-none absolute -bottom-20 start-1/3 h-48 w-48 rounded-full bg-[#C4A265]/10 blur-3xl"></div>
                <div class="absolute inset-x-0 top-0 h-[2px] bg-gradient-to-r from-transparent via-[#C4A265] to-transparent"></div>
                <div class="relative p-4 md:p-7 flex flex-col md:flex-row md:items-center gap-4 md:gap-5 justify-between">
                    <div class="flex items-start gap-3 md:gap-4 min-w-0">
                        <div class="w-12 h-12 md:w-14 md:h-14 rounded-xl bg-gradient-to-br from-[#C4A265] to-[#8B7043] flex items-center justify-center shadow-lg flex-shrink-0">
                            <svg class="w-6 h-6 md:w-7 md:h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        </div>
                        <div class="min-w-0">
                            <div class="flex items-center gap-2 mb-1">
                                <span class="h-[3px] w-6 bg-[#C4A265] rounded-full"></span>
                                <span class="text-[10px] font-bold text-[#C4A265] tracking-[0.25em] uppercase">{{ isRtl ? 'إنتاجية' : 'Productivity' }}</span>
                            </div>
                            <h1 class="text-xl md:text-3xl font-extrabold text-white tracking-tight truncate">{{ isRtl ? 'أداء وإنتاجية الموظفين' : 'Staff Performance & Productivity' }}</h1>
                            <p class="text-xs md:text-sm text-white/70 mt-1 max-w-xl">{{ isRtl ? 'الحضور والانصراف والإنتاجية والعمل الإضافي' : 'Attendance, productivity, and overtime analytics' }}</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2 flex-wrap">
                        <input v-model="dateFrom" type="date" class="doctorato-input rounded-lg bg-white/10 backdrop-blur-sm border border-white/20 text-white text-sm focus:ring-[#C4A265] focus:border-[#C4A265] [color-scheme:dark]" />
                        <span class="text-[#C4A265]">→</span>
                        <input v-model="dateTo" type="date" class="doctorato-input rounded-lg bg-white/10 backdrop-blur-sm border border-white/20 text-white text-sm focus:ring-[#C4A265] focus:border-[#C4A265] [color-scheme:dark]" />
                    </div>
                </div>
            </div>

            <!-- KPI Cards -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="bg-white rounded-xl border border-gray-200 p-4">
                    <div class="text-xl md:text-2xl font-bold text-gray-900">{{ totals.employees }}</div>
                    <div class="text-xs text-gray-500 mt-1">{{ isRtl ? 'إجمالي الموظفين' : 'Active Employees' }}</div>
                </div>
                <div class="bg-white rounded-xl border p-4"
                    :class="totals.avg_attendance >= 90 ? 'border-emerald-200' : 'border-amber-200'">
                    <div class="text-xl md:text-2xl font-bold" :class="attendanceColor(totals.avg_attendance)">{{ totals.avg_attendance }}%</div>
                    <div class="text-xs text-gray-500 mt-1">{{ isRtl ? 'متوسط الحضور' : 'Avg Attendance' }}</div>
                </div>
                <div class="bg-white rounded-xl border border-red-200 p-4">
                    <div class="text-xl md:text-2xl font-bold text-red-600">{{ totals.total_absent }}</div>
                    <div class="text-xs text-gray-500 mt-1">{{ isRtl ? 'إجمالي الغياب' : 'Total Absent Days' }}</div>
                </div>
                <div class="bg-white rounded-xl border border-slate-200 p-4">
                    <div class="text-xl md:text-2xl font-bold text-[#1B365D]">{{ totals.total_overtime }}h</div>
                    <div class="text-xs text-gray-500 mt-1">{{ isRtl ? 'إجمالي العمل الإضافي' : 'Total Overtime' }}</div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Staff Table (2/3) -->
                <div class="lg:col-span-2">
                    <!-- Sort -->
                    <div class="flex items-center gap-2 mb-3">
                        <span class="text-sm text-gray-500">{{ isRtl ? 'ترتيب:' : 'Sort:' }}</span>
                        <button v-for="opt in [
                            { key: 'attendance', en: 'Attendance', ar: 'الحضور' },
                            { key: 'activity', en: 'Activity', ar: 'النشاط' },
                            { key: 'overtime', en: 'Overtime', ar: 'إضافي' },
                            { key: 'absent', en: 'Absences', ar: 'الغياب' },
                        ]" :key="opt.key" @click="sortBy = opt.key"
                            class="px-3 py-1 text-xs font-medium rounded-full border transition"
                            :class="sortBy === opt.key ? 'bg-[#1B365D] text-white border-[#1B365D]' : 'bg-white text-gray-600 border-gray-300'">
                            {{ isRtl ? opt.ar : opt.en }}
                        </button>
                    </div>

                    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                        <table class="w-full">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="text-start px-4 py-3 text-xs font-semibold text-gray-500 uppercase">{{ isRtl ? 'الموظف' : 'Employee' }}</th>
                                    <th class="text-center px-3 py-3 text-xs font-semibold text-gray-500 uppercase">{{ isRtl ? 'حضور' : 'Present' }}</th>
                                    <th class="text-center px-3 py-3 text-xs font-semibold text-gray-500 uppercase">{{ isRtl ? 'غياب' : 'Absent' }}</th>
                                    <th class="text-center px-3 py-3 text-xs font-semibold text-gray-500 uppercase">{{ isRtl ? 'تأخير' : 'Late' }}</th>
                                    <th class="text-center px-3 py-3 text-xs font-semibold text-gray-500 uppercase">{{ isRtl ? 'إضافي' : 'OT' }}</th>
                                    <th class="text-center px-3 py-3 text-xs font-semibold text-gray-500 uppercase">{{ isRtl ? 'نشاط' : 'Activity' }}</th>
                                    <th class="px-3 py-3 text-xs font-semibold text-gray-500 uppercase w-32">{{ isRtl ? 'المعدل' : 'Rate' }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                <tr v-for="emp in sortedStaff" :key="emp.id" class="hover:bg-gray-50/50">
                                    <td class="px-4 py-3">
                                        <div class="text-sm font-semibold text-gray-900">{{ emp.name }}</div>
                                        <div class="text-xs text-gray-500">{{ isRtl ? emp.job_title_ar : emp.job_title_en }}</div>
                                    </td>
                                    <td class="px-3 py-3 text-center text-sm font-medium text-emerald-600">{{ emp.present_days }}</td>
                                    <td class="px-3 py-3 text-center text-sm font-medium" :class="emp.absent_days > 0 ? 'text-red-600' : 'text-gray-400'">{{ emp.absent_days }}</td>
                                    <td class="px-3 py-3 text-center text-sm font-medium" :class="emp.late_days > 0 ? 'text-amber-600' : 'text-gray-400'">{{ emp.late_days }}</td>
                                    <td class="px-3 py-3 text-center text-sm font-medium text-[#1B365D]">{{ emp.overtime_hours }}h</td>
                                    <td class="px-3 py-3 text-center text-sm text-gray-600">{{ emp.activity_count }}</td>
                                    <td class="px-3 py-3">
                                        <div class="flex items-center gap-2">
                                            <div class="flex-1 bg-gray-100 rounded-full h-2">
                                                <div class="h-2 rounded-full transition-all" :class="attendanceBg(emp.attendance_rate)"
                                                    :style="{ width: `${emp.attendance_rate}%` }"></div>
                                            </div>
                                            <span class="text-xs font-bold w-10 text-end" :class="attendanceColor(emp.attendance_rate)">{{ emp.attendance_rate }}%</span>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>

                        <div v-if="!staff?.length" class="p-16 text-center">
                            <p class="text-gray-400">{{ isRtl ? 'لا يوجد موظفين نشطين' : 'No active employees' }}</p>
                        </div>
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="space-y-4">
                    <!-- Department Breakdown -->
                    <div class="bg-white rounded-xl border border-gray-200 p-4">
                        <h3 class="text-sm font-semibold text-gray-900 mb-3">{{ isRtl ? 'حسب القسم' : 'By Department' }}</h3>
                        <div class="space-y-3">
                            <div v-for="dept in byDepartment" :key="dept.department_en" class="p-2 rounded-lg bg-gray-50">
                                <div class="flex justify-between items-center mb-1">
                                    <span class="text-xs font-medium text-gray-700">{{ isRtl ? dept.department_ar : dept.department_en }}</span>
                                    <span class="text-[10px] text-gray-400">{{ dept.count }} {{ isRtl ? 'موظف' : 'staff' }}</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <div class="flex-1 bg-gray-200 rounded-full h-1.5">
                                        <div class="h-1.5 rounded-full" :class="attendanceBg(dept.avg_attendance)"
                                            :style="{ width: `${dept.avg_attendance}%` }"></div>
                                    </div>
                                    <span class="text-[10px] font-bold" :class="attendanceColor(dept.avg_attendance)">{{ dept.avg_attendance }}%</span>
                                </div>
                                <div class="flex gap-3 mt-1 text-[10px] text-gray-400">
                                    <span>{{ isRtl ? 'غياب:' : 'Absent:' }} {{ dept.total_absent }}</span>
                                    <span>{{ isRtl ? 'إضافي:' : 'OT:' }} {{ dept.total_overtime }}h</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Daily Attendance Trend -->
                    <div class="bg-white rounded-xl border border-gray-200 p-4" v-if="dailyTrend?.length">
                        <h3 class="text-sm font-semibold text-gray-900 mb-3">{{ isRtl ? 'اتجاه الحضور اليومي' : 'Daily Attendance Trend' }}</h3>
                        <div class="flex items-end gap-0.5 h-24">
                            <div v-for="d in dailyTrend" :key="d.day" class="flex-1 flex flex-col items-center group relative">
                                <div class="w-full max-w-[12px] flex flex-col justify-end h-20">
                                    <!-- Present (green) -->
                                    <div class="bg-emerald-400 rounded-t transition-all"
                                        :style="{ height: `${(d.present / maxDailyTotal) * 100}%`, minHeight: d.present > 0 ? '2px' : '0' }"></div>
                                    <!-- Late (amber) -->
                                    <div class="bg-amber-400 transition-all"
                                        :style="{ height: `${(d.late / maxDailyTotal) * 100}%`, minHeight: d.late > 0 ? '1px' : '0' }"></div>
                                    <!-- Absent (red) -->
                                    <div class="bg-red-400 rounded-b transition-all"
                                        :style="{ height: `${(d.absent / maxDailyTotal) * 100}%`, minHeight: d.absent > 0 ? '1px' : '0' }"></div>
                                </div>
                                <div class="absolute bottom-full mb-1 hidden group-hover:block bg-gray-800 text-white rounded px-1.5 py-0.5 text-[9px] whitespace-nowrap z-10">
                                    {{ d.day }}: {{ d.present }}✓ {{ d.late }}⏰ {{ d.absent }}✗
                                </div>
                            </div>
                        </div>
                        <div class="flex items-center gap-3 mt-2 justify-center text-[10px]">
                            <div class="flex items-center gap-1"><div class="w-2 h-2 rounded bg-emerald-400"></div>{{ isRtl ? 'حاضر' : 'Present' }}</div>
                            <div class="flex items-center gap-1"><div class="w-2 h-2 rounded bg-amber-400"></div>{{ isRtl ? 'متأخر' : 'Late' }}</div>
                            <div class="flex items-center gap-1"><div class="w-2 h-2 rounded bg-red-400"></div>{{ isRtl ? 'غائب' : 'Absent' }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
