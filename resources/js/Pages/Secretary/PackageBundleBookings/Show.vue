<script setup>
import { ref, computed, reactive } from 'vue';
import { Link, router, useForm , usePage } from '@inertiajs/vue3';
import SecretaryLayout from '@/Layouts/SecretaryLayout.vue';
import DoctorDatePicker from '@/Components/DoctorDatePicker.vue';
import { useCurrency } from '@/Composables/useCurrency.js';

defineOptions({ layout: SecretaryLayout });

const page = usePage();
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const props = defineProps({
    bundleBooking: Object,
    paymentMethods: Array,
    doctors: Array,
    doctorSchedules: Array,
});

const { formatCurrency, currencyCode } = useCurrency();

/* -- Helpers ----------------------------------------------- */
function formatDate(date) {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
}
function formatDateTime(date) {
    if (!date) return '-';
    return new Date(date).toLocaleString('en-GB', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' });
}
function formatTime(time) {
    if (!time) return '-';
    const [h, m] = time.split(':');
    const d = new Date(2000, 0, 1, h, m);
    return d.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit', hour12: true });
}
function jsToSystemDay(jsDay) {
    const map = { 0: 1, 1: 2, 2: 3, 3: 4, 4: 5, 5: 6, 6: 0 };
    return map[jsDay];
}

/* -- Status Colors ---------------------------------------- */
const bookingStatusColors = {
    pending: 'bg-yellow-100 text-yellow-800',
    confirmed: 'bg-blue-100 text-blue-800',
    in_progress: 'bg-indigo-100 text-indigo-800',
    completed: 'bg-green-100 text-green-800',
    cancelled: 'bg-red-100 text-red-800',
};
const serviceStatusColors = {
    pending: 'bg-yellow-50 text-yellow-700 border-yellow-200',
    in_progress: 'bg-indigo-50 text-indigo-700 border-indigo-200',
    completed: 'bg-green-50 text-green-700 border-green-200',
    cancelled: 'bg-red-50 text-red-700 border-red-200',
};
const visitStatusColors = {
    waiting: 'bg-yellow-100 text-yellow-800',
    in_progress: 'bg-blue-100 text-blue-800',
    completed: 'bg-green-100 text-green-800',
    cancelled: 'bg-red-100 text-red-800',
};
const appointmentStatusColors = {
    scheduled: 'bg-blue-100 text-blue-800',
    confirmed: 'bg-indigo-100 text-indigo-800',
    checked_in: 'bg-yellow-100 text-yellow-800',
    in_progress: 'bg-purple-100 text-purple-800',
    completed: 'bg-green-100 text-green-800',
    cancelled: 'bg-red-100 text-red-800',
    no_show: 'bg-gray-100 text-gray-800',
};

/* -- Computed --------------------------------------------- */
const b = computed(() => props.bundleBooking);
const invoice = computed(() => b.value?.invoice);
const payments = computed(() => invoice.value?.payments || []);
const totalPaid = computed(() => payments.value.reduce((s, p) => s + Number(p.amount || 0), 0));
const remainingBalance = computed(() => Number(invoice.value?.total || 0) - Number(invoice.value?.paid_amount || 0));
const hasAppointments = computed(() => (b.value?.appointments || []).length > 0);

function getServiceProgress(bs) {
    if (!bs.sessions_count || bs.sessions_count === 0) return 0;
    return Math.round((bs.completed_sessions / bs.sessions_count) * 100);
}

/* -- Payment Modal ---------------------------------------- */
const showPaymentModal = ref(false);
const paymentForm = useForm({
    payment_method_id: null,
    amount: '',
    reference_number: '',
    notes: '',
});

function openPaymentModal() {
    paymentForm.reset();
    paymentForm.amount = remainingBalance.value > 0 ? remainingBalance.value : '';
    showPaymentModal.value = true;
}

function submitPayment() {
    paymentForm.post(`/secretary/bundle-bookings/${b.value.id}/payment`, {
        preserveScroll: true,
        onSuccess: () => {
            showPaymentModal.value = false;
            paymentForm.reset();
        },
    });
}

/* -- Check-In --------------------------------------------- */
const checkingIn = ref(null);

function checkInAppointment(appointment) {
    checkingIn.value = appointment.id;
    router.post(`/secretary/bundle-bookings/${b.value.id}/appointments/${appointment.id}/check-in`, {}, {
        preserveScroll: true,
        onFinish: () => { checkingIn.value = null; },
    });
}

/* -- Reschedule Modal ------------------------------------- */
const showRescheduleModal = ref(false);
const rescheduleTarget = ref(null);
const rescheduleForm = reactive({
    doctor_id: null,
    date: '',
    start_time: '',
    end_time: '',
    availableSlots: [],
    loadingSlots: false,
    scheduleWarning: '',
});
const rescheduling = ref(false);

function openReschedule(appointment) {
    rescheduleTarget.value = appointment;
    rescheduleForm.doctor_id = appointment.doctor_id;
    rescheduleForm.date = '';
    rescheduleForm.start_time = '';
    rescheduleForm.end_time = '';
    rescheduleForm.availableSlots = [];
    rescheduleForm.scheduleWarning = '';
    showRescheduleModal.value = true;
}

async function onRescheduleDateChange() {
    if (!rescheduleForm.date || !rescheduleForm.doctor_id) return;

    const dateObj = new Date(rescheduleForm.date + 'T00:00:00');
    const jsDay = dateObj.getDay();
    const systemDay = jsToSystemDay(jsDay);
    const dayNames = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
    const schedule = props.doctorSchedules.find(
        s => Number(s.doctor_id) === Number(rescheduleForm.doctor_id) && Number(s.day_of_week) === systemDay
    );

    if (!schedule) {
        rescheduleForm.scheduleWarning = `Doctor does not work on ${dayNames[jsDay]}s`;
        rescheduleForm.availableSlots = [];
        rescheduleForm.start_time = '';
        rescheduleForm.end_time = '';
        return;
    }

    rescheduleForm.scheduleWarning = '';
    rescheduleForm.loadingSlots = true;
    rescheduleForm.availableSlots = [];
    rescheduleForm.start_time = '';
    rescheduleForm.end_time = '';

    // Get service duration
    const apt = rescheduleTarget.value;
    const bs = apt?.bundle_booking_service;
    const duration = bs?.service?.session_duration_minutes || 30;

    try {
        const params = new URLSearchParams({
            doctor_id: rescheduleForm.doctor_id,
            date: rescheduleForm.date,
            duration: duration,
        });
        const response = await fetch(`/api/time-slots?${params.toString()}`);
        if (response.ok) {
            const data = await response.json();
            rescheduleForm.availableSlots = data.slots || data || [];
        }
    } catch (err) {
        rescheduleForm.availableSlots = [];
    } finally {
        rescheduleForm.loadingSlots = false;
    }
}

function selectRescheduleSlot(slot) {
    rescheduleForm.start_time = slot.start;
    rescheduleForm.end_time = slot.end;
}

function submitReschedule() {
    if (!rescheduleForm.date || !rescheduleForm.start_time || !rescheduleForm.end_time) return;
    rescheduling.value = true;

    router.post(`/secretary/bundle-bookings/${b.value.id}/appointments/${rescheduleTarget.value.id}/reschedule`, {
        doctor_id: rescheduleForm.doctor_id,
        date: rescheduleForm.date,
        start_time: rescheduleForm.start_time,
        end_time: rescheduleForm.end_time,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            showRescheduleModal.value = false;
        },
        onFinish: () => { rescheduling.value = false; },
    });
}

