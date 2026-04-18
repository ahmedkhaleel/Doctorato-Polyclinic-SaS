<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import { Link, router, useForm, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import QuickAddPatientModal from '@/Components/QuickAddPatientModal.vue';
import DoctorDatePicker from '@/Components/DoctorDatePicker.vue';
import { usePermissions } from '@/Composables/usePermissions.js';
import { useCurrency } from '@/Composables/useCurrency.js';

defineOptions({ layout: AdminLayout });

const { can } = usePermissions();
const { formatCurrency, currencyCode } = useCurrency();
const page = usePage();
const locale = computed(() => page.props.locale || 'ar');
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const props = defineProps({
    booking: Object,
    paymentMethods: Array,
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
    followUpInfo: { type: Object, default: null },
    followupFee: { type: Number, default: 0 },
});

/* bookingTypeLabels - uses $t in template instead */
const bookingTypeLabels = {
    dermatology_consultation: 'Dermatology Consultation',
    cosmetic_consultation: 'Cosmetic Consultation',
    service: 'Service Booking',
    dental_consultation: 'Dental Consultation',
    dental_service: 'Dental Service',
    pediatric_consultation: 'Pediatric Consultation',
    pediatric_service: 'Pediatric Service',
};

const isConsultationBooking = computed(() => {
    return ['dermatology_consultation', 'cosmetic_consultation', 'dental_consultation', 'pediatric_consultation'].includes(props.booking.booking_type);
});

const bookingTypeColors = {
    dermatology_consultation: 'bg-slate-50 text-[#1B365D] border-slate-200',
    cosmetic_consultation: 'bg-amber-50 text-[#C4A265] border-amber-200',
    service: 'bg-emerald-50 text-emerald-700 border-emerald-200',
    dental_consultation: 'bg-slate-50 text-[#1B365D] border-slate-200',
    dental_service: 'bg-teal-50 text-teal-700 border-teal-200',
    pediatric_consultation: 'bg-emerald-50 text-emerald-700 border-emerald-200',
    pediatric_service: 'bg-lime-50 text-lime-700 border-lime-200',
};

/* ── Helpers ───────────────────────────────────────────── */
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
    try {
        const [h, m] = time.split(':');
        const hr = parseInt(h);
        const ampm = hr >= 12 ? 'PM' : 'AM';
        return `${hr % 12 || 12}:${m} ${ampm}`;
    } catch { return time; }
}

/* ── Status Colors ─────────────────────────────────────── */
const bookingStatusColors = {
    unconfirmed: 'bg-amber-50 text-amber-700 border-amber-200',
    confirmed: 'bg-slate-50 text-[#1B365D] border-slate-200',
    in_progress: 'bg-slate-50 text-[#1B365D] border-slate-200',
    completed: 'bg-emerald-50 text-emerald-700 border-emerald-200',
    cancelled: 'bg-red-50 text-red-700 border-red-200',
};

const bookingStatusLabels = {
    unconfirmed: 'Unconfirmed',
    confirmed: 'Confirmed',
    in_progress: 'In Progress',
    completed: 'Completed',
    cancelled: 'Cancelled',
};

const appointmentStatusColors = {
    scheduled: 'bg-gray-50 text-gray-600 border-gray-200',
    confirmed: 'bg-slate-50 text-[#1B365D] border-slate-200',
    checked_in: 'bg-slate-50 text-[#1B365D] border-slate-200',
    in_progress: 'bg-amber-50 text-amber-700 border-amber-200',
    completed: 'bg-emerald-50 text-emerald-700 border-emerald-200',
    cancelled: 'bg-red-50 text-red-700 border-red-200',
    no_show: 'bg-amber-50 text-[#C4A265] border-amber-200',
};

const sourceColors = {
    website: 'bg-slate-50 text-[#1B365D] border-slate-200',
    secretary: 'bg-amber-50 text-amber-700 border-amber-200',
    admin: 'bg-amber-50 text-amber-700 border-amber-200',
};

const serviceStatusColors = {
    active: 'bg-emerald-50 text-emerald-700 border-emerald-200',
    pending: 'bg-amber-50 text-amber-700 border-amber-200',
    completed: 'bg-slate-50 text-[#1B365D] border-slate-200',
    cancelled: 'bg-red-50 text-red-700 border-red-200',
};

/* ── Expandable Services ───────────────────────────────── */
const expandedServices = ref({});

function toggleServiceExpand(index) {
    expandedServices.value[index] = !expandedServices.value[index];
}

/* ── Invoice computed ──────────────────────────────────── */
const invoice = computed(() => props.booking.invoice);
const invoiceBalance = computed(() => {
    if (!invoice.value) return 0;
    return Math.max(0, Number(invoice.value.total || 0) - Number(invoice.value.paid_amount || 0));
});

/* ── Status Update Form ────────────────────────────────── */
const statusForm = useForm({
    status: props.booking.status,
    admin_notes: props.booking.admin_notes || '',
});

function updateStatus() {
    statusForm.post(`/admin/bookings/${props.booking.id}/update`, {
        preserveScroll: true,
    });
}

/* ── Payment Form ──────────────────────────────────────── */
const showPaymentForm = ref(false);

const paymentForm = useForm({
    payment_method_id: '',
    amount: '',
    reference_number: '',
    notes: '',
});

function openPaymentForm() {
    paymentForm.amount = invoiceBalance.value > 0 ? invoiceBalance.value : '';
    showPaymentForm.value = true;
}

function submitPayment() {
    paymentForm.post(`/admin/bookings/${props.booking.id}/payment`, {
        preserveScroll: true,
        onSuccess: () => {
            paymentForm.reset();
            showPaymentForm.value = false;
        },
    });
}

/* ── Confirmation Form (for unconfirmed bookings) ──────── */
const showConfirmSection = ref(false);

const defaultConsultationFee = computed(() => {
    if (props.booking.booking_type === 'dermatology_consultation') return props.defaultDermatologyFee || 0;
    if (props.booking.booking_type === 'cosmetic_consultation') return props.defaultCosmeticFee || 0;
    if (props.booking.booking_type === 'dental_consultation') return props.defaultDentalFee || 0;
    return 0;
});

const confirmForm = useForm({
    patient_id: '',
    services: [{
        service_id: props.booking.service_id || '',
        doctor_id: props.booking.doctor_id || '',
        sessions_count: 1,
        unit_price: isConsultationBooking.value ? defaultConsultationFee.value : (props.booking.service?.price || 0),
        discount_per_session: 0,
    }],
    appointments: [{ service_index: 0, doctor_id: props.booking.doctor_id || '', appointment_date: props.booking.preferred_date || '', start_time: '', session_number: 1 }],
    notes: props.booking.notes || '',
});

/* Patient search for confirm form */
const patientSearch = ref('');
const showPatientDropdown = ref(false);
const selectedPatient = ref(null);

/* ── Unified Searchable Dropdown System ─────────────────── */
const openDropdown = ref(null);
const dropdownSearches = ref({});

function toggleDropdown(key) {
    if (openDropdown.value === key) {
        openDropdown.value = null;
    } else {
        openDropdown.value = key;
        dropdownSearches.value[key] = '';
    }
}

function getServiceName(serviceId) {
    if (!serviceId) return '';
    const s = props.services?.find(s => s.id == serviceId);
    return s ? (s.name_en || s.name_ar) : '';
}

function getDoctorName(doctorId) {
    if (!doctorId) return '';
    const d = props.doctors?.find(d => d.id == doctorId);
    return d ? (d.name_en || d.name_ar || d.name) : '';
}

function filteredServicesList(key) {
    const q = (dropdownSearches.value[key] || '').toLowerCase();
    if (!props.services) return [];
    if (!q) return props.services;
    return props.services.filter(s =>
        (s.name_ar && s.name_ar.includes(dropdownSearches.value[key])) ||
        (s.name_en && s.name_en.toLowerCase().includes(q))
    );
}

function filteredDoctorsList(key) {
    const q = (dropdownSearches.value[key] || '').toLowerCase();
    if (!props.doctors) return [];
    if (!q) return props.doctors;
    return props.doctors.filter(d =>
        (d.name_ar && d.name_ar.includes(dropdownSearches.value[key])) ||
        (d.name_en && d.name_en.toLowerCase().includes(q)) ||
        (d.name && d.name.toLowerCase().includes(q))
    );
}

/* ── Status Dropdown Options ──────────────────────────── */
const statusOptions = [
    { value: 'unconfirmed', color: 'bg-amber-400', bg: 'bg-amber-50 text-amber-700' },
    { value: 'confirmed', color: 'bg-slate-400', bg: 'bg-slate-50 text-[#1B365D]' },
    { value: 'in_progress', color: 'bg-slate-400', bg: 'bg-slate-50 text-[#1B365D]' },
    { value: 'completed', color: 'bg-emerald-400', bg: 'bg-emerald-50 text-emerald-700' },
    { value: 'cancelled', color: 'bg-red-400', bg: 'bg-red-50 text-red-700' },
];

function getStatusOption(val) {
    return statusOptions.find(o => o.value === val) || statusOptions[0];
}

function filteredStatusOptions(key) {
    const q = (dropdownSearches.value[key] || '').toLowerCase();
    if (!q) return statusOptions;
    return statusOptions.filter(o => {
        const label = o.value.replace(/_/g, ' ');
        return label.includes(q);
    });
}

/* Click-outside handler for all dropdowns */
function handleClickOutside(e) {
    if (!e.target.closest('.patient-search-wrapper')) {
        showPatientDropdown.value = false;
    }
    if (openDropdown.value && !e.target.closest('.ss-dropdown')) {
        openDropdown.value = null;
    }
}
onMounted(() => {
    document.addEventListener('click', handleClickOutside);
});
onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside);
});

/* Quick Add Patient Modal */
const showNewPatientModal = ref(false);
const newPatientPrefill = computed(() => ({
    full_name: props.booking?.full_name || '',
    phone: props.booking?.phone || '',
    email: props.booking?.email || '',
}));

function onPatientCreated(patient) {
    selectPatient(patient);
    if (props.patients && !props.patients.find(p => p.id === patient.id)) {
        props.patients.unshift(patient);
    }
}

const filteredPatients = computed(() => {
    if (!props.patients) return [];
    if (!patientSearch.value) return props.patients.slice(0, 20);
    const q = patientSearch.value.toLowerCase();
    return props.patients.filter(p =>
        p.full_name?.toLowerCase().includes(q) ||
        p.phone?.includes(q) ||
        p.file_number?.toString().includes(q)
    ).slice(0, 20);
});

function selectPatient(p) {
    selectedPatient.value = p;
    confirmForm.patient_id = p.id;
    patientSearch.value = p.full_name;
    showPatientDropdown.value = false;
}

function clearPatient() {
    selectedPatient.value = null;
    confirmForm.patient_id = '';
    patientSearch.value = '';
}

/* Service management in confirm form */
function addConfirmService() {
    confirmForm.services.push({ service_id: '', doctor_id: '', sessions_count: 1, unit_price: 0, discount_per_session: 0 });
}

function removeConfirmService(index) {
    if (confirmForm.services.length > 1) {
        confirmForm.services.splice(index, 1);
        // Remove related appointments
        confirmForm.appointments = confirmForm.appointments.filter(a => a.service_index !== index).map(a => {
            if (a.service_index > index) a.service_index--;
            return a;
        });
    }
}

/* Watch service changes to update price */
function onServiceChange(index) {
    const svc = props.services?.find(s => s.id == confirmForm.services[index].service_id);
    if (svc) {
        const price = parseFloat(svc.price_after_discount) || parseFloat(svc.price) || 0;
        confirmForm.services[index].unit_price = price;
        if (svc.default_sessions) {
            confirmForm.services[index].sessions_count = svc.default_sessions;
        }
    }
}

/* Appointment management in confirm form */
function addConfirmAppointment() {
    const lastAppt = confirmForm.appointments[confirmForm.appointments.length - 1];
    confirmForm.appointments.push({
        service_index: lastAppt?.service_index || 0,
        doctor_id: lastAppt?.doctor_id || '',
        appointment_date: '',
        start_time: '',
        session_number: confirmForm.appointments.length + 1,
    });
}

function removeConfirmAppointment(index) {
    if (confirmForm.appointments.length > 1) {
        confirmForm.appointments.splice(index, 1);
    }
}

/* Time slots */
const timeSlots = ref({});
const loadingSlots = ref({});

function onApptDateChange(apptIdx, newDate) {
    confirmForm.appointments[apptIdx].appointment_date = newDate;
    confirmForm.appointments[apptIdx].start_time = '';
    fetchTimeSlots(apptIdx);
}

async function fetchTimeSlots(apptIndex) {
    const appt = confirmForm.appointments[apptIndex];
    if (!appt.doctor_id || !appt.appointment_date) return;

    const svcIndex = appt.service_index;
    const svc = props.services?.find(s => s.id == confirmForm.services[svcIndex]?.service_id);
    const duration = svc?.duration || 30;

    const key = `${apptIndex}`;
    loadingSlots.value[key] = true;

    try {
        const response = await fetch(`/api/time-slots?doctor_id=${appt.doctor_id}&date=${appt.appointment_date}&duration=${duration}`);
        if (response.ok) {
            const data = await response.json();
            timeSlots.value[key] = data.slots || data || [];
        }
    } catch (e) {
        timeSlots.value[key] = [];
    } finally {
        loadingSlots.value[key] = false;
    }
}

const confirmProcessing = ref(false);
const confirmErrors = ref({});

function buildTransformedData() {
    const transformedServices = confirmForm.services.map((svc, svcIdx) => {
        const svcAppointments = confirmForm.appointments
            .filter(a => a.service_index === svcIdx)
            .map(a => {
                const service = props.services?.find(s => s.id == svc.service_id);
                const duration = service?.session_duration_minutes || 30;
                let endTime = a.start_time;
                if (a.start_time) {
                    const [h, m] = a.start_time.split(':').map(Number);
                    const totalMin = h * 60 + m + duration;
                    endTime = `${String(Math.floor(totalMin / 60)).padStart(2, '0')}:${String(totalMin % 60).padStart(2, '0')}`;
                }
                return {
                    doctor_id: a.doctor_id || svc.doctor_id || null,
                    date: a.appointment_date,
                    start_time: a.start_time,
                    end_time: endTime,
                };
            });

        if (svcAppointments.length === 0) {
            svcAppointments.push({
                doctor_id: svc.doctor_id || null,
                date: '',
                start_time: '',
                end_time: '',
            });
        }

        return {
            service_id: isConsultationBooking.value ? null : svc.service_id,
            doctor_id: svc.doctor_id || null,
            sessions_count: svc.sessions_count,
            unit_price: svc.unit_price,
            discount_per_session: svc.discount_per_session,
            appointments: svcAppointments,
        };
    });

    return {
        patient_id: confirmForm.patient_id,
        services: transformedServices,
        notes: confirmForm.notes,
    };
}

function getConsultationFeeForDoctor(doctorId) {
    const doctor = props.doctors?.find(d => d.id == doctorId);
    if (props.booking.booking_type === 'dermatology_consultation') {
        // Use settings-based pricing: consultant vs specialist
        if (doctor?.doctor_type === 'consultant') return props.dermatologyConsultantFee || props.defaultDermatologyFee || 0;
        if (doctor?.doctor_type === 'specialist') return props.dermatologySpecialistFee || props.defaultDermatologyFee || 0;
        return props.dermatologyConsultantFee || props.defaultDermatologyFee || 0;
    }
    if (props.booking.booking_type === 'cosmetic_consultation') {
        return props.cosmeticConsultationFee || props.defaultCosmeticFee || 0;
    }
    return 0;
}

