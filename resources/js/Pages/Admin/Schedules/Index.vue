<script setup>
import { ref, computed } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const page = usePage();
const locale = computed(() => page.props.locale || 'ar');
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const props = defineProps({
    doctors: Array,
    stats: Object,
    filters: Object,
});

/* ── Days Config ────────────────────────────────── */
const days = [
    { index: 0, en: 'Saturday',  ar: 'السبت' },
    { index: 1, en: 'Sunday',    ar: 'الأحد' },
    { index: 2, en: 'Monday',    ar: 'الاثنين' },
    { index: 3, en: 'Tuesday',   ar: 'الثلاثاء' },
    { index: 4, en: 'Wednesday', ar: 'الأربعاء' },
    { index: 5, en: 'Thursday',  ar: 'الخميس' },
    { index: 6, en: 'Friday',    ar: 'الجمعة' },
];

const todayIndex = computed(() => {
    const map = { 6: 0, 0: 1, 1: 2, 2: 3, 3: 4, 4: 5, 5: 6 };
    return map[new Date().getDay()] ?? 0;
});

/* ── Department Filter ──────────────────────────── */
const deptFilter = ref(props.filters?.department || '');
const departments = computed(() => {
    const depts = new Set();
    props.doctors.forEach(d => { if (d.department) depts.add(d.department); });
    return Array.from(depts).sort();
});

const filteredDoctors = computed(() => {
    if (!deptFilter.value) return props.doctors;
    return props.doctors.filter(d => d.department === deptFilter.value);
});

/* ── Edit Mode ──────────────────────────────────── */
const editingDoctor = ref(null);
const editSchedules = ref({});

function startEdit(doctor) {
    editingDoctor.value = doctor.id;
    const schedules = {};
    days.forEach(day => {
        const existing = doctor.schedules[day.index];
        schedules[day.index] = {
            day_of_week: day.index,
            start_time: existing?.start_time || '09:00',
            end_time: existing?.end_time || '17:00',
            is_active: existing?.is_active ?? false,
        };
    });
    editSchedules.value = schedules;
}

function cancelEdit() {
    editingDoctor.value = null;
    editSchedules.value = {};
}

function saveSchedule(doctorId) {
    const schedules = Object.values(editSchedules.value);
    router.post(`/admin/schedules/${doctorId}/update`, { schedules }, {
        preserveState: true,
        onSuccess: () => cancelEdit(),
    });
}

function toggleDay(dayIndex) {
    editSchedules.value[dayIndex].is_active = !editSchedules.value[dayIndex].is_active;
}

/* ── Helpers ────────────────────────────────────── */
function formatTime(time) {
    if (!time) return '';
    const [h, m] = time.split(':');
    const hour = parseInt(h);
    const ampm = hour >= 12 ? 'PM' : 'AM';
    const h12 = hour % 12 || 12;
    return `${h12}:${m} ${ampm}`;
}

function isOnVacation(doctor) {
    const today = new Date().toISOString().split('T')[0];
    return doctor.upcoming_vacations?.some(v => v.start_date <= today && v.end_date >= today);
}

function getWorkingHours(doctor) {
    let total = 0;
    for (const dayIdx in doctor.schedules) {
        const s = doctor.schedules[dayIdx];
        if (s.is_active) {
            const [sh, sm] = s.start_time.split(':').map(Number);
            const [eh, em] = s.end_time.split(':').map(Number);
            total += (eh * 60 + em) - (sh * 60 + sm);
        }
    }
    return (total / 60).toFixed(1);
}

function getActiveDays(doctor) {
    return Object.values(doctor.schedules).filter(s => s.is_active).length;
}
</script>