/* -- Retouch Modal ---------------------------------------- */
const showRetouchModal = ref(false);
const retouchForm = reactive({
    package_bundle_booking_service_id: null,
    doctor_id: null,
    date: '',
    start_time: '',
    end_time: '',
    availableSlots: [],
    loadingSlots: false,
    scheduleWarning: '',
});
const retouching = ref(false);

function openRetouchModal() {
    retouchForm.package_bundle_booking_service_id = null;
    retouchForm.doctor_id = null;
    retouchForm.date = '';
    retouchForm.start_time = '';
    retouchForm.end_time = '';
    retouchForm.availableSlots = [];
    retouchForm.scheduleWarning = '';
    showRetouchModal.value = true;
}

async function onRetouchDateChange() {
    if (!retouchForm.date || !retouchForm.doctor_id) return;

    const dateObj = new Date(retouchForm.date + 'T00:00:00');
    const jsDay = dateObj.getDay();
    const systemDay = jsToSystemDay(jsDay);
    const dayNames = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
    const schedule = props.doctorSchedules.find(
        s => Number(s.doctor_id) === Number(retouchForm.doctor_id) && Number(s.day_of_week) === systemDay
    );

    if (!schedule) {
        retouchForm.scheduleWarning = `Doctor does not work on ${dayNames[jsDay]}s`;
        retouchForm.availableSlots = [];
        retouchForm.start_time = '';
        retouchForm.end_time = '';
        return;
    }

    retouchForm.scheduleWarning = '';
    retouchForm.loadingSlots = true;
    retouchForm.availableSlots = [];
    retouchForm.start_time = '';
    retouchForm.end_time = '';

    // Get service duration
    const bsId = retouchForm.package_bundle_booking_service_id;
    const bs = b.value.bundle_services?.find(s => s.id === bsId);
    const duration = bs?.service?.session_duration_minutes || 30;

    try {
        const params = new URLSearchParams({
            doctor_id: retouchForm.doctor_id,
            date: retouchForm.date,
            duration: duration,
        });
        const response = await fetch(`/api/time-slots?${params.toString()}`);
        if (response.ok) {
            const data = await response.json();
            retouchForm.availableSlots = data.slots || data || [];
        }
    } catch (err) {
        retouchForm.availableSlots = [];
    } finally {
        retouchForm.loadingSlots = false;
    }
}