function onConfirmDoctorChange(svcIdx) {
    if (isConsultationBooking.value) {
        confirmForm.services[svcIdx].unit_price = getConsultationFeeForDoctor(confirmForm.services[svcIdx].doctor_id);
    }
}

function submitConfirmation() {
    confirmProcessing.value = true;
    confirmErrors.value = {};

    const data = buildTransformedData();

    router.post(`/admin/bookings/${props.booking.id}/confirm`, data, {
        preserveScroll: true,
        onSuccess: () => {
            showConfirmSection.value = false;
        },
        onError: (errors) => {
            confirmErrors.value = errors;
        },
        onFinish: () => {
            confirmProcessing.value = false;
        },
    });
}

/* ── Computed ──────────────────────────────────────────── */
const bookingNumber = computed(() => props.booking.booking_number || `#${props.booking.id}`);
const isUnconfirmed = computed(() => props.booking.status === 'unconfirmed');
const hasInvoiceBalance = computed(() => invoice.value && invoiceBalance.value > 0);

/* ── Retouch Session Form ─────────────────────────────── */
const showRetouchForm = ref(false);
const canAddRetouch = computed(() => {
    const status = props.booking.status;
    return (status === 'in_progress' || status === 'completed') && props.booking.booking_services?.length > 0;
});

const retouchForm = useForm({
    booking_service_id: '',
    doctor_id: '',
    appointment_date: '',
    start_time: '',
});

// Auto-select first booking service
watch(() => showRetouchForm.value, (show) => {
    if (show && props.booking.booking_services?.length === 1) {
        retouchForm.booking_service_id = props.booking.booking_services[0].id;
    }
});

const retouchTimeSlots = ref([]);
const retouchLoadingSlots = ref(false);

async function fetchRetouchTimeSlots() {
    if (!retouchForm.doctor_id || !retouchForm.appointment_date) return;

    retouchLoadingSlots.value = true;
    try {
        const bs = props.booking.booking_services?.find(s => s.id == retouchForm.booking_service_id);
        const duration = bs?.service?.session_duration_minutes || 30;
        const response = await fetch(`/api/time-slots?doctor_id=${retouchForm.doctor_id}&date=${retouchForm.appointment_date}&duration=${duration}`);
        if (response.ok) {
            const data = await response.json();
            retouchTimeSlots.value = data.slots || data || [];
        }
    } catch (e) {
        retouchTimeSlots.value = [];
    } finally {
        retouchLoadingSlots.value = false;
    }
}

function submitRetouch() {
    retouchForm.post(`/admin/bookings/${props.booking.id}/retouch`, {
        preserveScroll: true,
        onSuccess: () => {
            retouchForm.reset();
            showRetouchForm.value = false;
            retouchTimeSlots.value = [];
        },
    });
}

// Count retouch sessions for a booking service
function retouchCount(bs) {
    return bs.appointments?.filter(a => a.is_retouch)?.length || 0;
}

/* ── Consent Documents ─────────────────────────────────── */
const consentForm = useForm({
    consents: null,
});
const consentFileInput = ref(null);

function onConsentFilesSelected(event) {
    const files = event.target.files;
    if (!files?.length) return;
    consentForm.consents = files;
    consentForm.post(`/admin/bookings/${props.booking.id}/consents`, {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => {
            consentForm.reset();
            if (consentFileInput.value) consentFileInput.value.value = '';
        },
        onError: () => {
            if (consentFileInput.value) consentFileInput.value.value = '';
        },
    });
}

function deleteConsent(consentId) {
    if (!confirm('Delete this consent document?')) return;
    router.post(`/admin/bookings/${props.booking.id}/consents/${consentId}/delete`, {
        preserveScroll: true,
    });
}

/* ── Follow-up Info ────────────────────────────────────── */
const showFollowUpBanner = computed(() => {
    return props.followUpInfo && props.followUpInfo.eligible;
});

/* ── Check-in Appointment ─────────────────────────────── */
const checkingIn = ref({});

function canCheckIn(appt) {
    return props.booking.status === 'in_progress'
        && !appt.visit_id
        && !appt.visit
        && !['cancelled', 'no_show', 'completed'].includes(appt.status);
}

function checkInAppointment(appt) {
    if (checkingIn.value[appt.id]) return;
    checkingIn.value[appt.id] = true;
    router.post(`/admin/bookings/${props.booking.id}/appointments/${appt.id}/check-in`, {}, {
        preserveScroll: true,
        onFinish: () => {
            checkingIn.value[appt.id] = false;
        },
    });
}

/* ── Reschedule Appointment ───────────────────────────── */
const editingAppointment = ref(null);

const rescheduleForm = useForm({
    appointment_date: '',
    start_time: '',
    end_time: '',
    doctor_id: '',
});

const rescheduleTimeSlots = ref([]);
const rescheduleLoadingSlots = ref(false);

function canReschedule(appt) {
    return !appt.visit_id
        && !appt.visit
        && !['completed', 'cancelled', 'no_show'].includes(appt.status);
}

function openRescheduleModal(appt) {
    editingAppointment.value = appt;
    rescheduleForm.appointment_date = appt.appointment_date?.split('T')[0] || '';
    rescheduleForm.start_time = appt.start_time?.substring(0, 5) || '';
    rescheduleForm.end_time = appt.end_time?.substring(0, 5) || '';
    rescheduleForm.doctor_id = appt.doctor_id || '';
    rescheduleTimeSlots.value = [];

    if (rescheduleForm.doctor_id && rescheduleForm.appointment_date) {
        fetchRescheduleTimeSlots();
    }
}

function closeRescheduleModal() {
    editingAppointment.value = null;
    rescheduleForm.reset();
    rescheduleTimeSlots.value = [];
}

async function fetchRescheduleTimeSlots() {
    if (!rescheduleForm.doctor_id || !rescheduleForm.appointment_date) return;

    rescheduleLoadingSlots.value = true;
    try {
        const bs = editingAppointment.value?.booking_service_id
            ? props.booking.booking_services?.find(s => s.id == editingAppointment.value.booking_service_id)
            : null;
        const duration = bs?.service?.session_duration_minutes || 30;
        const response = await fetch(`/api/time-slots?doctor_id=${rescheduleForm.doctor_id}&date=${rescheduleForm.appointment_date}&duration=${duration}`);
        if (response.ok) {
            const data = await response.json();
            rescheduleTimeSlots.value = data.slots || data || [];
        }
    } catch (e) {
        rescheduleTimeSlots.value = [];
    } finally {
        rescheduleLoadingSlots.value = false;
    }
}

function selectRescheduleSlot(slot) {
    rescheduleForm.start_time = slot.start;
    rescheduleForm.end_time = slot.end;
}

function submitReschedule() {
    if (!editingAppointment.value) return;
    rescheduleForm.put(`/admin/bookings/${props.booking.id}/appointments/${editingAppointment.value.id}/reschedule`, {
        preserveScroll: true,
        onSuccess: () => closeRescheduleModal(),
    });
}

/* ── Delete Booking (Super Admin Only) ─────────────────── */
const deletingBooking = ref(false);

function deleteBooking() {
    if (!confirm('Are you sure you want to permanently delete this booking? This will remove all appointments, services, invoice, and payments. This action cannot be undone.')) return;
    deletingBooking.value = true;
    router.post(`/admin/bookings/${props.booking.id}/delete`, {
        onFinish: () => { deletingBooking.value = false; },
    });
}

/* ── Edit Services (Super Admin Only) ──────────────────── */
const showEditServices = ref(false);

const editServicesForm = useForm({
    services: [],
});

function initEditServices() {
    editServicesForm.services = (props.booking.booking_services || []).map(bs => ({
        id: bs.id,
        service_id: bs.service_id || '',
        doctor_id: bs.doctor_id || '',
        sessions_count: bs.sessions_count || 1,
        unit_price: parseFloat(bs.unit_price) || 0,
        discount_per_session: parseFloat(bs.discount_per_session) || 0,
        notes: bs.notes || '',
        _delete: false,
    }));
    showEditServices.value = true;
}

function addEditService() {
    editServicesForm.services.push({
        id: null,
        service_id: '',
        doctor_id: '',
        sessions_count: 1,
        unit_price: 0,
        discount_per_session: 0,
        notes: '',
        _delete: false,
    });
}

function onEditServiceChange(index) {
    const svc = props.services?.find(s => s.id == editServicesForm.services[index].service_id);
    if (svc) {
        editServicesForm.services[index].unit_price = parseFloat(svc.price_after_discount) || parseFloat(svc.price) || 0;
        if (svc.default_sessions) {
            editServicesForm.services[index].sessions_count = svc.default_sessions;
        }
    }
}

function markServiceForDeletion(index) {
    const svc = editServicesForm.services[index];
    if (svc.id) {
        svc._delete = !svc._delete;
    } else {
        editServicesForm.services.splice(index, 1);
    }
}

function editServiceTotal(svc) {
    return ((parseFloat(svc.unit_price) || 0) - (parseFloat(svc.discount_per_session) || 0)) * (parseInt(svc.sessions_count) || 1);
}

const editServicesGrandTotal = computed(() => {
    return editServicesForm.services
        .filter(s => !s._delete)
        .reduce((sum, s) => sum + editServiceTotal(s), 0);
});

function submitEditServices() {
    editServicesForm.put(`/admin/bookings/${props.booking.id}/services`, {
        preserveScroll: true,
        onSuccess: () => { showEditServices.value = false; },
    });
}
</script>

