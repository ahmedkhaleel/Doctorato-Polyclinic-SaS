<script setup>
import { computed, ref, watch, onMounted, nextTick } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import SecretaryLayout from '@/Layouts/SecretaryLayout.vue';

defineOptions({ layout: SecretaryLayout });

const page = usePage();
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const props = defineProps({
    vaccinations: Object,
    stats: Object,
    filters: Object,
});

const search = ref(props.filters?.search || '');
const statusFilter = ref(props.filters?.status || '');

let searchTimeout;

function applyFilters() {
    router.get('/secretary/pediatric/vaccinations', {
        search: search.value || undefined,
        status: statusFilter.value || undefined,
    }, { preserveState: true, replace: true });
}

watch(search, () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => applyFilters(), 400);
});

watch(statusFilter, () => applyFilters());

// ─── Status Update Logic ────────────────────────────────────
const editingId = ref(null);
const editForm = ref({ status: '', given_date: '', notes: '' });
const submitting = ref(false);

const statusOptions = [
    { value: 'scheduled', labelAr: 'مجدول', labelEn: 'Scheduled' },
    { value: 'given', labelAr: 'تم إعطاؤه', labelEn: 'Given' },
    { value: 'missed', labelAr: 'فائت', labelEn: 'Missed' },
    { value: 'postponed', labelAr: 'مؤجل', labelEn: 'Postponed' },
    { value: 'contraindicated', labelAr: 'موانع', labelEn: 'Contraindicated' },
];

function openStatusEdit(vaccination) {
    editingId.value = vaccination.id;
    editForm.value = {
        status: vaccination.status,
        given_date: vaccination.given_date || '',
        notes: vaccination.notes || '',
    };
}

function cancelEdit() {
    editingId.value = null;
    editForm.value = { status: '', given_date: '', notes: '' };
}

function submitStatusUpdate(vaccinationId) {
    submitting.value = true;
    router.post(`/secretary/pediatric/vaccinations/${vaccinationId}/status`, {
        status: editForm.value.status,
        given_date: editForm.value.status === 'given' ? editForm.value.given_date : null,
        notes: editForm.value.notes || null,
    }, {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => {
            editingId.value = null;
            editForm.value = { status: '', given_date: '', notes: '' };
        },
        onFinish: () => {
            submitting.value = false;
        },
    });
}

// ─── Status Colors & Labels ─────────────────────────────────
const vaccinationStatusColors = {
    given: 'bg-emerald-50 text-emerald-700 border-emerald-200',
    overdue: 'bg-red-50 text-red-700 border-red-200',
    upcoming: 'bg-slate-50 text-[#1B365D] border-slate-200',
    scheduled: 'bg-slate-50 text-[#1B365D] border-slate-200',
    missed: 'bg-red-50 text-red-600 border-red-200',
    postponed: 'bg-amber-50 text-amber-700 border-amber-200',
    contraindicated: 'bg-gray-100 text-gray-500 border-gray-200',
};

const vaccinationStatusLabels = computed(() => ({
    given: isRtl.value ? 'تم إعطاؤه' : 'Given',
    overdue: isRtl.value ? 'متأخر' : 'Overdue',
    upcoming: isRtl.value ? 'قادم' : 'Upcoming',
    scheduled: isRtl.value ? 'مجدول' : 'Scheduled',
    missed: isRtl.value ? 'فائت' : 'Missed',
    postponed: isRtl.value ? 'مؤجل' : 'Postponed',
    contraindicated: isRtl.value ? 'موانع' : 'Contraindicated',
}));

const statCards = computed(() => [
    {
        label: isRtl.value ? 'الإجمالي' : 'Total',
        value: props.stats?.total ?? 0,
        color: 'text-white',
        bg: 'from-gray-700 to-gray-800',
        icon: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2',
    },
    {
        label: isRtl.value ? 'تم إعطاؤها' : 'Given',
        value: props.stats?.given ?? 0,
        color: 'text-emerald-600',
        bg: 'from-emerald-50 to-emerald-50',
        borderColor: 'border-emerald-100',
        icon: 'M5 13l4 4L19 7',
    },
    {
        label: isRtl.value ? 'متأخرة' : 'Overdue',
        value: props.stats?.overdue ?? 0,
        color: 'text-red-600',
        bg: 'from-red-50 to-amber-50',
        borderColor: 'border-red-100',
        icon: 'M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
    },
    {
        label: isRtl.value ? 'قادمة' : 'Upcoming',
        value: props.stats?.upcoming ?? 0,
        color: 'text-[#1B365D]',
        bg: 'from-slate-50 to-slate-50',
        borderColor: 'border-slate-100',
        icon: 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z',
    },
]);

