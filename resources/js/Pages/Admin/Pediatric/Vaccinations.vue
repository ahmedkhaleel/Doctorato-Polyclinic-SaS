<script setup>
import { ref, computed, watch, reactive } from 'vue';
import { Link, usePage, router, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { useConfirm } from '@/Composables/useConfirm.js';

defineOptions({ layout: AdminLayout });

const { confirm } = useConfirm();
const page = usePage();
const locale = computed(() => page.props.locale || 'ar');
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');
const translations = computed(() => page.props.translations || {});
function t(key) { return translations.value[key] || key; }

const props = defineProps({
    vaccinations: Object,
    stats: Object,
    filters: Object,
    pediatricPatients: { type: Array, default: () => [] },
    supplies: { type: Array, default: () => [] },
});

/* ── Filters ───────────────────────────────────────────── */
const search = ref(props.filters?.search || '');
const statusFilter = ref(props.filters?.status || '');
let searchTimer = null;

function applyFilters() {
    router.get('/admin/pediatric/vaccinations', {
        search: search.value || undefined,
        status: statusFilter.value || undefined,
    }, { preserveState: true, preserveScroll: true });
}

watch(search, () => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(applyFilters, 400);
});

watch(statusFilter, () => applyFilters());

/* ── Stats cards ───────────────────────────────────────── */
const statCards = computed(() => [
    {
        labelEn: 'Total', labelAr: 'الإجمالي',
        value: props.stats?.total ?? 0,
        gradient: 'from-gray-500 to-gray-600',
        lightBg: 'bg-gray-50', iconColor: 'text-gray-500',
    },
    {
        labelEn: 'Given', labelAr: 'تم التطعيم',
        value: props.stats?.given ?? 0,
        gradient: 'from-emerald-500 to-emerald-600',
        lightBg: 'bg-emerald-50', iconColor: 'text-emerald-500',
    },
    {
        labelEn: 'Scheduled', labelAr: 'مجدولة',
        value: props.stats?.scheduled ?? 0,
        gradient: 'from-[#1B365D] to-[#1B365D]',
        lightBg: 'bg-slate-50', iconColor: 'text-[#1B365D]',
    },
    {
        labelEn: 'Overdue', labelAr: 'متأخرة',
        value: props.stats?.overdue ?? 0,
        gradient: 'from-red-500 to-[#C4A265]',
        lightBg: 'bg-red-50', iconColor: 'text-red-500',
        isAlert: (props.stats?.overdue ?? 0) > 0,
    },
    {
        labelEn: 'Missed', labelAr: 'فائتة',
        value: props.stats?.missed ?? 0,
        gradient: 'from-gray-400 to-gray-500',
        lightBg: 'bg-gray-50', iconColor: 'text-gray-400',
    },
]);

/* ── Status badge ──────────────────────────────────────── */
const statusStyles = {
    given:     { bg: 'bg-emerald-50', text: 'text-emerald-700', dot: 'bg-emerald-500' },
    scheduled: { bg: 'bg-slate-50',    text: 'text-[#1B365D]',    dot: 'bg-[#1B365D]' },
    overdue:   { bg: 'bg-red-50',     text: 'text-red-700',     dot: 'bg-red-500' },
    missed:    { bg: 'bg-gray-100',   text: 'text-gray-600',    dot: 'bg-gray-400' },
};

function getStatusStyle(status) { return statusStyles[status] || statusStyles.scheduled; }

function statusLabel(status) {
    const labels = {
        given:     { en: 'Given',     ar: 'تم' },
        scheduled: { en: 'Scheduled', ar: 'مجدول' },
        overdue:   { en: 'Overdue',   ar: 'متأخر' },
        missed:    { en: 'Missed',    ar: 'فائت' },
    };
    return isRtl.value ? (labels[status]?.ar || status) : (labels[status]?.en || status);
}

/* ── Helpers ────────────────────────────────────────────── */
function formatDate(date) {
    if (!date) return '-';
    const loc = isRtl.value ? 'ar-EG' : 'en-GB';
    return new Date(date).toLocaleDateString(loc, { day: '2-digit', month: 'short', year: 'numeric' });
}