function selectRetouchSlot(slot) {
    retouchForm.start_time = slot.start;
    retouchForm.end_time = slot.end;
}

function submitRetouch() {
    if (!retouchForm.package_bundle_booking_service_id || !retouchForm.doctor_id || !retouchForm.date || !retouchForm.start_time || !retouchForm.end_time) return;
    retouching.value = true;

    router.post(`/secretary/bundle-bookings/${b.value.id}/retouch`, {
        package_bundle_booking_service_id: retouchForm.package_bundle_booking_service_id,
        doctor_id: retouchForm.doctor_id,
        date: retouchForm.date,
        start_time: retouchForm.start_time,
        end_time: retouchForm.end_time,
    }, {
        preserveScroll: true,
        onSuccess: () => { showRetouchModal.value = false; },
        onFinish: () => { retouching.value = false; },
    });
}

/* -- Sections Toggle -------------------------------------- */
const expandedSections = ref({ services: true, appointments: true, visits: true, payments: true });
function toggleSection(key) {
    expandedSections.value[key] = !expandedSections.value[key];
}
</script>

<template>
    <div class="space-y-6">
        <!-- Header -->
        <div class="flex items-center justify-between flex-wrap gap-3">
            <div class="flex items-center gap-3">
                <Link href="/secretary/bundle-bookings"
                      class="p-2 rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-100 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                </Link>
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">Bundle Booking</h1>
                    <span class="text-sm font-mono" style="color: #0d9488;">{{ b.booking_number }}</span>
                </div>
            </div>
            <div class="flex items-center gap-2 flex-wrap">
                <button v-if="b.status === 'in_progress'"
                        @click="openRetouchModal"
                        class="inline-flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium text-white transition"
                        style="background-color: #7c3aed;">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                    </svg>
                    Add Retouch
                </button>
                <button v-if="b.status !== 'cancelled' && b.status !== 'completed' && remainingBalance > 0"
                        @click="openPaymentModal"
                        class="inline-flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium text-white transition"
                        style="background-color: #0d9488;">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                    Record Payment
                </button>
            </div>
        </div>

        <!-- Overview Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-white rounded-lg shadow-sm p-5">
                <div class="text-xs font-medium text-gray-400 uppercase mb-2">{{ isRtl ? 'الحالة' : 'Status' }}</div>
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold"
                      :class="bookingStatusColors[b.status] || 'bg-gray-100 text-gray-800'">
                    {{ b.status?.replace(/_/g, ' ') }}
                </span>
            </div>
            <div class="bg-white rounded-lg shadow-sm p-5">
                <div class="text-xs font-medium text-gray-400 uppercase mb-2">Progress</div>
                <div class="flex items-center gap-3">
                    <div class="flex-1 bg-gray-200 rounded-full h-2.5">
                        <div class="h-2.5 rounded-full transition-all" :style="`width: ${b.progress_percentage}%; background-color: #0d9488;`"></div>
                    </div>
                    <span class="text-sm font-bold text-gray-700">{{ b.progress_percentage }}%</span>
                </div>
            </div>
            <div class="bg-white rounded-lg shadow-sm p-5">
                <div class="text-xs font-medium text-gray-400 uppercase mb-2">Total Price</div>
                <div class="text-xl font-bold" style="color: #0d9488;">{{ formatCurrency(b.total_price) }}</div>
            </div>
            <div class="bg-white rounded-lg shadow-sm p-5">
                <div class="text-xs font-medium text-gray-400 uppercase mb-2">Balance Due</div>
                <div class="text-xl font-bold" :class="Number(b.balance_due) > 0 ? 'text-red-600' : 'text-green-600'">
                    {{ Number(b.balance_due) > 0 ? formatCurrency(b.balance_due) : 'Paid' }}
                </div>
                <div class="text-xs text-gray-400 mt-1">Paid: {{ formatCurrency(b.total_paid) }}</div>
            </div>
        </div>

        <!-- Patient & Bundle -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div class="bg-white rounded-lg shadow-sm p-6">
                <h3 class="text-xs font-semibold text-gray-400 uppercase mb-4">{{ isRtl ? 'المريض' : 'Patient' }}</h3>
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 rounded-full flex items-center justify-center text-white text-lg font-semibold" style="background-color: #0d9488;">{{ b.patient?.full_name?.charAt(0) }}</div>
                    <div>
                        <div class="font-semibold text-gray-900 text-lg">{{ b.patient?.full_name }}</div>
                        <div class="text-sm text-gray-500">{{ b.patient?.phone }}</div>
                        <div v-if="b.patient?.file_number" class="text-xs font-mono mt-0.5" style="color: #0d9488;">File #{{ b.patient.file_number }}</div>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-lg shadow-sm p-6">
                <h3 class="text-xs font-semibold text-gray-400 uppercase mb-4">{{ isRtl ? 'تفاصيل الحجز' : 'Booking Details' }}</h3>
                <div class="grid grid-cols-2 gap-3 text-sm">
                    <div><span class="text-gray-400">Bundle</span><div class="font-medium text-gray-900">{{ b.package_bundle?.name_en }}</div></div>
                    <div><span class="text-gray-400">{{ isRtl ? 'المصدر' : 'Source' }}</span><div class="font-medium text-gray-900 capitalize">{{ b.source }}</div></div>
                    <div><span class="text-gray-400">Created</span><div class="font-medium text-gray-900">{{ formatDateTime(b.created_at) }}</div></div>
                    <div v-if="b.started_at"><span class="text-gray-400">Started</span><div class="font-medium text-gray-900">{{ formatDateTime(b.started_at) }}</div></div>
                    <div v-if="b.completed_at"><span class="text-gray-400">{{ isRtl ? 'مكتمل' : 'Completed' }}</span><div class="font-medium text-green-600">{{ formatDateTime(b.completed_at) }}</div></div>
                </div>
                <div v-if="b.promo_code" class="mt-4 pt-3 border-t border-teal-100 flex items-center gap-2">
                    <svg class="w-4 h-4 text-teal-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5.5A2.5 2.5 0 109.5 8H12zm-7 4h14M5 12a2 2 0 110-4h14a2 2 0 110 4M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7" /></svg>
                    <span class="text-xs font-semibold text-teal-700 uppercase tracking-wider">Promo Code:</span>
                    <span class="font-bold font-mono tracking-wider text-sm text-gray-900">{{ b.promo_code }}</span>
                    <span v-if="b.invoice?.discount_code_id" class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-100 text-emerald-700">Applied</span>
                </div>
                <div v-if="b.notes" class="mt-4 pt-3 border-t border-gray-100">
                    <span class="text-xs text-gray-400">{{ isRtl ? 'ملاحظات' : 'Notes' }}</span>
                    <div class="text-sm text-gray-700 mt-1">{{ b.notes }}</div>
                </div>
            </div>
        </div>

        <!-- Services Progress -->
        <div class="bg-white rounded-lg shadow-sm overflow-hidden">
            <button @click="toggleSection('services')" class="w-full flex items-center justify-between p-5 ltr:text-left rtl:ltr:text-right rtl:text-left hover:bg-gray-50 transition">
                <h3 class="text-lg font-semibold text-gray-800">Services Progress</h3>
                <svg class="w-5 h-5 text-gray-400 transition-transform" :class="expandedSections.services ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
            </button>
            <div v-show="expandedSections.services" class="px-5 pb-5 space-y-4">
                <div v-for="bs in b.bundle_services" :key="bs.id"
                     class="border rounded-xl p-4"
                     :class="bs.status === 'completed' ? 'border-green-200 bg-green-50/30' : bs.status === 'cancelled' ? 'border-red-200 bg-red-50/30' : 'border-gray-200'">
                    <div class="flex items-start justify-between mb-3">
                        <div>
                            <div class="font-medium text-gray-900">{{ bs.service?.name_en }}</div>
                            <div class="text-sm text-gray-500 mt-0.5">Doctor: <span class="font-medium">{{ bs.doctor?.name_en || '-' }}</span></div>
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold border" :class="serviceStatusColors[bs.status] || 'bg-gray-50 text-gray-700 border-gray-200'">{{ bs.status?.replace(/_/g, ' ') }}</span>
                            <span class="text-sm font-semibold" style="color: #0d9488;">{{ formatCurrency(bs.bundle_price) }}</span>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="flex-1 bg-gray-200 rounded-full h-2">
                            <div class="h-2 rounded-full transition-all" :style="`width: ${getServiceProgress(bs)}%; background-color: ${bs.status === 'completed' ? '#22c55e' : '#0d9488'};`"></div>
                        </div>
                        <span class="text-xs font-medium text-gray-600 w-24 ltr:text-right rtl:text-left">{{ bs.completed_sessions }} / {{ bs.sessions_count }} sessions</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Appointments Schedule -->
        <div v-if="hasAppointments" class="bg-white rounded-lg shadow-sm overflow-hidden">
            <button @click="toggleSection('appointments')" class="w-full flex items-center justify-between p-5 ltr:text-left rtl:ltr:text-right rtl:text-left hover:bg-gray-50 transition">
                <h3 class="text-lg font-semibold text-gray-800">Appointments Schedule ({{ b.appointments?.length || 0 }})</h3>
                <svg class="w-5 h-5 text-gray-400 transition-transform" :class="expandedSections.appointments ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
            </button>
            <div v-show="expandedSections.appointments" class="px-5 pb-5">
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="border-b border-gray-200">
                                <th class="px-3 py-2.5 ltr:text-left rtl:ltr:text-right rtl:text-left text-xs font-medium text-gray-500 uppercase">{{ isRtl ? 'الخدمة' : 'Service' }}</th>
                                <th class="px-3 py-2.5 text-center text-xs font-medium text-gray-500 uppercase">Session</th>
                                <th class="px-3 py-2.5 ltr:text-left rtl:ltr:text-right rtl:text-left text-xs font-medium text-gray-500 uppercase">{{ isRtl ? 'الطبيب' : 'Doctor' }}</th>
                                <th class="px-3 py-2.5 ltr:text-left rtl:ltr:text-right rtl:text-left text-xs font-medium text-gray-500 uppercase">{{ isRtl ? 'التاريخ' : 'Date' }}</th>
                                <th class="px-3 py-2.5 ltr:text-left rtl:ltr:text-right rtl:text-left text-xs font-medium text-gray-500 uppercase">{{ isRtl ? 'الوقت' : 'Time' }}</th>
                                <th class="px-3 py-2.5 text-center text-xs font-medium text-gray-500 uppercase">{{ isRtl ? 'الحالة' : 'Status' }}</th>
                                <th class="px-3 py-2.5 text-center text-xs font-medium text-gray-500 uppercase">{{ isRtl ? 'إجراءات' : 'Actions' }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="apt in b.appointments" :key="apt.id" class="hover:bg-gray-50">
                                <td class="px-3 py-2.5 font-medium text-gray-800">
                                    {{ apt.bundle_booking_service?.service?.name_en || '-' }}
                                    <span v-if="apt.is_retouch" class="ltr:ml-1 rtl:mr-1 text-xs text-purple-600 font-semibold">(Retouch)</span>
                                </td>
                                <td class="px-3 py-2.5 text-center text-gray-600">{{ apt.session_number }}</td>
                                <td class="px-3 py-2.5 text-gray-600">{{ apt.doctor?.name_en || '-' }}</td>
                                <td class="px-3 py-2.5 text-gray-600">{{ formatDate(apt.appointment_date) }}</td>
                                <td class="px-3 py-2.5 text-gray-600">{{ formatTime(apt.start_time) }} - {{ formatTime(apt.end_time) }}</td>
                                <td class="px-3 py-2.5 text-center">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold"
                                          :class="appointmentStatusColors[apt.status] || 'bg-gray-100 text-gray-800'">
                                        {{ apt.status?.replace(/_/g, ' ') }}
                                    </span>
                                </td>
                                <td class="px-3 py-2.5 text-center">
                                    <div class="flex items-center justify-center gap-1">
                                        <!-- Check-in button -->
                                        <button v-if="['scheduled', 'confirmed'].includes(apt.status) && !apt.visit_id && ['in_progress', 'confirmed'].includes(b.status)"
                                                @click="checkInAppointment(apt)"
                                                :disabled="checkingIn === apt.id"
                                                class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-medium text-white transition disabled:opacity-50"
                                                style="background-color: #22c55e;">
                                            <svg v-if="checkingIn === apt.id" class="w-3 h-3 animate-spin" fill="none" viewBox="0 0 24 24">
                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                                            </svg>
                                            Check In
                                        </button>
                                        <!-- Reschedule button -->
                                        <button v-if="['scheduled', 'confirmed'].includes(apt.status) && !apt.visit_id"
                                                @click="openReschedule(apt)"
                                                class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-medium text-gray-600 bg-gray-100 hover:bg-gray-200 transition">
                                            Reschedule
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Visits -->
        <div class="bg-white rounded-lg shadow-sm overflow-hidden">
            <button @click="toggleSection('visits')" class="w-full flex items-center justify-between p-5 ltr:text-left rtl:ltr:text-right rtl:text-left hover:bg-gray-50 transition">
                <h3 class="text-lg font-semibold text-gray-800">Visits ({{ b.visits?.length || 0 }})</h3>
                <svg class="w-5 h-5 text-gray-400 transition-transform" :class="expandedSections.visits ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
            </button>
            <div v-show="expandedSections.visits" class="px-5 pb-5">
                <div v-if="b.visits && b.visits.length > 0" class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead><tr class="border-b border-gray-200">
                            <th class="px-3 py-2.5 ltr:text-left rtl:ltr:text-right rtl:text-left text-xs font-medium text-gray-500 uppercase">{{ isRtl ? 'الخدمة' : 'Service' }}</th>
                            <th class="px-3 py-2.5 text-center text-xs font-medium text-gray-500 uppercase">Session #</th>
                            <th class="px-3 py-2.5 ltr:text-left rtl:ltr:text-right rtl:text-left text-xs font-medium text-gray-500 uppercase">{{ isRtl ? 'الطبيب' : 'Doctor' }}</th>
                            <th class="px-3 py-2.5 ltr:text-left rtl:ltr:text-right rtl:text-left text-xs font-medium text-gray-500 uppercase">{{ isRtl ? 'التاريخ' : 'Date' }}</th>
                            <th class="px-3 py-2.5 text-center text-xs font-medium text-gray-500 uppercase">{{ isRtl ? 'الحالة' : 'Status' }}</th>
                        </tr></thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="visit in b.visits" :key="visit.id" class="hover:bg-gray-50">
                                <td class="px-3 py-2.5 font-medium text-gray-800">{{ visit.service?.name_en || '-' }}</td>
                                <td class="px-3 py-2.5 text-center text-gray-600">{{ visit.session_number || '-' }}</td>
                                <td class="px-3 py-2.5 text-gray-600">{{ visit.doctor?.name_en || '-' }}</td>
                                <td class="px-3 py-2.5 text-gray-600">{{ formatDate(visit.visit_date) }}</td>
                                <td class="px-3 py-2.5 text-center"><span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold" :class="visitStatusColors[visit.status] || 'bg-gray-100 text-gray-800'">{{ visit.status?.replace(/_/g, ' ') }}</span></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div v-else class="text-center py-6 text-sm text-gray-500">
                    No visits created yet. Visits are created when patients check in for their appointments.
                </div>
            </div>
        </div>

        <!-- Payments -->
        <div class="bg-white rounded-lg shadow-sm overflow-hidden">
            <button @click="toggleSection('payments')" class="w-full flex items-center justify-between p-5 ltr:text-left rtl:ltr:text-right rtl:text-left hover:bg-gray-50 transition">
                <h3 class="text-lg font-semibold text-gray-800">Payments ({{ payments.length }})</h3>
                <svg class="w-5 h-5 text-gray-400 transition-transform" :class="expandedSections.payments ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
            </button>
            <div v-show="expandedSections.payments" class="px-5 pb-5">
                <table v-if="payments.length > 0" class="min-w-full text-sm">
                    <thead><tr class="border-b border-gray-200">
                        <th class="px-3 py-2.5 ltr:text-left rtl:ltr:text-right rtl:text-left text-xs font-medium text-gray-500 uppercase">{{ isRtl ? 'التاريخ' : 'Date' }}</th>
                        <th class="px-3 py-2.5 ltr:text-left rtl:ltr:text-right rtl:text-left text-xs font-medium text-gray-500 uppercase">{{ isRtl ? 'الطريقة' : 'Method' }}</th>
                        <th class="px-3 py-2.5 ltr:text-right rtl:text-left text-xs font-medium text-gray-500 uppercase">{{ isRtl ? 'المبلغ' : 'Amount' }}</th>
                        <th class="px-3 py-2.5 ltr:text-left rtl:ltr:text-right rtl:text-left text-xs font-medium text-gray-500 uppercase">Reference</th>
                    </tr></thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="payment in payments" :key="payment.id" class="hover:bg-gray-50">
                            <td class="px-3 py-2.5 text-gray-600">{{ formatDate(payment.payment_date) }}</td>
                            <td class="px-3 py-2.5 text-gray-600">{{ payment.payment_method?.name_en || '-' }}</td>
                            <td class="px-3 py-2.5 ltr:text-right rtl:text-left font-semibold text-green-600">{{ formatCurrency(payment.amount) }}</td>
                            <td class="px-3 py-2.5 text-gray-500 font-mono text-xs">{{ payment.reference_number || '-' }}</td>
                        </tr>
                    </tbody>
                </table>
                <div v-else class="text-center py-6 text-sm text-gray-500">No payments recorded yet.</div>
                <div class="mt-4 border-t border-gray-200 pt-4">
                    <div class="max-w-xs ltr:ml-auto rtl:mr-auto space-y-2 text-sm">
                        <div class="flex justify-between"><span class="text-gray-500">{{ isRtl ? 'الإجمالي' : 'Total' }}</span><span class="font-bold" style="color: #0d9488;">{{ formatCurrency(invoice?.total) }}</span></div>
                        <div class="flex justify-between"><span class="text-gray-500">{{ isRtl ? 'المدفوع' : 'Paid' }}</span><span class="font-bold text-green-600">{{ formatCurrency(totalPaid) }}</span></div>
                        <div class="flex justify-between border-t pt-2"><span class="font-bold text-gray-700">Balance Due</span><span class="font-bold" :class="remainingBalance > 0 ? 'text-red-600' : 'text-green-600'">{{ formatCurrency(remainingBalance) }}</span></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Payment Modal -->
    <Teleport to="body">
        <div v-if="showPaymentModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="fixed inset-0 bg-black/40" @click="showPaymentModal = false"></div>
            <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md p-6 z-10">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-lg font-semibold text-gray-800">{{ isRtl ? 'تسجيل دفعة' : 'Record Payment' }}</h3>
                    <button @click="showPaymentModal = false" class="p-1 text-gray-400 hover:text-gray-600"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg></button>
                </div>
                <form @submit.prevent="submitPayment" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Payment Method *</label>
                        <select v-model="paymentForm.payment_method_id" class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-teal-200">
                            <option :value="null" disabled>Select method</option>
                            <option v-for="pm in paymentMethods" :key="pm.id" :value="pm.id">{{ pm.name_en }}</option>
                        </select>
                        <p v-if="paymentForm.errors.payment_method_id" class="text-red-500 text-xs mt-1">{{ paymentForm.errors.payment_method_id }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Amount ({{ currencyCode }}) *</label>
                        <input v-model="paymentForm.amount" type="number" step="0.01" min="0.01" class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-teal-200" />
                        <p v-if="paymentForm.errors.amount" class="text-red-500 text-xs mt-1">{{ paymentForm.errors.amount }}</p>
                        <p v-if="remainingBalance > 0" class="text-xs text-gray-400 mt-1">Remaining: {{ formatCurrency(remainingBalance) }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Reference Number</label>
                        <input v-model="paymentForm.reference_number" type="text" class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-teal-200" placeholder="Optional" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ isRtl ? 'ملاحظات' : 'Notes' }}</label>
                        <textarea v-model="paymentForm.notes" rows="2" class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-teal-200 resize-none" placeholder="Optional"></textarea>
                    </div>
                    <div class="flex gap-3 pt-2">
                        <button type="button" @click="showPaymentModal = false" class="flex-1 px-4 py-2.5 rounded-lg text-sm font-medium text-gray-600 bg-gray-100 hover:bg-gray-200 transition">{{ isRtl ? 'إلغاء' : 'Cancel' }}</button>
                        <button type="submit" :disabled="paymentForm.processing" class="flex-1 px-4 py-2.5 rounded-lg text-sm font-medium text-white transition disabled:opacity-50" style="background-color: #0d9488;">
                            {{ paymentForm.processing ? 'Processing...' : 'Record Payment' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </Teleport>

    <!-- Reschedule Modal -->
    <Teleport to="body">
        <div v-if="showRescheduleModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="fixed inset-0 bg-black/40" @click="showRescheduleModal = false"></div>
            <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-lg p-6 z-10 max-h-[90vh] overflow-y-auto">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-lg font-semibold text-gray-800">Reschedule Appointment</h3>
                    <button @click="showRescheduleModal = false" class="p-1 text-gray-400 hover:text-gray-600 transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ isRtl ? 'الطبيب' : 'Doctor' }}</label>
                        <select v-model="rescheduleForm.doctor_id" @change="rescheduleForm.date = ''; rescheduleForm.availableSlots = []; rescheduleForm.start_time = ''; rescheduleForm.end_time = '';"
                                class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-teal-200 focus:border-transparent">
                            <option v-for="doc in doctors" :key="doc.id" :value="doc.id">{{ doc.name_en }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ isRtl ? 'التاريخ' : 'Date' }}</label>
                        <DoctorDatePicker
                            v-model="rescheduleForm.date"
                            :doctor-id="rescheduleForm.doctor_id"
                            :doctor-schedules="doctorSchedules"
                            :min-date="new Date().toISOString().split('T')[0]"
                            accent-color="#0d9488"
                            popover
                            @update:model-value="onRescheduleDateChange"
                        />
                    </div>
                    <div v-if="rescheduleForm.scheduleWarning" class="p-2 bg-amber-50 border border-amber-200 rounded-lg text-xs text-amber-700">
                        {{ rescheduleForm.scheduleWarning }}
                    </div>
                    <div v-if="rescheduleForm.loadingSlots" class="flex items-center justify-center py-4 text-sm text-gray-500">
                        <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                        </svg>
                        Loading available slots...
                    </div>
                    <div v-else-if="rescheduleForm.date && rescheduleForm.availableSlots.length > 0">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Available Time Slots</label>
                        <div class="flex flex-wrap gap-2">
                            <button v-for="slot in rescheduleForm.availableSlots" :key="slot.start"
                                    @click="selectRescheduleSlot(slot)" type="button"
                                    class="px-3 py-1.5 text-xs rounded-lg border transition-all"
                                    :class="rescheduleForm.start_time === slot.start ? 'text-white border-transparent' : 'border-gray-200 text-gray-600 hover:border-gray-300 hover:bg-gray-50'"
                                    :style="rescheduleForm.start_time === slot.start ? 'background-color: #0d9488;' : ''">
                                {{ slot.start_12h || slot.start }}
                            </button>
                        </div>
                    </div>
                    <div v-else-if="rescheduleForm.date && !rescheduleForm.loadingSlots && !rescheduleForm.scheduleWarning"
                         class="text-center py-3 text-xs text-gray-400 bg-gray-50 rounded-lg">
                        No available slots for this date
                    </div>
                    <div class="flex gap-3 pt-2">
                        <button type="button" @click="showRescheduleModal = false" class="flex-1 px-4 py-2.5 rounded-lg text-sm font-medium text-gray-600 bg-gray-100 hover:bg-gray-200 transition">{{ isRtl ? 'إلغاء' : 'Cancel' }}</button>
                        <button @click="submitReschedule" :disabled="rescheduling || !rescheduleForm.start_time"
                                class="flex-1 px-4 py-2.5 rounded-lg text-sm font-medium text-white transition disabled:opacity-50"
                                style="background-color: #0d9488;">
                            {{ rescheduling ? 'Rescheduling...' : 'Reschedule' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </Teleport>

    <!-- Retouch Modal -->
    <Teleport to="body">
        <div v-if="showRetouchModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="fixed inset-0 bg-black/40" @click="showRetouchModal = false"></div>
            <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-lg p-6 z-10 max-h-[90vh] overflow-y-auto">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-lg font-semibold text-gray-800">Add Retouch Session</h3>
                    <button @click="showRetouchModal = false" class="p-1 text-gray-400 hover:text-gray-600 transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Service *</label>
                        <select v-model="retouchForm.package_bundle_booking_service_id"
                                class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-teal-200 focus:border-transparent">
                            <option :value="null" disabled>Select service</option>
                            <option v-for="bs in b.bundle_services" :key="bs.id" :value="bs.id">
                                {{ bs.service?.name_en }} ({{ bs.completed_sessions }}/{{ bs.sessions_count }} sessions)
                            </option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Doctor *</label>
                        <select v-model="retouchForm.doctor_id" @change="retouchForm.date = ''; retouchForm.availableSlots = []; retouchForm.start_time = ''; retouchForm.end_time = '';"
                                class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-teal-200 focus:border-transparent">
                            <option :value="null" disabled>Select doctor</option>
                            <option v-for="doc in doctors" :key="doc.id" :value="doc.id">{{ doc.name_en }}</option>
                        </select>
                    </div>
                    <div v-if="retouchForm.doctor_id">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Date *</label>
                        <DoctorDatePicker
                            v-model="retouchForm.date"
                            :doctor-id="retouchForm.doctor_id"
                            :doctor-schedules="doctorSchedules"
                            :min-date="new Date().toISOString().split('T')[0]"
                            accent-color="#0d9488"
                            popover
                            @update:model-value="onRetouchDateChange"
                        />
                    </div>
                    <div v-if="retouchForm.scheduleWarning" class="p-2 bg-amber-50 border border-amber-200 rounded-lg text-xs text-amber-700">
                        {{ retouchForm.scheduleWarning }}
                    </div>
                    <div v-if="retouchForm.loadingSlots" class="flex items-center justify-center py-4 text-sm text-gray-500">
                        <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                        </svg>
                        Loading available slots...
                    </div>
                    <div v-else-if="retouchForm.date && retouchForm.availableSlots.length > 0">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Available Time Slots</label>
                        <div class="flex flex-wrap gap-2">
                            <button v-for="slot in retouchForm.availableSlots" :key="slot.start"
                                    @click="selectRetouchSlot(slot)" type="button"
                                    class="px-3 py-1.5 text-xs rounded-lg border transition-all"
                                    :class="retouchForm.start_time === slot.start ? 'text-white border-transparent' : 'border-gray-200 text-gray-600 hover:border-gray-300 hover:bg-gray-50'"
                                    :style="retouchForm.start_time === slot.start ? 'background-color: #0d9488;' : ''">
                                {{ slot.start_12h || slot.start }}
                            </button>
                        </div>
                    </div>
                    <div v-else-if="retouchForm.date && !retouchForm.loadingSlots && !retouchForm.scheduleWarning"
                         class="text-center py-3 text-xs text-gray-400 bg-gray-50 rounded-lg">
                        No available slots for this date
                    </div>
                    <div class="flex gap-3 pt-2">
                        <button type="button" @click="showRetouchModal = false" class="flex-1 px-4 py-2.5 rounded-lg text-sm font-medium text-gray-600 bg-gray-100 hover:bg-gray-200 transition">{{ isRtl ? 'إلغاء' : 'Cancel' }}</button>
                        <button @click="submitRetouch" :disabled="retouching || !retouchForm.start_time || !retouchForm.package_bundle_booking_service_id"
                                class="flex-1 px-4 py-2.5 rounded-lg text-sm font-medium text-white transition disabled:opacity-50"
                                style="background-color: #7c3aed;">
                            {{ retouching ? 'Adding...' : 'Add Retouch Session' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </Teleport>
</template>