<template>
    <div class="space-y-6">
        <!-- ─── Header ─────────────────────────────────────────── -->
        <!-- ═════════ Navy Hero Header ═════════ -->
        <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-[#1B365D] via-[#1B365D] to-[#0F2444] shadow-xl">
            <div class="pointer-events-none absolute -top-16 -end-16 h-56 w-56 rounded-full bg-[#C4A265]/20 blur-3xl"></div>
            <div class="pointer-events-none absolute -bottom-20 start-1/3 h-48 w-48 rounded-full bg-[#C4A265]/10 blur-3xl"></div>
            <div class="absolute inset-x-0 top-0 h-[2px] bg-gradient-to-r from-transparent via-[#C4A265] to-transparent"></div>
            <div class="relative p-4 md:p-6 flex flex-col md:flex-row md:items-center gap-4 md:gap-5 justify-between">
                <div class="flex items-start gap-3 md:gap-4 min-w-0">
                    <Link href="/admin/bookings" class="w-10 h-10 md:w-11 md:h-11 rounded-xl bg-white/10 backdrop-blur-sm border border-white/20 hover:bg-white/20 flex items-center justify-center text-white transition flex-shrink-0">
                        <svg class="w-5 h-5 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                    </Link>
                    <div class="w-12 h-12 md:w-14 md:h-14 rounded-xl bg-gradient-to-br from-[#C4A265] to-[#8B7043] flex items-center justify-center shadow-lg flex-shrink-0">
                        <svg class="w-6 h-6 md:w-7 md:h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    </div>
                    <div class="min-w-0">
                        <div class="flex items-center gap-2 mb-1">
                            <span class="h-[3px] w-6 bg-[#C4A265] rounded-full"></span>
                            <span class="text-[10px] font-bold text-[#C4A265] tracking-[0.25em] uppercase">{{ $t('a_booking') || 'Booking' }}</span>
                        </div>
                        <div class="flex flex-wrap items-center gap-2 md:gap-3">
                            <h1 class="text-xl md:text-2xl font-extrabold text-white tracking-tight">{{ $t('a_booking') }} {{ bookingNumber }}</h1>
                            <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-semibold border capitalize ring-1 ring-white/20" :class="bookingStatusColors[booking.status] || 'bg-white/10 text-white border-white/20'">
                                {{ $t('a_' + booking.status) || booking.status }}
                            </span>
                            <span v-if="booking.source" class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-semibold border capitalize ring-1 ring-white/20" :class="sourceColors[booking.source] || 'bg-white/10 text-white border-white/20'">
                                {{ booking.source }}
                            </span>
                            <span v-if="booking.booking_type" class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-semibold border ring-1 ring-white/20" :class="bookingTypeColors[booking.booking_type] || 'bg-white/10 text-white border-white/20'">
                                {{ $t('a_' + booking.booking_type) || booking.booking_type }}
                            </span>
                        </div>
                        <p class="text-xs md:text-sm text-white/70 mt-1">{{ $t('a_created') }} {{ formatDateTime(booking.created_at) }}
                            <span v-if="booking.creator"> {{ $t('a_by') }} {{ booking.creator.name }}</span>
                        </p>
                    </div>
                </div>
                <div class="flex items-center gap-2 flex-wrap">
                    <a :href="`/admin/bookings/${booking.id}/receipt`" target="_blank" class="inline-flex items-center gap-1.5 px-4 py-2 bg-white/10 backdrop-blur-sm border border-white/20 rounded-xl text-sm font-semibold text-white hover:bg-white/20 transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                        {{ $t('a_print_receipt') }}
                    </a>
                    <button v-if="can('bookings.delete')" type="button" @click="deleteBooking" :disabled="deletingBooking"
                        class="inline-flex items-center gap-1.5 px-4 py-2 bg-red-500/20 border border-red-400/30 rounded-xl text-sm font-semibold text-red-200 hover:bg-red-500/30 transition disabled:opacity-50">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        {{ deletingBooking ? $t('a_deleting') : $t('a_delete') }}
                    </button>
                </div>
            </div>
        </div>

        <!-- ─── Follow-up Eligibility Banner ───────────────────── -->
        <div v-if="showFollowUpBanner" class="flex items-start gap-3 px-5 py-4 bg-teal-50 border border-teal-200 rounded-2xl">
            <div class="w-8 h-8 rounded-lg bg-teal-100 flex items-center justify-center flex-shrink-0 mt-0.5">
                <svg class="w-4 h-4 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            </div>
            <div class="flex-1">
                <p class="text-sm font-semibold text-teal-800">{{ $t('a_eligible_for_followup') }}</p>
                <p class="text-xs text-teal-600 mt-0.5">
                    {{ $t('a_followup_consultation_note', { date: formatDate(followUpInfo.last_visit_date), days: followUpInfo.window_days }) }}
                    <span class="font-bold">{{ formatCurrency(followupFee) }}</span>.
                </p>
            </div>
        </div>

        <!-- ─── Main Grid ──────────────────────────────────────── -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- ─── Left Column (2/3) ──────────────────────────── -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Patient Info Card -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100/80 p-6">
                    <div class="flex items-center gap-2 mb-4 border-b border-gray-100 pb-3">
                        <div class="w-8 h-8 rounded-lg bg-amber-50 flex items-center justify-center">
                            <svg class="w-4 h-4" style="color: #C4A265;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                        </div>
                        <h2 class="text-sm font-bold text-gray-800">{{ $t('a_patient_information') }}</h2>
                        <Link v-if="booking.patient" :href="`/admin/patients/${booking.patient.id}`" class="ltr:ml-auto rtl:mr-auto text-xs font-semibold hover:opacity-80 transition" style="color: #C4A265;">
                            {{ $t('a_view_profile') }}
                        </Link>
                    </div>
                    <template v-if="booking.patient">
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 rounded-full bg-gradient-to-br from-[#C4A265] to-[#8B7043] flex items-center justify-center text-white text-lg font-bold flex-shrink-0">
                                {{ booking.patient.full_name?.charAt(0) || '?' }}
                            </div>
                            <div class="flex-1 grid grid-cols-1 sm:grid-cols-3 gap-3">
                                <div>
                                    <p class="text-xs text-gray-400 mb-0.5">{{ $t('a_file_number') }}</p>
                                    <p class="text-sm font-mono font-semibold" style="color: #C4A265;">{{ booking.patient.file_number || '-' }}</p>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-400 mb-0.5">{{ $t('a_full_name') }}</p>
                                    <p class="text-sm font-semibold text-gray-800">{{ booking.patient.full_name }}</p>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-400 mb-0.5">{{ $t('a_phone') }}</p>
                                    <p class="text-sm text-gray-600">{{ booking.patient.phone || '-' }}</p>
                                </div>
                            </div>
                        </div>
                    </template>
                    <template v-else>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div>
                                <p class="text-xs text-gray-400 mb-0.5">{{ $t('a_name') }}</p>
                                <p class="text-sm font-semibold text-gray-800">{{ booking.full_name || '-' }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-400 mb-0.5">{{ $t('a_phone') }}</p>
                                <p class="text-sm text-gray-600">{{ booking.phone || '-' }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-400 mb-0.5">{{ $t('a_email') }}</p>
                                <p class="text-sm text-gray-600">{{ booking.email || '-' }}</p>
                            </div>
                        </div>
                        <div v-if="isUnconfirmed" class="mt-3 flex items-center gap-2 px-3 py-2 bg-amber-50 rounded-lg border border-amber-200">
                            <svg class="w-4 h-4 text-amber-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z" /></svg>
                            <p class="text-xs text-amber-700">{{ $t('a_no_linked_patient') }}</p>
                        </div>
                    </template>
                </div>

                <!-- Booking Request Details (Service, Doctor, Preferred Date/Time from website booking) -->
                <div v-if="booking.service || booking.doctor || booking.preferred_date || booking.preferred_time" class="bg-gradient-to-br from-[#FAF7F2] to-white rounded-2xl shadow-sm border border-[#C4A265]/20 p-6">
                    <div class="flex items-center gap-2 mb-4">
                        <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-[#C4A265] to-[#A68B52] flex items-center justify-center flex-shrink-0">
                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" /></svg>
                        </div>
                        <h3 class="text-sm font-bold text-gray-800">{{ $t('a_booking_request_details') }}</h3>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div v-if="booking.service">
                            <p class="text-xs text-gray-400 mb-1">{{ $t('a_requested_service') }}</p>
                            <div class="flex items-center gap-2">
                                <span class="inline-flex items-center justify-center w-7 h-7 rounded-lg bg-emerald-50 text-emerald-600">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
                                </span>
                                <span class="text-sm font-semibold text-gray-800">{{ booking.service.name_en || booking.service.name_ar }}</span>
                            </div>
                        </div>
                        <div v-if="booking.doctor">
                            <p class="text-xs text-gray-400 mb-1">{{ $t('a_requested_doctor') }}</p>
                            <div class="flex items-center gap-2">
                                <span class="inline-flex items-center justify-center w-7 h-7 rounded-lg bg-slate-50 text-[#1B365D]">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                                </span>
                                <span class="text-sm font-semibold text-gray-800">{{ booking.doctor.name_en || booking.doctor.name_ar }}</span>
                            </div>
                        </div>
                        <div v-if="booking.preferred_date">
                            <p class="text-xs text-gray-400 mb-1">{{ $t('a_preferred_date') }}</p>
                            <div class="flex items-center gap-2">
                                <span class="inline-flex items-center justify-center w-7 h-7 rounded-lg bg-amber-50 text-amber-600">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                                </span>
                                <span class="text-sm font-semibold text-gray-800">{{ formatDate(booking.preferred_date) }}</span>
                            </div>
                        </div>
                        <div v-if="booking.preferred_time">
                            <p class="text-xs text-gray-400 mb-1">{{ $t('a_preferred_time') }}</p>
                            <div class="flex items-center gap-2">
                                <span class="inline-flex items-center justify-center w-7 h-7 rounded-lg bg-slate-50 text-[#1B365D]">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                </span>
                                <span class="text-sm font-semibold text-gray-800">{{ formatTime(booking.preferred_time) }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Promo Code Badge -->
                <div v-if="booking.promo_code" class="bg-[#F5E7C8]/50 rounded-2xl border border-amber-200/60 p-4 flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-[#C4A265] to-[#A68B52] flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5.5A2.5 2.5 0 109.5 8H12zm-7 4h14M5 12a2 2 0 110-4h14a2 2 0 110 4M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7" /></svg>
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-amber-700 uppercase tracking-wider">{{ $t('a_promo_code') }}</p>
                        <p class="text-base font-bold font-mono tracking-wider text-gray-900">{{ booking.promo_code }}</p>
                    </div>
                    <div v-if="booking.invoice?.discount_code_id" class="ltr:ml-auto rtl:mr-auto">
                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-700">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" /></svg>
                            {{ $t('a_applied') }}
                        </span>
                    </div>
                </div>

                <!-- Notes Card -->
                <div v-if="booking.notes" class="bg-white rounded-2xl shadow-sm border border-gray-100/80 p-6">
                    <h3 class="text-sm font-bold text-gray-800 mb-2">{{ $t('a_booking_notes') }}</h3>
                    <p class="text-sm text-gray-600 whitespace-pre-wrap">{{ booking.notes }}</p>
                </div>

                <!-- ─── Services Table ─────────────────────────────── -->
                <div v-if="booking.booking_services?.length" class="bg-white rounded-2xl shadow-sm border border-gray-100/80 overflow-hidden">
                    <div class="px-4 md:px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                        <h2 class="text-sm font-bold text-gray-800">{{ $t('a_services') }}</h2>
                        <button v-if="can('bookings.edit_services') && !isUnconfirmed && !showEditServices" type="button" @click="initEditServices"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold border rounded-lg hover:bg-[#F5E7C8]/40 transition"
                            style="color: #C4A265; border-color: rgba(196, 162, 101, 0.4);">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                            {{ $t('a_edit_services') }}
                        </button>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="bg-gray-50/80">
                                    <th class="px-4 md:px-6 py-3 text-start text-xs font-semibold text-gray-500 uppercase"></th>
                                    <th class="px-4 md:px-6 py-3 text-start text-xs font-semibold text-gray-500 uppercase">{{ $t('a_service') }}</th>
                                    <th class="px-4 md:px-6 py-3 text-start text-xs font-semibold text-gray-500 uppercase">{{ $t('a_doctor') }}</th>
                                    <th class="px-4 md:px-6 py-3 text-start text-xs font-semibold text-gray-500 uppercase">{{ $t('a_sessions') }}</th>
                                    <th class="px-4 md:px-6 py-3 text-start text-xs font-semibold text-gray-500 uppercase">{{ $t('a_unit_price') }}</th>
                                    <th class="px-4 md:px-6 py-3 text-start text-xs font-semibold text-gray-500 uppercase">{{ $t('a_discount') }}</th>
                                    <th class="px-4 md:px-6 py-3 text-end text-xs font-semibold text-gray-500 uppercase">{{ $t('a_total') }}</th>
                                    <th class="px-4 md:px-6 py-3 text-start text-xs font-semibold text-gray-500 uppercase">{{ $t('a_status') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <template v-for="(bs, bsIndex) in booking.booking_services" :key="bs.id || bsIndex">
                                    <!-- Service Row -->
                                    <tr class="hover:bg-gray-50/50 transition-colors cursor-pointer" @click="toggleServiceExpand(bsIndex)">
                                        <td class="px-4 md:px-6 py-3">
                                            <button type="button" class="text-gray-400 hover:text-gray-600 transition">
                                                <svg class="w-4 h-4 transition-transform duration-200" :class="{ 'rotate-90': expandedServices[bsIndex] }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                                </svg>
                                            </button>
                                        </td>
                                        <td class="px-4 md:px-6 py-3">
                                            <span class="font-semibold text-gray-800">{{ bs.service?.name_en || bs.service?.name_ar || (booking.booking_type ? $t('a_' + booking.booking_type) : '-') }}</span>
                                        </td>
                                        <td class="px-4 md:px-6 py-3 text-gray-600">{{ bs.doctor?.name_en || bs.doctor?.name_ar || '-' }}</td>
                                        <td class="px-4 md:px-6 py-3">
                                            <span class="font-semibold" style="color: #C4A265;">{{ bs.completed_sessions || 0 }}</span>
                                            <span class="text-gray-400"> / {{ bs.sessions_count }}</span>
                                            <span v-if="retouchCount(bs) > 0" class="ltr:ml-1 rtl:mr-1 text-[#1B365D] text-xs font-medium">(+{{ retouchCount(bs) }} {{ $t('a_retouch') }})</span>
                                            <div class="mt-1 h-1.5 bg-gray-100 rounded-full overflow-hidden w-16">
                                                <div class="h-full rounded-full transition-all"
                                                     style="background-color: #C4A265;"
                                                     :style="{ width: (bs.sessions_count > 0 ? ((bs.completed_sessions || 0) / bs.sessions_count * 100) : 0) + '%' }"></div>
                                            </div>
                                        </td>
                                        <td class="px-4 md:px-6 py-3 text-gray-600">{{ formatCurrency(bs.unit_price) }}</td>
                                        <td class="px-4 md:px-6 py-3 text-gray-600">{{ bs.discount_per_session > 0 ? formatCurrency(bs.discount_per_session) + '/' + $t('a_session') : '-' }}</td>
                                        <td class="px-4 md:px-6 py-3 text-end font-bold text-gray-800">{{ formatCurrency(bs.total_price) }}</td>
                                        <td class="px-4 md:px-6 py-3">
                                            <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-semibold border capitalize" :class="serviceStatusColors[bs.status] || 'bg-gray-50 text-gray-600 border-gray-200'">
                                                {{ bs.status || '-' }}
                                            </span>
                                        </td>
                                    </tr>
                                    <!-- Expanded Appointments Sub-rows -->
                                    <tr v-if="expandedServices[bsIndex] && bs.appointments?.length">
                                        <td colspan="8" class="p-0">
                                            <div class="bg-gray-50/60 border-t border-gray-100">
                                                <div class="px-8 py-3">
                                                    <p class="text-xs font-semibold text-gray-500 uppercase mb-2">{{ $t('a_appointments') }}</p>
                                                    <table class="w-full text-xs">
                                                        <thead>
                                                            <tr class="text-gray-400">
                                                                <th class="text-start py-1.5 ltr:pr-3 rtl:pl-3">#</th>
                                                                <th class="text-start py-1.5 ltr:pr-3 rtl:pl-3">{{ $t('a_date') }}</th>
                                                                <th class="text-start py-1.5 ltr:pr-3 rtl:pl-3">{{ $t('a_time') }}</th>
                                                                <th class="text-start py-1.5 ltr:pr-3 rtl:pl-3">{{ $t('a_doctor') }}</th>
                                                                <th class="text-start py-1.5 ltr:pr-3 rtl:pl-3">{{ $t('a_status') }}</th>
                                                                <th class="text-start py-1.5">{{ $t('a_visit') }}</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody class="divide-y divide-gray-100/80">
                                                            <tr v-for="appt in bs.appointments" :key="appt.id" class="hover:bg-white/60">
                                                                <td class="py-2 ltr:pr-3 rtl:pl-3 font-medium text-gray-500">
                                                                    {{ appt.session_number || '-' }}
                                                                    <span v-if="appt.is_retouch" class="ltr:ml-1 rtl:mr-1 inline-flex px-1.5 py-0.5 rounded text-[9px] font-bold bg-slate-50 text-[#1B365D] border border-slate-200">{{ $t('a_retouch') }}</span>
                                                                </td>
                                                                <td class="py-2 ltr:pr-3 rtl:pl-3 text-gray-700">{{ formatDate(appt.appointment_date) }}</td>
                                                                <td class="py-2 ltr:pr-3 rtl:pl-3 text-gray-600">{{ formatTime(appt.start_time) }} - {{ formatTime(appt.end_time) }}</td>
                                                                <td class="py-2 ltr:pr-3 rtl:pl-3 text-gray-600">{{ appt.doctor?.name_en || appt.doctor?.name_ar || '-' }}</td>
                                                                <td class="py-2 ltr:pr-3 rtl:pl-3">
                                                                    <span class="inline-flex px-2 py-0.5 rounded-full text-[10px] font-semibold border capitalize" :class="appointmentStatusColors[appt.status] || 'bg-gray-50 text-gray-600 border-gray-200'">
                                                                        {{ appt.status?.replace('_', ' ') || '-' }}
                                                                    </span>
                                                                </td>
                                                                <td class="py-2">
                                                                    <div class="flex items-center gap-1.5">
                                                                        <Link v-if="appt.visit" :href="`/admin/visits/${appt.visit.id || appt.visit}`" class="font-semibold hover:opacity-80" style="color: #C4A265;">
                                                                            {{ $t('a_view') }}
                                                                        </Link>
                                                                        <button v-else-if="canCheckIn(appt)" @click="checkInAppointment(appt)" :disabled="checkingIn[appt.id]" class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-semibold text-white transition-all" :class="checkingIn[appt.id] ? 'bg-gray-300' : 'bg-emerald-500 hover:bg-emerald-600'">
                                                                            {{ checkingIn[appt.id] ? '...' : $t('a_check_in') }}
                                                                        </button>
                                                                        <button v-if="canReschedule(appt)" @click="openRescheduleModal(appt)" class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-medium text-gray-400 hover:text-[#C4A265] hover:bg-[#C4A265]/5 border border-gray-200 hover:border-[#C4A265]/30 transition-all" :title="$t('a_edit')">
                                                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                                                                            {{ $t('a_edit') }}
                                                                        </button>
                                                                        <span v-if="!appt.visit && !canCheckIn(appt) && !canReschedule(appt)" class="text-gray-300">-</span>
                                                                    </div>
                                                                </td>
                                                            </tr>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr v-else-if="expandedServices[bsIndex] && (!bs.appointments || bs.appointments.length === 0)">
                                        <td colspan="8" class="p-0">
                                            <div class="bg-gray-50/60 border-t border-gray-100 px-8 py-4 text-center">
                                                <p class="text-xs text-gray-400">{{ $t('a_no_appointments_for_service') }}</p>
                                            </div>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- ─── Edit Services ─────────────────────────────────── -->
                <div v-if="showEditServices && can('bookings.edit_services')" class="bg-white rounded-2xl shadow-sm border overflow-hidden" style="border-color: #C4A265;">
                    <div class="px-4 md:px-6 py-4 border-b bg-[#F5E7C8]/30 flex items-center justify-between" style="border-color: rgba(196, 162, 101, 0.3);">
                        <h2 class="text-sm font-bold" style="color: #9a7d3a;">{{ $t('a_edit_services') }}</h2>
                        <button type="button" @click="showEditServices = false" class="text-gray-400 hover:text-gray-600 transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                        </button>
                    </div>
                    <form @submit.prevent="submitEditServices" class="p-4 md:p-6 space-y-4">
                        <div v-for="(svc, idx) in editServicesForm.services" :key="idx"
                             class="p-4 rounded-xl border space-y-3 transition-all"
                             :class="svc._delete ? 'bg-red-50/50 border-red-200 opacity-60' : 'bg-gray-50 border-gray-100'">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-semibold" :class="svc._delete ? 'text-red-500 line-through' : 'text-gray-500'">
                                    {{ $t('a_service') }} {{ idx + 1 }} <span v-if="svc.id" class="text-gray-400 font-normal">({{ $t('a_existing') }})</span><span v-else class="text-emerald-500 font-normal">({{ $t('a_new') }})</span>
                                </span>
                                <button type="button" @click="markServiceForDeletion(idx)"
                                    class="text-xs font-medium px-2 py-1 rounded-lg transition"
                                    :class="svc._delete ? 'text-gray-600 hover:text-gray-800 bg-gray-200' : 'text-red-400 hover:text-red-600 hover:bg-red-50'">
                                    {{ svc._delete ? $t('a_undo') : $t('a_remove') }}
                                </button>
                            </div>
                            <div v-if="!svc._delete" class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div class="relative ss-dropdown">
                                    <label class="block text-xs text-gray-500 mb-1">{{ $t('a_service') }}</label>
                                    <button type="button" @click.stop="toggleDropdown('ed-svc-' + idx)"
                                        class="w-full flex items-center justify-between px-3 py-2 border border-gray-200 rounded-lg text-sm bg-white hover:border-gray-300 focus:ring-2 focus:ring-[#C4A265]/30 focus:border-transparent transition">
                                        <span class="truncate" :class="svc.service_id ? 'text-gray-800' : 'text-gray-400'">
                                            {{ getServiceName(svc.service_id) || $t('a_select_service') }}
                                        </span>
                                        <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                                    </button>
                                    <Transition enter-active-class="transition duration-150" enter-from-class="opacity-0 -translate-y-1" enter-to-class="opacity-100 translate-y-0" leave-active-class="transition duration-100" leave-from-class="opacity-100" leave-to-class="opacity-0">
                                        <div v-if="openDropdown === 'ed-svc-' + idx" class="absolute z-40 left-0 right-0 mt-1 bg-white border border-gray-200 rounded-xl shadow-xl overflow-hidden">
                                            <div class="p-2 border-b border-gray-100">
                                                <div class="relative">
                                                    <svg class="absolute ltr:left-2.5 rtl:right-2.5 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                                                    <input v-model="dropdownSearches['ed-svc-' + idx]" type="text" :placeholder="$t('a_search') + '...'" class="doctorato-input w-full ltr:pl-8 rtl:pr-8 ltr:pr-3 rtl:pl-3 py-1.5 text-sm border border-gray-200 rounded-lg focus:ring-1 focus:ring-[#C4A265]/30 focus:border-transparent" @click.stop />
                                                </div>
                                            </div>
                                            <div class="max-h-48 overflow-y-auto">
                                                <button v-for="s in filteredServicesList('ed-svc-' + idx)" :key="s.id" type="button"
                                                    @click="svc.service_id = s.id; onEditServiceChange(idx); openDropdown = null"
                                                    class="w-full flex items-center justify-between px-3 py-2 text-sm text-start hover:bg-[#F5E7C8]/40 transition"
                                                    :class="svc.service_id == s.id ? 'bg-[#F5E7C8]/40 font-semibold' : ''">
                                                    <span class="truncate">{{ s.name_en || s.name_ar }}</span>
                                                    <span class="text-xs text-gray-400 flex-shrink-0 ltr:ml-2 rtl:mr-2">{{ formatCurrency(s.price) }}</span>
                                                </button>
                                                <p v-if="!filteredServicesList('ed-svc-' + idx).length" class="text-xs text-gray-400 text-center py-3">{{ $t('a_no_results') }}</p>
                                            </div>
                                        </div>
                                    </Transition>
                                </div>
                                <div class="relative ss-dropdown">
                                    <label class="block text-xs text-gray-500 mb-1">{{ $t('a_doctor') }}</label>
                                    <button type="button" @click.stop="toggleDropdown('ed-doc-' + idx)"
                                        class="w-full flex items-center justify-between px-3 py-2 border border-gray-200 rounded-lg text-sm bg-white hover:border-gray-300 focus:ring-2 focus:ring-[#C4A265]/30 focus:border-transparent transition">
                                        <span :class="svc.doctor_id ? 'text-gray-800' : 'text-gray-400'">
                                            {{ getDoctorName(svc.doctor_id) || $t('a_select_doctor') }}
                                        </span>
                                        <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                                    </button>
                                    <Transition enter-active-class="transition duration-150" enter-from-class="opacity-0 -translate-y-1" enter-to-class="opacity-100 translate-y-0" leave-active-class="transition duration-100" leave-from-class="opacity-100" leave-to-class="opacity-0">
                                        <div v-if="openDropdown === 'ed-doc-' + idx" class="absolute z-40 left-0 right-0 mt-1 bg-white border border-gray-200 rounded-xl shadow-xl overflow-hidden">
                                            <div class="p-2 border-b border-gray-100">
                                                <div class="relative">
                                                    <svg class="absolute ltr:left-2.5 rtl:right-2.5 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                                                    <input v-model="dropdownSearches['ed-doc-' + idx]" type="text" :placeholder="$t('a_search') + '...'" class="doctorato-input w-full ltr:pl-8 rtl:pr-8 ltr:pr-3 rtl:pl-3 py-1.5 text-sm border border-gray-200 rounded-lg focus:ring-1 focus:ring-[#C4A265]/30 focus:border-transparent" @click.stop />
                                                </div>
                                            </div>
                                            <div class="max-h-48 overflow-y-auto">
                                                <button v-for="d in filteredDoctorsList('ed-doc-' + idx)" :key="d.id" type="button"
                                                    @click="svc.doctor_id = d.id; openDropdown = null"
                                                    class="w-full flex items-center gap-2 px-3 py-2 text-sm text-start hover:bg-[#F5E7C8]/40 transition"
                                                    :class="svc.doctor_id == d.id ? 'bg-[#F5E7C8]/40 font-semibold' : ''">
                                                    <span class="w-6 h-6 rounded-full bg-gradient-to-br from-[#1B365D] to-[#1B365D] flex items-center justify-center text-white text-[10px] font-bold flex-shrink-0">{{ (d.name_en || d.name_ar || '?').charAt(0) }}</span>
                                                    <span>{{ d.name_en || d.name_ar }}</span>
                                                </button>
                                                <p v-if="!filteredDoctorsList('ed-doc-' + idx).length" class="text-xs text-gray-400 text-center py-3">{{ $t('a_no_results') }}</p>
                                            </div>
                                        </div>
                                    </Transition>
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-500 mb-1">{{ $t('a_sessions') }}</label>
                                    <input v-model.number="svc.sessions_count" type="number" min="1" max="50" class="doctorato-input w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-[#C4A265]/30 focus:border-transparent" />
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-500 mb-1">{{ $t('a_unit_price') }} ({{ currencyCode }})</label>
                                    <input v-model.number="svc.unit_price" type="number" min="0" step="0.01" class="doctorato-input w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-[#C4A265]/30 focus:border-transparent" />
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-500 mb-1">{{ $t('a_discount_per_session') }} ({{ currencyCode }})</label>
                                    <input v-model.number="svc.discount_per_session" type="number" min="0" step="0.01" class="doctorato-input w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-[#C4A265]/30 focus:border-transparent" />
                                </div>
                                <div class="flex items-end">
                                    <div>
                                        <label class="block text-xs text-gray-500 mb-1">{{ $t('a_subtotal') }}</label>
                                        <p class="text-sm font-bold py-2" style="color: #C4A265;">{{ formatCurrency(editServiceTotal(svc)) }}</p>
                                    </div>
                                </div>
                                <div class="sm:col-span-2">
                                    <label class="block text-xs text-gray-500 mb-1">{{ $t('a_notes') }}</label>
                                    <input v-model="svc.notes" type="text" :placeholder="$t('a_optional_notes')" class="doctorato-input w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-[#C4A265]/30 focus:border-transparent" />
                                </div>
                            </div>
                        </div>

                        <button type="button" @click="addEditService" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold border rounded-lg hover:bg-[#F5E7C8]/40 transition" style="color: #C4A265; border-color: rgba(196, 162, 101, 0.4);">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                            {{ $t('a_add_service') }}
                        </button>

                        <!-- Grand Total -->
                        <div class="flex items-center justify-between px-4 py-3 bg-gray-50 rounded-xl border border-gray-100">
                            <span class="text-sm font-semibold text-gray-700">{{ $t('a_grand_total') }}</span>
                            <span class="text-base font-bold" style="color: #C4A265;">{{ formatCurrency(editServicesGrandTotal) }}</span>
                        </div>

                        <!-- Info Box -->
                        <div class="flex items-start gap-2 px-4 py-3 bg-amber-50 rounded-xl border border-amber-100">
                            <svg class="w-4 h-4 text-amber-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            <p class="text-xs text-amber-700">{{ $t('a_invoice_recalculated_notice') }}</p>
                        </div>

                        <!-- Validation Errors -->
                        <div v-if="editServicesForm.errors && Object.keys(editServicesForm.errors).length" class="p-3 bg-red-50 border border-red-200 rounded-xl">
                            <p class="text-xs font-semibold text-red-700 mb-1">{{ $t('a_fix_errors') }}</p>
                            <ul class="text-xs text-red-600 list-disc list-inside space-y-0.5">
                                <li v-for="(err, key) in editServicesForm.errors" :key="key">{{ err }}</li>
                            </ul>
                        </div>

                        <!-- Actions -->
                        <div class="flex items-center gap-3 pt-2 border-t border-gray-100">
                            <button type="submit" :disabled="editServicesForm.processing" class="px-5 py-2.5 text-white rounded-xl text-sm font-semibold hover:opacity-90 transition-all disabled:opacity-50 shadow-sm" style="background-color: #C4A265;">
                                {{ editServicesForm.processing ? $t('a_saving') : $t('a_save_changes') }}
                            </button>
                            <button type="button" @click="showEditServices = false" class="px-5 py-2.5 border border-gray-200 text-gray-600 rounded-xl text-sm font-medium hover:bg-gray-50 transition">
                                {{ $t('a_cancel') }}
                            </button>
                        </div>
                    </form>
                </div>

                <!-- ─── Add Retouch Session ──────────────────────────── -->
                <div v-if="canAddRetouch" class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                    <button type="button" @click="showRetouchForm = !showRetouchForm"
                        class="w-full px-4 md:px-6 py-4 flex items-center justify-between bg-slate-50/80 hover:bg-slate-50 transition-colors">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg bg-slate-100 flex items-center justify-center">
                                <svg class="w-4 h-4 text-[#1B365D]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
                            </div>
                            <h2 class="text-sm font-bold text-[#1B365D]">{{ $t('a_add_retouch_session') }}</h2>
                            <span class="text-xs text-[#1B365D] font-medium">({{ $t('a_free_followup') }})</span>
                        </div>
                        <svg class="w-5 h-5 text-[#1B365D] transition-transform duration-200" :class="{ 'rotate-180': showRetouchForm }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>

                    <div v-if="showRetouchForm" class="p-4 md:p-6 space-y-4 border-t border-slate-100">
                        <form @submit.prevent="submitRetouch" class="space-y-4">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <!-- Service Selection -->
                                <div>
                                    <label class="block text-xs font-medium text-gray-500 mb-1.5">{{ $t('a_service') }} <span class="text-red-500">*</span></label>
                                    <select v-model="retouchForm.booking_service_id" class="doctorato-input w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-slate-200 focus:border-transparent bg-white">
                                        <option value="">{{ $t('a_select_service') }}</option>
                                        <option v-for="bs in booking.booking_services" :key="bs.id" :value="bs.id">
                                            {{ bs.service?.name_en || bs.service?.name_ar || (booking.booking_type ? $t('a_' + booking.booking_type) : $t('a_service')) }}
                                            ({{ bs.completed_sessions || 0 }}/{{ bs.sessions_count }} {{ $t('a_sessions') }})
                                        </option>
                                    </select>
                                    <p v-if="retouchForm.errors.booking_service_id" class="mt-1 text-xs text-red-600">{{ retouchForm.errors.booking_service_id }}</p>
                                </div>

                                <!-- Doctor Selection -->
                                <div>
                                    <label class="block text-xs font-medium text-gray-500 mb-1.5">{{ $t('a_doctor') }} <span class="text-red-500">*</span></label>
                                    <select v-model="retouchForm.doctor_id" @change="fetchRetouchTimeSlots" class="doctorato-input w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-slate-200 focus:border-transparent bg-white">
                                        <option value="">{{ $t('a_select_doctor') }}</option>
                                        <option v-for="d in doctors" :key="d.id" :value="d.id">{{ d.name_en || d.name_ar }}</option>
                                    </select>
                                    <p v-if="retouchForm.errors.doctor_id" class="mt-1 text-xs text-red-600">{{ retouchForm.errors.doctor_id }}</p>
                                </div>

                                <!-- Date -->
                                <div>
                                    <label class="block text-xs font-medium text-gray-500 mb-1.5">{{ $t('a_date') }} <span class="text-red-500">*</span></label>
                                    <input v-model="retouchForm.appointment_date" type="date" :min="new Date().toISOString().split('T')[0]" @change="fetchRetouchTimeSlots" class="doctorato-input w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-slate-200 focus:border-transparent" />
                                    <p v-if="retouchForm.errors.appointment_date" class="mt-1 text-xs text-red-600">{{ retouchForm.errors.appointment_date }}</p>
                                </div>

                                <!-- Time Slot -->
                                <div>
                                    <label class="block text-xs font-medium text-gray-500 mb-1.5">{{ $t('a_time_slot') }} <span class="text-red-500">*</span></label>
                                    <div v-if="retouchLoadingSlots" class="text-xs text-gray-400 py-2.5">{{ $t('a_loading_slots') }}</div>
                                    <select v-else-if="retouchTimeSlots.length" v-model="retouchForm.start_time" class="doctorato-input w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-slate-200 focus:border-transparent bg-white">
                                        <option value="">{{ $t('a_select_time') }}</option>
                                        <option v-for="slot in retouchTimeSlots" :key="slot.start || slot" :value="slot.start || slot">
                                            {{ formatTime(slot.start || slot) }} {{ slot.end ? '- ' + formatTime(slot.end) : '' }}
                                        </option>
                                    </select>
                                    <input v-else v-model="retouchForm.start_time" type="time" class="doctorato-input w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-slate-200 focus:border-transparent" />
                                    <p v-if="retouchForm.errors.start_time" class="mt-1 text-xs text-red-600">{{ retouchForm.errors.start_time }}</p>
                                </div>
                            </div>

                            <!-- Info Box -->
                            <div class="flex items-start gap-2 px-4 py-3 bg-slate-50 rounded-xl border border-slate-100">
                                <svg class="w-4 h-4 text-[#1B365D] mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                <p class="text-xs text-[#1B365D]">{{ $t('a_retouch_info') }}</p>
                            </div>

                            <!-- Submit -->
                            <div class="flex items-center gap-3">
                                <button type="submit" :disabled="retouchForm.processing" class="px-5 py-2.5 text-white rounded-xl text-sm font-semibold hover:opacity-90 transition-all disabled:opacity-50 shadow-sm bg-[#1B365D]">
                                    {{ retouchForm.processing ? $t('a_adding') : $t('a_add_retouch_session') }}
                                </button>
                                <button type="button" @click="showRetouchForm = false; retouchForm.reset()" class="px-5 py-2.5 border border-gray-200 text-gray-600 rounded-xl text-sm font-medium hover:bg-gray-50 transition">
                                    {{ $t('a_cancel') }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- ─── All Appointments (Flat List) ───────────────── -->
                <div v-if="booking.appointments?.length" class="bg-white rounded-2xl shadow-sm border border-gray-100/80 overflow-hidden">
                    <div class="px-4 md:px-6 py-4 border-b border-gray-100">
                        <h2 class="text-sm font-bold text-gray-800">{{ $t('a_all_appointments') }}</h2>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="bg-gray-50/80">
                                    <th class="px-4 md:px-6 py-3 text-start text-xs font-semibold text-gray-500 uppercase">#</th>
                                    <th class="px-4 md:px-6 py-3 text-start text-xs font-semibold text-gray-500 uppercase">{{ $t('a_date') }}</th>
                                    <th class="px-4 md:px-6 py-3 text-start text-xs font-semibold text-gray-500 uppercase">{{ $t('a_time') }}</th>
                                    <th class="px-4 md:px-6 py-3 text-start text-xs font-semibold text-gray-500 uppercase">{{ $t('a_doctor') }}</th>
                                    <th class="px-4 md:px-6 py-3 text-start text-xs font-semibold text-gray-500 uppercase">{{ $t('a_status') }}</th>
                                    <th class="px-4 md:px-6 py-3 text-start text-xs font-semibold text-gray-500 uppercase">{{ $t('a_actions') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr v-for="appt in booking.appointments" :key="appt.id" class="hover:bg-gray-50/50 transition-colors">
                                    <td class="px-4 md:px-6 py-3 font-medium text-gray-500">
                                        {{ appt.session_number || '-' }}
                                        <span v-if="appt.is_retouch" class="ltr:ml-1 rtl:mr-1 inline-flex px-1.5 py-0.5 rounded text-[10px] font-bold bg-slate-50 text-[#1B365D] border border-slate-200">{{ $t('a_retouch') }}</span>
                                    </td>
                                    <td class="px-4 md:px-6 py-3 text-gray-700">{{ formatDate(appt.appointment_date) }}</td>
                                    <td class="px-4 md:px-6 py-3 text-gray-600">{{ formatTime(appt.start_time) }} - {{ formatTime(appt.end_time) }}</td>
                                    <td class="px-4 md:px-6 py-3 text-gray-600">{{ appt.doctor?.name_en || appt.doctor?.name_ar || '-' }}</td>
                                    <td class="px-4 md:px-6 py-3">
                                        <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-semibold border capitalize" :class="appointmentStatusColors[appt.status] || 'bg-gray-50 text-gray-600 border-gray-200'">
                                            {{ appt.status?.replace('_', ' ') || '-' }}
                                        </span>
                                    </td>
                                    <td class="px-4 md:px-6 py-3">
                                        <div class="flex items-center gap-2">
                                            <Link v-if="appt.visit" :href="`/admin/visits/${appt.visit.id || appt.visit}`" class="text-xs font-semibold hover:opacity-80" style="color: #C4A265;">
                                                {{ $t('a_view_visit') }}
                                            </Link>
                                            <button v-else-if="canCheckIn(appt)" @click="checkInAppointment(appt)" :disabled="checkingIn[appt.id]" class="inline-flex items-center gap-1 px-3 py-1 rounded-lg text-xs font-semibold text-white transition-all shadow-sm" :class="checkingIn[appt.id] ? 'bg-gray-300 cursor-not-allowed' : 'bg-emerald-500 hover:bg-emerald-600'">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                                {{ checkingIn[appt.id] ? $t('a_checking_in') : $t('a_check_in') }}
                                            </button>
                                            <button v-if="canReschedule(appt)" @click="openRescheduleModal(appt)" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-medium text-gray-500 hover:text-[#C4A265] hover:bg-[#C4A265]/5 border border-gray-200 hover:border-[#C4A265]/30 transition-all" :title="$t('a_reschedule')">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                                                {{ $t('a_edit') }}
                                            </button>
                                            <span v-if="!appt.visit && !canCheckIn(appt) && !canReschedule(appt)" class="text-gray-300 text-xs">-</span>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- ─── Invoice Section ────────────────────────────── -->
                <div v-if="invoice" class="bg-white rounded-2xl shadow-sm border border-gray-100/80 overflow-hidden">
                    <div class="px-4 md:px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                        <div>
                            <h2 class="text-sm font-bold text-gray-800">{{ $t('a_invoice') }}</h2>
                            <p class="text-xs text-gray-400 mt-0.5">
                                {{ invoice.invoice_number || `#${invoice.id}` }}
                                <span class="mx-1">&middot;</span>
                                {{ formatDate(invoice.invoice_date || invoice.created_at) }}
                            </p>
                        </div>
                        <Link :href="`/admin/invoices/${invoice.id}`" class="text-xs font-semibold hover:opacity-80 transition" style="color: #C4A265;">
                            {{ $t('a_view_invoice') }}
                        </Link>
                    </div>

                    <!-- Invoice Items -->
                    <table v-if="invoice.items?.length" class="w-full text-sm">
                        <thead>
                            <tr class="bg-gray-50/80">
                                <th class="px-4 md:px-6 py-3 text-start text-xs font-semibold text-gray-500 uppercase">{{ $t('a_description') }}</th>
                                <th class="px-4 md:px-6 py-3 text-start text-xs font-semibold text-gray-500 uppercase">{{ $t('a_qty') }}</th>
                                <th class="px-4 md:px-6 py-3 text-start text-xs font-semibold text-gray-500 uppercase">{{ $t('a_unit_price') }}</th>
                                <th class="px-4 md:px-6 py-3 text-end text-xs font-semibold text-gray-500 uppercase">{{ $t('a_total') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="item in invoice.items" :key="item.id" class="hover:bg-gray-50/50">
                                <td class="px-4 md:px-6 py-3">
                                    <div class="font-medium text-gray-900">{{ item.description_en || item.description }}</div>
                                    <div v-if="item.description_ar" class="text-gray-400 text-xs" dir="rtl">{{ item.description_ar }}</div>
                                </td>
                                <td class="px-4 md:px-6 py-3 text-gray-500">{{ item.quantity }}</td>
                                <td class="px-4 md:px-6 py-3 text-gray-500">{{ formatCurrency(item.unit_price) }}</td>
                                <td class="px-4 md:px-6 py-3 text-end font-bold text-gray-800">{{ formatCurrency(item.total) }}</td>
                            </tr>
                        </tbody>
                    </table>

                    <!-- Invoice Summary -->
                    <div class="px-4 md:px-6 py-4 bg-gray-50/50 border-t border-gray-100">
                        <div class="flex justify-end">
                            <dl class="space-y-2 w-64">
                                <div class="flex justify-between text-sm">
                                    <dt class="text-gray-500">{{ $t('a_subtotal') }}</dt>
                                    <dd class="text-gray-700">{{ formatCurrency(invoice.subtotal) }}</dd>
                                </div>
                                <div v-if="invoice.discount_amount > 0" class="flex justify-between text-sm">
                                    <dt class="text-gray-500">{{ $t('a_discount') }}</dt>
                                    <dd class="text-red-600">-{{ formatCurrency(invoice.discount_amount) }}</dd>
                                </div>
                                <div class="flex justify-between text-base font-bold border-t border-gray-200 pt-2">
                                    <dt class="text-gray-800">{{ $t('a_total') }}</dt>
                                    <dd style="color: #C4A265;">{{ formatCurrency(invoice.total) }}</dd>
                                </div>
                                <div class="flex justify-between text-sm">
                                    <dt class="text-gray-500">{{ $t('a_paid') }}</dt>
                                    <dd class="text-emerald-600 font-medium">{{ formatCurrency(invoice.paid_amount) }}</dd>
                                </div>
                                <div class="flex justify-between text-sm font-bold">
                                    <dt class="text-gray-800">{{ $t('a_balance') }}</dt>
                                    <dd :class="invoiceBalance > 0 ? 'text-red-600' : 'text-emerald-600'">{{ formatCurrency(invoiceBalance) }}</dd>
                                </div>
                            </dl>
                        </div>
                    </div>
                </div>

                <!-- ─── Payments History ───────────────────────────── -->
                <div v-if="invoice?.payments?.length" class="bg-white rounded-2xl shadow-sm border border-gray-100/80 overflow-hidden">
                    <div class="px-4 md:px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                        <h2 class="text-sm font-bold text-gray-800">{{ $t('a_payments_history') }}</h2>
                        <button
                            v-if="hasInvoiceBalance"
                            type="button"
                            @click="openPaymentForm"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 text-white rounded-lg text-xs font-semibold transition shadow-sm hover:opacity-90"
                            style="background-color: #C4A265;"
                        >
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                            {{ $t('a_add_payment') }}
                        </button>
                    </div>
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-gray-50/80">
                                <th class="px-4 md:px-6 py-3 text-start text-xs font-semibold text-gray-500 uppercase">{{ $t('a_date') }}</th>
                                <th class="px-4 md:px-6 py-3 text-start text-xs font-semibold text-gray-500 uppercase">{{ $t('a_amount') }}</th>
                                <th class="px-4 md:px-6 py-3 text-start text-xs font-semibold text-gray-500 uppercase">{{ $t('a_method') }}</th>
                                <th class="px-4 md:px-6 py-3 text-start text-xs font-semibold text-gray-500 uppercase">{{ $t('a_reference') }}</th>
                                <th class="px-4 md:px-6 py-3 text-start text-xs font-semibold text-gray-500 uppercase">{{ $t('a_received_by') }}</th>
                                <th class="px-4 md:px-6 py-3 text-center text-xs font-semibold text-gray-500 uppercase">{{ $t('a_receipt') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="payment in invoice.payments" :key="payment.id" class="hover:bg-gray-50/50">
                                <td class="px-4 md:px-6 py-3 text-gray-500">{{ formatDate(payment.payment_date || payment.created_at) }}</td>
                                <td class="px-4 md:px-6 py-3 font-bold text-emerald-600">{{ formatCurrency(payment.amount) }}</td>
                                <td class="px-4 md:px-6 py-3 text-gray-500">{{ payment.payment_method?.name_en || payment.paymentMethod?.name_en || '-' }}</td>
                                <td class="px-4 md:px-6 py-3 text-gray-500 font-mono">{{ payment.reference_number || '-' }}</td>
                                <td class="px-4 md:px-6 py-3 text-gray-500">{{ payment.receiver?.name || '-' }}</td>
                                <td class="px-4 md:px-6 py-3 text-center">
                                    <a :href="`/admin/bookings/${booking.id}/payments/${payment.id}/receipt`"
                                       target="_blank"
                                       class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-semibold rounded-lg border transition-all hover:opacity-80"
                                       style="color: #C4A265; border-color: rgba(196, 162, 101, 0.4);"
                                       :title="$t('a_print_payment_receipt')">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" /></svg>
                                        {{ $t('a_print') }}
                                    </a>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- ─── Payment Form (Inline) ─────────────────────── -->
                <div v-if="showPaymentForm" class="bg-white rounded-2xl shadow-sm border overflow-hidden" style="border-color: #C4A265;">
                    <div class="px-4 md:px-6 py-4 border-b bg-[#F5E7C8]/30" style="border-color: rgba(196, 162, 101, 0.3);">
                        <div class="flex items-center justify-between">
                            <h2 class="text-sm font-bold" style="color: #9a7d3a;">{{ $t('a_record_payment') }}</h2>
                            <button type="button" @click="showPaymentForm = false" class="text-gray-400 hover:text-gray-600 transition">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                            </button>
                        </div>
                    </div>
                    <form @submit.prevent="submitPayment" class="p-6">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-medium text-gray-500 mb-1.5">{{ $t('a_payment_method') }} <span class="text-red-500">*</span></label>
                                <select v-model="paymentForm.payment_method_id" class="doctorato-input w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-[#C4A265]/30 focus:border-transparent bg-white">
                                    <option value="">{{ $t('a_select_method') }}</option>
                                    <option v-for="method in paymentMethods" :key="method.id" :value="method.id">{{ method.name_en }}</option>
                                </select>
                                <p v-if="paymentForm.errors.payment_method_id" class="mt-1 text-xs text-red-600">{{ paymentForm.errors.payment_method_id }}</p>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-500 mb-1.5">{{ $t('a_amount') }} <span class="text-red-500">*</span></label>
                                <input v-model.number="paymentForm.amount" type="number" min="0" step="0.01" :placeholder="`Balance: ${formatCurrency(invoiceBalance)}`" class="doctorato-input w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-[#C4A265]/30 focus:border-transparent" />
                                <p v-if="paymentForm.errors.amount" class="mt-1 text-xs text-red-600">{{ paymentForm.errors.amount }}</p>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-500 mb-1.5">{{ $t('a_reference') }} #</label>
                                <input v-model="paymentForm.reference_number" type="text" :placeholder="$t('a_reference_placeholder')" class="doctorato-input w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-[#C4A265]/30 focus:border-transparent" />
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-500 mb-1.5">{{ $t('a_notes') }}</label>
                                <input v-model="paymentForm.notes" type="text" :placeholder="$t('a_optional_notes')" class="doctorato-input w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-[#C4A265]/30 focus:border-transparent" />
                            </div>
                        </div>
                        <div class="flex items-center gap-3 mt-5">
                            <button type="submit" :disabled="paymentForm.processing" class="px-5 py-2.5 text-white rounded-xl text-sm font-semibold hover:opacity-90 transition-all disabled:opacity-50 shadow-sm" style="background-color: #C4A265;">
                                {{ paymentForm.processing ? $t('a_processing') : $t('a_record_payment') }}
                            </button>
                            <button type="button" @click="showPaymentForm = false" class="px-5 py-2.5 border border-gray-200 text-gray-600 rounded-xl text-sm font-medium hover:bg-gray-50 transition">
                                {{ $t('a_cancel') }}
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Add Payment Button (when no payments yet but balance > 0) -->
                <div v-if="hasInvoiceBalance && !showPaymentForm && (!invoice?.payments || invoice.payments.length === 0)" class="bg-white rounded-2xl shadow-sm border border-gray-100/80 p-4 md:p-6 text-center">
                    <svg class="w-10 h-10 text-gray-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
                    <p class="text-sm text-gray-500 mb-3">{{ $t('a_no_payments_yet') }} {{ $t('a_balance') }}: <span class="font-bold text-red-600">{{ formatCurrency(invoiceBalance) }}</span></p>
                    <button @click="openPaymentForm" type="button" class="inline-flex items-center gap-1.5 px-4 py-2 text-white rounded-xl text-sm font-semibold hover:opacity-90 transition shadow-sm" style="background-color: #C4A265;">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                        {{ $t('a_record_first_payment') }}
                    </button>
                </div>

                <!-- ═══════════════════════════════════════════════════════ -->
                <!-- ─── Confirm Booking Section (Unconfirmed Only) ─────── -->
                <!-- ═══════════════════════════════════════════════════════ -->
                <div v-if="isUnconfirmed && patients" class="bg-white rounded-2xl shadow-sm border border-amber-200 overflow-hidden">
                    <button type="button" @click="showConfirmSection = !showConfirmSection"
                        class="w-full px-4 md:px-6 py-4 flex items-center justify-between bg-[#F5E7C8]/50 hover:bg-[#F5E7C8]/40 transition-colors">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg bg-amber-100 flex items-center justify-center">
                                <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            </div>
                            <h2 class="text-sm font-bold text-amber-800">{{ $t('a_confirm_this_booking') }}</h2>
                        </div>
                        <svg class="w-5 h-5 text-amber-600 transition-transform duration-200" :class="{ 'rotate-180': showConfirmSection }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>

                    <div v-if="showConfirmSection" class="p-4 md:p-6 space-y-6 border-t border-amber-100">
                        <!-- General Validation Errors -->
                        <div v-if="Object.keys(confirmErrors).length" class="p-3 bg-red-50 border border-red-200 rounded-xl">
                            <p class="text-xs font-semibold text-red-700 mb-1">{{ $t('a_fix_errors') }}</p>
                            <ul class="text-xs text-red-600 list-disc list-inside space-y-0.5">
                                <li v-for="(err, key) in confirmErrors" :key="key">{{ err }}</li>
                            </ul>
                        </div>
                        <form @submit.prevent="submitConfirmation" class="space-y-6">
                            <!-- Step 1: Select Patient -->
                            <div>
                                <h3 class="text-sm font-bold text-gray-800 mb-3 flex items-center gap-2">
                                    <span class="w-6 h-6 rounded-full text-white text-xs font-bold flex items-center justify-center" style="background-color: #C4A265;">1</span>
                                    {{ $t('a_select_patient') }}
                                </h3>
                                <div class="relative">
                                    <!-- Selected patient display -->
                                    <div v-if="selectedPatient" class="flex items-center justify-between px-4 py-3 border rounded-xl bg-[#F5E7C8]/30" style="border-color: rgba(196, 162, 101, 0.4);">
                                        <div class="flex items-center gap-3">
                                            <div class="w-8 h-8 rounded-full bg-gradient-to-br from-[#C4A265] to-[#8B7043] flex items-center justify-center text-white text-xs font-bold">
                                                {{ selectedPatient.full_name?.charAt(0) }}
                                            </div>
                                            <div>
                                                <div class="text-sm font-medium text-gray-900">{{ selectedPatient.full_name }}</div>
                                                <div class="text-xs text-gray-500">{{ selectedPatient.phone }} &middot; File #{{ selectedPatient.file_number }}</div>
                                            </div>
                                        </div>
                                        <button type="button" @click="clearPatient" class="text-gray-400 hover:text-red-500 transition">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                                        </button>
                                    </div>
                                    <!-- Search input -->
                                    <div v-else class="relative patient-search-wrapper">
                                        <svg class="absolute ltr:left-3 rtl:right-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                                        <input v-model="patientSearch" type="text" :placeholder="$t('a_search_patient_placeholder')"
                                            class="doctorato-input w-full ltr:pl-10 rtl:pr-10 ltr:pr-4 rtl:pl-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-[#C4A265]/30 focus:border-transparent transition"
                                            @focus="showPatientDropdown = true" />
                                        <div v-if="showPatientDropdown && filteredPatients.length > 0" class="absolute z-30 left-0 right-0 mt-1 bg-white border border-gray-200 rounded-xl shadow-lg max-h-48 overflow-y-auto">
                                            <button v-for="p in filteredPatients" :key="p.id" type="button" @click="selectPatient(p)"
                                                class="w-full flex items-center gap-3 px-4 py-2.5 hover:bg-[#F5E7C8]/40 transition text-start border-b border-gray-50 last:border-b-0">
                                                <div class="w-7 h-7 rounded-full bg-gradient-to-br from-[#C4A265] to-[#8B7043] flex items-center justify-center text-white text-xs font-bold flex-shrink-0">
                                                    {{ p.full_name?.charAt(0) }}
                                                </div>
                                                <div>
                                                    <div class="text-sm font-medium text-gray-900">{{ p.full_name }}</div>
                                                    <div class="text-xs text-gray-400">{{ p.phone }} &middot; File #{{ p.file_number }}</div>
                                                </div>
                                            </button>
                                        </div>
                                    </div>
                                    <p v-if="confirmErrors.patient_id" class="mt-1 text-xs text-red-600">{{ confirmErrors.patient_id }}</p>

                                    <!-- Add New Patient Button -->
                                    <button v-if="!selectedPatient" type="button" @click="showNewPatientModal = true"
                                        class="mt-3 inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold border rounded-lg hover:opacity-90 transition"
                                        style="color: #C4A265; border-color: rgba(196, 162, 101, 0.4);">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" /></svg>
                                        {{ $t('a_add_new_patient') }}
                                    </button>
                                </div>
                            </div>

                            <!-- Step 2: Services / Consultation Details -->
                            <div>
                                <h3 class="text-sm font-bold text-gray-800 mb-3 flex items-center gap-2">
                                    <span class="w-6 h-6 rounded-full text-white text-xs font-bold flex items-center justify-center" style="background-color: #C4A265;">2</span>
                                    {{ isConsultationBooking ? $t('a_consultation_details') : $t('a_services') }}
                                </h3>

                                <!-- Consultation Mode -->
                                <div v-if="isConsultationBooking" class="space-y-3">
                                    <div class="p-4 bg-gray-50 rounded-xl border border-gray-100 space-y-3">
                                        <span class="text-xs font-semibold text-gray-500">
                                            {{ $t('a_' + booking.booking_type) }}
                                        </span>
                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                            <div class="relative ss-dropdown">
                                                <label class="block text-xs text-gray-500 mb-1">{{ $t('a_doctor') }} <span class="text-red-500">*</span></label>
                                                <button type="button" @click.stop="toggleDropdown('consult-doctor')"
                                                    class="w-full flex items-center justify-between px-3 py-2 border border-gray-200 rounded-lg text-sm bg-white hover:border-gray-300 focus:ring-2 focus:ring-[#C4A265]/30 focus:border-transparent transition">
                                                    <span :class="confirmForm.services[0].doctor_id ? 'text-gray-800' : 'text-gray-400'">
                                                        {{ getDoctorName(confirmForm.services[0].doctor_id) || $t('a_select_doctor') }}
                                                    </span>
                                                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                                                </button>
                                                <Transition enter-active-class="transition duration-150" enter-from-class="opacity-0 -translate-y-1" enter-to-class="opacity-100 translate-y-0" leave-active-class="transition duration-100" leave-from-class="opacity-100" leave-to-class="opacity-0">
                                                    <div v-if="openDropdown === 'consult-doctor'" class="absolute z-40 left-0 right-0 mt-1 bg-white border border-gray-200 rounded-xl shadow-xl overflow-hidden">
                                                        <div class="p-2 border-b border-gray-100">
                                                            <div class="relative">
                                                                <svg class="absolute ltr:left-2.5 rtl:right-2.5 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                                                                <input v-model="dropdownSearches['consult-doctor']" type="text" :placeholder="$t('a_search') + '...'" class="doctorato-input w-full ltr:pl-8 rtl:pr-8 ltr:pr-3 rtl:pl-3 py-1.5 text-sm border border-gray-200 rounded-lg focus:ring-1 focus:ring-[#C4A265]/30 focus:border-transparent" @click.stop />
                                                            </div>
                                                        </div>
                                                        <div class="max-h-48 overflow-y-auto">
                                                            <button v-for="d in filteredDoctorsList('consult-doctor')" :key="d.id" type="button"
                                                                @click="confirmForm.services[0].doctor_id = d.id; onConfirmDoctorChange(0); openDropdown = null"
                                                                class="w-full flex items-center gap-2 px-3 py-2 text-sm text-start hover:bg-[#F5E7C8]/40 transition"
                                                                :class="confirmForm.services[0].doctor_id == d.id ? 'bg-[#F5E7C8]/40 font-semibold' : ''">
                                                                <span class="w-6 h-6 rounded-full bg-gradient-to-br from-[#1B365D] to-[#1B365D] flex items-center justify-center text-white text-[10px] font-bold flex-shrink-0">{{ (d.name_en || d.name_ar || '?').charAt(0) }}</span>
                                                                <span>{{ d.name_en || d.name_ar || d.name }}</span>
                                                            </button>
                                                            <p v-if="!filteredDoctorsList('consult-doctor').length" class="text-xs text-gray-400 text-center py-3">{{ $t('a_no_results') }}</p>
                                                        </div>
                                                    </div>
                                                </Transition>
                                            </div>
                                            <div>
                                                <label class="block text-xs text-gray-500 mb-1">{{ $t('a_consultation_fee') }} ({{ currencyCode }}) <span class="text-red-500">*</span></label>
                                                <input v-model.number="confirmForm.services[0].unit_price" type="number" min="0" step="0.01" class="doctorato-input w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-[#C4A265]/30 focus:border-transparent" />
                                            </div>
                                            <div>
                                                <label class="block text-xs text-gray-500 mb-1">{{ $t('a_discount') }} ({{ currencyCode }})</label>
                                                <input v-model.number="confirmForm.services[0].discount_per_session" type="number" min="0" step="0.01" class="doctorato-input w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-[#C4A265]/30 focus:border-transparent" />
                                            </div>
                                            <div class="flex items-end">
                                                <div>
                                                    <label class="block text-xs text-gray-500 mb-1">{{ $t('a_total') }}</label>
                                                    <p class="text-sm font-bold py-2" style="color: #C4A265;">
                                                        {{ formatCurrency(confirmForm.services[0].unit_price - confirmForm.services[0].discount_per_session) }}
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <p v-if="confirmErrors.services" class="text-xs text-red-600">{{ confirmErrors.services }}</p>
                                </div>

                                <!-- Service Mode -->
                                <div v-else class="space-y-3">
                                    <div v-for="(svc, svcIdx) in confirmForm.services" :key="svcIdx"
                                         class="p-4 bg-gray-50 rounded-xl border border-gray-100 space-y-3">
                                        <div class="flex items-center justify-between">
                                            <span class="text-xs font-semibold text-gray-500">{{ $t('a_service') }} {{ svcIdx + 1 }}</span>
                                            <button v-if="confirmForm.services.length > 1" type="button" @click="removeConfirmService(svcIdx)" class="text-red-400 hover:text-red-600 transition">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                            </button>
                                        </div>
                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                            <div class="relative ss-dropdown">
                                                <label class="block text-xs text-gray-500 mb-1">{{ $t('a_service') }} <span class="text-red-500">*</span></label>
                                                <button type="button" @click.stop="toggleDropdown('cf-svc-' + svcIdx)"
                                                    class="w-full flex items-center justify-between px-3 py-2 border border-gray-200 rounded-lg text-sm bg-white hover:border-gray-300 focus:ring-2 focus:ring-[#C4A265]/30 focus:border-transparent transition">
                                                    <span class="truncate" :class="svc.service_id ? 'text-gray-800' : 'text-gray-400'">
                                                        {{ getServiceName(svc.service_id) || $t('a_select_service') }}
                                                    </span>
                                                    <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                                                </button>
                                                <Transition enter-active-class="transition duration-150" enter-from-class="opacity-0 -translate-y-1" enter-to-class="opacity-100 translate-y-0" leave-active-class="transition duration-100" leave-from-class="opacity-100" leave-to-class="opacity-0">
                                                    <div v-if="openDropdown === 'cf-svc-' + svcIdx" class="absolute z-40 left-0 right-0 mt-1 bg-white border border-gray-200 rounded-xl shadow-xl overflow-hidden">
                                                        <div class="p-2 border-b border-gray-100">
                                                            <div class="relative">
                                                                <svg class="absolute ltr:left-2.5 rtl:right-2.5 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                                                                <input v-model="dropdownSearches['cf-svc-' + svcIdx]" type="text" :placeholder="$t('a_search') + '...'" class="doctorato-input w-full ltr:pl-8 rtl:pr-8 ltr:pr-3 rtl:pl-3 py-1.5 text-sm border border-gray-200 rounded-lg focus:ring-1 focus:ring-[#C4A265]/30 focus:border-transparent" @click.stop />
                                                            </div>
                                                        </div>
                                                        <div class="max-h-48 overflow-y-auto">
                                                            <button v-for="s in filteredServicesList('cf-svc-' + svcIdx)" :key="s.id" type="button"
                                                                @click="svc.service_id = s.id; onServiceChange(svcIdx); openDropdown = null"
                                                                class="w-full flex items-center justify-between px-3 py-2 text-sm text-start hover:bg-[#F5E7C8]/40 transition"
                                                                :class="svc.service_id == s.id ? 'bg-[#F5E7C8]/40 font-semibold' : ''">
                                                                <span class="truncate">{{ s.name_en || s.name_ar }}</span>
                                                                <span class="text-xs text-gray-400 flex-shrink-0 ltr:ml-2 rtl:mr-2">{{ formatCurrency(s.price) }}</span>
                                                            </button>
                                                            <p v-if="!filteredServicesList('cf-svc-' + svcIdx).length" class="text-xs text-gray-400 text-center py-3">{{ $t('a_no_results') }}</p>
                                                        </div>
                                                    </div>
                                                </Transition>
                                            </div>
                                            <div class="relative ss-dropdown">
                                                <label class="block text-xs text-gray-500 mb-1">{{ $t('a_doctor') }} <span class="text-red-500">*</span></label>
                                                <button type="button" @click.stop="toggleDropdown('cf-doc-' + svcIdx)"
                                                    class="w-full flex items-center justify-between px-3 py-2 border border-gray-200 rounded-lg text-sm bg-white hover:border-gray-300 focus:ring-2 focus:ring-[#C4A265]/30 focus:border-transparent transition">
                                                    <span :class="svc.doctor_id ? 'text-gray-800' : 'text-gray-400'">
                                                        {{ getDoctorName(svc.doctor_id) || $t('a_select_doctor') }}
                                                    </span>
                                                    <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                                                </button>
                                                <Transition enter-active-class="transition duration-150" enter-from-class="opacity-0 -translate-y-1" enter-to-class="opacity-100 translate-y-0" leave-active-class="transition duration-100" leave-from-class="opacity-100" leave-to-class="opacity-0">
                                                    <div v-if="openDropdown === 'cf-doc-' + svcIdx" class="absolute z-40 left-0 right-0 mt-1 bg-white border border-gray-200 rounded-xl shadow-xl overflow-hidden">
                                                        <div class="p-2 border-b border-gray-100">
                                                            <div class="relative">
                                                                <svg class="absolute ltr:left-2.5 rtl:right-2.5 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                                                                <input v-model="dropdownSearches['cf-doc-' + svcIdx]" type="text" :placeholder="$t('a_search') + '...'" class="doctorato-input w-full ltr:pl-8 rtl:pr-8 ltr:pr-3 rtl:pl-3 py-1.5 text-sm border border-gray-200 rounded-lg focus:ring-1 focus:ring-[#C4A265]/30 focus:border-transparent" @click.stop />
                                                            </div>
                                                        </div>
                                                        <div class="max-h-48 overflow-y-auto">
                                                            <button v-for="d in filteredDoctorsList('cf-doc-' + svcIdx)" :key="d.id" type="button"
                                                                @click="svc.doctor_id = d.id; openDropdown = null"
                                                                class="w-full flex items-center gap-2 px-3 py-2 text-sm text-start hover:bg-[#F5E7C8]/40 transition"
                                                                :class="svc.doctor_id == d.id ? 'bg-[#F5E7C8]/40 font-semibold' : ''">
                                                                <span class="w-6 h-6 rounded-full bg-gradient-to-br from-[#1B365D] to-[#1B365D] flex items-center justify-center text-white text-[10px] font-bold flex-shrink-0">{{ (d.name_en || d.name_ar || '?').charAt(0) }}</span>
                                                                <span>{{ d.name_en || d.name_ar || d.name }}</span>
                                                            </button>
                                                            <p v-if="!filteredDoctorsList('cf-doc-' + svcIdx).length" class="text-xs text-gray-400 text-center py-3">{{ $t('a_no_results') }}</p>
                                                        </div>
                                                    </div>
                                                </Transition>
                                            </div>
                                            <div>
                                                <label class="block text-xs text-gray-500 mb-1">{{ $t('a_sessions') }}</label>
                                                <input v-model.number="svc.sessions_count" type="number" min="1" class="doctorato-input w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-[#C4A265]/30 focus:border-transparent" />
                                            </div>
                                            <div>
                                                <label class="block text-xs text-gray-500 mb-1">{{ $t('a_unit_price') }}</label>
                                                <input v-model.number="svc.unit_price" type="number" min="0" step="0.01" class="doctorato-input w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-[#C4A265]/30 focus:border-transparent" />
                                            </div>
                                            <div>
                                                <label class="block text-xs text-gray-500 mb-1">{{ $t('a_discount_per_session') }}</label>
                                                <input v-model.number="svc.discount_per_session" type="number" min="0" step="0.01" class="doctorato-input w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-[#C4A265]/30 focus:border-transparent" />
                                            </div>
                                            <div class="flex items-end">
                                                <div>
                                                    <label class="block text-xs text-gray-500 mb-1">{{ $t('a_subtotal') }}</label>
                                                    <p class="text-sm font-bold py-2" style="color: #C4A265;">
                                                        {{ formatCurrency((svc.unit_price - svc.discount_per_session) * svc.sessions_count) }}
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <button type="button" @click="addConfirmService" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold border rounded-lg hover:bg-[#F5E7C8]/40 transition" style="color: #C4A265; border-color: rgba(196, 162, 101, 0.4);">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                                        {{ $t('a_add_service') }}
                                    </button>
                                    <p v-if="confirmErrors.services" class="text-xs text-red-600">{{ confirmErrors.services }}</p>
                                </div>
                            </div>

                            <!-- Step 3: Appointments -->
                            <div>
                                <h3 class="text-sm font-bold text-gray-800 mb-3 flex items-center gap-2">
                                    <span class="w-6 h-6 rounded-full text-white text-xs font-bold flex items-center justify-center" style="background-color: #C4A265;">3</span>
                                    {{ $t('a_appointments') }}
                                </h3>
                                <div class="space-y-3">
                                    <div v-for="(appt, apptIdx) in confirmForm.appointments" :key="apptIdx"
                                         class="p-4 bg-gray-50 rounded-xl border border-gray-100 space-y-3">
                                        <div class="flex items-center justify-between">
                                            <span class="text-xs font-semibold text-gray-500">{{ $t('a_appointment') }} {{ apptIdx + 1 }}</span>
                                            <button v-if="confirmForm.appointments.length > 1" type="button" @click="removeConfirmAppointment(apptIdx)" class="text-red-400 hover:text-red-600 transition">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                                            </button>
                                        </div>
                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                            <div class="relative ss-dropdown">
                                                <label class="block text-xs text-gray-500 mb-1">{{ $t('a_for_service') }}</label>
                                                <button type="button" @click.stop="toggleDropdown('appt-svc-' + apptIdx)"
                                                    class="w-full flex items-center justify-between px-3 py-2 border border-gray-200 rounded-lg text-sm bg-white hover:border-gray-300 focus:ring-2 focus:ring-[#C4A265]/30 focus:border-transparent transition">
                                                    <span class="truncate text-gray-800">
                                                        {{ isConsultationBooking
                                                            ? $t('a_' + booking.booking_type)
                                                            : ($t('a_service') + ' ' + (appt.service_index + 1) + ': ' + (getServiceName(confirmForm.services[appt.service_index]?.service_id) || $t('a_not_selected')))
                                                        }}
                                                    </span>
                                                    <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                                                </button>
                                                <Transition enter-active-class="transition duration-150" enter-from-class="opacity-0 -translate-y-1" enter-to-class="opacity-100 translate-y-0" leave-active-class="transition duration-100" leave-from-class="opacity-100" leave-to-class="opacity-0">
                                                    <div v-if="openDropdown === 'appt-svc-' + apptIdx" class="absolute z-40 left-0 right-0 mt-1 bg-white border border-gray-200 rounded-xl shadow-xl overflow-hidden">
                                                        <div class="max-h-48 overflow-y-auto">
                                                            <button v-for="(svc, si) in confirmForm.services" :key="si" type="button"
                                                                @click="appt.service_index = si; openDropdown = null"
                                                                class="w-full px-3 py-2.5 text-sm text-start hover:bg-[#F5E7C8]/40 transition"
                                                                :class="appt.service_index === si ? 'bg-[#F5E7C8]/40 font-semibold' : ''">
                                                                {{ isConsultationBooking
                                                                    ? $t('a_' + booking.booking_type)
                                                                    : ($t('a_service') + ' ' + (si + 1) + ': ' + (getServiceName(svc.service_id) || $t('a_not_selected')))
                                                                }}
                                                            </button>
                                                        </div>
                                                    </div>
                                                </Transition>
                                            </div>
                                            <div class="relative ss-dropdown">
                                                <label class="block text-xs text-gray-500 mb-1">{{ $t('a_doctor') }}</label>
                                                <button type="button" @click.stop="toggleDropdown('appt-doc-' + apptIdx)"
                                                    class="w-full flex items-center justify-between px-3 py-2 border border-gray-200 rounded-lg text-sm bg-white hover:border-gray-300 focus:ring-2 focus:ring-[#C4A265]/30 focus:border-transparent transition">
                                                    <span :class="appt.doctor_id ? 'text-gray-800' : 'text-gray-400'">
                                                        {{ getDoctorName(appt.doctor_id) || $t('a_select_doctor') }}
                                                    </span>
                                                    <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                                                </button>
                                                <Transition enter-active-class="transition duration-150" enter-from-class="opacity-0 -translate-y-1" enter-to-class="opacity-100 translate-y-0" leave-active-class="transition duration-100" leave-from-class="opacity-100" leave-to-class="opacity-0">
                                                    <div v-if="openDropdown === 'appt-doc-' + apptIdx" class="absolute z-40 left-0 right-0 mt-1 bg-white border border-gray-200 rounded-xl shadow-xl overflow-hidden">
                                                        <div class="p-2 border-b border-gray-100">
                                                            <div class="relative">
                                                                <svg class="absolute ltr:left-2.5 rtl:right-2.5 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                                                                <input v-model="dropdownSearches['appt-doc-' + apptIdx]" type="text" :placeholder="$t('a_search') + '...'" class="doctorato-input w-full ltr:pl-8 rtl:pr-8 ltr:pr-3 rtl:pl-3 py-1.5 text-sm border border-gray-200 rounded-lg focus:ring-1 focus:ring-[#C4A265]/30 focus:border-transparent" @click.stop />
                                                            </div>
                                                        </div>
                                                        <div class="max-h-48 overflow-y-auto">
                                                            <button v-for="d in filteredDoctorsList('appt-doc-' + apptIdx)" :key="d.id" type="button"
                                                                @click="appt.doctor_id = d.id; appt.start_time = ''; fetchTimeSlots(apptIdx); openDropdown = null"
                                                                class="w-full flex items-center gap-2 px-3 py-2 text-sm text-start hover:bg-[#F5E7C8]/40 transition"
                                                                :class="appt.doctor_id == d.id ? 'bg-[#F5E7C8]/40 font-semibold' : ''">
                                                                <span class="w-6 h-6 rounded-full bg-gradient-to-br from-[#1B365D] to-[#1B365D] flex items-center justify-center text-white text-[10px] font-bold flex-shrink-0">{{ (d.name_en || d.name_ar || '?').charAt(0) }}</span>
                                                                <span>{{ d.name_en || d.name_ar || d.name }}</span>
                                                            </button>
                                                            <p v-if="!filteredDoctorsList('appt-doc-' + apptIdx).length" class="text-xs text-gray-400 text-center py-3">{{ $t('a_no_results') }}</p>
                                                        </div>
                                                    </div>
                                                </Transition>
                                            </div>
                                            <div>
                                                <label class="block text-xs text-gray-500 mb-1">{{ $t('a_date') }} <span class="text-red-500">*</span></label>
                                                <DoctorDatePicker
                                                    :model-value="appt.appointment_date"
                                                    @update:model-value="onApptDateChange(apptIdx, $event)"
                                                    :doctor-id="appt.doctor_id"
                                                    :doctor-schedules="doctorSchedules"
                                                    :popover="true"
                                                    :disabled="!appt.doctor_id"
                                                />
                                            </div>
                                            <div>
                                                <label class="block text-xs text-gray-500 mb-1">{{ $t('a_time_slot') }}</label>
                                                <div v-if="loadingSlots[apptIdx]" class="flex items-center gap-2 py-2.5">
                                                    <svg class="animate-spin w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/></svg>
                                                    <span class="text-xs text-gray-400">{{ $t('a_loading_slots') }}</span>
                                                </div>
                                                <div v-else-if="timeSlots[apptIdx]?.length" class="flex flex-wrap gap-1.5">
                                                    <button v-for="slot in timeSlots[apptIdx]" :key="slot.start || slot" type="button"
                                                        @click="appt.start_time = slot.start || slot"
                                                        class="px-2.5 py-1.5 text-xs font-medium rounded-lg border transition-all duration-150"
                                                        :class="appt.start_time === (slot.start || slot)
                                                            ? 'text-white border-transparent shadow-sm'
                                                            : 'bg-white text-gray-600 border-gray-200 hover:border-gray-300 hover:bg-gray-50'"
                                                        :style="appt.start_time === (slot.start || slot) ? 'background-color: #C4A265; border-color: #C4A265;' : ''">
                                                        {{ slot.start_12h || formatTime(slot.start || slot) }}
                                                    </button>
                                                </div>
                                                <div v-else-if="appt.appointment_date && appt.doctor_id" class="text-xs text-gray-400 py-2">
                                                    <input v-model="appt.start_time" type="time" class="doctorato-input w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-[#C4A265]/30 focus:border-transparent" />
                                                </div>
                                                <p v-else class="text-xs text-gray-400 py-2">{{ $t('a_select_doctor_and_date') }}</p>
                                            </div>
                                            <div>
                                                <label class="block text-xs text-gray-500 mb-1">{{ $t('a_session') }} #</label>
                                                <input v-model.number="appt.session_number" type="number" min="1" class="doctorato-input w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-[#C4A265]/30 focus:border-transparent" />
                                            </div>
                                        </div>
                                    </div>
                                    <button type="button" @click="addConfirmAppointment" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold border rounded-lg hover:bg-[#F5E7C8]/40 transition" style="color: #C4A265; border-color: rgba(196, 162, 101, 0.4);">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                                        {{ $t('a_add_appointment') }}
                                    </button>
                                    <p v-if="confirmErrors.appointments" class="text-xs text-red-600">{{ confirmErrors.appointments }}</p>
                                </div>
                            </div>

                            <!-- Notes -->
                            <div>
                                <label class="block text-xs font-medium text-gray-500 mb-1.5">{{ $t('a_notes') }}</label>
                                <textarea v-model="confirmForm.notes" rows="2" class="doctorato-input w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-[#C4A265]/30 focus:border-transparent" :placeholder="$t('a_optional_notes')"></textarea>
                            </div>

                            <!-- Submit -->
                            <div class="flex items-center gap-3 pt-2 border-t border-gray-100">
                                <button type="submit" :disabled="confirmProcessing" class="px-4 md:px-6 py-2.5 text-white rounded-xl text-sm font-semibold hover:opacity-90 transition-all disabled:opacity-50 shadow-sm" style="background-color: #C4A265;">
                                    <span v-if="confirmProcessing" class="flex items-center gap-2">
                                        <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/></svg>
                                        {{ $t('a_confirming') }}
                                    </span>
                                    <span v-else>{{ $t('a_confirm_booking') }}</span>
                                </button>
                                <button type="button" @click="showConfirmSection = false" class="px-4 md:px-6 py-2.5 border border-gray-200 text-gray-600 rounded-xl text-sm font-medium hover:bg-gray-50 transition">
                                    {{ $t('a_cancel') }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- ─── Right Column (Sidebar 1/3) ─────────────────── -->
            <div class="space-y-6">
                <!-- Status Update Card -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100/80 p-6">
                    <h3 class="text-sm font-bold text-gray-800 mb-4 border-b border-gray-100 pb-2">{{ $t('a_update_status') }}</h3>
                    <form @submit.prevent="updateStatus" class="space-y-4">
                        <div>
                            <label class="block text-xs text-gray-500 mb-1.5">{{ $t('a_status') }}</label>
                            <div class="relative ss-dropdown">
                                <button type="button" @click.stop="toggleDropdown('status')"
                                    class="w-full flex items-center justify-between px-4 py-2.5 bg-white border border-gray-200 rounded-xl text-sm hover:border-gray-300 focus:ring-2 focus:ring-[#C4A265]/30 focus:border-[#C4A265] transition">
                                    <span class="flex items-center gap-2">
                                        <span class="w-2.5 h-2.5 rounded-full" :class="getStatusOption(statusForm.status).color"></span>
                                        <span class="font-medium text-gray-700">{{ $t('a_' + statusForm.status) }}</span>
                                    </span>
                                    <svg class="w-4 h-4 text-gray-400 transition-transform" :class="openDropdown === 'status' ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                                </button>
                                <div v-if="openDropdown === 'status'" class="absolute z-40 left-0 right-0 mt-1 bg-white border border-gray-200 rounded-xl shadow-xl overflow-hidden">
                                    <div class="p-2 border-b border-gray-100">
                                        <input v-model="dropdownSearches['status']" type="text" :placeholder="locale === 'ar' ? 'بحث...' : 'Search...'" class="doctorato-input w-full px-3 py-1.5 text-sm border border-gray-200 rounded-lg focus:ring-1 focus:ring-[#C4A265]/30 focus:border-[#C4A265]/60" @click.stop />
                                    </div>
                                    <div class="max-h-48 overflow-y-auto">
                                        <button v-for="opt in filteredStatusOptions('status')" :key="opt.value" type="button"
                                            @click="statusForm.status = opt.value; openDropdown = null"
                                            class="w-full flex items-center gap-2.5 px-4 py-2.5 text-sm hover:bg-gray-50 transition text-start"
                                            :class="statusForm.status === opt.value ? 'bg-amber-50' : ''">
                                            <span class="w-2.5 h-2.5 rounded-full flex-shrink-0" :class="opt.color"></span>
                                            <span class="flex-1" :class="statusForm.status === opt.value ? 'font-semibold text-amber-700' : 'text-gray-700'">{{ $t('a_' + opt.value) }}</span>
                                            <svg v-if="statusForm.status === opt.value" class="w-4 h-4 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" /></svg>
                                        </button>
                                        <div v-if="filteredStatusOptions('status').length === 0" class="px-4 py-3 text-sm text-gray-400 text-center">{{ locale === 'ar' ? 'لا توجد نتائج' : 'No results' }}</div>
                                    </div>
                                </div>
                            </div>
                            <p v-if="statusForm.errors.status" class="mt-1 text-xs text-red-600">{{ statusForm.errors.status }}</p>
                        </div>
                        <div>
                            <label class="block text-xs text-gray-500 mb-1.5">{{ $t('a_admin_notes') }}</label>
                            <textarea v-model="statusForm.admin_notes" rows="3" class="doctorato-input w-full px-4 py-2.5 bg-white border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-[#C4A265]/30 focus:border-transparent" :placeholder="$t('a_internal_notes')"></textarea>
                        </div>
                        <button type="submit" :disabled="statusForm.processing" class="w-full py-2.5 px-4 rounded-xl text-white font-semibold text-sm hover:opacity-90 transition-all duration-300 disabled:opacity-50 shadow-sm" style="background-color: #C4A265;">
                            {{ statusForm.processing ? $t('a_updating') : $t('a_update_status') }}
                        </button>
                    </form>
                </div>

                <!-- Invoice Summary (Sidebar) -->
                <div v-if="invoice" class="bg-white rounded-2xl shadow-sm border border-gray-100/80 p-6">
                    <h3 class="text-sm font-bold text-gray-800 mb-4 border-b border-gray-100 pb-2">{{ $t('a_invoice_summary') }}</h3>
                    <dl class="space-y-3">
                        <div class="flex justify-between text-sm">
                            <dt class="text-gray-500">{{ $t('a_invoice') }} #</dt>
                            <dd class="font-mono font-medium" style="color: #C4A265;">{{ invoice.invoice_number || invoice.id }}</dd>
                        </div>
                        <div class="flex justify-between text-sm">
                            <dt class="text-gray-500">{{ $t('a_subtotal') }}</dt>
                            <dd class="text-gray-700">{{ formatCurrency(invoice.subtotal) }}</dd>
                        </div>
                        <div v-if="invoice.discount_amount > 0" class="flex justify-between text-sm">
                            <dt class="text-gray-500">{{ $t('a_discount') }}</dt>
                            <dd class="text-red-600">-{{ formatCurrency(invoice.discount_amount) }}</dd>
                        </div>
                        <div class="flex justify-between text-base font-bold border-t border-gray-100 pt-3">
                            <dt class="text-gray-800">{{ $t('a_total') }}</dt>
                            <dd style="color: #C4A265;">{{ formatCurrency(invoice.total) }}</dd>
                        </div>
                        <div class="flex justify-between text-sm border-t border-gray-100 pt-3">
                            <dt class="text-gray-500">{{ $t('a_paid') }}</dt>
                            <dd class="text-emerald-600 font-medium">{{ formatCurrency(invoice.paid_amount) }}</dd>
                        </div>
                        <div class="flex justify-between text-sm font-bold">
                            <dt class="text-gray-800">{{ $t('a_balance') }}</dt>
                            <dd :class="invoiceBalance > 0 ? 'text-red-600' : 'text-emerald-600'">{{ formatCurrency(invoiceBalance) }}</dd>
                        </div>
                    </dl>
                </div>

                <!-- Booking Details Card -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100/80 p-6">
                    <h3 class="text-sm font-bold text-gray-800 mb-4 border-b border-gray-100 pb-2">{{ $t('a_booking_details') }}</h3>
                    <dl class="space-y-3">
                        <div class="flex justify-between text-sm">
                            <dt class="text-gray-500">{{ $t('a_booking') }} #</dt>
                            <dd class="font-mono font-medium text-gray-800">{{ bookingNumber }}</dd>
                        </div>
                        <div class="flex justify-between text-sm">
                            <dt class="text-gray-500">{{ $t('a_status') }}</dt>
                            <dd>
                                <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-semibold border capitalize" :class="bookingStatusColors[booking.status]">
                                    {{ $t('a_' + booking.status) || booking.status }}
                                </span>
                            </dd>
                        </div>
                        <div v-if="booking.source" class="flex justify-between text-sm">
                            <dt class="text-gray-500">{{ $t('a_source') }}</dt>
                            <dd>
                                <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-semibold border capitalize" :class="sourceColors[booking.source]">
                                    {{ booking.source }}
                                </span>
                            </dd>
                        </div>
                        <div class="flex justify-between text-sm">
                            <dt class="text-gray-500">{{ $t('a_created') }}</dt>
                            <dd class="text-gray-700">{{ formatDate(booking.created_at) }}</dd>
                        </div>
                        <div v-if="booking.creator" class="flex justify-between text-sm">
                            <dt class="text-gray-500">{{ $t('a_created_by') }}</dt>
                            <dd class="text-gray-700">{{ booking.creator.name }}</dd>
                        </div>
                    </dl>
                </div>

                <!-- Admin Notes Card -->
                <div v-if="booking.admin_notes" class="bg-white rounded-2xl shadow-sm border border-gray-100/80 p-6">
                    <h3 class="text-sm font-bold text-gray-800 mb-2">{{ $t('a_admin_notes') }}</h3>
                    <p class="text-sm text-gray-600 whitespace-pre-wrap">{{ booking.admin_notes }}</p>
                </div>

                <!-- Consent Documents Card -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100/80 p-6">
                    <h3 class="text-sm font-bold text-gray-800 mb-4 border-b border-gray-100 pb-2">{{ $t('a_consent_documents') }}</h3>

                    <!-- Existing consents -->
                    <div v-if="booking.consents?.length" class="space-y-2 mb-4">
                        <div v-for="consent in booking.consents" :key="consent.id" class="flex items-center gap-3 p-2.5 bg-gray-50 rounded-xl border border-gray-100 group">
                            <a :href="consent.file_url" target="_blank" class="w-12 h-12 rounded-lg overflow-hidden bg-gray-200 flex-shrink-0 block">
                                <img :src="consent.file_url" :alt="consent.original_name" class="w-full h-full object-cover" />
                            </a>
                            <div class="flex-1 min-w-0">
                                <a :href="consent.file_url" target="_blank" class="text-xs font-medium text-gray-700 truncate block hover:underline">{{ consent.original_name }}</a>
                                <p class="text-[10px] text-gray-400 mt-0.5">{{ consent.uploader?.name || $t('a_unknown') }} &middot; {{ formatDate(consent.created_at) }}</p>
                            </div>
                            <button type="button" @click="deleteConsent(consent.id)" class="opacity-0 group-hover:opacity-100 text-gray-400 hover:text-red-500 transition p-1" :title="$t('a_delete')">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                            </button>
                        </div>
                    </div>
                    <div v-else class="text-center py-4 mb-3">
                        <svg class="w-8 h-8 text-gray-300 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                        <p class="text-xs text-gray-400">{{ $t('a_no_consent_documents') }}</p>
                    </div>

                    <!-- Upload button -->
                    <label class="w-full flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl text-sm font-semibold cursor-pointer hover:opacity-90 transition border-2 border-dashed"
                           style="color: #C4A265; border-color: rgba(196, 162, 101, 0.4);"
                           :class="{ 'opacity-50 pointer-events-none': consentForm.processing }">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                        {{ consentForm.processing ? $t('a_uploading') : $t('a_upload_consent_images') }}
                        <input ref="consentFileInput" type="file" multiple accept="image/jpeg,image/png,image/webp" class="hidden" @change="onConsentFilesSelected" />
                    </label>
                    <p v-if="consentForm.errors.consents" class="mt-1.5 text-xs text-red-600">{{ consentForm.errors.consents }}</p>
                    <p v-if="consentForm.errors['consents.0']" class="mt-1.5 text-xs text-red-600">{{ consentForm.errors['consents.0'] }}</p>
                </div>

                <!-- Quick Actions -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100/80 p-6">
                    <h3 class="text-sm font-bold text-gray-800 mb-3 border-b border-gray-100 pb-2">{{ $t('a_quick_actions') }}</h3>
                    <div class="space-y-2">
                        <Link href="/admin/bookings" class="w-full flex items-center gap-2 px-4 py-2.5 rounded-lg text-sm font-medium text-gray-600 hover:bg-gray-50 transition border border-gray-200">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16" /></svg>
                            {{ $t('a_all_bookings') }}
                        </Link>
                        <Link v-if="booking.patient" :href="`/admin/patients/${booking.patient.id}`" class="w-full flex items-center gap-2 px-4 py-2.5 rounded-lg text-sm font-medium hover:bg-[#F5E7C8]/40 transition border" style="color: #C4A265; border-color: rgba(196, 162, 101, 0.4);">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                            {{ $t('a_view_patient') }}
                        </Link>
                        <Link v-if="invoice" :href="`/admin/invoices/${invoice.id}`" class="w-full flex items-center gap-2 px-4 py-2.5 rounded-lg text-sm font-medium hover:bg-[#F5E7C8]/40 transition border" style="color: #C4A265; border-color: rgba(196, 162, 101, 0.4);">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z" /></svg>
                            {{ $t('a_view_invoice') }}
                        </Link>
                        <a :href="`/admin/bookings/${booking.id}/receipt`" target="_blank" class="w-full flex items-center gap-2 px-4 py-2.5 rounded-lg text-sm font-medium text-gray-600 hover:bg-gray-50 transition border border-gray-200">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" /></svg>
                            {{ $t('a_print_receipt') }}
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <QuickAddPatientModal
            :show="showNewPatientModal"
            theme="gold"
            :prefill="newPatientPrefill"
            submit-url="/admin/patients/quick-create"
            @close="showNewPatientModal = false"
            @created="onPatientCreated"
        />

        <!-- ─── Reschedule Appointment Modal ──────────────────── -->
        <Teleport to="body">
            <div v-if="editingAppointment" class="fixed inset-0 z-[60] flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="closeRescheduleModal"></div>
                <div class="relative w-full max-w-lg bg-white rounded-2xl shadow-2xl overflow-hidden transform transition-all">
                    <!-- Modal Header -->
                    <div class="px-4 md:px-6 py-4 border-b border-gray-100 bg-gradient-to-r from-[#C4A265]/5 to-transparent">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-lg font-bold text-gray-900">{{ $t('a_edit_appointment') }}</h3>
                                <p class="text-xs text-gray-500 mt-0.5">
                                    {{ $t('a_session') }} #{{ editingAppointment.session_number || '-' }}
                                    <span v-if="editingAppointment.is_retouch" class="ltr:ml-1 rtl:mr-1 text-[#1B365D]">({{ $t('a_retouch') }})</span>
                                </p>
                            </div>
                            <button @click="closeRescheduleModal" class="p-1.5 rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-100 transition-colors">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                            </button>
                        </div>
                    </div>

                    <!-- Modal Body -->
                    <div class="px-4 md:px-6 py-5 space-y-5 max-h-[70vh] overflow-y-auto">
                        <!-- Doctor -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1.5">{{ $t('a_doctor') }}</label>
                            <select v-model="rescheduleForm.doctor_id" @change="fetchRescheduleTimeSlots" class="doctorato-input w-full rounded-xl border-gray-200 text-sm focus:border-[#C4A265] focus:ring-[#C4A265]/20 transition">
                                <option value="">{{ $t('a_select_doctor') }}</option>
                                <option v-for="doc in doctors" :key="doc.id" :value="doc.id">
                                    {{ doc.name_en || doc.name_ar }}
                                </option>
                            </select>
                            <p v-if="rescheduleForm.errors.doctor_id" class="mt-1 text-xs text-red-600">{{ rescheduleForm.errors.doctor_id }}</p>
                        </div>

                        <!-- Date -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1.5">{{ $t('a_appointment_date') }}</label>
                            <input type="date" v-model="rescheduleForm.appointment_date" @change="fetchRescheduleTimeSlots" class="doctorato-input w-full rounded-xl border-gray-200 text-sm focus:border-[#C4A265] focus:ring-[#C4A265]/20 transition" />
                            <p v-if="rescheduleForm.errors.appointment_date" class="mt-1 text-xs text-red-600">{{ rescheduleForm.errors.appointment_date }}</p>
                        </div>

                        <!-- Time Slots -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1.5">{{ $t('a_available_time_slots') }}</label>
                            <div v-if="rescheduleLoadingSlots" class="flex items-center gap-2 text-sm text-gray-400 py-3">
                                <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                                {{ $t('a_loading_slots') }}
                            </div>
                            <div v-else-if="rescheduleTimeSlots.length > 0" class="grid grid-cols-1 md:grid-cols-3 sm:grid-cols-4 gap-2 max-h-48 overflow-y-auto pr-1">
                                <button v-for="slot in rescheduleTimeSlots" :key="slot.start" @click="selectRescheduleSlot(slot)" type="button"
                                    class="px-3 py-2 rounded-lg text-xs font-medium border transition-all text-center"
                                    :class="rescheduleForm.start_time === slot.start ? 'bg-[#C4A265] text-white border-[#C4A265] shadow-md' : 'bg-white text-gray-600 border-gray-200 hover:border-[#C4A265]/40 hover:bg-[#C4A265]/5'">
                                    {{ slot.start_12h || formatTime(slot.start) }}
                                </button>
                            </div>
                            <p v-else-if="rescheduleForm.doctor_id && rescheduleForm.appointment_date && !rescheduleLoadingSlots" class="text-xs text-gray-400 py-3 text-center bg-gray-50 rounded-lg">
                                {{ $t('a_no_slots_available') }}
                            </p>
                            <p v-else class="text-xs text-gray-400 py-3 text-center">
                                {{ $t('a_select_doctor_and_date') }}
                            </p>
                        </div>

                        <!-- Selected Time Display -->
                        <div v-if="rescheduleForm.start_time && rescheduleForm.end_time" class="bg-[#C4A265]/5 rounded-xl p-3 border border-[#C4A265]/20">
                            <div class="flex items-center gap-2">
                                <svg class="w-4 h-4 text-[#C4A265]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                <span class="text-sm font-semibold text-gray-800">
                                    {{ formatTime(rescheduleForm.start_time) }} - {{ formatTime(rescheduleForm.end_time) }}
                                </span>
                                <span class="text-xs text-gray-500">
                                    {{ rescheduleForm.appointment_date }}
                                </span>
                            </div>
                        </div>

                        <!-- Validation errors -->
                        <p v-if="rescheduleForm.errors.start_time" class="text-xs text-red-600">{{ rescheduleForm.errors.start_time }}</p>
                        <p v-if="rescheduleForm.errors.end_time" class="text-xs text-red-600">{{ rescheduleForm.errors.end_time }}</p>
                    </div>

                    <!-- Modal Footer -->
                    <div class="px-4 md:px-6 py-4 border-t border-gray-100 bg-gray-50/50 flex items-center justify-end gap-3">
                        <button @click="closeRescheduleModal" class="px-4 py-2 rounded-xl text-sm font-medium text-gray-600 hover:text-gray-800 hover:bg-gray-100 transition-colors">
                            {{ $t('a_cancel') }}
                        </button>
                        <button @click="submitReschedule" :disabled="rescheduleForm.processing || !rescheduleForm.start_time || !rescheduleForm.doctor_id || !rescheduleForm.appointment_date"
                            class="px-5 py-2 rounded-xl text-sm font-semibold text-white shadow-sm transition-all disabled:opacity-40 disabled:cursor-not-allowed"
                            :class="rescheduleForm.processing ? 'bg-gray-400' : 'bg-[#C4A265] hover:bg-[#B3914F] active:scale-95'">
                            {{ rescheduleForm.processing ? $t('a_saving') : $t('a_save_changes') }}
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </div>
</template>