/* ── Pagination ────────────────────────────────────────── */
const paginationLinks = computed(() => props.vaccinations?.links || []);

/* ── Modals & Forms ────────────────────────────────────── */
const showNewModal = ref(false);
const showInitModal = ref(false);
const showStatusModal = ref(false);
const editingVaccination = ref(null);

const newForm = useForm({
    id: null,
    patient_id: '',
    vaccine_name: '',
    vaccine_name_ar: '',
    dose_number: '',
    scheduled_age: '',
    scheduled_date: '',
    given_date: '',
    batch_number: '',
    manufacturer: '',
    site_of_injection: '',
    status: 'scheduled',
    side_effects: '',
    notes: '',
});

const initForm = useForm({ patient_id: '' });

const statusForm = useForm({
    status: 'given',
    given_date: new Date().toISOString().split('T')[0],
    batch_number: '',
    manufacturer: '',
    site_of_injection: '',
    side_effects: '',
    notes: '',
    supply_id: '',
});

function openNewModal() {
    newForm.reset();
    newForm.clearErrors();
    newForm.status = 'scheduled';
    newForm.scheduled_date = new Date().toISOString().split('T')[0];
    editingVaccination.value = null;
    showNewModal.value = true;
}

function openEditModal(v) {
    newForm.id = v.id;
    newForm.patient_id = v.patient_id;
    newForm.vaccine_name = v.vaccine_name;
    newForm.vaccine_name_ar = v.vaccine_name_ar;
    newForm.dose_number = v.dose_number;
    newForm.scheduled_age = v.scheduled_age;
    newForm.scheduled_date = v.scheduled_date?.split('T')[0] || '';
    newForm.given_date = v.given_date?.split('T')[0] || '';
    newForm.batch_number = v.batch_number || '';
    newForm.manufacturer = v.manufacturer || '';
    newForm.site_of_injection = v.site_of_injection || '';
    newForm.status = v.status;
    newForm.side_effects = v.side_effects || '';
    newForm.notes = v.notes || '';
    editingVaccination.value = v;
    showNewModal.value = true;
}

function submitNew() {
    newForm.post('/admin/pediatric/vaccinations', {
        preserveScroll: true,
        onSuccess: () => {
            showNewModal.value = false;
            newForm.reset();
        },
    });
}

function openInitModal() {
    initForm.reset();
    showInitModal.value = true;
}

function submitInit() {
    if (!initForm.patient_id) return;
    initForm.post(`/admin/pediatric/patients/${initForm.patient_id}/vaccinations/initialize`, {
        preserveScroll: true,
        onSuccess: () => { showInitModal.value = false; },
    });
}

function openStatusModal(v) {
    editingVaccination.value = v;
    statusForm.reset();
    statusForm.status = v.status === 'scheduled' ? 'given' : v.status;
    statusForm.given_date = new Date().toISOString().split('T')[0];
    statusForm.batch_number = v.batch_number || '';
    statusForm.manufacturer = v.manufacturer || '';
    showStatusModal.value = true;
}

function submitStatus() {
    if (!editingVaccination.value) return;
    statusForm.post(`/admin/pediatric/vaccinations/${editingVaccination.value.id}/status`, {
        preserveScroll: true,
        onSuccess: () => { showStatusModal.value = false; editingVaccination.value = null; },
    });
}

function deleteVaccination(v) {
    confirm(isRtl.value ? 'هل أنت متأكد من حذف هذا التطعيم؟' : 'Are you sure you want to delete this vaccination?', () => {
        router.post(`/admin/pediatric/vaccinations/${v.id}/delete`, {}, {
            preserveScroll: true,
        });
    });
}
</script>

