<script setup>
import { ref, reactive, computed, watch, nextTick, onMounted, onUnmounted } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import QuickAddPatientModal from '@/Components/QuickAddPatientModal.vue';
import DoctorDatePicker from '@/Components/DoctorDatePicker.vue';
import PromoCodeInput from '@/Components/PromoCodeInput.vue';
import { usePermissions } from '@/Composables/usePermissions.js';
import { useCurrency } from '@/Composables/useCurrency.js';

const { can } = usePermissions();
const { formatCurrency, currencyCode } = useCurrency();
const page = usePage();
const locale = computed(() => page.props.locale || 'ar');
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const props = defineProps({
    patients: Array,
    serviceCategories: Array,
    services: Array,
    doctors: Array,
    doctorSchedules: Array,
    defaultDermatologyFee: { type: Number, default: 0 },
    defaultCosmeticFee: { type: Number, default: 0 },
    dermatologyConsultantFee: { type: Number, default: 0 },
    dermatologySpecialistFee: { type: Number, default: 0 },
    cosmeticConsultationFee: { type: Number, default: 0 },
    followupFee: { type: Number, default: 0 },
    followupWindowDays: { type: Number, default: 15 },
    dentalConsultantFee: { type: Number, default: 0 },
    dentalSpecialistFee: { type: Number, default: 0 },
});

/* ------------------------------------------------------------------ */
/*  State                                                              */
/* ------------------------------------------------------------------ */

const currentStep = ref(1);
const totalSteps = 4;
const processing = ref(false);
const errors = ref({});

// Booking Type
const bookingType = ref('service');

// Step 1 - Patient
const patientSearch = ref('');
const showPatientDropdown = ref(false);
const selectedPatient = ref(null);

// Auto-select patient from query param (e.g. from patient profile)
const urlParams = new URLSearchParams(window.location.search);
const preselectedPatientId = urlParams.get('patient_id');
if (preselectedPatientId && props.patients) {
    const found = props.patients.find(p => p.id == preselectedPatientId);
    if (found) {
        selectedPatient.value = found;
        patientSearch.value = found.full_name;
    }
}

// Step 2 - Services
const serviceRows = reactive([createServiceRow()]);

// Step 3 - Appointments (keyed by service row index)
const appointmentData = reactive({});

// Step 4 - Notes & Promo
const notes = ref('');
const promoCode = ref('');

/* ------------------------------------------------------------------ */
/*  Helpers                                                            */
/* ------------------------------------------------------------------ */

function createServiceRow() {
    return {
        service_id: '',
        doctor_id: '',
        sessions_count: 1,
        unit_price: 0,
        discount_per_session: 0,
        notes: '',
    };
}

function jsToSystemDay(jsDay) {
    // JS getDay(): 0=Sun,1=Mon,2=Tue,3=Wed,4=Thu,5=Fri,6=Sat
    // System:       0=Sat,1=Sun,2=Mon,3=Tue,4=Wed,5=Thu,6=Fri
    const map = { 0: 1, 1: 2, 2: 3, 3: 4, 4: 5, 5: 6, 6: 0 };
    return map[jsDay];
}

function serviceRowTotal(row) {
    const price = Number(row.unit_price) || 0;
    const discount = Number(row.discount_per_session) || 0;
    const sessions = Number(row.sessions_count) || 0;
    return Math.max(0, (price - discount) * sessions);
}

const grandTotal = computed(() => {
    return serviceRows.reduce((sum, row) => sum + serviceRowTotal(row), 0);
});

function getService(id) {
    return props.services.find(s => s.id == id);
}

function getDoctor(id) {
    return props.doctors.find(d => d.id == id);
}


/* ── Module availability ── */
const modules = computed(() => page.props.modules || {});
const isDentalEnabled = computed(() => {
    const dental = Object.values(modules.value).find(m => m.slug === 'dental');
    return dental?.enabled ?? false;
});
const isPediatricEnabled = computed(() => {
    const ped = Object.values(modules.value).find(m => m.slug === 'pediatric');
    return ped?.enabled ?? false;
});

const isConsultation = computed(() =>
    ['dermatology_consultation', 'cosmetic_consultation', 'dental_consultation', 'pediatric_consultation'].includes(bookingType.value)
);

const isDental = computed(() =>
    bookingType.value === 'dental_consultation' || bookingType.value === 'dental_service'
);

const filteredServices = computed(() => {
    if (isDental.value) return (props.services || []).filter(s => s.module === 'dental');
    return (props.services || []).filter(s => !s.module || s.module === 'derma');
});

const filteredServiceCategories = computed(() => {
    if (isDental.value) {
        return (props.serviceCategories || []).map(cat => ({
            ...cat,
            services: (cat.services || []).filter(s => s.module === 'dental')
        })).filter(cat => cat.services.length > 0);
    }
    return (props.serviceCategories || []).map(cat => ({
        ...cat,
        services: (cat.services || []).filter(s => !s.module || s.module === 'derma')
    })).filter(cat => cat.services.length > 0);
});

const filteredDoctors = computed(() => {
    if (isDental.value) return (props.doctors || []).filter(d => d.module === 'dental');
    return (props.doctors || []).filter(d => !d.module || d.module === 'derma');
});

function getConsultationFeeForDoctor(doctorId) {
    const doctor = getDoctor(doctorId);
    if (bookingType.value === 'dermatology_consultation') {
        // Check follow-up eligibility first
        if (followUpInfo.value?.eligible) return props.followupFee || 0;
        // Use settings-based pricing by doctor type
        if (doctor?.doctor_type === 'consultant') return props.dermatologyConsultantFee || props.defaultDermatologyFee || 0;
        if (doctor?.doctor_type === 'specialist') return props.dermatologySpecialistFee || props.defaultDermatologyFee || 0;
        return props.dermatologyConsultantFee || props.defaultDermatologyFee || 0;
    }
    if (bookingType.value === 'cosmetic_consultation') {
        return props.cosmeticConsultationFee || props.defaultCosmeticFee || 0;
    }
    if (bookingType.value === 'dental_consultation') {
        if (doctor?.doctor_type === 'consultant') return props.dentalConsultantFee || 0;
        if (doctor?.doctor_type === 'specialist') return props.dentalSpecialistFee || 0;
        return props.dentalConsultantFee || 0;
    }
    return 0;
}

// Watch bookingType changes to reset service rows for consultation
watch(bookingType, (newType) => {
    // Reset service rows
    serviceRows.splice(0, serviceRows.length, createServiceRow());
    // Clear appointments
    Object.keys(appointmentData).forEach(k => delete appointmentData[k]);

    if (newType === 'dermatology_consultation') {
        serviceRows[0].sessions_count = 1;
        serviceRows[0].service_id = '';
        serviceRows[0].unit_price = followUpInfo.value?.eligible
            ? (props.followupFee || 0)
            : (props.dermatologyConsultantFee || props.defaultDermatologyFee || 0);
    } else if (newType === 'cosmetic_consultation') {
        serviceRows[0].sessions_count = 1;
        serviceRows[0].service_id = '';
        serviceRows[0].unit_price = props.cosmeticConsultationFee || props.defaultCosmeticFee || 0;
    } else if (newType === 'dental_consultation') {
        serviceRows[0].sessions_count = 1;
        serviceRows[0].service_id = '';
        serviceRows[0].unit_price = props.dentalConsultantFee || 0;
    } else if (newType === 'dental_service') {
        serviceRows[0].sessions_count = 1;
        serviceRows[0].service_id = '';
        serviceRows[0].unit_price = 0;
    }
});

/* ------------------------------------------------------------------ */
/*  Step 1 - Patient                                                   */
/* ------------------------------------------------------------------ */

const filteredPatients = computed(() => {
    if (!patientSearch.value) return (props.patients || []).slice(0, 30);
    const s = patientSearch.value.toLowerCase();
    return (props.patients || []).filter(p =>
        p.full_name?.toLowerCase().includes(s) ||
        p.phone?.includes(s) ||
        p.file_number?.includes(s)
    ).slice(0, 30);
});

