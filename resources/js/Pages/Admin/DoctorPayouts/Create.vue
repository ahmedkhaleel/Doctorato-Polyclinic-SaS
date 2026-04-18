<script setup>
import { ref, computed, watch } from 'vue';
import { useForm, Link, router, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { useCurrency } from '@/Composables/useCurrency.js';

defineOptions({ layout: AdminLayout });

const pg = usePage();
const locale = computed(() => pg.props.locale || 'ar');
const isRtl = computed(() => (pg.props.dir || 'rtl') === 'rtl');

const props = defineProps({
    doctors: Array,
    unpaidVisits: Array,
    filters: Object,
});

const doctorId = ref(props.filters?.doctor_id || '');
const dateFrom = ref(props.filters?.date_from || '');
const dateTo = ref(props.filters?.date_to || '');
const visits = ref(props.unpaidVisits || []);
const selectedIds = ref([]);
const loading = ref(false);

// Active step tracking
const currentStep = computed(() => {
    if (!doctorId.value) return 1;
    return 2;
});

const selectedDoctor = computed(() => {
    if (!doctorId.value) return null;
    return props.doctors?.find(d => d.id == doctorId.value) || null;
});

const form = useForm({
    doctor_id: props.filters?.doctor_id || '',
    period_start: '',
    period_end: '',
    visit_ids: [],
    deductions: 0,
    deduction_notes: '',
    notes: '',
});

// Fetch unpaid visits when doctor or date changes
async function fetchVisits() {
    if (!doctorId.value) {
        visits.value = [];
        selectedIds.value = [];
        return;
    }
    loading.value = true;
    try {
        const params = new URLSearchParams({ doctor_id: doctorId.value });
        if (dateFrom.value) params.append('date_from', dateFrom.value);
        if (dateTo.value) params.append('date_to', dateTo.value);
        const response = await fetch(`/admin/api/doctor-unpaid-visits?${params}`);
        visits.value = await response.json();
        selectedIds.value = [];
    } catch (e) {
        console.error('Failed to fetch visits', e);
        visits.value = [];
    } finally {
        loading.value = false;
    }
}

watch(doctorId, () => {
    form.doctor_id = doctorId.value;
    fetchVisits();
});

const allSelected = computed(() => visits.value.length > 0 && selectedIds.value.length === visits.value.length);

function toggleAll() {
    if (allSelected.value) {
        selectedIds.value = [];
    } else {
        selectedIds.value = visits.value.map(v => v.id);
    }
}

function toggleVisit(id) {
    const index = selectedIds.value.indexOf(id);
    if (index > -1) {
        selectedIds.value.splice(index, 1);
    } else {
        selectedIds.value.push(id);
    }
}

const selectedVisits = computed(() => visits.value.filter(v => selectedIds.value.includes(v.id)));
const totalRevenue = computed(() => selectedVisits.value.reduce((sum, v) => sum + (v.visit_amount || 0), 0));
const totalCommission = computed(() => selectedVisits.value.reduce((sum, v) => sum + (v.commission_amount || 0), 0));
const netAmount = computed(() => Math.max(totalCommission.value - (parseFloat(form.deductions) || 0), 0));

// Auto-calculate period from selected visits
const autoDateRange = computed(() => {
    if (!selectedVisits.value.length) return { start: '', end: '' };
    const dates = selectedVisits.value.map(v => v.visit_date).sort();
    return { start: dates[0], end: dates[dates.length - 1] };
});

const { formatCurrency, currencyCode } = useCurrency();

function formatDate(d) {
    if (!d) return '—';
    return new Date(d).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
}

function selectAllVisible() {
    selectedIds.value = visits.value.map(v => v.id);
}

function submit() {
    form.visit_ids = selectedIds.value;
    form.period_start = autoDateRange.value.start;
    form.period_end = autoDateRange.value.end;
    form.post('/admin/doctor-payouts');
}

const visitTypeConfig = {
    consultation: { label: 'Consultation', bg: 'bg-slate-50', text: 'text-[#1B365D]', icon: 'stethoscope' },
    session: { label: 'Session', bg: 'bg-slate-50', text: 'text-[#1B365D]', icon: 'session' },
    follow_up: { label: 'Follow Up', bg: 'bg-teal-50', text: 'text-teal-600', icon: 'follow-up' },
};
</script>

<template>
    <div>
        <!-- Header -->
        <div class="mb-8">
            <div class="flex items-center gap-2 mb-3">
                <Link href="/admin/doctor-payouts" class="inline-flex items-center gap-1.5 text-sm text-gray-400 hover:text-[#C4A265] transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
                    {{ $t('a_back_to_payouts') }}
                </Link>
            </div>
            <div class="flex items-start justify-between">
                <div>
                    <h1 class="text-xl md:text-2xl font-bold text-gray-900">{{ $t('a_create_doctor_payout') }}</h1>
                    <p class="text-sm text-gray-500 mt-1">{{ $t('a_create_payout_description') }}</p>
                </div>
            </div>
        </div>

        <!-- Steps Indicator -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100/80 p-4 mb-6">
            <div class="flex items-center">
                <!-- Step 1 -->
                <div class="flex items-center gap-3 flex-1">
                    <div class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold transition-all"
                        :class="doctorId ? 'bg-emerald-500 text-white' : 'bg-[#C4A265] text-white ring-4 ring-[#C4A265]/20'">
                        <svg v-if="doctorId" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" /></svg>
                        <span v-else>1</span>
                    </div>
                    <div>
                        <p class="text-xs font-bold uppercase tracking-wider" :class="doctorId ? 'text-emerald-600' : 'text-[#C4A265]'">{{ $t('a_select_doctor') }}</p>
                        <p class="text-[11px] text-gray-400">{{ $t('a_choose_doctor_date') }}</p>
                    </div>
                </div>
                <!-- Connector -->
                <div class="w-16 h-px mx-2" :class="doctorId ? 'bg-emerald-300' : 'bg-gray-200'"></div>
                <!-- Step 2 -->
                <div class="flex items-center gap-3 flex-1">
                    <div class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold transition-all"
                        :class="selectedIds.length > 0 ? 'bg-[#C4A265] text-white ring-4 ring-[#C4A265]/20' : (doctorId ? 'bg-gray-200 text-gray-500' : 'bg-gray-100 text-gray-400')">
                        2
                    </div>
                    <div>
                        <p class="text-xs font-bold uppercase tracking-wider" :class="selectedIds.length > 0 ? 'text-[#C4A265]' : 'text-gray-400'">{{ $t('a_select_visits') }}</p>
                        <p class="text-[11px] text-gray-400">{{ $t('a_pick_visits') }}</p>
                    </div>
                </div>
                <!-- Connector -->
                <div class="w-16 h-px mx-2" :class="selectedIds.length > 0 ? 'bg-[#C4A265]/40' : 'bg-gray-200'"></div>
                <!-- Step 3 -->
                <div class="flex items-center gap-3 flex-1">
                    <div class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold transition-all"
                        :class="selectedIds.length > 0 ? 'bg-gray-200 text-gray-500' : 'bg-gray-100 text-gray-400'">
                        3
                    </div>
                    <div>
                        <p class="text-xs font-bold uppercase tracking-wider text-gray-400">{{ $t('a_review_submit') }}</p>
                        <p class="text-[11px] text-gray-400">{{ $t('a_finalize_payout') }}</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid lg:grid-cols-3 gap-6">
            <!-- Left: Main Content Area -->
            <div class="lg:col-span-2 space-y-6">

                <!-- Step 1: Doctor & Date Selection -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100/80 overflow-hidden">
                    <div class="px-4 md:px-6 py-4 border-b border-gray-100 bg-gradient-to-r from-gray-50 to-white">
                        <div class="flex items-center gap-3">
                            <div class="w-7 h-7 rounded-lg bg-[#C4A265]/10 flex items-center justify-center">
                                <svg class="w-4 h-4 text-[#C4A265]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                </svg>
                            </div>
                            <div>
                                <h2 class="text-sm font-bold text-gray-800">{{ $t('a_doctor_date_range') }}</h2>
                                <p class="text-[11px] text-gray-400">{{ $t('a_select_doctor_date_hint') }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="p-6">
                        <div class="grid sm:grid-cols-3 gap-5">
                            <div>
                                <label class="text-[11px] font-semibold text-gray-500 uppercase tracking-wide mb-1.5 block">{{ $t('a_doctor') }} <span class="text-red-400">*</span></label>
                                <div class="relative">
                                    <select v-model="doctorId" class="doctorato-input w-full pl-10 pr-4 py-2.5 rounded-xl border-gray-200 text-sm font-medium focus:ring-2 focus:ring-[#C4A265]/30 focus:border-[#C4A265] transition-all appearance-none bg-white">
                                        <option value="">{{ $t('a_choose_doctor') }}</option>
                                        <option v-for="d in doctors" :key="d.id" :value="d.id">Dr. {{ d.name_en }}</option>
                                    </select>
                                    <div class="absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none">
                                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                                    </div>
                                </div>
                            </div>
                            <div>
                                <label class="text-[11px] font-semibold text-gray-500 uppercase tracking-wide mb-1.5 block">{{ $t('a_from_date') }}</label>
                                <div class="relative">
                                    <input type="date" v-model="dateFrom" @change="fetchVisits" :max="dateTo || undefined" class="doctorato-input w-full pl-10 pr-4 py-2.5 rounded-xl border-gray-200 text-sm focus:ring-2 focus:ring-[#C4A265]/30 focus:border-[#C4A265] transition-all" />
                                    <div class="absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none">
                                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                                    </div>
                                </div>
                            </div>
                            <div>
                                <label class="text-[11px] font-semibold text-gray-500 uppercase tracking-wide mb-1.5 block">{{ $t('a_to_date') }}</label>
                                <div class="relative">
                                    <input type="date" v-model="dateTo" @change="fetchVisits" :min="dateFrom || undefined" class="doctorato-input w-full pl-10 pr-4 py-2.5 rounded-xl border-gray-200 text-sm focus:ring-2 focus:ring-[#C4A265]/30 focus:border-[#C4A265] transition-all" />
                                    <div class="absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none">
                                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Selected Doctor Info Card -->
                        <transition enter-active-class="transition duration-300 ease-out" enter-from-class="opacity-0 -translate-y-2" enter-to-class="opacity-100 translate-y-0" leave-active-class="transition duration-200 ease-in" leave-from-class="opacity-100" leave-to-class="opacity-0">
                            <div v-if="selectedDoctor" class="mt-5 bg-gradient-to-r from-[#C4A265]/5 via-[#C4A265]/3 to-transparent rounded-xl p-4 border border-[#C4A265]/10 flex items-center gap-4">
                                <div class="w-11 h-11 rounded-full bg-gradient-to-br from-[#C4A265] to-[#D4B87A] flex items-center justify-center text-white font-bold text-sm shadow-sm">
                                    {{ selectedDoctor.name_en?.charAt(0) }}
                                </div>
                                <div class="flex-1">
                                    <p class="text-sm font-bold text-gray-800">Dr. {{ selectedDoctor.name_en }}</p>
                                    <p class="text-xs text-gray-400">{{ $t('a_commission_rate') }}: <span class="font-semibold text-[#C4A265]">{{ selectedDoctor.default_commission_percentage || 0 }}%</span></p>
                                </div>
                                <div class="text-right">
                                    <p class="text-[10px] uppercase tracking-wider text-gray-400 font-semibold">{{ $t('a_available_visits') }}</p>
                                    <p class="text-lg font-bold" :class="visits.length > 0 ? 'text-[#C4A265]' : 'text-gray-300'">{{ visits.length }}</p>
                                </div>
                            </div>
                        </transition>
                    </div>
                </div>

                <!-- Step 2: Visit Selection Table -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100/80 overflow-hidden">
                    <div class="px-4 md:px-6 py-4 border-b border-gray-100 bg-gradient-to-r from-gray-50 to-white">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="w-7 h-7 rounded-lg bg-[#C4A265]/10 flex items-center justify-center">
                                    <svg class="w-4 h-4 text-[#C4A265]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                                    </svg>
                                </div>
                                <div>
                                    <h2 class="text-sm font-bold text-gray-800">{{ $t('a_unpaid_visits') }}</h2>
                                    <p class="text-[11px] text-gray-400">{{ $t('a_select_visits_hint') }}</p>
                                </div>
                            </div>
                            <div v-if="visits.length > 0" class="flex items-center gap-2">
                                <button v-if="selectedIds.length < visits.length" @click="selectAllVisible"
                                    class="text-[11px] font-semibold text-[#C4A265] hover:text-[#B3914F] px-3 py-1.5 rounded-lg hover:bg-[#C4A265]/5 transition-all">
                                    {{ $t('a_select_all') }} ({{ visits.length }})
                                </button>
                                <button v-else @click="selectedIds = []"
                                    class="text-[11px] font-semibold text-gray-500 hover:text-gray-700 px-3 py-1.5 rounded-lg hover:bg-gray-50 transition-all">
                                    {{ $t('a_deselect_all') }}
                                </button>
                                <span class="text-[11px] text-gray-300">|</span>
                                <span class="text-[11px] font-semibold px-2.5 py-1 rounded-full" :class="selectedIds.length > 0 ? 'bg-[#C4A265]/10 text-[#C4A265]' : 'bg-gray-100 text-gray-400'">
                                    {{ selectedIds.length }} {{ $t('a_selected') }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Loading State -->
                    <div v-if="loading" class="py-16 text-center">
                        <div class="inline-flex flex-col items-center gap-3">
                            <div class="w-10 h-10 border-3 border-[#C4A265]/30 border-t-[#C4A265] rounded-full animate-spin"></div>
                            <p class="text-sm text-gray-400">{{ $t('a_loading_visits') }}</p>
                        </div>
                    </div>

                    <!-- Empty: No Doctor Selected -->
                    <div v-else-if="!doctorId" class="py-16 text-center">
                        <div class="inline-flex flex-col items-center gap-3">
                            <div class="w-16 h-16 rounded-2xl bg-gray-50 flex items-center justify-center">
                                <svg class="w-8 h-8 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-500">{{ $t('a_no_doctor_selected') }}</p>
                                <p class="text-xs text-gray-400 mt-0.5">{{ $t('a_select_doctor_hint') }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Empty: No Visits -->
                    <div v-else-if="visits.length === 0" class="py-16 text-center">
                        <div class="inline-flex flex-col items-center gap-3">
                            <div class="w-16 h-16 rounded-2xl bg-emerald-50 flex items-center justify-center">
                                <svg class="w-8 h-8 text-emerald-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-500">{{ $t('a_all_caught_up') }}</p>
                                <p class="text-xs text-gray-400 mt-0.5">{{ $t('a_no_unpaid_visits') }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Visits Table -->
                    <div v-else class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="bg-gray-50/80 border-b border-gray-100">
                                    <th class="px-4 py-3 text-left w-10">
                                        <input type="checkbox" :checked="allSelected" @change="toggleAll" class="w-4 h-4 rounded border-gray-300 text-[#C4A265] focus:ring-[#C4A265]/40 cursor-pointer transition" />
                                    </th>
                                    <th class="px-3 py-3 text-start text-[10px] font-semibold text-gray-400 uppercase tracking-wider">{{ $t('a_date') }}</th>
                                    <th class="px-3 py-3 text-start text-[10px] font-semibold text-gray-400 uppercase tracking-wider">{{ $t('a_patient') }}</th>
                                    <th class="px-3 py-3 text-start text-[10px] font-semibold text-gray-400 uppercase tracking-wider">{{ $t('a_type') }}</th>
                                    <th class="px-3 py-3 text-start text-[10px] font-semibold text-gray-400 uppercase tracking-wider">{{ $t('a_service') }}</th>
                                    <th class="px-3 py-3 text-end text-[10px] font-semibold text-gray-400 uppercase tracking-wider">{{ $t('a_revenue') }}</th>
                                    <th class="px-3 py-3 text-end text-[10px] font-semibold text-gray-400 uppercase tracking-wider">{{ $t('a_rate') }}</th>
                                    <th class="px-4 py-3 text-end text-[10px] font-semibold text-gray-400 uppercase tracking-wider">{{ $t('a_commission') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                <tr v-for="v in visits" :key="v.id"
                                    @click="toggleVisit(v.id)"
                                    class="cursor-pointer transition-all duration-150"
                                    :class="selectedIds.includes(v.id)
                                        ? 'bg-[#C4A265]/[0.04] hover:bg-[#C4A265]/[0.07] border-l-2 border-l-[#C4A265]'
                                        : 'hover:bg-gray-50/70 border-l-2 border-l-transparent'">
                                    <td class="px-4 py-3" @click.stop>
                                        <input type="checkbox" :checked="selectedIds.includes(v.id)" @change="toggleVisit(v.id)" class="w-4 h-4 rounded border-gray-300 text-[#C4A265] focus:ring-[#C4A265]/40 cursor-pointer transition" />
                                    </td>
                                    <td class="px-3 py-3">
                                        <span class="text-xs font-medium text-gray-700">{{ formatDate(v.visit_date) }}</span>
                                    </td>
                                    <td class="px-3 py-3">
                                        <span class="text-xs font-semibold text-gray-800">{{ v.patient_name }}</span>
                                    </td>
                                    <td class="px-3 py-3">
                                        <span class="inline-flex items-center gap-1 text-[10px] font-semibold px-2 py-0.5 rounded-full"
                                            :class="[visitTypeConfig[v.visit_type]?.bg || 'bg-gray-50', visitTypeConfig[v.visit_type]?.text || 'text-gray-500']">
                                            {{ visitTypeConfig[v.visit_type]?.label || v.visit_type }}
                                        </span>
                                    </td>
                                    <td class="px-3 py-3">
                                        <span class="text-xs text-gray-600">{{ v.service_name || '—' }}</span>
                                    </td>
                                    <td class="px-3 py-3 text-right">
                                        <span class="text-xs text-gray-500 font-medium">{{ formatCurrency(v.visit_amount) }}</span>
                                    </td>
                                    <td class="px-3 py-3 text-right">
                                        <span class="text-xs font-medium text-gray-500">{{ v.commission_rate }}%</span>
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <span class="text-xs font-bold text-[#C4A265]">{{ formatCurrency(v.commission_amount) }} <span class="font-normal text-gray-400">{{ currencyCode }}</span></span>
                                    </td>
                                </tr>
                            </tbody>
                            <!-- Table Footer Totals -->
                            <tfoot v-if="selectedIds.length > 0" class="bg-[#C4A265]/[0.04] border-t-2 border-[#C4A265]/20">
                                <tr>
                                    <td colspan="5" class="px-4 py-3 text-xs font-bold text-gray-600">
                                        Total ({{ selectedIds.length }} visit{{ selectedIds.length !== 1 ? 's' : '' }} selected)
                                    </td>
                                    <td class="px-3 py-3 text-right text-xs font-bold text-gray-700">{{ formatCurrency(totalRevenue) }}</td>
                                    <td class="px-3 py-3"></td>
                                    <td class="px-4 py-3 text-right text-sm font-bold text-[#C4A265]">{{ formatCurrency(totalCommission) }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Right: Summary Sidebar -->
            <div class="space-y-5">
                <!-- Payout Summary Card -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100/80 overflow-hidden sticky top-6">
                    <!-- Card Header -->
                    <div class="px-5 py-4 border-b border-gray-100 bg-gradient-to-r from-gray-50 to-white">
                        <div class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-[#C4A265]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                            </svg>
                            <h3 class="text-sm font-bold text-gray-800">{{ $t('a_payout_summary') }}</h3>
                        </div>
                    </div>

                    <div class="p-5">
                        <!-- Summary Lines -->
                        <div class="space-y-3 mb-5">
                            <div class="flex justify-between items-center text-sm">
                                <span class="text-gray-500 flex items-center gap-2">
                                    <span class="w-2 h-2 rounded-full bg-[#C4A265]/50"></span>
                                    {{ $t('a_selected_visits') }}
                                </span>
                                <span class="font-bold text-gray-800 tabular-nums">{{ selectedIds.length }}</span>
                            </div>
                            <div class="flex justify-between items-center text-sm">
                                <span class="text-gray-500 flex items-center gap-2">
                                    <span class="w-2 h-2 rounded-full bg-slate-400/50"></span>
                                    {{ $t('a_period') }}
                                </span>
                                <span class="text-xs font-medium text-gray-600 tabular-nums">
                                    <template v-if="autoDateRange.start">{{ formatDate(autoDateRange.start) }} → {{ formatDate(autoDateRange.end) }}</template>
                                    <template v-else>—</template>
                                </span>
                            </div>
                            <div class="h-px bg-gray-100 my-1"></div>
                            <div class="flex justify-between items-center text-sm">
                                <span class="text-gray-500">{{ $t('a_total_revenue') }}</span>
                                <span class="font-semibold text-gray-700 tabular-nums">{{ formatCurrency(totalRevenue) }} <span class="text-xs text-gray-400">{{ currencyCode }}</span></span>
                            </div>
                            <div class="flex justify-between items-center text-sm">
                                <span class="text-gray-500">{{ $t('a_total_commission') }}</span>
                                <span class="font-bold text-[#C4A265] tabular-nums">{{ formatCurrency(totalCommission) }}</span>
                            </div>
                        </div>

                        <!-- Deductions -->
                        <div class="border-t border-gray-100 pt-4 mb-4">
                            <label class="text-[11px] font-semibold text-gray-500 uppercase tracking-wide mb-1.5 flex items-center gap-1.5">
                                <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4" /></svg>
                                {{ $t('a_deductions') }} ({{ currencyCode }})
                            </label>
                            <input type="number" v-model="form.deductions" min="0" step="0.01" placeholder="0"
                                class="doctorato-input w-full rounded-xl border-gray-200 text-sm font-medium focus:ring-2 focus:ring-[#C4A265]/30 focus:border-[#C4A265] transition-all tabular-nums" />
                            <transition enter-active-class="transition duration-200 ease-out" enter-from-class="opacity-0 -translate-y-1 max-h-0" enter-to-class="opacity-100 translate-y-0 max-h-20" leave-active-class="transition duration-150 ease-in" leave-from-class="opacity-100" leave-to-class="opacity-0">
                                <textarea v-if="form.deductions> 0" v-model="form.deduction_notes" placeholder="Reason for deductions..."
                                    rows="2" class="mt-2 w-full rounded-xl border-gray-200 text-xs focus:ring-2 focus:ring-[#C4A265]/30 focus:border-[#C4A265] transition-all resize-none"></textarea>
                            </transition>
                        </div>

                        <!-- Net Amount -->
                        <div class="rounded-xl p-4 mb-4 relative overflow-hidden" :class="selectedIds.length > 0 ? 'bg-gradient-to-br from-[#C4A265]/10 via-[#D4B87A]/5 to-[#C4A265]/10 border border-[#C4A265]/20' : 'bg-gray-50 border border-gray-100'">
                            <div v-if="selectedIds.length > 0" class="absolute top-0 left-0 right-0 h-0.5 bg-gradient-to-r from-[#C4A265] to-[#D4B87A]"></div>
                            <div class="flex justify-between items-center">
                                <span class="text-sm font-bold text-gray-700">{{ $t('a_net_amount') }}</span>
                                <div class="text-right">
                                    <span class="text-xl font-bold tabular-nums" :class="selectedIds.length > 0 ? 'text-[#C4A265]' : 'text-gray-300'">
                                        {{ formatCurrency(netAmount) }}
                                    </span>
                                    <span class="text-sm ml-0.5" :class="selectedIds.length > 0 ? 'text-[#C4A265]/70' : 'text-gray-300'">{{ currencyCode }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Notes -->
                        <div class="mb-5">
                            <label class="text-[11px] font-semibold text-gray-500 uppercase tracking-wide mb-1.5 flex items-center gap-1.5">
                                <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z" /></svg>
                                {{ $t('a_notes') }}
                            </label>
                            <textarea v-model="form.notes" placeholder="Optional notes for this payout..."
                                rows="2" class="doctorato-input w-full rounded-xl border-gray-200 text-xs focus:ring-2 focus:ring-[#C4A265]/30 focus:border-[#C4A265] transition-all resize-none"></textarea>
                        </div>

                        <!-- Errors -->
                        <transition enter-active-class="transition duration-200" enter-from-class="opacity-0" enter-to-class="opacity-100">
                            <div v-if="form.errors && Object.keys(form.errors).length" class="mb-4 p-3 bg-red-50 rounded-xl border border-red-100">
                                <div class="flex items-center gap-2 mb-1">
                                    <svg class="w-4 h-4 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                    <span class="text-xs font-semibold text-red-600">{{ $t('a_validation_error') }}</span>
                                </div>
                                <p v-for="(err, key) in form.errors" :key="key" class="text-xs text-red-500 ml-6">{{ err }}</p>
                            </div>
                        </transition>

                        <!-- Submit Button -->
                        <button
                            @click="submit"
                            :disabled="selectedIds.length === 0 || form.processing"
                            class="w-full py-3 text-sm font-bold rounded-xl transition-all duration-200 flex items-center justify-center gap-2 group"
                            :class="selectedIds.length > 0
                                ? 'bg-gradient-to-r from-[#C4A265] to-[#B3914F] text-white hover:from-[#B3914F] hover:to-[#A28145] shadow-sm hover:shadow-md active:scale-[0.98]'
                                : 'bg-gray-100 text-gray-400 cursor-not-allowed'">
                            <svg v-if="!form.processing" class="w-4 h-4 transition-transform group-hover:scale-110" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            <div v-else class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin"></div>
                            {{ form.processing ? $t('a_creating_payout') : selectedIds.length > 0 ? $t('a_create_payout') + ' — ' + selectedIds.length + ' ' + $t('a_visits') : $t('a_select_visits_to_continue') }}
                        </button>

                        <!-- Disclaimer -->
                        <p class="text-[10px] text-gray-400 text-center mt-3 leading-relaxed">
                            {{ $t('a_payout_draft_disclaimer') }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