<template>
    <AdminLayout :title="isRtl ? 'جداول الأطباء' : 'Doctor Schedules'">
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-xl md:text-2xl font-bold text-gray-900">{{ isRtl ? 'جداول عمل الأطباء' : 'Doctor Schedules' }}</h1>
                    <p class="text-sm text-gray-500 mt-1">{{ isRtl ? 'إدارة مواعيد العمل الأسبوعية' : 'Manage weekly work schedules' }}</p>
                </div>
            </div>

            <!-- Stats -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="bg-white rounded-xl border border-gray-200 p-4">
                    <div class="text-xl md:text-2xl font-bold text-gray-900">{{ stats.total_doctors }}</div>
                    <div class="text-xs text-gray-500 mt-1">{{ isRtl ? 'إجمالي الأطباء' : 'Total Doctors' }}</div>
                </div>
                <div class="bg-white rounded-xl border border-emerald-200 p-4">
                    <div class="text-xl md:text-2xl font-bold text-emerald-600">{{ stats.working_today }}</div>
                    <div class="text-xs text-gray-500 mt-1">{{ isRtl ? 'يعملون اليوم' : 'Working Today' }}</div>
                </div>
                <div class="bg-white rounded-xl border border-amber-200 p-4">
                    <div class="text-xl md:text-2xl font-bold text-amber-600">{{ stats.on_vacation }}</div>
                    <div class="text-xs text-gray-500 mt-1">{{ isRtl ? 'في إجازة' : 'On Vacation' }}</div>
                </div>
                <div class="bg-white rounded-xl border border-slate-200 p-4">
                    <div class="text-xl md:text-2xl font-bold text-[#1B365D]">{{ stats.available_today }}</div>
                    <div class="text-xs text-gray-500 mt-1">{{ isRtl ? 'متاحون اليوم' : 'Available Today' }}</div>
                </div>
            </div>

            <!-- Department Filter -->
            <div class="flex items-center gap-3" v-if="departments.length > 1">
                <span class="text-sm text-gray-600">{{ isRtl ? 'القسم:' : 'Department:' }}</span>
                <div class="flex flex-wrap gap-2">
                    <button @click="deptFilter = ''"
                        class="px-3 py-1.5 text-xs font-medium rounded-full border transition"
                        :class="!deptFilter ? 'bg-[#1B365D] text-white border-[#1B365D]' : 'bg-white text-gray-600 border-gray-300 hover:bg-gray-50'">
                        {{ isRtl ? 'الكل' : 'All' }}
                    </button>
                    <button v-for="dept in departments" :key="dept" @click="deptFilter = dept"
                        class="px-3 py-1.5 text-xs font-medium rounded-full border transition capitalize"
                        :class="deptFilter === dept ? 'bg-[#1B365D] text-white border-[#1B365D]' : 'bg-white text-gray-600 border-gray-300 hover:bg-gray-50'">
                        {{ dept }}
                    </button>
                </div>
            </div>

            <!-- Weekly Schedule Grid -->
            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[900px]">
                        <thead>
                            <tr class="bg-gray-50">
                                <th class="text-start px-4 py-3 text-xs font-semibold text-gray-500 uppercase w-48">
                                    {{ isRtl ? 'الطبيب' : 'Doctor' }}
                                </th>
                                <th v-for="day in days" :key="day.index"
                                    class="px-2 py-3 text-center text-xs font-semibold uppercase"
                                    :class="day.index === todayIndex ? 'text-[#1B365D] bg-slate-50' : 'text-gray-500'">
                                    <div>{{ isRtl ? day.ar : day.en.slice(0, 3) }}</div>
                                    <div v-if="day.index === todayIndex" class="mt-0.5">
                                        <span class="inline-block w-1.5 h-1.5 rounded-full bg-[#1B365D]"></span>
                                    </div>
                                </th>
                                <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase w-20">
                                    {{ isRtl ? 'ساعات' : 'Hours' }}
                                </th>
                                <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase w-24">
                                    {{ isRtl ? 'إجراءات' : 'Actions' }}
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="doctor in filteredDoctors" :key="doctor.id"
                                class="hover:bg-gray-50/50 transition">
                                <!-- Doctor Info -->
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-3">
                                        <div class="w-9 h-9 rounded-full bg-gradient-to-br from-slate-400 to-[#1B365D] flex items-center justify-center text-white text-sm font-bold flex-shrink-0">
                                            {{ (isRtl ? doctor.name_ar : doctor.name_en)?.charAt(0) || 'D' }}
                                        </div>
                                        <div class="min-w-0">
                                            <div class="text-sm font-semibold text-gray-900 truncate">
                                                {{ isRtl ? doctor.name_ar : doctor.name_en }}
                                            </div>
                                            <div class="text-xs text-gray-500 truncate">
                                                {{ isRtl ? doctor.specialty_ar : doctor.specialty_en }}
                                            </div>
                                            <span v-if="isOnVacation(doctor)"
                                                class="inline-flex items-center px-1.5 py-0.5 rounded-full text-[10px] font-medium bg-amber-100 text-amber-700 mt-0.5">
                                                {{ isRtl ? 'إجازة' : 'Vacation' }}
                                            </span>
                                        </div>
                                    </div>
                                </td>

                                <!-- Schedule Cells (View Mode) -->
                                <template v-if="editingDoctor !== doctor.id">
                                    <td v-for="day in days" :key="day.index" class="px-2 py-3 text-center"
                                        :class="day.index === todayIndex ? 'bg-slate-50/50' : ''">
                                        <div v-if="doctor.schedules[day.index]?.is_active"
                                            class="inline-flex flex-col items-center px-2 py-1 rounded-lg bg-emerald-50 border border-emerald-100">
                                            <span class="text-[11px] font-medium text-emerald-700">
                                                {{ formatTime(doctor.schedules[day.index].start_time) }}
                                            </span>
                                            <span class="text-[10px] text-emerald-500">—</span>
                                            <span class="text-[11px] font-medium text-emerald-700">
                                                {{ formatTime(doctor.schedules[day.index].end_time) }}
                                            </span>
                                        </div>
                                        <div v-else class="text-gray-300 text-lg">—</div>
                                    </td>
                                </template>

                                <!-- Schedule Cells (Edit Mode) -->
                                <template v-else>
                                    <td v-for="day in days" :key="day.index" class="px-1.5 py-2 text-center"
                                        :class="day.index === todayIndex ? 'bg-slate-50/50' : ''">
                                        <div class="space-y-1">
                                            <button @click="toggleDay(day.index)"
                                                class="w-full px-2 py-1 rounded text-[11px] font-medium transition"
                                                :class="editSchedules[day.index].is_active
                                                    ? 'bg-emerald-100 text-emerald-700'
                                                    : 'bg-gray-100 text-gray-400'">
                                                {{ editSchedules[day.index].is_active ? (isRtl ? 'يعمل' : 'ON') : (isRtl ? 'عطلة' : 'OFF') }}
                                            </button>
                                            <template v-if="editSchedules[day.index].is_active">
                                                <input type="time" v-model="editSchedules[day.index].start_time"
                                                    class="doctorato-input w-full text-[11px] px-1 py-0.5 rounded border border-gray-200 focus:ring-[#1B365D] focus:border-[#1B365D]" />
                                                <input type="time" v-model="editSchedules[day.index].end_time"
                                                    class="doctorato-input w-full text-[11px] px-1 py-0.5 rounded border border-gray-200 focus:ring-[#1B365D] focus:border-[#1B365D]" />
                                            </template>
                                        </div>
                                    </td>
                                </template>

                                <!-- Weekly Hours -->
                                <td class="px-4 py-3 text-center">
                                    <span class="text-sm font-semibold text-gray-700">{{ getWorkingHours(doctor) }}h</span>
                                    <div class="text-[10px] text-gray-400">{{ getActiveDays(doctor) }} {{ isRtl ? 'أيام' : 'days' }}</div>
                                </td>

                                <!-- Actions -->
                                <td class="px-4 py-3 text-center">
                                    <template v-if="editingDoctor !== doctor.id">
                                        <button @click="startEdit(doctor)"
                                            class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-medium text-[#1B365D] bg-slate-50 rounded-lg hover:bg-slate-100 transition">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                                            {{ isRtl ? 'تعديل' : 'Edit' }}
                                        </button>
                                    </template>
                                    <template v-else>
                                        <div class="flex items-center justify-center gap-1">
                                            <button @click="saveSchedule(doctor.id)"
                                                class="px-2.5 py-1.5 text-xs font-medium text-white bg-emerald-600 rounded-lg hover:bg-emerald-700 transition">
                                                {{ isRtl ? 'حفظ' : 'Save' }}
                                            </button>
                                            <button @click="cancelEdit"
                                                class="px-2.5 py-1.5 text-xs font-medium text-gray-600 bg-gray-100 rounded-lg hover:bg-gray-200 transition">
                                                {{ isRtl ? 'إلغاء' : 'Cancel' }}
                                            </button>
                                        </div>
                                    </template>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Empty -->
                <div v-if="!filteredDoctors.length" class="p-16 text-center">
                    <svg class="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                    <h3 class="text-lg font-medium text-gray-900">{{ isRtl ? 'لا يوجد أطباء' : 'No doctors found' }}</h3>
                </div>
            </div>

            <!-- Upcoming Vacations -->
            <div class="bg-white rounded-xl border border-gray-200 p-6" v-if="doctors.some(d => d.upcoming_vacations?.length)">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">{{ isRtl ? 'الإجازات القادمة' : 'Upcoming Vacations' }}</h3>
                <div class="space-y-3">
                    <div v-for="doctor in doctors.filter(d => d.upcoming_vacations?.length)" :key="'vac-'+doctor.id">
                        <div v-for="vac in doctor.upcoming_vacations" :key="vac.id"
                            class="flex items-center gap-4 p-3 rounded-lg bg-amber-50 border border-amber-100">
                            <div class="w-8 h-8 rounded-full bg-amber-200 flex items-center justify-center text-amber-700 text-sm font-bold flex-shrink-0">
                                {{ (isRtl ? doctor.name_ar : doctor.name_en)?.charAt(0) }}
                            </div>
                            <div class="flex-1 min-w-0">
                                <span class="text-sm font-semibold text-gray-900">{{ isRtl ? doctor.name_ar : doctor.name_en }}</span>
                                <span class="text-sm text-gray-500 mx-2">—</span>
                                <span class="text-sm text-amber-700">{{ vac.start_date }} → {{ vac.end_date }}</span>
                                <span v-if="vac.reason" class="text-xs text-gray-500 mx-2">({{ vac.reason }})</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