const followUpInfo = ref(null);

function selectPatient(p) {
    selectedPatient.value = p;
    patientSearch.value = p.full_name;
    showPatientDropdown.value = false;
    checkFollowUpEligibility(p.id);
}

async function checkFollowUpEligibility(patientId) {
    followUpInfo.value = null;
    try {
        const res = await fetch(`/admin/bookings/check-followup?patient_id=${patientId}`);
        if (res.ok) {
            const data = await res.json();
            followUpInfo.value = data.follow_up;
            // Auto-apply follow-up fee if eligible and currently on dermatology
            if (data.follow_up?.eligible && bookingType.value === 'dermatology_consultation') {
                serviceRows[0].unit_price = props.followupFee || 0;
            }
        }
    } catch { /* ignore */ }
}

function clearPatient() {
    selectedPatient.value = null;
    patientSearch.value = '';
    followUpInfo.value = null;
}

function handlePatientClickOutside(e) {
    // We handle this via @blur with a small delay
}

/* Quick Add Patient Modal */
const showNewPatientModal = ref(false);

function onPatientCreated(patient) {
    selectPatient(patient);
    if (props.patients && !props.patients.find(p => p.id === patient.id)) {
        props.patients.unshift(patient);
    }
}

/* ------------------------------------------------------------------ */
/*  Step 2 - Services                                                  */
/* ------------------------------------------------------------------ */

function addServiceRow() {
    serviceRows.push(createServiceRow());
}

function removeServiceRow(index) {
    if (serviceRows.length > 1) {
        serviceRows.splice(index, 1);
        // Clean up appointment data for removed and re-index
        const newAppointments = {};
        Object.keys(appointmentData).forEach(key => {
            const k = Number(key);
            if (k < index) {
                newAppointments[k] = appointmentData[k];
            } else if (k > index) {
                newAppointments[k - 1] = appointmentData[k];
            }
        });
        // Clear and re-assign
        Object.keys(appointmentData).forEach(k => delete appointmentData[k]);
        Object.assign(appointmentData, newAppointments);
    }
}

// Auto-fill when service selected
function onServiceChange(index) {
    const row = serviceRows[index];
    const svc = getService(row.service_id);
    if (svc) {
        row.unit_price = parseFloat(svc.price_after_discount) || parseFloat(svc.price) || 0;
        row.sessions_count = svc.default_sessions || 1;
    }
}

// Auto-fill consultation fee when doctor changes (for consultation bookings)
function onConsultationDoctorChange(index) {
    const row = serviceRows[index];
    row.unit_price = getConsultationFeeForDoctor(row.doctor_id);
}

// Watch session count changes to sync appointment slots
watch(
    () => serviceRows.map(r => ({ sessions_count: r.sessions_count, service_id: r.service_id, doctor_id: r.doctor_id })),
    (newVal) => {
        newVal.forEach((row, index) => {
            const count = Number(row.sessions_count) || 0;
            if (!appointmentData[index]) {
                appointmentData[index] = [];
            }
            const current = appointmentData[index];
            const doctorId = serviceRows[index].doctor_id;
            // Grow or shrink to match sessions_count
            while (current.length < count) {
                current.push({
                    doctor_id: doctorId || '',
                    date: '',
                    start_time: '',
                    end_time: '',
                    availableSlots: [],
                    loadingSlots: false,
                    scheduleWarning: '',
                });
            }
            while (current.length > count) {
                current.pop();
            }
            // Update doctor for new slots
            current.forEach(apt => {
                if (!apt.doctor_id && doctorId) {
                    apt.doctor_id = doctorId;
                }
            });
        });
    },
    { deep: true, immediate: true }
);

/* ------------------------------------------------------------------ */
/*  Step 3 - Appointments                                              */
/* ------------------------------------------------------------------ */

function getDoctorScheduleForDay(doctorId, systemDay) {
    return (props.doctorSchedules || []).find(
        s => s.doctor_id == doctorId && s.day_of_week == systemDay
    );
}

function checkDoctorAvailability(serviceIndex, aptIndex) {
    const apt = appointmentData[serviceIndex]?.[aptIndex];
    if (!apt) return;
    apt.scheduleWarning = '';
    if (!apt.doctor_id || !apt.date) return;

    const dateObj = new Date(apt.date + 'T00:00:00');
    const jsDay = dateObj.getDay();
    const systemDay = jsToSystemDay(jsDay);
    const schedule = getDoctorScheduleForDay(apt.doctor_id, systemDay);

    if (!schedule) {
        const dayNames = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
        apt.scheduleWarning = `Doctor does not work on ${dayNames[jsDay]}s`;
        apt.availableSlots = [];
        apt.start_time = '';
        apt.end_time = '';
        return;
    }

    fetchTimeSlots(serviceIndex, aptIndex);
}

async function fetchTimeSlots(serviceIndex, aptIndex) {
    const apt = appointmentData[serviceIndex]?.[aptIndex];
    if (!apt || !apt.doctor_id || !apt.date) return;

    const svc = serviceRows[serviceIndex].service_id ? getService(serviceRows[serviceIndex].service_id) : null;
    const duration = svc?.session_duration_minutes || 30;

    apt.loadingSlots = true;
    apt.availableSlots = [];
    apt.start_time = '';
    apt.end_time = '';

    try {
        const params = new URLSearchParams({
            doctor_id: apt.doctor_id,
            date: apt.date,
            duration: duration,
        });
        const response = await fetch(`/api/time-slots?${params.toString()}`);
        if (response.ok) {
            const data = await response.json();
            apt.availableSlots = data.slots || data || [];
        } else {
            apt.availableSlots = [];
        }
    } catch (err) {
        apt.availableSlots = [];
    } finally {
        apt.loadingSlots = false;
    }
}

function selectTimeSlot(serviceIndex, aptIndex, slot) {
    const apt = appointmentData[serviceIndex][aptIndex];
    apt.start_time = slot.start_time || slot.start;
    apt.end_time = slot.end_time || slot.end;
}

function formatTime12h(time24) {
    if (!time24) return '';
    const [h, m] = time24.split(':');
    const hour = parseInt(h);
    const period = hour >= 12 ? 'PM' : 'AM';
    const hour12 = hour === 0 ? 12 : hour > 12 ? hour - 12 : hour;
    return `${hour12}:${m} ${period}`;
}

function onAppointmentDoctorChange(serviceIndex, aptIndex) {
    const apt = appointmentData[serviceIndex]?.[aptIndex];
    if (!apt) return;
    apt.availableSlots = [];
    apt.start_time = '';
    apt.end_time = '';
    apt.scheduleWarning = '';
    if (apt.date) {
        checkDoctorAvailability(serviceIndex, aptIndex);
    }
}

function onAppointmentDateChange(serviceIndex, aptIndex) {
    checkDoctorAvailability(serviceIndex, aptIndex);
}

/* ------------------------------------------------------------------ */
/*  Navigation                                                         */
/* ------------------------------------------------------------------ */

function canProceed(step) {
    if (step === 1) return !!selectedPatient.value;
    if (step === 2) {
        if (isConsultation.value) {
            return serviceRows.length > 0 && serviceRows[0].doctor_id && serviceRows[0].unit_price > 0;
        }
        return serviceRows.length > 0 && serviceRows.every(r => r.service_id && r.doctor_id && r.sessions_count > 0);
    }
    if (step === 3) {
        for (let i = 0; i < serviceRows.length; i++) {
            const apts = appointmentData[i] || [];
            if (apts.some(a => !a.date || !a.start_time || !a.end_time)) return false;
        }
        return true;
    }
    return true;
}

function nextStep() {
    if (currentStep.value < totalSteps && canProceed(currentStep.value)) {
        currentStep.value++;
    }
}

function prevStep() {
    if (currentStep.value > 1) {
        currentStep.value--;
    }
}

function goToStep(step) {
    // Allow going to any previously completed step or the current step
    if (step <= currentStep.value) {
        currentStep.value = step;
    }
}

/* ------------------------------------------------------------------ */
/*  Submit                                                             */
/* ------------------------------------------------------------------ */