function formatDate(date) {
    if (!date) return '-';
    return new Date(date).toLocaleDateString(isRtl.value ? 'ar-EG' : 'en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
}

function calculateAge(dob) {
    if (!dob) return '-';
    const birth = new Date(dob);
    const now = new Date();
    const years = Math.floor((now - birth) / (365.25 * 24 * 60 * 60 * 1000));
    const months = Math.floor(((now - birth) % (365.25 * 24 * 60 * 60 * 1000)) / (30.44 * 24 * 60 * 60 * 1000));
    if (years < 1) return isRtl.value ? `${months} شهر` : `${months}mo`;
    return isRtl.value ? `${years} سنة` : `${years}y`;
}

function isOverdue(scheduledDate) {
    if (!scheduledDate) return false;
    return new Date(scheduledDate) < new Date();
}

const headerLoaded = ref(false);
const statsLoaded = ref(false);
const cardsLoaded = ref(false);
onMounted(() => {
    setTimeout(() => { headerLoaded.value = true; }, 50);
    setTimeout(() => { statsLoaded.value = true; }, 150);
    setTimeout(() => { cardsLoaded.value = true; }, 300);
});
</script>

<template>
    <div>
        <!-- HERO HEADER -->
        <div
            class="relative -mx-4 sm:-mx-6 lg:-mx-8 -mt-4 sm:-mt-6 mb-8 px-4 sm:px-6 lg:px-8 pt-8 pb-10 bg-gradient-to-br from-[#1B365D] via-[#1B365D] to-[#0F2444] overflow-hidden transition-all duration-700"
            :class="headerLoaded ? 'opacity-100 translate-y-0' : 'opacity-0 -translate-y-4'"
        >
            <div class="absolute inset-0 opacity-10" style="background: radial-gradient(circle at 30% 50%, #4CAF50 0%, transparent 40%), radial-gradient(circle at 70% 50%, #0d9488 0%, transparent 50%)"></div>
            <div class="relative z-10">
                <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4">
                    <div>
                        <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-white/10 backdrop-blur-sm mb-3">
                            <span class="w-2 h-2 rounded-full bg-[#4CAF50] animate-pulse"></span>
                            <span class="text-xs font-semibold text-gray-300">{{ isRtl ? 'عيادة الأطفال' : 'Pediatric Clinic' }}</span>
                        </div>
                        <h1 class="text-2xl sm:text-3xl font-bold text-white">{{ isRtl ? 'التطعيمات' : 'Vaccinations' }}</h1>
                        <p class="text-sm text-gray-400 mt-1.5">{{ isRtl ? 'إدارة ومتابعة جدول التطعيمات' : 'Manage and track vaccination schedules' }}</p>
                    </div>
                </div>

                <!-- Search & Status Filter -->
                <div class="flex flex-wrap items-center gap-2 sm:gap-3 mt-6">
                    <div class="relative flex-1 min-w-[200px] max-w-md">
                        <svg class="absolute left-3 rtl:left-auto rtl:right-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                        <input
                            v-model="search"
                            type="text"
                            :placeholder="isRtl ? 'بحث بالاسم، التطعيم...' : 'Search by name, vaccine...'"
                            class="doctorato-input w-full ltr:pl-10 ltr:pr-4 rtl:pr-10 rtl:pl-4 py-2.5 bg-white/10 border border-white/20 rounded-xl text-sm text-white placeholder-gray-400 focus:ring-2 focus:ring-[#4CAF50]/50 focus:border-[#4CAF50] transition"
                        />
                    </div>
                    <select
                        v-model="statusFilter"
                        class="doctorato-input px-4 py-2.5 bg-white/10 border border-white/20 rounded-xl text-sm text-white focus:ring-2 focus:ring-[#4CAF50]/50 focus:border-[#4CAF50] [&>option]:text-gray-900"
                    >
                        <option value="">{{ isRtl ? 'جميع الحالات' : 'All Status' }}</option>
                        <option value="given">{{ isRtl ? 'تم إعطاؤه' : 'Given' }}</option>
                        <option value="overdue">{{ isRtl ? 'متأخر' : 'Overdue' }}</option>
                        <option value="upcoming">{{ isRtl ? 'قادم' : 'Upcoming' }}</option>
                        <option value="scheduled">{{ isRtl ? 'مجدول' : 'Scheduled' }}</option>
                        <option value="missed">{{ isRtl ? 'فائت' : 'Missed' }}</option>
                        <option value="postponed">{{ isRtl ? 'مؤجل' : 'Postponed' }}</option>
                        <option value="contraindicated">{{ isRtl ? 'موانع' : 'Contraindicated' }}</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- STATS CARDS -->
        <div
            class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8 transition-all duration-500"
            :class="statsLoaded ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
        >
            <div
                v-for="(stat, idx) in statCards"
                :key="idx"
                class="relative rounded-2xl overflow-hidden border transition-all duration-300 hover:shadow-md"
                :class="[stat.borderColor || 'border-gray-700', `bg-gradient-to-br ${stat.bg}`]"
                :style="{ transitionDelay: `${idx * 60}ms` }"
            >
                <div class="p-4 sm:p-5">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-semibold" :class="idx === 0 ? 'text-gray-400' : 'text-gray-500'">{{ stat.label }}</p>
                            <p class="text-2xl sm:text-3xl font-bold mt-1" :class="stat.color">{{ stat.value }}</p>
                        </div>
                        <div class="w-10 h-10 rounded-xl flex items-center justify-center" :class="idx === 0 ? 'bg-white/10' : 'bg-white/60'">
                            <svg class="w-5 h-5" :class="stat.color" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="stat.icon" /></svg>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Success Flash -->
        <div v-if="page.props.flash?.success" class="mb-4 p-3 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm font-medium flex items-center gap-2">
            <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
            {{ page.props.flash.success }}
        </div>

        <!-- VACCINATION LIST -->
        <div class="space-y-3 transition-all duration-500" :class="cardsLoaded ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'">
            <div
                v-for="(vaccination, idx) in vaccinations.data"
                :key="vaccination.id"
                class="bg-white rounded-2xl shadow-sm border border-gray-100/80 hover:shadow-md transition-all duration-300 overflow-hidden"
                :class="{
                    'ltr:border-l-4 rtl:border-r-4 ltr:border-l-red-400 rtl:border-r-red-400': vaccination.status === 'overdue' || vaccination.status === 'missed',
                    'ltr:border-l-4 rtl:border-r-4 ltr:border-l-emerald-400 rtl:border-r-emerald-400': vaccination.status === 'given',
                    'ltr:border-l-4 rtl:border-r-4 ltr:border-l-blue-400 rtl:border-r-blue-400': vaccination.status === 'upcoming' || vaccination.status === 'scheduled',
                    'ltr:border-l-4 rtl:border-r-4 ltr:border-l-amber-400 rtl:border-r-amber-400': vaccination.status === 'postponed',
                    'ltr:border-l-4 rtl:border-r-4 ltr:border-l-gray-300 rtl:border-r-gray-300': vaccination.status === 'contraindicated',
                }"
                :style="{ transitionDelay: `${idx * 40}ms` }"
            >
                <div class="flex flex-col sm:flex-row sm:items-center gap-4 p-4 sm:p-5">
                    <!-- Vaccine Icon & Info -->
                    <div class="flex items-center gap-4 flex-1 min-w-0">
                        <div
                            class="w-11 h-11 rounded-xl flex-shrink-0 flex items-center justify-center"
                            :class="{
                                'bg-emerald-100 text-emerald-600': vaccination.status === 'given',
                                'bg-red-100 text-red-600': vaccination.status === 'overdue' || vaccination.status === 'missed',
                                'bg-slate-100 text-[#1B365D]': vaccination.status === 'upcoming' || vaccination.status === 'scheduled',
                                'bg-amber-100 text-amber-600': vaccination.status === 'postponed',
                                'bg-gray-100 text-gray-500': vaccination.status === 'contraindicated',
                            }"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path v-if="vaccination.status === 'given'" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                <path v-else-if="vaccination.status === 'overdue' || vaccination.status === 'missed'" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                <path v-else-if="vaccination.status === 'contraindicated'" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                                <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div class="min-w-0">
                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="font-bold text-gray-900 text-sm">
                                    {{ isRtl ? (vaccination.vaccine_name_ar || vaccination.vaccine_name) : (vaccination.vaccine_name || vaccination.vaccine_name_ar) }}
                                </span>
                                <span v-if="vaccination.dose" class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-[#4CAF50]/10 text-[#4CAF50] border border-[#4CAF50]/20">
                                    {{ isRtl ? `الجرعة ${vaccination.dose}` : `Dose ${vaccination.dose}` }}
                                </span>
                            </div>
                            <!-- Patient Info -->
                            <div class="flex items-center gap-2 mt-1 flex-wrap">
                                <Link
                                    v-if="vaccination.patient"
                                    :href="`/secretary/pediatric/patients/${vaccination.patient.id}`"
                                    class="text-xs font-semibold text-[#0d9488] hover:underline"
                                >
                                    {{ vaccination.patient.full_name }}
                                </Link>
                                <span v-if="vaccination.patient?.date_of_birth" class="text-[10px] text-gray-400">
                                    ({{ calculateAge(vaccination.patient.date_of_birth) }})
                                </span>
                                <span v-if="vaccination.patient?.file_number" class="text-[10px] font-mono text-gray-400">
                                    {{ vaccination.patient.file_number }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Scheduled Date -->
                    <div class="text-center hidden sm:block">
                        <p class="text-[10px] text-gray-400 font-semibold uppercase">{{ isRtl ? 'التاريخ المجدول' : 'Scheduled' }}</p>
                        <p class="text-sm font-semibold mt-0.5" :class="vaccination.status === 'overdue' ? 'text-red-600' : 'text-gray-700'">
                            {{ formatDate(vaccination.scheduled_date) }}
                        </p>
                    </div>

                    <!-- Given Date -->
                    <div v-if="vaccination.given_date" class="text-center hidden md:block">
                        <p class="text-[10px] text-gray-400 font-semibold uppercase">{{ isRtl ? 'تاريخ الإعطاء' : 'Given Date' }}</p>
                        <p class="text-sm font-semibold text-emerald-600 mt-0.5">{{ formatDate(vaccination.given_date) }}</p>
                    </div>

                    <!-- Status Badge (clickable to edit) -->
                    <button
                        v-if="editingId !== vaccination.id"
                        @click="openStatusEdit(vaccination)"
                        class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold border self-start sm:self-center cursor-pointer hover:ring-2 hover:ring-offset-1 hover:ring-[#0d9488]/30 transition-all group"
                        :class="vaccinationStatusColors[vaccination.status] || 'bg-gray-50 text-gray-500 border-gray-200'"
                        :title="isRtl ? 'انقر لتغيير الحالة' : 'Click to change status'"
                    >
                        {{ vaccinationStatusLabels[vaccination.status] || vaccination.status }}
                        <svg class="w-3 h-3 opacity-0 group-hover:opacity-100 transition-opacity" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                    </button>
                </div>

                <!-- Inline Status Edit Panel -->
                <div
                    v-if="editingId === vaccination.id"
                    class="border-t border-gray-100 bg-gray-50/50 p-4 sm:p-5"
                >
                    <div class="flex flex-col sm:flex-row sm:items-end gap-3 sm:gap-4">
                        <!-- Status Select -->
                        <div class="flex-1 min-w-0 max-w-[200px]">
                            <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1.5">
                                {{ isRtl ? 'الحالة' : 'Status' }}
                            </label>
                            <select
                                v-model="editForm.status"
                                class="doctorato-input w-full px-3 py-2 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-[#0d9488]/40 focus:border-[#0d9488] transition bg-white"
                            >
                                <option
                                    v-for="opt in statusOptions"
                                    :key="opt.value"
                                    :value="opt.value"
                                >
                                    {{ isRtl ? opt.labelAr : opt.labelEn }}
                                </option>
                            </select>
                        </div>

                        <!-- Given Date (shown only when status = given) -->
                        <div v-if="editForm.status === 'given'" class="flex-1 min-w-0 max-w-[200px]">
                            <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1.5">
                                {{ isRtl ? 'تاريخ الإعطاء' : 'Given Date' }}
                            </label>
                            <input
                                v-model="editForm.given_date"
                                type="date"
                                class="doctorato-input w-full px-3 py-2 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-[#0d9488]/40 focus:border-[#0d9488] transition bg-white"
                            />
                        </div>

                        <!-- Notes -->
                        <div class="flex-1 min-w-0">
                            <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1.5">
                                {{ isRtl ? 'ملاحظات' : 'Notes' }}
                            </label>
                            <input
                                v-model="editForm.notes"
                                type="text"
                                :placeholder="isRtl ? 'ملاحظات اختيارية...' : 'Optional notes...'"
                                maxlength="500"
                                class="doctorato-input w-full px-3 py-2 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-[#0d9488]/40 focus:border-[#0d9488] transition bg-white placeholder-gray-300"
                            />
                        </div>

                        <!-- Action Buttons -->
                        <div class="flex items-center gap-2 flex-shrink-0">
                            <button
                                @click="submitStatusUpdate(vaccination.id)"
                                :disabled="submitting"
                                class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-xs font-bold text-white bg-[#0d9488] hover:bg-[#0d9488]/90 disabled:opacity-50 disabled:cursor-not-allowed transition-all shadow-sm"
                            >
                                <svg v-if="submitting" class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" /><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" /></svg>
                                <svg v-else class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                                {{ isRtl ? 'حفظ' : 'Save' }}
                            </button>
                            <button
                                @click="cancelEdit"
                                :disabled="submitting"
                                class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-xs font-bold text-gray-600 bg-white border border-gray-200 hover:bg-gray-50 disabled:opacity-50 transition-all"
                            >
                                {{ isRtl ? 'إلغاء' : 'Cancel' }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Empty State -->
        <div v-if="!vaccinations.data || vaccinations.data.length === 0" class="py-16 text-center">
            <div class="w-16 h-16 mx-auto mb-4 rounded-2xl bg-emerald-50 flex items-center justify-center">
                <svg class="w-8 h-8 text-emerald-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" /></svg>
            </div>
            <p class="text-sm font-semibold text-gray-500">{{ isRtl ? 'لا توجد تطعيمات' : 'No vaccinations found' }}</p>
            <p class="text-xs text-gray-400 mt-1">{{ isRtl ? 'جرب تغيير البحث أو الفلاتر' : 'Try adjusting your search or filters' }}</p>
        </div>

        <!-- Pagination -->
        <div v-if="vaccinations.links && vaccinations.links.length > 3" class="flex flex-col sm:flex-row items-center justify-between gap-3 mt-6">
            <p class="text-xs text-gray-500">
                {{ isRtl ? 'عرض' : 'Showing' }} <span class="font-semibold">{{ vaccinations.from }}</span> {{ isRtl ? 'إلى' : 'to' }} <span class="font-semibold">{{ vaccinations.to }}</span> {{ isRtl ? 'من' : 'of' }} <span class="font-semibold">{{ vaccinations.total }}</span> {{ isRtl ? 'نتيجة' : 'results' }}
            </p>
            <nav class="flex items-center gap-1">
                <template v-for="link in vaccinations.links" :key="link.label">
                    <Link v-if="link.url" :href="link.url" class="px-3 py-1.5 rounded-lg text-xs font-medium transition-colors" :class="link.active ? 'bg-[#4CAF50] text-white shadow-sm' : 'text-gray-500 hover:bg-white hover:shadow-sm'" v-html="link.label" preserve-state />
                    <span v-else class="px-3 py-1.5 text-xs text-gray-300" v-html="link.label" />
                </template>
            </nav>
        </div>
    </div>
</template>