<template>
    <div class="space-y-8 pb-12">
        <!-- ── Hero ──────────────────────────────────────── -->
        <div class="ped-hero relative overflow-hidden rounded-3xl bg-gradient-to-br from-[#1B365D] via-[#1B365D] to-[#0F2444] p-8 md:p-10">
            <div class="absolute -top-20 ltr:-right-20 rtl:-left-20 w-72 h-72 bg-emerald-400/20 rounded-full blur-3xl"></div>
            <div class="absolute -bottom-16 ltr:-left-16 rtl:-right-16 w-56 h-56 bg-emerald-300/15 rounded-full blur-3xl"></div>

            <div class="absolute ltr:right-8 rtl:left-8 top-8 opacity-10">
                <svg class="w-28 h-28 text-white ped-float" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="0.5" d="M9.75 3.104v5.714a2.25 2.25 0 01-.659 1.591L5 14.5M9.75 3.104c-.251.023-.501.05-.75.082m.75-.082a24.301 24.301 0 014.5 0m0 0v5.714c0 .597.237 1.17.659 1.591L19.8 15.3M14.25 3.104c.251.023.501.05.75.082M19.8 15.3l-1.57.393A9.065 9.065 0 0112 15a9.065 9.065 0 00-6.23.693L5 14.5m14.8.8l1.402 1.402c1.232 1.232.65 3.318-1.067 3.611A48.309 48.309 0 0112 21c-2.773 0-5.491-.235-8.135-.687-1.718-.293-2.3-2.379-1.067-3.61L5 14.5" />
                </svg>
            </div>

            <div class="relative z-10">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div class="ped-hero-up">
                        <div class="flex items-center gap-3">
                            <div class="w-14 h-14 rounded-xl bg-gradient-to-br from-[#C4A265] to-[#8B7043] flex items-center justify-center shadow-lg shadow-black/20">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.75 3.104v5.714a2.25 2.25 0 01-.659 1.591L5 14.5M9.75 3.104c-.251.023-.501.05-.75.082m.75-.082a24.301 24.301 0 014.5 0m0 0v5.714c0 .597.237 1.17.659 1.591L19.8 15.3M14.25 3.104c.251.023.501.05.75.082M19.8 15.3l-1.57.393A9.065 9.065 0 0112 15a9.065 9.065 0 00-6.23.693L5 14.5m14.8.8l1.402 1.402c1.232 1.232.65 3.318-1.067 3.611A48.309 48.309 0 0112 21c-2.773 0-5.491-.235-8.135-.687-1.718-.293-2.3-2.379-1.067-3.61L5 14.5" /></svg>
                            </div>
                            <div>
                                <h1 class="text-2xl md:text-3xl font-bold text-white">
                                    {{ isRtl ? 'التطعيمات' : 'Vaccinations' }}
                                </h1>
                                <p class="text-emerald-100/80 text-sm mt-0.5">
                                    {{ isRtl ? 'إدارة جدول تطعيمات الأطفال' : 'Manage pediatric vaccination schedule' }}
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="ped-hero-up flex flex-wrap items-center gap-2" style="animation-delay: 0.15s">
                        <button
                            @click="openNewModal"
                            class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm font-semibold text-emerald-700 bg-white hover:bg-emerald-50 shadow-lg transition-all duration-300"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                            {{ isRtl ? 'تطعيم جديد' : 'New Vaccination' }}
                        </button>
                        <button
                            @click="openInitModal"
                            class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm font-semibold text-white bg-white/15 hover:bg-white/25 ring-1 ring-white/30 shadow-lg transition-all duration-300 backdrop-blur-sm"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" /></svg>
                            {{ isRtl ? 'تفعيل الجدول' : 'Initialize Schedule' }}
                        </button>
                        <Link
                            href="/admin/pediatric"
                            class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm font-semibold text-white bg-white/15 hover:bg-white/25 ring-1 ring-white/30 hover:ring-white/50 shadow-lg transition-all duration-300 backdrop-blur-sm"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" /></svg>
                            {{ isRtl ? 'لوحة التحكم' : 'Dashboard' }}
                        </Link>
                    </div>
                </div>
            </div>
        </div>

        <!-- ── Stats Row ─────────────────────────────────── -->
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-4">
            <div
                v-for="(card, index) in statCards"
                :key="index"
                class="ped-card-enter relative bg-white rounded-2xl p-5 shadow-sm border border-gray-100/80 overflow-hidden"
                :style="{ animationDelay: `${index * 0.08}s` }"
            >
                <div :class="`absolute top-0 left-0 right-0 h-1 bg-gradient-to-r ${card.gradient} opacity-80`"></div>
                <p class="text-[13px] font-medium text-gray-500">{{ isRtl ? card.labelAr : card.labelEn }}</p>
                <p class="text-2xl font-bold text-gray-900 mt-1.5 tabular-nums">{{ card.value }}</p>
                <span v-if="card.isAlert" class="absolute top-3 ltr:right-3 rtl:left-3 w-2 h-2 rounded-full bg-red-500 animate-pulse"></span>
            </div>
        </div>

        <!-- ── Search + Filter ───────────────────────────── -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100/80 p-5">
            <div class="flex flex-col sm:flex-row gap-3">
                <div class="relative flex-1 max-w-md">
                    <svg class="absolute top-1/2 -translate-y-1/2 ltr:left-3 rtl:right-3 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                    <input
                        v-model="search"
                        type="text"
                        :placeholder="isRtl ? 'بحث بالمريض أو التطعيم...' : 'Search by patient or vaccine...'"
                        class="doctorato-input w-full ltr:pl-10 rtl:pr-10 pr-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-[#C4A265]/20 focus:border-[#1B365D] transition"
                    />
                </div>
                <select
                    v-model="statusFilter"
                    class="doctorato-input px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-[#C4A265]/20 focus:border-[#1B365D] transition bg-white"
                >
                    <option value="">{{ isRtl ? 'كل الحالات' : 'All Statuses' }}</option>
                    <option value="given">{{ isRtl ? 'تم التطعيم' : 'Given' }}</option>
                    <option value="scheduled">{{ isRtl ? 'مجدولة' : 'Scheduled' }}</option>
                    <option value="overdue">{{ isRtl ? 'متأخرة' : 'Overdue' }}</option>
                    <option value="missed">{{ isRtl ? 'فائتة' : 'Missed' }}</option>
                </select>
            </div>
        </div>

        <!-- ── Vaccinations Table ────────────────────────── -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100/80 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50/80">
                        <tr>
                            <th class="text-start px-5 py-3.5 font-semibold text-gray-500">{{ isRtl ? 'المريض' : 'Patient' }}</th>
                            <th class="text-start px-5 py-3.5 font-semibold text-gray-500">{{ isRtl ? 'التطعيم' : 'Vaccine' }}</th>
                            <th class="text-center px-5 py-3.5 font-semibold text-gray-500 hidden sm:table-cell">{{ isRtl ? 'الجرعة' : 'Dose' }}</th>
                            <th class="text-start px-5 py-3.5 font-semibold text-gray-500 hidden md:table-cell">{{ isRtl ? 'تاريخ الجدولة' : 'Scheduled' }}</th>
                            <th class="text-start px-5 py-3.5 font-semibold text-gray-500 hidden lg:table-cell">{{ isRtl ? 'تاريخ التطعيم' : 'Given Date' }}</th>
                            <th class="text-center px-5 py-3.5 font-semibold text-gray-500">{{ isRtl ? 'الحالة' : 'Status' }}</th>
                            <th class="text-start px-5 py-3.5 font-semibold text-gray-500 hidden xl:table-cell">{{ isRtl ? 'رقم الدفعة' : 'Batch #' }}</th>
                            <th class="text-center px-5 py-3.5 font-semibold text-gray-500">{{ isRtl ? 'إجراءات' : 'Actions' }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="v in (vaccinations?.data || [])"
                            :key="v.id"
                            class="border-b border-gray-50 hover:bg-emerald-50/30 transition-colors"
                        >
                            <td class="px-5 py-3.5">
                                <div>
                                    <p class="font-semibold text-gray-800">{{ v.patient?.full_name || v.patient_name || '-' }}</p>
                                    <p class="text-xs text-gray-400">{{ v.patient?.file_number || '' }}</p>
                                </div>
                            </td>
                            <td class="px-5 py-3.5 font-medium text-gray-700">{{ v.vaccine_name || v.vaccine?.name || '-' }}</td>
                            <td class="px-5 py-3.5 text-center hidden sm:table-cell">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-gray-100 text-gray-600 text-xs font-semibold">
                                    {{ v.dose_number || v.dose || '-' }}
                                </span>
                            </td>
                            <td class="px-5 py-3.5 text-gray-500 hidden md:table-cell">{{ formatDate(v.scheduled_date) }}</td>
                            <td class="px-5 py-3.5 text-gray-500 hidden lg:table-cell">{{ formatDate(v.given_date) }}</td>
                            <td class="px-5 py-3.5 text-center">
                                <span
                                    :class="`inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold ${getStatusStyle(v.status).bg} ${getStatusStyle(v.status).text}`"
                                >
                                    <span :class="`w-1.5 h-1.5 rounded-full ${getStatusStyle(v.status).dot}`" :style="v.status === 'overdue' ? 'animation: pulse 2s infinite' : ''"></span>
                                    {{ statusLabel(v.status) }}
                                </span>
                            </td>
                            <td class="px-5 py-3.5 text-gray-400 font-mono text-xs hidden xl:table-cell">{{ v.batch_number || '-' }}</td>
                            <td class="px-5 py-3.5 text-center">
                                <div class="inline-flex items-center gap-1">
                                    <button
                                        v-if="v.status === 'scheduled' || v.status === 'missed'"
                                        @click="openStatusModal(v)"
                                        :title="isRtl ? 'تسجيل التطعيم' : 'Record Given'"
                                        class="p-1.5 rounded-lg text-emerald-600 hover:bg-emerald-50 transition"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                                    </button>
                                    <button
                                        @click="openEditModal(v)"
                                        :title="isRtl ? 'تعديل' : 'Edit'"
                                        class="p-1.5 rounded-lg text-[#1B365D] hover:bg-slate-50 transition"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                                    </button>
                                    <button
                                        @click="deleteVaccination(v)"
                                        :title="isRtl ? 'حذف' : 'Delete'"
                                        class="p-1.5 rounded-lg text-red-600 hover:bg-red-50 transition"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="!vaccinations?.data?.length">
                            <td colspan="8" class="px-5 py-12 text-center text-gray-400">
                                <svg class="w-12 h-12 mx-auto text-gray-200 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9.75 3.104v5.714a2.25 2.25 0 01-.659 1.591L5 14.5M9.75 3.104c-.251.023-.501.05-.75.082m.75-.082a24.301 24.301 0 014.5 0m0 0v5.714c0 .597.237 1.17.659 1.591L19.8 15.3M14.25 3.104c.251.023.501.05.75.082" /></svg>
                                {{ isRtl ? 'لا توجد تطعيمات' : 'No vaccinations found' }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div v-if="paginationLinks.length > 3" class="flex items-center justify-center gap-1 px-5 py-4 border-t border-gray-100">
                <template v-for="(link, i) in paginationLinks" :key="i">
                    <Link
                        v-if="link.url"
                        :href="link.url"
                        class="px-3 py-1.5 rounded-lg text-sm transition"
                        :class="link.active ? 'bg-emerald-500 text-white font-semibold shadow-sm' : 'text-gray-600 hover:bg-gray-100'"
                        v-html="link.label"
                        preserve-state
                        preserve-scroll
                    />
                    <span
                        v-else
                        class="px-3 py-1.5 text-sm text-gray-300"
                        v-html="link.label"
                    />
                </template>
            </div>
        </div>

        <!-- Modal: New/Edit Vaccination -->
        <div v-if="showNewModal" v-focus-trap="() => (showNewModal = false)" role="dialog" aria-modal="true" class="fixed inset-0 z-50 flex items-center justify-center p-4" @click.self="showNewModal = false">
            <div class="absolute inset-0 bg-black/40 backdrop-blur-sm"></div>
            <div class="relative bg-white rounded-3xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
                <div class="sticky top-0 bg-white border-b border-gray-100 px-6 py-4 flex items-center justify-between">
                    <h3 class="text-lg font-bold text-gray-800">
                        {{ editingVaccination ? (isRtl ? 'تعديل تطعيم' : 'Edit Vaccination') : (isRtl ? 'تطعيم جديد' : 'New Vaccination') }}
                    </h3>
                    <button @click="showNewModal = false" class="text-gray-400 hover:text-gray-600" :aria-label="isRtl ? 'إغلاق' : 'Close'" :title="isRtl ? 'إغلاق' : 'Close'">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>
                <form @submit.prevent="submitNew" class="p-6 space-y-4">
                    <!-- Patient -->
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 mb-1">{{ isRtl ? 'المريض' : 'Patient' }} *</label>
                        <select v-model="newForm.patient_id" required class="doctorato-input w-full px-3 py-2 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-[#C4A265]/30 focus:border-[#1B365D]">
                            <option value="">{{ isRtl ? 'اختر المريض' : 'Select patient' }}</option>
                            <option v-for="p in pediatricPatients" :key="p.id" :value="p.id">{{ p.full_name }} {{ p.file_number ? `(${p.file_number})` : '' }}</option>
                        </select>
                        <p v-if="newForm.errors.patient_id" class="text-xs text-red-500 mt-1">{{ newForm.errors.patient_id }}</p>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 mb-1">{{ isRtl ? 'اسم اللقاح (إنجليزي)' : 'Vaccine Name (EN)' }} *</label>
                            <input v-model="newForm.vaccine_name" required class="doctorato-input w-full px-3 py-2 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-[#C4A265]/30" />
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 mb-1">{{ isRtl ? 'اسم اللقاح (عربي)' : 'Vaccine Name (AR)' }}</label>
                            <input v-model="newForm.vaccine_name_ar" class="doctorato-input w-full px-3 py-2 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-[#C4A265]/30" />
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 mb-1">{{ isRtl ? 'رقم الجرعة' : 'Dose Number' }} *</label>
                            <input v-model="newForm.dose_number" required placeholder="Dose 1" class="doctorato-input w-full px-3 py-2 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-[#C4A265]/30" />
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 mb-1">{{ isRtl ? 'العمر المجدول' : 'Scheduled Age' }}</label>
                            <input v-model="newForm.scheduled_age" placeholder="2 months" class="doctorato-input w-full px-3 py-2 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-[#C4A265]/30" />
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 mb-1">{{ isRtl ? 'تاريخ الجدولة' : 'Scheduled Date' }} *</label>
                            <input v-model="newForm.scheduled_date" type="date" required class="doctorato-input w-full px-3 py-2 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-[#C4A265]/30" />
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 mb-1">{{ isRtl ? 'تاريخ التطعيم' : 'Given Date' }}</label>
                            <input v-model="newForm.given_date" type="date" class="doctorato-input w-full px-3 py-2 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-[#C4A265]/30" />
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-500 mb-1">{{ isRtl ? 'الحالة' : 'Status' }} *</label>
                        <select v-model="newForm.status" required class="doctorato-input w-full px-3 py-2 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-[#C4A265]/30">
                            <option value="scheduled">{{ isRtl ? 'مجدول' : 'Scheduled' }}</option>
                            <option value="given">{{ isRtl ? 'تم التطعيم' : 'Given' }}</option>
                            <option value="missed">{{ isRtl ? 'فائت' : 'Missed' }}</option>
                            <option value="postponed">{{ isRtl ? 'مؤجل' : 'Postponed' }}</option>
                            <option value="contraindicated">{{ isRtl ? 'موانع طبية' : 'Contraindicated' }}</option>
                        </select>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 mb-1">{{ isRtl ? 'رقم الدفعة' : 'Batch #' }}</label>
                            <input v-model="newForm.batch_number" class="doctorato-input w-full px-3 py-2 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-[#C4A265]/30" />
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 mb-1">{{ isRtl ? 'الشركة المصنعة' : 'Manufacturer' }}</label>
                            <input v-model="newForm.manufacturer" class="doctorato-input w-full px-3 py-2 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-[#C4A265]/30" />
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-500 mb-1">{{ isRtl ? 'موقع الحقن' : 'Injection Site' }}</label>
                        <input v-model="newForm.site_of_injection" placeholder="Left arm" class="doctorato-input w-full px-3 py-2 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-[#C4A265]/30" />
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-500 mb-1">{{ isRtl ? 'الآثار الجانبية' : 'Side Effects' }}</label>
                        <textarea v-model="newForm.side_effects" rows="2" class="doctorato-input w-full px-3 py-2 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-[#C4A265]/30"></textarea>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-500 mb-1">{{ isRtl ? 'ملاحظات' : 'Notes' }}</label>
                        <textarea v-model="newForm.notes" rows="2" class="doctorato-input w-full px-3 py-2 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-[#C4A265]/30"></textarea>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-100">
                        <button type="button" @click="showNewModal = false" class="px-4 py-2 rounded-xl text-sm font-semibold text-gray-600 hover:bg-gray-100 transition">
                            {{ isRtl ? 'إلغاء' : 'Cancel' }}
                        </button>
                        <button type="submit" :disabled="newForm.processing" class="px-5 py-2 rounded-xl text-sm font-bold text-white bg-gradient-to-r from-emerald-500 to-emerald-600 hover:from-emerald-600 hover:to-emerald-700 shadow-sm transition disabled:opacity-50">
                            {{ newForm.processing ? (isRtl ? 'جاري الحفظ...' : 'Saving...') : (isRtl ? 'حفظ' : 'Save') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Modal: Initialize Schedule -->
        <div v-if="showInitModal" v-focus-trap="() => (showInitModal = false)" role="dialog" aria-modal="true" class="fixed inset-0 z-50 flex items-center justify-center p-4" @click.self="showInitModal = false">
            <div class="absolute inset-0 bg-black/40 backdrop-blur-sm"></div>
            <div class="relative bg-white rounded-3xl shadow-2xl w-full max-w-md">
                <div class="px-6 py-5 border-b border-gray-100">
                    <h3 class="text-lg font-bold text-gray-800">{{ isRtl ? 'تفعيل جدول التطعيمات' : 'Initialize Vaccination Schedule' }}</h3>
                    <p class="text-xs text-gray-500 mt-1">{{ isRtl ? 'سيتم إنشاء جدول التطعيمات الكامل وفق منظمة الصحة العالمية' : 'Creates the full WHO vaccination schedule for the patient' }}</p>
                </div>
                <form @submit.prevent="submitInit" class="p-6 space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 mb-1">{{ isRtl ? 'المريض' : 'Patient' }} *</label>
                        <select v-model="initForm.patient_id" required class="doctorato-input w-full px-3 py-2 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-[#C4A265]/30 focus:border-[#1B365D]">
                            <option value="">{{ isRtl ? 'اختر المريض' : 'Select patient' }}</option>
                            <option v-for="p in pediatricPatients" :key="p.id" :value="p.id">{{ p.full_name }} {{ p.file_number ? `(${p.file_number})` : '' }}</option>
                        </select>
                    </div>
                    <div class="flex items-center justify-end gap-3 pt-3">
                        <button type="button" @click="showInitModal = false" class="px-4 py-2 rounded-xl text-sm font-semibold text-gray-600 hover:bg-gray-100">
                            {{ isRtl ? 'إلغاء' : 'Cancel' }}
                        </button>
                        <button type="submit" :disabled="initForm.processing" class="px-5 py-2 rounded-xl text-sm font-bold text-white bg-gradient-to-r from-emerald-500 to-emerald-600 shadow-sm disabled:opacity-50">
                            {{ initForm.processing ? '...' : (isRtl ? 'تفعيل' : 'Initialize') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Modal: Quick Status Update -->
        <div v-if="showStatusModal" v-focus-trap="() => (showStatusModal = false)" role="dialog" aria-modal="true" class="fixed inset-0 z-50 flex items-center justify-center p-4" @click.self="showStatusModal = false">
            <div class="absolute inset-0 bg-black/40 backdrop-blur-sm"></div>
            <div class="relative bg-white rounded-3xl shadow-2xl w-full max-w-md">
                <div class="px-6 py-5 border-b border-gray-100">
                    <h3 class="text-lg font-bold text-gray-800">{{ isRtl ? 'تسجيل التطعيم' : 'Record Vaccination' }}</h3>
                    <p v-if="editingVaccination" class="text-xs text-gray-500 mt-1">{{ editingVaccination.vaccine_name }} - {{ editingVaccination.patient?.full_name }}</p>
                </div>
                <form @submit.prevent="submitStatus" class="p-6 space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 mb-1">{{ isRtl ? 'الحالة' : 'Status' }} *</label>
                        <select v-model="statusForm.status" required class="doctorato-input w-full px-3 py-2 border border-gray-200 rounded-xl text-sm">
                            <option value="given">{{ isRtl ? 'تم التطعيم' : 'Given' }}</option>
                            <option value="missed">{{ isRtl ? 'فائت' : 'Missed' }}</option>
                            <option value="postponed">{{ isRtl ? 'مؤجل' : 'Postponed' }}</option>
                            <option value="contraindicated">{{ isRtl ? 'موانع طبية' : 'Contraindicated' }}</option>
                        </select>
                    </div>
                    <div v-if="statusForm.status === 'given'">
                        <label class="block text-xs font-semibold text-gray-500 mb-1">{{ isRtl ? 'تاريخ التطعيم' : 'Given Date' }} *</label>
                        <input v-model="statusForm.given_date" type="date" class="doctorato-input w-full px-3 py-2 border border-gray-200 rounded-xl text-sm" />
                    </div>
                    <div v-if="statusForm.status === 'given' && supplies.length">
                        <label class="block text-xs font-semibold text-gray-500 mb-1">{{ isRtl ? 'صنف اللقاح بالمخزون (يُخصم جرعة)' : 'Vaccine stock item (1 dose deducted)' }}</label>
                        <select v-model="statusForm.supply_id" class="doctorato-input w-full px-3 py-2 border border-gray-200 rounded-xl text-sm">
                            <option value="">{{ isRtl ? 'بدون خصم' : 'No deduction' }}</option>
                            <option v-for="s in supplies" :key="s.id" :value="s.id">{{ (isRtl ? (s.name_ar || s.name_en) : (s.name_en || s.name_ar)) }} ({{ isRtl ? 'المتاح' : 'avail' }}: {{ s.quantity }})</option>
                        </select>
                    </div>
                    <div v-if="statusForm.status === 'given'" class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 mb-1">{{ isRtl ? 'رقم الدفعة' : 'Batch #' }}</label>
                            <input v-model="statusForm.batch_number" class="doctorato-input w-full px-3 py-2 border border-gray-200 rounded-xl text-sm" />
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 mb-1">{{ isRtl ? 'الشركة' : 'Manufacturer' }}</label>
                            <input v-model="statusForm.manufacturer" class="doctorato-input w-full px-3 py-2 border border-gray-200 rounded-xl text-sm" />
                        </div>
                    </div>
                    <div v-if="statusForm.status === 'given'">
                        <label class="block text-xs font-semibold text-gray-500 mb-1">{{ isRtl ? 'موقع الحقن' : 'Injection Site' }}</label>
                        <input v-model="statusForm.site_of_injection" class="doctorato-input w-full px-3 py-2 border border-gray-200 rounded-xl text-sm" />
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 mb-1">{{ isRtl ? 'ملاحظات' : 'Notes' }}</label>
                        <textarea v-model="statusForm.notes" rows="2" class="doctorato-input w-full px-3 py-2 border border-gray-200 rounded-xl text-sm"></textarea>
                    </div>
                    <div class="flex items-center justify-end gap-3 pt-3 border-t border-gray-100">
                        <button type="button" @click="showStatusModal = false" class="px-4 py-2 rounded-xl text-sm font-semibold text-gray-600 hover:bg-gray-100">
                            {{ isRtl ? 'إلغاء' : 'Cancel' }}
                        </button>
                        <button type="submit" :disabled="statusForm.processing" class="px-5 py-2 rounded-xl text-sm font-bold text-white bg-gradient-to-r from-emerald-500 to-emerald-600 shadow-sm disabled:opacity-50">
                            {{ statusForm.processing ? '...' : (isRtl ? 'حفظ' : 'Save') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<style scoped>
.ped-hero-up {
    animation: pedHeroUp 0.7s cubic-bezier(0.16, 1, 0.3, 1) both;
}
@keyframes pedHeroUp {
    from { opacity: 0; transform: translateY(20px); }
    to   { opacity: 1; transform: translateY(0); }
}
.ped-float {
    animation: pedFloat 6s ease-in-out infinite;
}
@keyframes pedFloat {
    0%, 100% { transform: translateY(0); }
    50%      { transform: translateY(-12px); }
}
.ped-card-enter {
    animation: pedCardEnter 0.6s cubic-bezier(0.16, 1, 0.3, 1) both;
}
@keyframes pedCardEnter {
    from { opacity: 0; transform: translateY(16px); }
    to   { opacity: 1; transform: translateY(0); }
}
@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.5; }
}
</style>