function buildPayload() {
    return {
        patient_id: selectedPatient.value?.id,
        booking_type: bookingType.value,
        notes: notes.value,
        promo_code: promoCode.value || null,
        services: serviceRows.map((row, index) => ({
            service_id: row.service_id ? Number(row.service_id) : null,
            doctor_id: Number(row.doctor_id),
            sessions_count: Number(row.sessions_count),
            unit_price: Number(row.unit_price),
            discount_per_session: Number(row.discount_per_session),
            notes: row.notes || '',
            appointments: (appointmentData[index] || []).map(apt => ({
                doctor_id: Number(apt.doctor_id),
                date: apt.date,
                start_time: apt.start_time,
                end_time: apt.end_time,
            })),
        })),
    };
}

function submit() {
    processing.value = true;
    errors.value = {};

    router.post('/admin/bookings', buildPayload(), {
        onError: (errs) => {
            errors.value = errs;
            processing.value = false;
        },
        onFinish: () => {
            processing.value = false;
        },
    });
}

/* ------------------------------------------------------------------ */
/*  Step labels                                                        */
/* ------------------------------------------------------------------ */

const stepLabels = computed(() => [
    page.props.translations?.['a_select_patient'] || 'Select Patient',
    isConsultation.value ? (page.props.translations?.['a_consultation_details'] || 'Consultation Details') : (page.props.translations?.['a_add_services'] || 'Add Services'),
    page.props.translations?.['a_set_appointments'] || 'Set Appointments',
    page.props.translations?.['a_confirm'] || 'Confirm',
]);

/* ------------------------------------------------------------------ */
/*  Searchable Dropdown System                                         */
/* ------------------------------------------------------------------ */

const openDropdown = ref(null);
const ddSearch = reactive({});

function toggleDropdown(key) {
    if (openDropdown.value === key) {
        openDropdown.value = null;
    } else {
        openDropdown.value = key;
        ddSearch[key] = '';
        nextTick(() => {
            const el = document.querySelector(`.bk-dd-search-${key}`);
            if (el) el.focus();
        });
    }
}

function closeDropdown() {
    openDropdown.value = null;
}

function handleClickOutside(e) {
    if (!e.target.closest('.bk-dd')) {
        openDropdown.value = null;
    }
}

onMounted(() => document.addEventListener('click', handleClickOutside));
onUnmounted(() => document.removeEventListener('click', handleClickOutside));

/* Filtered doctors for search */
function searchedDoctors(key) {
    const q = (ddSearch[key] || '').toLowerCase();
    if (!q) return filteredDoctors.value;
    return filteredDoctors.value.filter(d =>
        (d.name_en || '').toLowerCase().includes(q) ||
        (d.name_ar || '').toLowerCase().includes(q)
    );
}

/* Filtered services for search (flat) */
function searchedServices(key) {
    const q = (ddSearch[key] || '').toLowerCase();
    if (!q) return filteredServices.value;
    return filteredServices.value.filter(s =>
        (s.name_en || '').toLowerCase().includes(q) ||
        (s.name_ar || '').toLowerCase().includes(q)
    );
}

/* Filtered service categories for search */
function searchedServiceCategories(key) {
    const q = (ddSearch[key] || '').toLowerCase();
    if (!q) return filteredServiceCategories.value;
    return filteredServiceCategories.value.map(cat => ({
        ...cat,
        services: (cat.services || []).filter(s =>
            (s.name_en || '').toLowerCase().includes(q) ||
            (s.name_ar || '').toLowerCase().includes(q) ||
            (cat.name_en || '').toLowerCase().includes(q) ||
            (cat.name_ar || '').toLowerCase().includes(q)
        )
    })).filter(cat => cat.services.length > 0);
}

function getDoctorLabel(id) {
    const d = getDoctor(id);
    if (!d) return '';
    return isRtl.value ? (d.name_ar || d.name_en) : (d.name_en || d.name_ar);
}

function getServiceLabel(id) {
    const s = getService(id);
    if (!s) return '';
    return isRtl.value ? (s.name_ar || s.name_en) : (s.name_en || s.name_ar);
}
</script>

<template>
    <AdminLayout :title="$t('a_new_booking')">
        <div>
            <!-- Header -->
            <div class="flex items-center gap-3 mb-6">
                <Link href="/admin/bookings" class="text-gray-400 hover:text-gray-600 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                </Link>
                <div>
                    <h1 class="text-xl md:text-2xl font-bold text-gray-900">{{ $t('a_new_booking') }}</h1>
                    <p class="text-sm text-gray-500 mt-1">{{ $t('a_create_booking_subtitle') }}</p>
                </div>
            </div>

            <!-- Booking Type Selector -->
            <div class="mb-6">
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100/80 p-5 max-w-4xl mx-auto">
                    <h3 class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-3">{{ $t('a_booking_type') }}</h3>
                    <div class="grid gap-3 grid-cols-2 sm:grid-cols-3 lg:grid-cols-4" :class="{ 'xl:grid-cols-5': isDentalEnabled || isPediatricEnabled }">
                        <button
                            type="button"
                            @click="bookingType = 'dermatology_consultation'"
                            :class="[
                                'p-3 rounded-xl border-2 transition-all text-start',
                                bookingType === 'dermatology_consultation'
                                    ? 'border-[#C4A265] bg-amber-50/50'
                                    : 'border-gray-200 hover:border-gray-300'
                            ]"
                        >
                            <p class="text-sm font-semibold text-gray-800">{{ $t('a_dermatology_consultation') }}</p>
                        </button>
                        <button
                            type="button"
                            @click="bookingType = 'cosmetic_consultation'"
                            :class="[
                                'p-3 rounded-xl border-2 transition-all text-start',
                                bookingType === 'cosmetic_consultation'
                                    ? 'border-[#C4A265] bg-amber-50/50'
                                    : 'border-gray-200 hover:border-gray-300'
                            ]"
                        >
                            <p class="text-sm font-semibold text-gray-800">{{ $t('a_cosmetic_consultation') }}</p>
                        </button>
                        <button
                            type="button"
                            @click="bookingType = 'service'"
                            :class="[
                                'p-3 rounded-xl border-2 transition-all text-start',
                                bookingType === 'service'
                                    ? 'border-[#C4A265] bg-amber-50/50'
                                    : 'border-gray-200 hover:border-gray-300'
                            ]"
                        >
                            <p class="text-sm font-semibold text-gray-800">{{ $t('a_book_service') }}</p>
                        </button>
                        <button
                            v-if="isDentalEnabled"
                            type="button"
                            @click="bookingType = 'dental_consultation'"
                            :class="[
                                'p-3 rounded-xl border-2 transition-all text-start',
                                bookingType === 'dental_consultation'
                                    ? 'border-[#1B365D] bg-slate-50/50'
                                    : 'border-gray-200 hover:border-gray-300'
                            ]"
                        >
                            <p class="text-sm font-semibold text-gray-800">{{ $t('a_dental_consultation') }}</p>
                        </button>
                        <button
                            v-if="isDentalEnabled"
                            type="button"
                            @click="bookingType = 'dental_service'"
                            :class="[
                                'p-3 rounded-xl border-2 transition-all text-start',
                                bookingType === 'dental_service'
                                    ? 'border-[#1B365D] bg-slate-50/50'
                                    : 'border-gray-200 hover:border-gray-300'
                            ]"
                        >
                            <p class="text-sm font-semibold text-gray-800">{{ $t('a_dental_service') }}</p>
                        </button>
                        <button
                            v-if="isPediatricEnabled"
                            type="button"
                            @click="bookingType = 'pediatric_consultation'"
                            :class="[
                                'p-3 rounded-xl border-2 transition-all text-start',
                                bookingType === 'pediatric_consultation'
                                    ? 'border-emerald-500 bg-emerald-50/50'
                                    : 'border-gray-200 hover:border-gray-300'
                            ]"
                        >
                            <p class="text-sm font-semibold text-gray-800">{{ isRtl ? 'كشف أطفال' : 'Pediatric Consultation' }}</p>
                        </button>
                        <button
                            v-if="isPediatricEnabled"
                            type="button"
                            @click="bookingType = 'pediatric_service'"
                            :class="[
                                'p-3 rounded-xl border-2 transition-all text-start',
                                bookingType === 'pediatric_service'
                                    ? 'border-emerald-500 bg-emerald-50/50'
                                    : 'border-gray-200 hover:border-gray-300'
                            ]"
                        >
                            <p class="text-sm font-semibold text-gray-800">{{ isRtl ? 'خدمة أطفال' : 'Pediatric Service' }}</p>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Follow-up Eligibility Banner -->
            <div v-if="followUpInfo?.eligible && bookingType === 'dermatology_consultation'" class="mb-6 max-w-4xl mx-auto">
                <div class="flex items-start gap-3 px-5 py-4 bg-teal-50 border border-teal-200 rounded-2xl">
                    <div class="w-8 h-8 rounded-lg bg-teal-100 flex items-center justify-center flex-shrink-0 mt-0.5">
                        <svg class="w-4 h-4 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    </div>
                    <div class="flex-1">
                        <p class="text-sm font-semibold text-teal-800">{{ $t('a_followup_visit_detected') }}</p>
                        <p class="text-xs text-teal-600 mt-0.5">
                            This patient had a dermatology consultation on {{ new Date(followUpInfo.last_visit_date).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' }) }}.
                            The reduced follow-up fee of <span class="font-bold">{{ formatCurrency(props.followupFee || 0) }}</span> has been auto-applied.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Step Indicator -->
            <div class="mb-8">
                <div class="flex items-center justify-between max-w-2xl mx-auto">
                    <template v-for="(label, index) in stepLabels" :key="index">
                        <button
                            @click="goToStep(index + 1)"
                            :class="[
                                'flex flex-col items-center gap-2 group',
                                index + 1 <= currentStep ? 'cursor-pointer' : 'cursor-default'
                            ]"
                        >
                            <div
                                :class="[
                                    'w-10 h-10 rounded-full flex items-center justify-center text-sm font-bold transition-all duration-300',
                                    currentStep === index + 1
                                        ? 'bg-[#C4A265] text-white shadow-lg'
                                        : currentStep > index + 1
                                            ? 'bg-[#C4A265] text-white'
                                            : 'bg-gray-100 text-gray-400'
                                ]"
                            >
                                <svg v-if="currentStep > index + 1" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                <span v-else>{{ index + 1 }}</span>
                            </div>
                            <span
                                :class="[
                                    'text-xs font-medium transition-colors',
                                    currentStep >= index + 1 ? 'text-amber-600' : 'text-gray-400'
                                ]"
                            >
                                {{ label }}
                            </span>
                        </button>
                        <!-- Connector line -->
                        <div
                            v-if="index < stepLabels.length - 1"
                            :class="[
                                'flex-1 h-0.5 mx-3 rounded-full transition-colors duration-300 -mt-6',
                                currentStep > index + 1 ? 'bg-[#C4A265]' : 'bg-gray-200'
                            ]"
                        ></div>
                    </template>
                </div>
            </div>

            <!-- Step Content -->
            <div class="max-w-4xl mx-auto">

                <!-- ============================================= -->
                <!-- STEP 1: Select Patient                         -->
                <!-- ============================================= -->
                <div v-show="currentStep === 1">
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100/80 p-6">
                        <h2 class="text-sm font-bold text-gray-800 mb-4 border-b border-gray-100 pb-2">{{ $t('a_select_patient') }}</h2>

                        <!-- Patient Search -->
                        <div class="relative max-w-lg">
                            <label class="block text-xs font-medium text-gray-500 mb-1.5">{{ $t('a_search_patient') }} <span class="text-red-500">*</span></label>
                            <div class="relative">
                                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                                <input
                                    v-model="patientSearch"
                                    @focus="showPatientDropdown = true"
                                    @blur="setTimeout(() => showPatientDropdown = false, 200)"
                                    type="text"
                                    :placeholder="$t('a_search_patient_placeholder')"
                                    class="w-full pl-10 pr-4 py-2.5 bg-white border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-amber-200 focus:border-transparent"
                                />
                                <button
                                    v-if="selectedPatient"
                                    type="button"
                                    @click="clearPatient"
                                    class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>

                            <!-- Dropdown -->
                            <div
                                v-if="showPatientDropdown && filteredPatients.length"
                                class="absolute z-30 w-full mt-1 bg-white border border-gray-200 rounded-xl shadow-lg max-h-60 overflow-y-auto"
                            >
                                <button
                                    v-for="p in filteredPatients"
                                    :key="p.id"
                                    @mousedown.prevent="selectPatient(p)"
                                    type="button"
                                    class="w-full px-4 py-2.5 text-start text-sm hover:bg-amber-50 transition-colors flex items-center justify-between"
                                >
                                    <div>
                                        <span class="font-semibold text-gray-800">{{ p.full_name }}</span>
                                        <span class="text-gray-400 ltr:ml-2 rtl:mr-2 text-xs">{{ p.phone }}</span>
                                    </div>
                                    <span class="text-xs text-gray-400 font-mono">{{ p.file_number }}</span>
                                </button>
                            </div>
                            <div
                                v-if="showPatientDropdown && patientSearch && !filteredPatients.length"
                                class="absolute z-30 w-full mt-1 bg-white border border-gray-200 rounded-xl shadow-lg p-4 text-center"
                            >
                                <p class="text-sm text-gray-400">{{ $t('a_no_patients_found') }}</p>
                                <button
                                    type="button"
                                    @mousedown.prevent="showNewPatientModal = true"
                                    class="mt-2 inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold transition-all border hover:bg-amber-50"
                                    style="color: #C4A265; border-color: rgba(196, 162, 101, 0.4);"
                                >
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                                    {{ $t('a_add_new_patient') }}
                                </button>
                            </div>
                        </div>

                        <!-- Quick Add Patient Button (always visible) -->
                        <div class="mt-3">
                            <button
                                type="button"
                                @click="showNewPatientModal = true"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold transition-all border hover:bg-amber-50"
                                style="color: #C4A265; border-color: rgba(196, 162, 101, 0.4);"
                            >
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" /></svg>
                                Add New Patient
                            </button>
                        </div>

                        <!-- Selected Patient Card -->
                        <div v-if="selectedPatient" class="mt-5 p-4 bg-amber-50/50 border border-amber-200 rounded-xl">
                            <div class="flex items-center gap-4">
                                <div class="w-12 h-12 rounded-xl bg-gradient-to-r from-amber-500 to-amber-400 flex items-center justify-center text-white text-lg font-bold flex-shrink-0">
                                    {{ selectedPatient.full_name?.charAt(0).toUpperCase() }}
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-bold text-gray-800">{{ selectedPatient.full_name }}</p>
                                    <div class="flex items-center gap-4 mt-1">
                                        <span class="text-xs text-gray-500 flex items-center gap-1">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" /></svg>
                                            {{ selectedPatient.phone || '-' }}
                                        </span>
                                        <span class="text-xs text-gray-500 flex items-center gap-1">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" /></svg>
                                            {{ selectedPatient.file_number || '-' }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ============================================= -->
                <!-- STEP 2: Add Services                           -->
                <!-- ============================================= -->
                <div v-show="currentStep === 2">
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100/80 p-6">

                        <!-- ==================== CONSULTATION MODE ==================== -->
                        <template v-if="isConsultation">
                            <div class="border-b border-gray-100 pb-2 mb-4">
                                <h2 class="text-sm font-bold text-gray-800">
                                    {{ $t('a_' + bookingType) }}
                                </h2>
                            </div>

                            <div class="p-4 bg-gray-50/50 border border-gray-100 rounded-xl">
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    <!-- Doctor Select (searchable) -->
                                    <div class="relative bk-dd">
                                        <label class="block text-xs font-medium text-gray-500 mb-1">{{ $t('a_doctor') }} <span class="text-red-500">*</span></label>
                                        <button type="button" @click.stop="toggleDropdown('cons_doc')"
                                            class="w-full flex items-center justify-between px-4 py-2.5 bg-white border border-gray-200 rounded-xl text-sm transition-all"
                                            :class="openDropdown === 'cons_doc' ? 'ring-2 ring-amber-200 border-transparent' : 'hover:border-gray-300'">
                                            <span :class="serviceRows[0].doctor_id ? 'text-gray-800' : 'text-gray-400'">
                                                {{ serviceRows[0].doctor_id ? getDoctorLabel(serviceRows[0].doctor_id) : $t('a_select_doctor') }}
                                            </span>
                                            <svg class="w-4 h-4 text-gray-400 transition-transform" :class="openDropdown === 'cons_doc' ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                                        </button>
                                        <Transition enter-active-class="transition duration-150" enter-from-class="opacity-0 -translate-y-1" enter-to-class="opacity-100 translate-y-0" leave-active-class="transition duration-100" leave-from-class="opacity-100" leave-to-class="opacity-0">
                                            <div v-if="openDropdown === 'cons_doc'" class="absolute z-40 w-full mt-1 bg-white border border-gray-200 rounded-xl shadow-lg overflow-hidden">
                                                <div class="p-2 border-b border-gray-100">
                                                    <input type="text" v-model="ddSearch['cons_doc']" class="bk-dd-search-cons_doc w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:ring-2 focus:ring-amber-200 focus:border-transparent" :placeholder="isRtl ? 'بحث عن طبيب...' : 'Search doctor...'" />
                                                </div>
                                                <div class="max-h-48 overflow-y-auto">
                                                    <button v-for="d in searchedDoctors('cons_doc')" :key="d.id" type="button"
                                                        @click="serviceRows[0].doctor_id = d.id; onConsultationDoctorChange(0); closeDropdown()"
                                                        class="w-full flex items-center justify-between px-4 py-2.5 text-sm text-start hover:bg-amber-50 transition-colors"
                                                        :class="serviceRows[0].doctor_id == d.id ? 'bg-amber-50/70 font-semibold text-amber-700' : 'text-gray-700'">
                                                        <div class="flex items-center gap-2">
                                                            <div class="w-7 h-7 rounded-lg bg-gradient-to-r from-amber-400 to-amber-300 flex items-center justify-center text-white text-xs font-bold flex-shrink-0">{{ (d.name_en || d.name_ar || '?').charAt(0) }}</div>
                                                            <span>{{ isRtl ? (d.name_ar || d.name_en) : (d.name_en || d.name_ar) }}</span>
                                                        </div>
                                                        <svg v-if="serviceRows[0].doctor_id == d.id" class="w-4 h-4 text-amber-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" /></svg>
                                                    </button>
                                                    <div v-if="searchedDoctors('cons_doc').length === 0" class="px-4 py-3 text-sm text-gray-400 text-center">{{ isRtl ? 'لا توجد نتائج' : 'No results' }}</div>
                                                </div>
                                            </div>
                                        </Transition>
                                    </div>

                                    <!-- Consultation Fee -->
                                    <div>
                                        <label class="block text-xs font-medium text-gray-500 mb-1">{{ $t('a_consultation_fee') }} ({{ currencyCode }}) <span class="text-red-500">*</span></label>
                                        <input
                                            v-model.number="serviceRows[0].unit_price"
                                            type="number"
                                            min="0"
                                            step="0.01"
                                            class="w-full px-4 py-2.5 bg-white border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-amber-200 focus:border-transparent"
                                        />
                                    </div>

                                    <!-- Discount -->
                                    <div>
                                        <label class="block text-xs font-medium text-gray-500 mb-1">{{ $t('a_discount') }} ({{ currencyCode }})</label>
                                        <input
                                            v-model.number="serviceRows[0].discount_per_session"
                                            type="number"
                                            min="0"
                                            step="0.01"
                                            class="w-full px-4 py-2.5 bg-white border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-amber-200 focus:border-transparent"
                                        />
                                    </div>

                                    <!-- Total -->
                                    <div>
                                        <label class="block text-xs font-medium text-gray-500 mb-1">{{ $t('a_total') }}</label>
                                        <div class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm font-bold text-amber-600">
                                            {{ formatCurrency(serviceRowTotal(serviceRows[0])) }}
                                        </div>
                                    </div>
                                </div>

                                <!-- Notes -->
                                <div class="mt-3">
                                    <label class="block text-xs font-medium text-gray-500 mb-1">{{ $t('a_notes_optional') }}</label>
                                    <input
                                        v-model="serviceRows[0].notes"
                                        type="text"
                                        placeholder="Consultation notes..."
                                        class="w-full px-4 py-2 bg-white border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-amber-200 focus:border-transparent"
                                    />
                                </div>
                            </div>
                        </template>

                        <!-- ==================== SERVICE MODE ==================== -->
                        <template v-else>
                            <div class="flex items-center justify-between border-b border-gray-100 pb-2 mb-4">
                                <h2 class="text-sm font-bold text-gray-800">{{ $t('a_services') }}</h2>
                                <button
                                    type="button"
                                    @click="addServiceRow"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 text-white rounded-lg text-xs font-semibold hover:opacity-90 transition"
                                    style="background-color: #C4A265;"
                                >
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                                    {{ $t('a_add_service') }}
                                </button>
                            </div>

                            <div class="space-y-4">
                                <div
                                    v-for="(row, index) in serviceRows"
                                    :key="index"
                                    class="p-4 bg-gray-50/50 border border-gray-100 rounded-xl"
                                >
                                    <div class="flex items-center justify-between mb-3">
                                        <span class="text-xs font-semibold text-gray-500 uppercase">{{ $t('a_service') }} {{ index + 1 }}</span>
                                        <button
                                            v-if="serviceRows.length > 1"
                                            type="button"
                                            @click="removeServiceRow(index)"
                                            class="text-red-400 hover:text-red-600 transition"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                        </button>
                                    </div>

                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                        <!-- Service Select (searchable) -->
                                        <div class="relative bk-dd">
                                            <label class="block text-xs font-medium text-gray-500 mb-1">{{ $t('a_service') }} <span class="text-red-500">*</span></label>
                                            <button type="button" @click.stop="toggleDropdown('svc_' + index)"
                                                class="w-full flex items-center justify-between px-4 py-2.5 bg-white border border-gray-200 rounded-xl text-sm transition-all"
                                                :class="openDropdown === 'svc_' + index ? 'ring-2 ring-amber-200 border-transparent' : 'hover:border-gray-300'">
                                                <span class="truncate" :class="row.service_id ? 'text-gray-800' : 'text-gray-400'">
                                                    {{ row.service_id ? getServiceLabel(row.service_id) : $t('a_select_service') }}
                                                </span>
                                                <svg class="w-4 h-4 text-gray-400 flex-shrink-0 transition-transform" :class="openDropdown === 'svc_' + index ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                                            </button>
                                            <Transition enter-active-class="transition duration-150" enter-from-class="opacity-0 -translate-y-1" enter-to-class="opacity-100 translate-y-0" leave-active-class="transition duration-100" leave-from-class="opacity-100" leave-to-class="opacity-0">
                                                <div v-if="openDropdown === 'svc_' + index" class="absolute z-40 w-full mt-1 bg-white border border-gray-200 rounded-xl shadow-lg overflow-hidden">
                                                    <div class="p-2 border-b border-gray-100">
                                                        <input type="text" v-model="ddSearch['svc_' + index]" :class="'bk-dd-search-svc_' + index" class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:ring-2 focus:ring-amber-200 focus:border-transparent" :placeholder="isRtl ? 'بحث عن خدمة...' : 'Search service...'" />
                                                    </div>
                                                    <div class="max-h-56 overflow-y-auto">
                                                        <template v-if="searchedServiceCategories('svc_' + index).length">
                                                            <template v-for="cat in searchedServiceCategories('svc_' + index)" :key="cat.id">
                                                                <div class="px-3 py-1.5 text-[10px] font-bold text-gray-400 uppercase tracking-wider bg-gray-50 sticky top-0">{{ isRtl ? (cat.name_ar || cat.name_en) : (cat.name_en || cat.name_ar) }}</div>
                                                                <button v-for="s in cat.services" :key="s.id" type="button"
                                                                    @click="row.service_id = s.id; onServiceChange(index); closeDropdown()"
                                                                    class="w-full flex items-center justify-between px-4 py-2.5 text-sm text-start hover:bg-amber-50 transition-colors"
                                                                    :class="row.service_id == s.id ? 'bg-amber-50/70 font-semibold text-amber-700' : 'text-gray-700'">
                                                                    <span class="truncate">{{ isRtl ? (s.name_ar || s.name_en) : (s.name_en || s.name_ar) }}</span>
                                                                    <div class="flex items-center gap-2 flex-shrink-0">
                                                                        <span class="text-xs text-gray-400">{{ formatCurrency(s.price_after_discount || s.price) }}</span>
                                                                        <svg v-if="row.service_id == s.id" class="w-4 h-4 text-amber-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" /></svg>
                                                                    </div>
                                                                </button>
                                                            </template>
                                                        </template>
                                                        <template v-else-if="searchedServices('svc_' + index).length">
                                                            <button v-for="s in searchedServices('svc_' + index)" :key="s.id" type="button"
                                                                @click="row.service_id = s.id; onServiceChange(index); closeDropdown()"
                                                                class="w-full flex items-center justify-between px-4 py-2.5 text-sm text-start hover:bg-amber-50 transition-colors"
                                                                :class="row.service_id == s.id ? 'bg-amber-50/70 font-semibold text-amber-700' : 'text-gray-700'">
                                                                <span class="truncate">{{ isRtl ? (s.name_ar || s.name_en) : (s.name_en || s.name_ar) }}</span>
                                                                <div class="flex items-center gap-2 flex-shrink-0">
                                                                    <span class="text-xs text-gray-400">{{ formatCurrency(s.price_after_discount || s.price) }}</span>
                                                                    <svg v-if="row.service_id == s.id" class="w-4 h-4 text-amber-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" /></svg>
                                                                </div>
                                                            </button>
                                                        </template>
                                                        <div v-else class="px-4 py-3 text-sm text-gray-400 text-center">{{ isRtl ? 'لا توجد نتائج' : 'No results' }}</div>
                                                    </div>
                                                </div>
                                            </Transition>
                                        </div>

                                        <!-- Doctor Select (searchable) -->
                                        <div class="relative bk-dd">
                                            <label class="block text-xs font-medium text-gray-500 mb-1">{{ $t('a_doctor') }} <span class="text-red-500">*</span></label>
                                            <button type="button" @click.stop="toggleDropdown('doc_' + index)"
                                                class="w-full flex items-center justify-between px-4 py-2.5 bg-white border border-gray-200 rounded-xl text-sm transition-all"
                                                :class="openDropdown === 'doc_' + index ? 'ring-2 ring-amber-200 border-transparent' : 'hover:border-gray-300'">
                                                <span :class="row.doctor_id ? 'text-gray-800' : 'text-gray-400'">
                                                    {{ row.doctor_id ? getDoctorLabel(row.doctor_id) : $t('a_select_doctor') }}
                                                </span>
                                                <svg class="w-4 h-4 text-gray-400 transition-transform" :class="openDropdown === 'doc_' + index ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                                            </button>
                                            <Transition enter-active-class="transition duration-150" enter-from-class="opacity-0 -translate-y-1" enter-to-class="opacity-100 translate-y-0" leave-active-class="transition duration-100" leave-from-class="opacity-100" leave-to-class="opacity-0">
                                                <div v-if="openDropdown === 'doc_' + index" class="absolute z-40 w-full mt-1 bg-white border border-gray-200 rounded-xl shadow-lg overflow-hidden">
                                                    <div class="p-2 border-b border-gray-100">
                                                        <input type="text" v-model="ddSearch['doc_' + index]" :class="'bk-dd-search-doc_' + index" class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:ring-2 focus:ring-amber-200 focus:border-transparent" :placeholder="isRtl ? 'بحث عن طبيب...' : 'Search doctor...'" />
                                                    </div>
                                                    <div class="max-h-48 overflow-y-auto">
                                                        <button v-for="d in searchedDoctors('doc_' + index)" :key="d.id" type="button"
                                                            @click="row.doctor_id = d.id; closeDropdown()"
                                                            class="w-full flex items-center justify-between px-4 py-2.5 text-sm text-start hover:bg-amber-50 transition-colors"
                                                            :class="row.doctor_id == d.id ? 'bg-amber-50/70 font-semibold text-amber-700' : 'text-gray-700'">
                                                            <div class="flex items-center gap-2">
                                                                <div class="w-7 h-7 rounded-lg bg-gradient-to-r from-amber-400 to-amber-300 flex items-center justify-center text-white text-xs font-bold flex-shrink-0">{{ (d.name_en || d.name_ar || '?').charAt(0) }}</div>
                                                                <span>{{ isRtl ? (d.name_ar || d.name_en) : (d.name_en || d.name_ar) }}</span>
                                                            </div>
                                                            <svg v-if="row.doctor_id == d.id" class="w-4 h-4 text-amber-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" /></svg>
                                                        </button>
                                                        <div v-if="searchedDoctors('doc_' + index).length === 0" class="px-4 py-3 text-sm text-gray-400 text-center">{{ isRtl ? 'لا توجد نتائج' : 'No results' }}</div>
                                                    </div>
                                                </div>
                                            </Transition>
                                        </div>

                                        <!-- Sessions Count -->
                                        <div>
                                            <label class="block text-xs font-medium text-gray-500 mb-1">{{ $t('a_sessions') }} <span class="text-red-500">*</span></label>
                                            <input
                                                v-model.number="row.sessions_count"
                                                type="number"
                                                min="1"
                                                class="w-full px-4 py-2.5 bg-white border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-amber-200 focus:border-transparent"
                                            />
                                        </div>

                                        <!-- Unit Price -->
                                        <div>
                                            <label class="block text-xs font-medium text-gray-500 mb-1">{{ $t('a_unit_price') }} ({{ currencyCode }})</label>
                                            <input
                                                v-model.number="row.unit_price"
                                                type="number"
                                                min="0"
                                                step="0.01"
                                                class="w-full px-4 py-2.5 bg-white border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-amber-200 focus:border-transparent"
                                            />
                                        </div>

                                        <!-- Discount per Session -->
                                        <div>
                                            <label class="block text-xs font-medium text-gray-500 mb-1">{{ $t('a_discount_per_session') }} ({{ currencyCode }})</label>
                                            <input
                                                v-model.number="row.discount_per_session"
                                                type="number"
                                                min="0"
                                                step="0.01"
                                                class="w-full px-4 py-2.5 bg-white border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-amber-200 focus:border-transparent"
                                            />
                                        </div>

                                        <!-- Row Total -->
                                        <div>
                                            <label class="block text-xs font-medium text-gray-500 mb-1">{{ $t('a_total') }}</label>
                                            <div class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm font-bold text-amber-600">
                                                {{ formatCurrency(serviceRowTotal(row)) }}
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Service notes -->
                                    <div class="mt-3">
                                        <label class="block text-xs font-medium text-gray-500 mb-1">{{ $t('a_notes_optional') }}</label>
                                        <input
                                            v-model="row.notes"
                                            type="text"
                                            :placeholder="$t('a_service_notes_placeholder')"
                                            class="w-full px-4 py-2 bg-white border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-amber-200 focus:border-transparent"
                                        />
                                    </div>
                                </div>
                            </div>
                        </template>

                        <!-- Running Total -->
                        <div class="mt-5 pt-4 border-t border-gray-100 flex items-center justify-between">
                            <span class="text-sm font-semibold text-gray-600">{{ $t('a_grand_total') }}</span>
                            <span class="text-xl font-bold" style="color: #C4A265;">{{ formatCurrency(grandTotal) }}</span>
                        </div>
                    </div>
                </div>

                <!-- ============================================= -->
                <!-- STEP 3: Set Appointments                       -->
                <!-- ============================================= -->
                <div v-show="currentStep === 3">
                    <div class="space-y-6">
                        <div
                            v-for="(row, sIndex) in serviceRows"
                            :key="sIndex"
                            class="bg-white rounded-2xl shadow-sm border border-gray-100/80 p-6"
                        >
                            <h2 class="text-sm font-bold text-gray-800 mb-1">
                                {{ isConsultation
                                    ? $t('a_' + bookingType)
                                    : (getService(row.service_id)?.name_en || getService(row.service_id)?.name_ar || $t('a_service') + ' ' + (sIndex + 1))
                                }}
                            </h2>
                            <p class="text-xs text-gray-400 mb-4">
                                {{ row.sessions_count }} {{ $t('a_sessions') }}
                                {{ $t('a_with') }} {{ getDoctor(row.doctor_id)?.name_en || getDoctor(row.doctor_id)?.name_ar || '-' }}
                            </p>

                            <div class="space-y-4">
                                <div
                                    v-for="(apt, aIndex) in (appointmentData[sIndex] || [])"
                                    :key="aIndex"
                                    class="p-4 bg-gray-50/50 border border-gray-100 rounded-xl"
                                >
                                    <div class="flex items-center gap-2 mb-3">
                                        <div class="w-6 h-6 rounded-full bg-amber-100 flex items-center justify-center text-xs font-bold" style="color: #C4A265;">
                                            {{ aIndex + 1 }}
                                        </div>
                                        <span class="text-xs font-semibold text-gray-500">{{ $t('a_session') }} {{ aIndex + 1 }}</span>
                                    </div>

                                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                        <!-- Doctor override (searchable) -->
                                        <div class="relative bk-dd">
                                            <label class="block text-xs font-medium text-gray-500 mb-1">{{ $t('a_doctor') }}</label>
                                            <button type="button" @click.stop="toggleDropdown('apt_doc_' + sIndex + '_' + aIndex)"
                                                class="w-full flex items-center justify-between px-3 py-2 bg-white border border-gray-200 rounded-xl text-sm transition-all"
                                                :class="openDropdown === 'apt_doc_' + sIndex + '_' + aIndex ? 'ring-2 ring-amber-200 border-transparent' : 'hover:border-gray-300'">
                                                <span :class="apt.doctor_id ? 'text-gray-800' : 'text-gray-400'">
                                                    {{ apt.doctor_id ? getDoctorLabel(apt.doctor_id) : $t('a_select_doctor') }}
                                                </span>
                                                <svg class="w-4 h-4 text-gray-400 transition-transform" :class="openDropdown === 'apt_doc_' + sIndex + '_' + aIndex ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                                            </button>
                                            <Transition enter-active-class="transition duration-150" enter-from-class="opacity-0 -translate-y-1" enter-to-class="opacity-100 translate-y-0" leave-active-class="transition duration-100" leave-from-class="opacity-100" leave-to-class="opacity-0">
                                                <div v-if="openDropdown === 'apt_doc_' + sIndex + '_' + aIndex" class="absolute z-40 w-full mt-1 bg-white border border-gray-200 rounded-xl shadow-lg overflow-hidden">
                                                    <div class="p-2 border-b border-gray-100">
                                                        <input type="text" v-model="ddSearch['apt_doc_' + sIndex + '_' + aIndex]" :class="'bk-dd-search-apt_doc_' + sIndex + '_' + aIndex" class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:ring-2 focus:ring-amber-200 focus:border-transparent" :placeholder="isRtl ? 'بحث عن طبيب...' : 'Search doctor...'" />
                                                    </div>
                                                    <div class="max-h-48 overflow-y-auto">
                                                        <button v-for="d in searchedDoctors('apt_doc_' + sIndex + '_' + aIndex)" :key="d.id" type="button"
                                                            @click="apt.doctor_id = d.id; onAppointmentDoctorChange(sIndex, aIndex); closeDropdown()"
                                                            class="w-full flex items-center justify-between px-4 py-2.5 text-sm text-start hover:bg-amber-50 transition-colors"
                                                            :class="apt.doctor_id == d.id ? 'bg-amber-50/70 font-semibold text-amber-700' : 'text-gray-700'">
                                                            <div class="flex items-center gap-2">
                                                                <div class="w-7 h-7 rounded-lg bg-gradient-to-r from-amber-400 to-amber-300 flex items-center justify-center text-white text-xs font-bold flex-shrink-0">{{ (d.name_en || d.name_ar || '?').charAt(0) }}</div>
                                                                <span>{{ isRtl ? (d.name_ar || d.name_en) : (d.name_en || d.name_ar) }}</span>
                                                            </div>
                                                            <svg v-if="apt.doctor_id == d.id" class="w-4 h-4 text-amber-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" /></svg>
                                                        </button>
                                                        <div v-if="searchedDoctors('apt_doc_' + sIndex + '_' + aIndex).length === 0" class="px-4 py-3 text-sm text-gray-400 text-center">{{ isRtl ? 'لا توجد نتائج' : 'No results' }}</div>
                                                    </div>
                                                </div>
                                            </Transition>
                                        </div>

                                        <!-- Date (Doctor Availability Calendar) -->
                                        <div>
                                            <label class="block text-xs font-medium text-gray-500 mb-1">{{ $t('a_date') }} <span class="text-red-500">*</span></label>
                                            <DoctorDatePicker
                                                v-model="apt.date"
                                                :doctor-id="apt.doctor_id"
                                                :doctor-schedules="doctorSchedules"
                                                :min-date="new Date().toISOString().split('T')[0]"
                                                accent-color="#C4A265"
                                                popover
                                                @update:model-value="onAppointmentDateChange(sIndex, aIndex)"
                                            />
                                        </div>

                                        <!-- Selected Time Display -->
                                        <div>
                                            <label class="block text-xs font-medium text-gray-500 mb-1">{{ $t('a_time') }}</label>
                                            <div
                                                v-if="apt.start_time && apt.end_time"
                                                class="w-full px-3 py-2 bg-amber-50 border border-amber-200 rounded-xl text-sm font-semibold"
                                                style="color: #C4A265;"
                                            >
                                                {{ formatTime12h(apt.start_time) }} - {{ formatTime12h(apt.end_time) }}
                                            </div>
                                            <div v-else class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-xl text-sm text-gray-400">
                                                {{ $t('a_select_slot_below') }}
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Schedule Warning -->
                                    <div v-if="apt.scheduleWarning" class="mt-3 flex items-center gap-2 p-2.5 bg-amber-50 border border-amber-200 rounded-lg">
                                        <svg class="w-4 h-4 text-amber-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.34 16.5c-.77.833.192 2.5 1.732 2.5z" />
                                        </svg>
                                        <span class="text-xs text-amber-700 font-medium">{{ apt.scheduleWarning }}</span>
                                    </div>

                                    <!-- Loading Slots -->
                                    <div v-if="apt.loadingSlots" class="mt-3 flex items-center gap-2">
                                        <svg class="w-4 h-4 animate-spin" style="color: #C4A265;" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        <span class="text-xs text-gray-500">{{ $t('a_loading_slots') }}</span>
                                    </div>

                                    <!-- Available Time Slots -->
                                    <div v-if="apt.availableSlots.length > 0 && !apt.loadingSlots" class="mt-3">
                                        <p class="text-xs font-medium text-gray-500 mb-2">{{ $t('a_available_slots') }}</p>
                                        <div class="flex flex-wrap gap-2">
                                            <button
                                                v-for="(slot, slotIdx) in apt.availableSlots"
                                                :key="slotIdx"
                                                type="button"
                                                @click="selectTimeSlot(sIndex, aIndex, slot)"
                                                :class="[
                                                    'px-3 py-1.5 rounded-lg text-xs font-semibold transition-all border',
                                                    apt.start_time === (slot.start_time || slot.start) && apt.end_time === (slot.end_time || slot.end)
                                                        ? 'bg-[#C4A265] text-white border-[#C4A265] shadow-sm'
                                                        : 'bg-white text-gray-600 border-gray-200 hover:border-amber-300 hover:bg-amber-50'
                                                ]"
                                            >
                                                {{ slot.start_12h || formatTime12h(slot.start_time || slot.start) }} - {{ slot.end_12h || formatTime12h(slot.end_time || slot.end) }}
                                            </button>
                                        </div>
                                    </div>

                                    <!-- No slots message -->
                                    <div
                                        v-if="apt.date && apt.doctor_id && !apt.loadingSlots && !apt.scheduleWarning && apt.availableSlots.length === 0"
                                        class="mt-3 p-2.5 bg-gray-50 border border-gray-200 rounded-lg text-center"
                                    >
                                        <p class="text-xs text-gray-400">{{ $t('a_no_slots_for_date') }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ============================================= -->
                <!-- STEP 4: Summary & Confirm                      -->
                <!-- ============================================= -->
                <div v-show="currentStep === 4">
                    <div class="space-y-6">
                        <!-- Patient Summary -->
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-100/80 p-6">
                            <h2 class="text-sm font-bold text-gray-800 mb-4 border-b border-gray-100 pb-2">{{ $t('a_patient') }}</h2>
                            <div v-if="selectedPatient" class="flex items-center gap-4">
                                <div class="w-12 h-12 rounded-xl bg-gradient-to-r from-amber-500 to-amber-400 flex items-center justify-center text-white text-lg font-bold flex-shrink-0">
                                    {{ selectedPatient.full_name?.charAt(0).toUpperCase() }}
                                </div>
                                <div>
                                    <p class="text-sm font-bold text-gray-800">{{ selectedPatient.full_name }}</p>
                                    <div class="flex items-center gap-4 mt-0.5">
                                        <span class="text-xs text-gray-500">{{ selectedPatient.phone || '-' }}</span>
                                        <span class="text-xs text-gray-500 font-mono">{{ selectedPatient.file_number || '-' }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Services Summary -->
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-100/80 p-6">
                            <h2 class="text-sm font-bold text-gray-800 mb-4 border-b border-gray-100 pb-2">{{ $t('a_services_and_appointments') }}</h2>
                            <div class="space-y-5">
                                <div v-for="(row, sIndex) in serviceRows" :key="sIndex">
                                    <div class="flex items-start justify-between">
                                        <div>
                                            <p class="text-sm font-semibold text-gray-800">
                                                {{ isConsultation
                                                    ? $t('a_' + bookingType)
                                                    : (getService(row.service_id)?.name_en || getService(row.service_id)?.name_ar || '-')
                                                }}
                                            </p>
                                            <p class="text-xs text-gray-400 mt-0.5">
                                                {{ getDoctor(row.doctor_id)?.name_en || getDoctor(row.doctor_id)?.name_ar || '-' }}
                                                &middot; {{ row.sessions_count }} {{ $t('a_sessions') }}
                                            </p>
                                        </div>
                                        <div class="text-end">
                                            <p class="text-sm font-bold text-gray-800">{{ formatCurrency(serviceRowTotal(row)) }}</p>
                                            <p v-if="row.discount_per_session > 0" class="text-xs text-red-500 mt-0.5">
                                                -{{ formatCurrency(row.discount_per_session) }} / {{ $t('a_session') }}
                                            </p>
                                        </div>
                                    </div>

                                    <!-- Appointments for this service -->
                                    <div v-if="appointmentData[sIndex]?.length" class="mt-2 ltr:ml-4 rtl:mr-4 space-y-1">
                                        <div
                                            v-for="(apt, aIndex) in appointmentData[sIndex]"
                                            :key="aIndex"
                                            class="flex items-center gap-3 text-xs text-gray-500"
                                        >
                                            <span class="w-5 h-5 rounded-full bg-gray-100 text-gray-500 flex items-center justify-center text-[10px] font-bold flex-shrink-0">
                                                {{ aIndex + 1 }}
                                            </span>
                                            <svg class="w-3.5 h-3.5 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                            </svg>
                                            <span>{{ apt.date }}</span>
                                            <svg class="w-3.5 h-3.5 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                            <span>{{ formatTime12h(apt.start_time) }} - {{ formatTime12h(apt.end_time) }}</span>
                                            <span v-if="apt.doctor_id != row.doctor_id" class="text-amber-600 font-medium">
                                                (Dr. {{ getDoctor(apt.doctor_id)?.name_en || getDoctor(apt.doctor_id)?.name_ar }})
                                            </span>
                                        </div>
                                    </div>

                                    <div v-if="sIndex < serviceRows.length - 1" class="mt-4 border-b border-gray-100"></div>
                                </div>
                            </div>

                            <!-- {{ $t('a_grand_total') }} -->
                            <div class="mt-5 pt-4 border-t border-gray-100 flex items-center justify-between">
                                <span class="text-sm font-semibold text-gray-600">{{ $t('a_grand_total') }}</span>
                                <span class="text-xl font-bold" style="color: #C4A265;">{{ formatCurrency(grandTotal) }}</span>
                            </div>
                        </div>

                        <!-- Notes -->
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-100/80 p-6">
                            <h2 class="text-sm font-bold text-gray-800 mb-4 border-b border-gray-100 pb-2">{{ $t('a_notes') }}</h2>
                            <textarea
                                v-model="notes"
                                rows="3"
                                placeholder="Optional booking notes..."
                                class="w-full px-4 py-2.5 bg-white border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-amber-200 focus:border-transparent"
                            ></textarea>
                        </div>

                        <!-- Promo Code -->
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-100/80 p-6">
                            <PromoCodeInput
                                v-model="promoCode"
                                booking-type="service"
                                :service-id="serviceRows[0]?.service_id ? Number(serviceRows[0].service_id) : null"
                            />
                        </div>

                        <!-- Errors -->
                        <div v-if="Object.keys(errors).length > 0" class="bg-red-50 border border-red-200 rounded-xl p-4">
                            <div class="flex items-center gap-2 mb-2">
                                <svg class="w-4 h-4 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <span class="text-sm font-semibold text-red-700">{{ $t('a_fix_errors') }}</span>
                            </div>
                            <ul class="list-disc list-inside space-y-1">
                                <li v-for="(msg, key) in errors" :key="key" class="text-xs text-red-600">{{ msg }}</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- ============================================= -->
                <!-- Navigation Buttons                             -->
                <!-- ============================================= -->
                <div class="flex items-center justify-between mt-8">
                    <div>
                        <button
                            v-if="currentStep > 1"
                            type="button"
                            @click="prevStep"
                            class="inline-flex items-center gap-2 px-5 py-2.5 bg-white border border-gray-200 text-gray-600 rounded-xl text-sm font-semibold hover:bg-gray-50 transition-all"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                            </svg>
                            {{ $t('a_previous') }}
                        </button>
                    </div>
                    <div>
                        <button
                            v-if="currentStep < totalSteps"
                            type="button"
                            @click="nextStep"
                            :disabled="!canProceed(currentStep)"
                            class="inline-flex items-center gap-2 px-4 md:px-6 py-2.5 text-white rounded-xl text-sm font-semibold hover:opacity-90 transition-all shadow-lg disabled:opacity-50 disabled:cursor-not-allowed"
                            style="background-color: #C4A265;"
                        >
                            {{ $t('a_next') }}
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </button>
                        <button
                            v-if="currentStep === totalSteps"
                            type="button"
                            @click="submit"
                            :disabled="processing"
                            class="inline-flex items-center gap-2 px-4 md:px-6 py-2.5 text-white rounded-xl text-sm font-semibold hover:opacity-90 transition-all shadow-lg disabled:opacity-50"
                            style="background-color: #C4A265;"
                        >
                            <svg v-if="processing" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            {{ processing ? $t('a_creating') : $t('a_create_booking') }}
                        </button>
                    </div>
                </div>

            </div>
        </div>
        <QuickAddPatientModal
            :show="showNewPatientModal"
            theme="gold"
            :prefill="{}"
            submit-url="/admin/patients/quick-create"
            @close="showNewPatientModal = false"
            @created="onPatientCreated"
        />
    </AdminLayout>
</template>
