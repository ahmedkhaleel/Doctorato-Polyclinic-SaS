<script setup>
import { ref, reactive, computed, watch, nextTick, onMounted } from 'vue';
import { Link, router , usePage } from '@inertiajs/vue3';
import SecretaryLayout from '@/Layouts/SecretaryLayout.vue';
import QuickAddPatientModal from '@/Components/QuickAddPatientModal.vue';
import PatientSearchSelect from '@/Components/Doctor/PatientSearchSelect.vue';
import DoctorDatePicker from '@/Components/DoctorDatePicker.vue';
import PromoCodeInput from '@/Components/PromoCodeInput.vue';
import { useCurrency } from '@/Composables/useCurrency.js';

defineOptions({ layout: SecretaryLayout });

const page = usePage();
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const props = defineProps({
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
    pediatricConsultantFee: { type: Number, default: 0 },
    pediatricSpecialistFee: { type: Number, default: 0 },
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

const cardsVisible = ref(false);
onMounted(() => { setTimeout(() => cardsVisible.value = true, 100); });

// Step 1 - Patient
const patientSearch = ref('');
const showPatientDropdown = ref(false);
const selectedPatient = ref(null);

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

const { formatCurrency, currencyCode } = useCurrency();

const modules = computed(() => page.props.modules || {});
const isDermaEnabled = computed(() => modules.value.derma?.enabled !== false);
const isDentalEnabled = computed(() => modules.value.dental?.enabled === true);
const isPediatricEnabled = computed(() => modules.value.pediatric?.enabled === true);

const isConsultation = computed(() =>
    bookingType.value === 'dermatology_consultation' || bookingType.value === 'cosmetic_consultation' || bookingType.value === 'dental_consultation' || bookingType.value === 'pediatric_consultation'
);

const isDental = computed(() =>
    bookingType.value === 'dental_consultation' || bookingType.value === 'dental_service'
);

const isPediatric = computed(() =>
    bookingType.value === 'pediatric_consultation' || bookingType.value === 'pediatric_service'
);

const defaultMod = computed(() => page.props.defaultModule || 'derma');

const currentModule = computed(() => {
    if (isDental.value) return 'dental';
    if (isPediatric.value) return 'pediatric';
    return defaultMod.value;
});

const bookingTypeCards = computed(() => {
    const groups = [];

    if (isDermaEnabled.value) {
        groups.push({
            module: 'derma',
            titleAr: 'الجلدية والتجميل',
            titleEn: 'Dermatology & Cosmetics',
            color: '#0d9488',
            items: [
                { value: 'dermatology_consultation', titleAr: 'استشارة جلدية', titleEn: 'Dermatology Consultation', descAr: 'فحص وتشخيص الأمراض الجلدية', descEn: 'Skin examination & diagnosis', iconPath: 'M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z' },
                { value: 'cosmetic_consultation', titleAr: 'استشارة تجميلية', titleEn: 'Cosmetic Consultation', descAr: 'استشارة تجميلية متخصصة', descEn: 'Specialized cosmetic consultation', iconPath: 'M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09zM18.259 8.715L18 9.75l-.259-1.035a3.375 3.375 0 00-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 002.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 002.455 2.456L21.75 6l-1.036.259a3.375 3.375 0 00-2.455 2.456z' },
                { value: 'service', titleAr: 'حجز خدمة', titleEn: 'Book Service', descAr: 'حجز جلسة علاجية أو تجميلية', descEn: 'Book a treatment session', iconPath: 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z' },
            ],
        });
    } else {
        // General group always shows service even without derma
        groups.push({
            module: 'general',
            titleAr: 'عام',
            titleEn: 'General',
            color: '#0d9488',
            items: [
                { value: 'service', titleAr: 'حجز خدمة', titleEn: 'Book Service', descAr: 'حجز جلسة علاجية أو تجميلية', descEn: 'Book a treatment session', iconPath: 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z' },
            ],
        });
    }

    if (isDentalEnabled.value) {
        groups.push({
            module: 'dental',
            titleAr: 'طب الأسنان',
            titleEn: 'Dental',
            color: '#06b6d4',
            items: [
                { value: 'dental_consultation', titleAr: 'استشارة أسنان', titleEn: 'Dental Consultation', descAr: 'فحص وتشخيص مشاكل الأسنان', descEn: 'Dental examination & diagnosis', iconPath: 'M15.182 15.182a4.5 4.5 0 01-6.364 0M21 12a9 9 0 11-18 0 9 9 0 0118 0zM9.75 9.75c0 .414-.168.75-.375.75S9 10.164 9 9.75 9.168 9 9.375 9s.375.336.375.75zm-.375 0h.008v.015h-.008V9.75zm5.625 0c0 .414-.168.75-.375.75s-.375-.336-.375-.75.168-.75.375-.75.375.336.375.75zm-.375 0h.008v.015h-.008V9.75z' },
                { value: 'dental_service', titleAr: 'خدمة أسنان', titleEn: 'Dental Service', descAr: 'حجز علاج أو إجراء للأسنان', descEn: 'Book a dental procedure', iconPath: 'M21.75 6.75a4.5 4.5 0 01-4.884 4.484c-1.076-.091-2.264.071-2.95.904l-7.152 8.684a2.548 2.548 0 11-3.586-3.586l8.684-7.152c.833-.686.995-1.874.904-2.95a4.5 4.5 0 016.336-4.486l-3.276 3.276a3.004 3.004 0 002.25 2.25l3.276-3.276c.256.565.398 1.192.398 1.852z' },
            ],
        });
    }

    if (isPediatricEnabled.value) {
        groups.push({
            module: 'pediatric',
            titleAr: 'طب الأطفال',
            titleEn: 'Pediatrics',
            color: '#16a34a',
            items: [
                { value: 'pediatric_consultation', titleAr: 'استشارة أطفال', titleEn: 'Pediatric Consultation', descAr: 'فحص وتشخيص أمراض الأطفال', descEn: 'Pediatric examination & diagnosis', iconPath: 'M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z' },
                { value: 'pediatric_service', titleAr: 'خدمة أطفال', titleEn: 'Pediatric Service', descAr: 'تطعيم أو متابعة نمو', descEn: 'Vaccination or growth monitoring', iconPath: 'M9.75 3.104v5.714a2.25 2.25 0 01-.659 1.591L5 14.5m9.5-11.396v5.714c0 .597.237 1.17.659 1.591L19 14.5M9.75 3.104c-.251.023-.501.05-.75.082m.75-.082a24.301 24.301 0 014.5 0m0 0c.251.023.501.05.75.082M5 14.5l1.429 5.57a1.2 1.2 0 001.165.93h8.812a1.2 1.2 0 001.165-.93L19 14.5' },
            ],
        });
    }

    return groups;
});

const selectedTypeInfo = computed(() => {
    for (const group of bookingTypeCards.value) {
        const found = group.items.find(t => t.value === bookingType.value);
        if (found) return { ...found, color: group.color };
    }
    return null;
});

const filteredServices = computed(() => {
    const mod = currentModule.value;
    return (props.services || []).filter(s => s.module === mod || (!s.module && mod === defaultMod.value));
});

const filteredServiceCategories = computed(() => {
    const mod = currentModule.value;
    return (props.serviceCategories || []).map(cat => ({
        ...cat,
        services: (cat.services || []).filter(s => s.module === mod || (!s.module && mod === defaultMod.value))
    })).filter(cat => cat.services.length > 0);
});

const filteredDoctors = computed(() => {
    const mod = currentModule.value;
    return (props.doctors || []).filter(d => d.module === mod || (!d.module && mod === defaultMod.value));
});

function getConsultationFeeForDoctor(doctorId) {
    const doctor = getDoctor(doctorId);
    if (bookingType.value === 'dermatology_consultation') {
        if (followUpInfo.value?.eligible) return props.followupFee || 0;
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
    if (bookingType.value === 'pediatric_consultation') {
        if (doctor?.doctor_type === 'consultant') return props.pediatricConsultantFee || 0;
        if (doctor?.doctor_type === 'specialist') return props.pediatricSpecialistFee || 0;
        return props.pediatricConsultantFee || 0;
    }
    return 0;
}

function onConsultationDoctorChange(index) {
    const row = serviceRows[index];
    row.unit_price = getConsultationFeeForDoctor(row.doctor_id);
}

// Watch booking type changes to reset form
watch(bookingType, (newType) => {
    // Reset service rows
    serviceRows.splice(0, serviceRows.length, createServiceRow());
    // Reset appointment data
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
    } else if (newType === 'pediatric_consultation') {
        serviceRows[0].sessions_count = 1;
        serviceRows[0].service_id = '';
        serviceRows[0].unit_price = props.pediatricConsultantFee || 0;
    } else if (newType === 'pediatric_service') {
        serviceRows[0].sessions_count = 1;
        serviceRows[0].service_id = '';
        serviceRows[0].unit_price = 0;
    }
});

/* ------------------------------------------------------------------ */
/*  Step 1 - Patient                                                   */
/* ------------------------------------------------------------------ */

const filteredPatients = ref([]);
const patientSearchLoading = ref(false);
let patientSearchTimer = null;
let patientAbortController = null;

watch(patientSearch, (val) => {
    clearTimeout(patientSearchTimer);
    if (!val || val.length < 2) {
        filteredPatients.value = [];
        return;
    }
    patientSearchTimer = setTimeout(() => {
        patientSearchLoading.value = true;
        if (patientAbortController) patientAbortController.abort();
        patientAbortController = new AbortController();
        fetch(`/secretary/api/patients?q=${encodeURIComponent(val)}`, {
            signal: patientAbortController.signal,
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        })
            .then(r => r.json())
            .then(data => { filteredPatients.value = data; patientSearchLoading.value = false; showPatientDropdown.value = true; })
            .catch(e => { if (e.name !== 'AbortError') patientSearchLoading.value = false; });
    }, 300);
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
        const res = await fetch(`/secretary/bookings/check-followup?patient_id=${patientId}`);
        if (res.ok) {
            const data = await res.json();
            followUpInfo.value = data.follow_up;
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

function handlePatientSelect(patient) {
    if (patient) {
        selectPatient(patient);
    } else {
        clearPatient();
    }
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
            service_id: isConsultation.value ? null : Number(row.service_id),
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

    router.post('/secretary/bookings', buildPayload(), {
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

const stepLabels = computed(() => isRtl.value ? [
    'اختر المريض',
    isConsultation.value ? 'تفاصيل الكشف' : 'إضافة الخدمات',
    'تحديد المواعيد',
    'تأكيد',
] : [
    'Select Patient',
    isConsultation.value ? 'Consultation Details' : 'Add Services',
    'Set Appointments',
    'Confirm',
]);
</script>

<template>
    <div>
        <!-- Header -->
        <div class="flex items-center gap-3 mb-6">
            <Link href="/secretary/bookings" class="text-gray-400 hover:text-gray-600 transition-colors">
                <svg class="w-5 h-5 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
            </Link>
            <div>
                <h1 class="text-2xl font-bold text-gray-900">{{ isRtl ? 'حجز جديد' : 'New Booking' }}</h1>
                <p class="text-sm text-gray-500 mt-1">{{ isRtl ? 'إنشاء حجز بالخدمات والمواعيد' : 'Create a booking with services and appointments' }}</p>
            </div>
        </div>

        <!-- Booking Type Selector -->
        <div class="mb-6 max-w-4xl mx-auto">
          <div class="bg-white rounded-2xl shadow-sm border border-gray-100/80 p-4 sm:p-5 overflow-hidden">
            <h3 class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-4 text-center">
              {{ isRtl ? 'اختر نوع الحجز' : 'Select Booking Type' }}
            </h3>

            <!-- All cards in one flat grid — 3 cols on mobile, up to 4 on desktop -->
            <div class="grid grid-cols-3 sm:grid-cols-4 lg:grid-cols-7 gap-2 sm:gap-3">
              <template v-for="(group, gIndex) in bookingTypeCards" :key="group.module">
                <button
                  v-for="(type, tIndex) in group.items"
                  :key="type.value"
                  @click="bookingType = type.value"
                  type="button"
                  class="booking-card relative flex flex-col items-center text-center p-2.5 sm:p-3 rounded-xl border-2 transition-all duration-300 group/card cursor-pointer"
                  :class="[
                    bookingType === type.value
                      ? 'shadow-lg scale-[1.04] -translate-y-0.5'
                      : 'border-gray-100 hover:border-gray-200 hover:-translate-y-0.5 hover:shadow-md',
                  ]"
                  :style="[
                    cardsVisible ? { animationDelay: ((gIndex * 3 + tIndex) * 70) + 'ms' } : { opacity: 0 },
                    bookingType === type.value
                      ? { borderColor: group.color, backgroundColor: group.color + '0A', boxShadow: '0 8px 20px -4px ' + group.color + '30' }
                      : {},
                  ]"
                >
                  <!-- Glow ring behind icon when active -->
                  <div class="relative mb-2">
                    <div
                      v-if="bookingType === type.value"
                      class="absolute inset-0 rounded-xl blur-md opacity-40"
                      :style="{ backgroundColor: group.color }"
                    ></div>
                    <div
                      class="relative w-11 h-11 sm:w-12 sm:h-12 rounded-xl flex items-center justify-center transition-all duration-300"
                      :class="bookingType === type.value ? 'icon-pulse' : ''"
                      :style="bookingType === type.value
                        ? { background: `linear-gradient(135deg, ${group.color}, ${group.color}CC)`, color: '#fff' }
                        : { backgroundColor: '#f3f4f6', color: '#9ca3af' }"
                    >
                      <svg class="w-5 h-5 sm:w-5.5 sm:h-5.5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" :d="type.iconPath" />
                      </svg>
                    </div>
                  </div>

                  <!-- Title -->
                  <p
                    class="text-[10px] sm:text-[11px] font-bold leading-tight transition-colors duration-200 line-clamp-2"
                    :style="bookingType === type.value ? { color: group.color } : { color: '#374151' }"
                  >
                    {{ isRtl ? type.titleAr : type.titleEn }}
                  </p>

                  <!-- Active check badge -->
                  <div
                    v-if="bookingType === type.value"
                    class="active-dot absolute -top-1 -end-1 w-5 h-5 rounded-full flex items-center justify-center shadow-sm"
                    :style="{ backgroundColor: group.color }"
                  >
                    <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                    </svg>
                  </div>

                  <!-- Module color bar at bottom -->
                  <div
                    class="absolute bottom-0 inset-x-0 h-0.5 rounded-b-xl transition-all duration-300"
                    :style="{ backgroundColor: bookingType === type.value ? group.color : 'transparent' }"
                  ></div>
                </button>
              </template>
            </div>

            <!-- Selected type description -->
            <div v-if="selectedTypeInfo" class="mt-3 flex items-center justify-center gap-2 text-xs text-gray-500 transition-all duration-300">
              <div class="w-1.5 h-1.5 rounded-full" :style="{ backgroundColor: selectedTypeInfo.color }"></div>
              <span>{{ isRtl ? selectedTypeInfo.descAr : selectedTypeInfo.descEn }}</span>
            </div>
          </div>
        </div>

            <!-- Follow-up Eligibility Banner -->
            <div v-if="followUpInfo?.eligible && bookingType === 'dermatology_consultation'" class="mb-6 max-w-4xl mx-auto">
                <div class="flex items-start gap-3 px-3 sm:px-5 py-4 bg-teal-50 border border-teal-200 rounded-2xl">
                    <div class="w-8 h-8 rounded-lg bg-teal-100 flex items-center justify-center flex-shrink-0 mt-0.5">
                        <svg class="w-4 h-4 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    </div>
                    <div class="flex-1">
                        <p class="text-sm font-semibold text-teal-800">{{ isRtl ? 'تم اكتشاف زيارة متابعة' : 'Follow-up Visit Detected' }}</p>
                        <p class="text-xs text-teal-600 mt-0.5">
                            This patient had a dermatology consultation on {{ new Date(followUpInfo.last_visit_date).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' }) }}.
                            The reduced follow-up fee of <span class="font-bold">{{ formatCurrency(props.followupFee) }}</span> has been auto-applied.
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
                                    ? 'bg-gradient-to-r from-teal-500 to-[#1B365D] text-white shadow-lg shadow-teal-500/30'
                                    : currentStep > index + 1
                                        ? 'bg-teal-500 text-white'
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
                                currentStep >= index + 1 ? 'text-teal-600' : 'text-gray-400'
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
                            currentStep > index + 1 ? 'bg-teal-500' : 'bg-gray-200'
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
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100/80 p-4 sm:p-6">
                    <h2 class="text-sm font-bold text-gray-800 mb-4 border-b border-gray-100 pb-2">{{ isRtl ? 'اختر المريض' : 'Select Patient' }}</h2>

                    <!-- Patient Search Select -->
                    <div class="max-w-lg">
                        <label class="block text-xs font-medium text-gray-500 mb-1.5">{{ isRtl ? 'بحث عن مريض' : 'Search Patient' }} <span class="text-red-500">*</span></label>
                        <PatientSearchSelect
                            :model-value="selectedPatient?.id || ''"
                            :initial-patient="selectedPatient"
                            search-url="/secretary/api/patients"
                            accent-color="#0d9488"
                            @patient-selected="handlePatientSelect"
                        />
                    </div>

                    <!-- Add New Patient Button -->
                    <button v-if="!selectedPatient" type="button" @click="showNewPatientModal = true"
                        class="mt-3 inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-teal-600 border border-teal-200 rounded-lg hover:bg-teal-50 transition">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" /></svg>
                        {{ isRtl ? 'إضافة مريض جديد' : 'Add New Patient' }}
                    </button>

                    <!-- Selected Patient Card -->
                    <div v-if="selectedPatient" class="mt-5 p-4 bg-teal-50/50 border border-teal-100 rounded-xl">
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 rounded-xl bg-gradient-to-r from-teal-500 to-[#1B365D] flex items-center justify-center text-white text-lg font-bold flex-shrink-0">
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
            <!-- STEP 2: Add Services / Consultation Details    -->
            <!-- ============================================= -->
            <div v-show="currentStep === 2">
                <!-- Consultation Mode -->
                <template v-if="isConsultation">
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100/80 p-4 sm:p-6">
                        <div class="border-b border-gray-100 pb-2 mb-4">
                            <h2 class="text-sm font-bold text-gray-800">
                                {{ bookingType === 'dermatology_consultation' ? (isRtl ? 'استشارة جلدية' : 'Dermatology Consultation') : bookingType === 'dental_consultation' ? (isRtl ? 'استشارة أسنان' : 'Dental Consultation') : bookingType === 'pediatric_consultation' ? (isRtl ? 'استشارة أطفال' : 'Pediatric Consultation') : (isRtl ? 'استشارة تجميلية' : 'Cosmetic Consultation') }}
                            </h2>
                            <p class="text-xs text-gray-400 mt-1">{{ isRtl ? 'اختر الطبيب وأكد رسوم الكشف' : 'Select the doctor and confirm the consultation fee' }}</p>
                        </div>

                        <div class="p-4 bg-gray-50/50 border border-gray-100 rounded-xl">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <!-- Doctor Select -->
                                <div>
                                    <label class="block text-xs font-medium text-gray-500 mb-1">{{ isRtl ? 'الطبيب' : 'Doctor' }} <span class="text-red-500">*</span></label>
                                    <select
                                        v-model="serviceRows[0].doctor_id"
                                        @change="onConsultationDoctorChange(0)"
                                        class="w-full px-4 py-2.5 bg-white border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-teal-500/30 focus:border-teal-500"
                                    >
                                        <option value="">{{ isRtl ? 'اختر الطبيب' : 'Select doctor' }}</option>
                                        <option v-for="d in filteredDoctors" :key="d.id" :value="d.id">{{ d.name_en || d.name_ar }}</option>
                                    </select>
                                </div>

                                <!-- Consultation Fee -->
                                <div>
                                    <label class="block text-xs font-medium text-gray-500 mb-1">{{ isRtl ? 'رسوم الكشف' : 'Consultation Fee' }} ({{ currencyCode }}) <span class="text-red-500">*</span></label>
                                    <input
                                        v-model.number="serviceRows[0].unit_price"
                                        type="number"
                                        min="0"
                                        step="0.01"
                                        class="w-full px-4 py-2.5 bg-white border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-teal-500/30 focus:border-teal-500"
                                    />
                                </div>

                                <!-- Discount -->
                                <div>
                                    <label class="block text-xs font-medium text-gray-500 mb-1">{{ isRtl ? 'الخصم' : 'Discount' }} ({{ currencyCode }})</label>
                                    <input
                                        v-model.number="serviceRows[0].discount_per_session"
                                        type="number"
                                        min="0"
                                        step="0.01"
                                        class="w-full px-4 py-2.5 bg-white border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-teal-500/30 focus:border-teal-500"
                                    />
                                </div>

                                <!-- Total -->
                                <div>
                                    <label class="block text-xs font-medium text-gray-500 mb-1">{{ isRtl ? 'الإجمالي' : 'Total' }}</label>
                                    <div class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm font-bold text-teal-600">
                                        {{ formatCurrency(serviceRowTotal(serviceRows[0])) }}
                                    </div>
                                </div>
                            </div>

                            <!-- Notes -->
                            <div class="mt-3">
                                <label class="block text-xs font-medium text-gray-500 mb-1">{{ isRtl ? 'ملاحظات (اختياري)' : 'Notes (optional)' }}</label>
                                <input
                                    v-model="serviceRows[0].notes"
                                    type="text"
                                    :placeholder="isRtl ? 'ملاحظات الاستشارة...' : 'Consultation notes...'"
                                    class="w-full px-4 py-2 bg-white border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-teal-500/30 focus:border-teal-500"
                                />
                            </div>
                        </div>
                    </div>
                </template>

                <!-- Service Mode -->
                <template v-else>
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100/80 p-4 sm:p-6">
                        <div class="flex items-center justify-between border-b border-gray-100 pb-2 mb-4">
                            <h2 class="text-sm font-bold text-gray-800">{{ isRtl ? 'الخدمات' : 'Services' }}</h2>
                            <button
                                type="button"
                                @click="addServiceRow"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-teal-500 text-white rounded-lg text-xs font-semibold hover:bg-teal-600 transition"
                            >
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                                {{ isRtl ? 'إضافة خدمة' : 'Add Service' }}
                            </button>
                        </div>

                        <div class="space-y-4">
                            <div
                                v-for="(row, index) in serviceRows"
                                :key="index"
                                class="p-4 bg-gray-50/50 border border-gray-100 rounded-xl"
                            >
                                <div class="flex items-center justify-between mb-3">
                                    <span class="text-xs font-semibold text-gray-500 uppercase">{{ isRtl ? 'الخدمة' : 'Service' }} {{ index + 1 }}</span>
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
                                    <!-- Service Select -->
                                    <div>
                                        <label class="block text-xs font-medium text-gray-500 mb-1">{{ isRtl ? 'الخدمة' : 'Service' }} <span class="text-red-500">*</span></label>
                                        <select
                                            v-model="row.service_id"
                                            @change="onServiceChange(index)"
                                            class="w-full px-4 py-2.5 bg-white border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-teal-500/30 focus:border-teal-500"
                                        >
                                            <option value="">{{ isRtl ? 'اختر الخدمة' : 'Select service' }}</option>
                                            <template v-if="filteredServiceCategories && filteredServiceCategories.length">
                                                <template v-for="cat in filteredServiceCategories" :key="cat.id">
                                                    <optgroup v-if="cat.services && cat.services.length" :label="cat.name_en || cat.name_ar">
                                                        <option v-for="s in cat.services" :key="s.id" :value="s.id">
                                                            {{ s.name_en || s.name_ar }} -- {{ formatCurrency(s.price_after_discount || s.price) }}
                                                        </option>
                                                    </optgroup>
                                                </template>
                                            </template>
                                            <template v-else>
                                                <option v-for="s in filteredServices" :key="s.id" :value="s.id">
                                                    {{ s.name_en || s.name_ar }} -- {{ formatCurrency(s.price_after_discount || s.price) }}
                                                </option>
                                            </template>
                                        </select>
                                    </div>

                                    <!-- Doctor Select -->
                                    <div>
                                        <label class="block text-xs font-medium text-gray-500 mb-1">{{ isRtl ? 'الطبيب' : 'Doctor' }} <span class="text-red-500">*</span></label>
                                        <select
                                            v-model="row.doctor_id"
                                            class="w-full px-4 py-2.5 bg-white border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-teal-500/30 focus:border-teal-500"
                                        >
                                            <option value="">{{ isRtl ? 'اختر الطبيب' : 'Select doctor' }}</option>
                                            <option v-for="d in filteredDoctors" :key="d.id" :value="d.id">{{ d.name_en || d.name_ar }}</option>
                                        </select>
                                    </div>

                                    <!-- Sessions Count -->
                                    <div>
                                        <label class="block text-xs font-medium text-gray-500 mb-1">{{ isRtl ? 'الجلسات' : 'Sessions' }} <span class="text-red-500">*</span></label>
                                        <input
                                            v-model.number="row.sessions_count"
                                            type="number"
                                            min="1"
                                            class="w-full px-4 py-2.5 bg-white border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-teal-500/30 focus:border-teal-500"
                                        />
                                    </div>

                                    <!-- Unit Price -->
                                    <div>
                                        <label class="block text-xs font-medium text-gray-500 mb-1">{{ isRtl ? 'سعر الوحدة' : 'Unit Price' }} ({{ currencyCode }})</label>
                                        <input
                                            v-model.number="row.unit_price"
                                            type="number"
                                            min="0"
                                            step="0.01"
                                            class="w-full px-4 py-2.5 bg-white border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-teal-500/30 focus:border-teal-500"
                                        />
                                    </div>

                                    <!-- Discount per Session -->
                                    <div>
                                        <label class="block text-xs font-medium text-gray-500 mb-1">{{ isRtl ? 'الخصم / جلسة' : 'Discount / Session' }} ({{ currencyCode }})</label>
                                        <input
                                            v-model.number="row.discount_per_session"
                                            type="number"
                                            min="0"
                                            step="0.01"
                                            class="w-full px-4 py-2.5 bg-white border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-teal-500/30 focus:border-teal-500"
                                        />
                                    </div>

                                    <!-- Row Total -->
                                    <div>
                                        <label class="block text-xs font-medium text-gray-500 mb-1">{{ isRtl ? 'الإجمالي' : 'Total' }}</label>
                                        <div class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm font-bold text-teal-600">
                                            {{ formatCurrency(serviceRowTotal(row)) }}
                                        </div>
                                    </div>
                                </div>

                                <!-- Service notes -->
                                <div class="mt-3">
                                    <label class="block text-xs font-medium text-gray-500 mb-1">{{ isRtl ? 'ملاحظات (اختياري)' : 'Notes (optional)' }}</label>
                                    <input
                                        v-model="row.notes"
                                        type="text"
                                        :placeholder="isRtl ? 'ملاحظات خاصة بالخدمة...' : 'Service-specific notes...'"
                                        class="w-full px-4 py-2 bg-white border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-teal-500/30 focus:border-teal-500"
                                    />
                                </div>
                            </div>
                        </div>

                        <!-- Running Total -->
                        <div class="mt-5 pt-4 border-t border-gray-100 flex items-center justify-between">
                            <span class="text-sm font-semibold text-gray-600">{{ isRtl ? 'الإجمالي الكلي' : 'Grand Total' }}</span>
                            <span class="text-xl font-bold text-teal-600">{{ formatCurrency(grandTotal) }}</span>
                        </div>
                    </div>
                </template>
            </div>

            <!-- ============================================= -->
            <!-- STEP 3: Set Appointments                       -->
            <!-- ============================================= -->
            <div v-show="currentStep === 3">
                <div class="space-y-6">
                    <div
                        v-for="(row, sIndex) in serviceRows"
                        :key="sIndex"
                        class="bg-white rounded-2xl shadow-sm border border-gray-100/80 p-4 sm:p-6"
                    >
                        <h2 class="text-sm font-bold text-gray-800 mb-1">
                            {{ isConsultation
                                ? (bookingType === 'dermatology_consultation' ? (isRtl ? 'استشارة جلدية' : 'Dermatology Consultation') : bookingType === 'dental_consultation' ? (isRtl ? 'استشارة أسنان' : 'Dental Consultation') : bookingType === 'pediatric_consultation' ? (isRtl ? 'استشارة أطفال' : 'Pediatric Consultation') : (isRtl ? 'استشارة تجميلية' : 'Cosmetic Consultation'))
                                : (getService(row.service_id)?.name_en || getService(row.service_id)?.name_ar || (isRtl ? 'الخدمة' : 'Service') + ' ' + (sIndex + 1))
                            }}
                        </h2>
                        <p class="text-xs text-gray-400 mb-4">
                            {{ row.sessions_count }} {{ row.sessions_count > 1 ? (isRtl ? 'جلسات' : 'sessions') : (isRtl ? 'جلسة' : 'session') }}
                            {{ isRtl ? 'مع د.' : 'with Dr.' }} {{ getDoctor(row.doctor_id)?.name_en || getDoctor(row.doctor_id)?.name_ar || '-' }}
                        </p>

                        <div class="space-y-4">
                            <div
                                v-for="(apt, aIndex) in (appointmentData[sIndex] || [])"
                                :key="aIndex"
                                class="p-4 bg-gray-50/50 border border-gray-100 rounded-xl"
                            >
                                <div class="flex items-center gap-2 mb-3">
                                    <div class="w-6 h-6 rounded-full bg-teal-100 text-teal-700 flex items-center justify-center text-xs font-bold">
                                        {{ aIndex + 1 }}
                                    </div>
                                    <span class="text-xs font-semibold text-gray-500">{{ isRtl ? 'الجلسة' : 'Session' }} {{ aIndex + 1 }}</span>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                                    <!-- Doctor override -->
                                    <div>
                                        <label class="block text-xs font-medium text-gray-500 mb-1">{{ isRtl ? 'الطبيب' : 'Doctor' }}</label>
                                        <select
                                            v-model="apt.doctor_id"
                                            @change="onAppointmentDoctorChange(sIndex, aIndex)"
                                            class="w-full px-3 py-2 bg-white border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-teal-500/30 focus:border-teal-500"
                                        >
                                            <option value="">{{ isRtl ? 'اختر الطبيب' : 'Select doctor' }}</option>
                                            <option v-for="d in filteredDoctors" :key="d.id" :value="d.id">{{ d.name_en || d.name_ar }}</option>
                                        </select>
                                    </div>

                                    <!-- Date (Doctor Availability Calendar) -->
                                    <div>
                                        <label class="block text-xs font-medium text-gray-500 mb-1">{{ isRtl ? 'التاريخ' : 'Date' }} <span class="text-red-500">*</span></label>
                                        <DoctorDatePicker
                                            v-model="apt.date"
                                            :doctor-id="apt.doctor_id"
                                            :doctor-schedules="doctorSchedules"
                                            :min-date="new Date().toISOString().split('T')[0]"
                                            accent-color="#14b8a6"
                                            popover
                                            @update:model-value="onAppointmentDateChange(sIndex, aIndex)"
                                        />
                                    </div>

                                    <!-- Selected Time Display -->
                                    <div>
                                        <label class="block text-xs font-medium text-gray-500 mb-1">{{ isRtl ? 'الوقت' : 'Time' }}</label>
                                        <div
                                            v-if="apt.start_time && apt.end_time"
                                            class="w-full px-3 py-2 bg-teal-50 border border-teal-200 rounded-xl text-sm font-semibold text-teal-700"
                                        >
                                            {{ formatTime12h(apt.start_time) }} - {{ formatTime12h(apt.end_time) }}
                                        </div>
                                        <div v-else class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-xl text-sm text-gray-400">
                                            {{ isRtl ? 'اختر الوقت أدناه' : 'Select a slot below' }}
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
                                    <svg class="w-4 h-4 text-teal-500 animate-spin" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    <span class="text-xs text-gray-500">{{ isRtl ? 'جاري تحميل المواعيد المتاحة...' : 'Loading available slots...' }}</span>
                                </div>

                                <!-- Available Time Slots -->
                                <div v-if="apt.availableSlots.length > 0 && !apt.loadingSlots" class="mt-3">
                                    <p class="text-xs font-medium text-gray-500 mb-2">{{ isRtl ? 'المواعيد المتاحة' : 'Available Slots' }}</p>
                                    <div class="flex flex-wrap gap-2">
                                        <button
                                            v-for="(slot, slotIdx) in apt.availableSlots"
                                            :key="slotIdx"
                                            type="button"
                                            @click="selectTimeSlot(sIndex, aIndex, slot)"
                                            :class="[
                                                'px-3 py-1.5 rounded-lg text-xs font-semibold transition-all border',
                                                apt.start_time === (slot.start_time || slot.start) && apt.end_time === (slot.end_time || slot.end)
                                                    ? 'bg-teal-500 text-white border-teal-500 shadow-sm'
                                                    : 'bg-white text-gray-600 border-gray-200 hover:border-teal-300 hover:bg-teal-50'
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
                                    <p class="text-xs text-gray-400">{{ isRtl ? 'لا توجد مواعيد متاحة لهذا التاريخ' : 'No available time slots for this date' }}</p>
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
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100/80 p-4 sm:p-6">
                        <h2 class="text-sm font-bold text-gray-800 mb-4 border-b border-gray-100 pb-2">{{ isRtl ? 'المريض' : 'Patient' }}</h2>
                        <div v-if="selectedPatient" class="flex items-center gap-4">
                            <div class="w-12 h-12 rounded-xl bg-gradient-to-r from-teal-500 to-[#1B365D] flex items-center justify-center text-white text-lg font-bold flex-shrink-0">
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
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100/80 p-4 sm:p-6">
                        <h2 class="text-sm font-bold text-gray-800 mb-4 border-b border-gray-100 pb-2">{{ isRtl ? 'الخدمات والمواعيد' : 'Services & Appointments' }}</h2>
                        <div class="space-y-5">
                            <div v-for="(row, sIndex) in serviceRows" :key="sIndex">
                                <div class="flex items-start justify-between">
                                    <div>
                                        <p class="text-sm font-semibold text-gray-800">
                                            {{ isConsultation
                                                ? (bookingType === 'dermatology_consultation' ? (isRtl ? 'استشارة جلدية' : 'Dermatology Consultation') : bookingType === 'dental_consultation' ? (isRtl ? 'استشارة أسنان' : 'Dental Consultation') : bookingType === 'pediatric_consultation' ? (isRtl ? 'استشارة أطفال' : 'Pediatric Consultation') : (isRtl ? 'استشارة تجميلية' : 'Cosmetic Consultation'))
                                                : (getService(row.service_id)?.name_en || getService(row.service_id)?.name_ar || '-')
                                            }}
                                        </p>
                                        <p class="text-xs text-gray-400 mt-0.5">
                                            {{ isRtl ? 'د.' : 'Dr.' }} {{ getDoctor(row.doctor_id)?.name_en || getDoctor(row.doctor_id)?.name_ar || '-' }}
                                            &middot; {{ row.sessions_count }} {{ row.sessions_count > 1 ? (isRtl ? 'جلسات' : 'sessions') : (isRtl ? 'جلسة' : 'session') }}
                                        </p>
                                    </div>
                                    <div class="ltr:text-right rtl:text-left">
                                        <p class="text-sm font-bold text-gray-800">{{ formatCurrency(serviceRowTotal(row)) }}</p>
                                        <p v-if="row.discount_per_session > 0" class="text-xs text-red-500 mt-0.5">
                                            -{{ formatCurrency(row.discount_per_session) }} / {{ isRtl ? 'جلسة' : 'session' }}
                                        </p>
                                    </div>
                                </div>

                                <!-- Appointments for this service -->
                                <div v-if="appointmentData[sIndex]?.length" class="mt-2 ml-4 space-y-1">
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
                                        <span v-if="apt.doctor_id != row.doctor_id" class="text-teal-600 font-medium">
                                            ({{ isRtl ? 'د.' : 'Dr.' }} {{ getDoctor(apt.doctor_id)?.name_en || getDoctor(apt.doctor_id)?.name_ar }})
                                        </span>
                                    </div>
                                </div>

                                <div v-if="sIndex < serviceRows.length - 1" class="mt-4 border-b border-gray-100"></div>
                            </div>
                        </div>

                        <!-- Grand Total -->
                        <div class="mt-5 pt-4 border-t border-gray-100 flex items-center justify-between">
                            <span class="text-sm font-semibold text-gray-600">{{ isRtl ? 'الإجمالي الكلي' : 'Grand Total' }}</span>
                            <span class="text-xl font-bold text-teal-600">{{ formatCurrency(grandTotal) }}</span>
                        </div>
                    </div>

                    <!-- Notes -->
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100/80 p-4 sm:p-6">
                        <h2 class="text-sm font-bold text-gray-800 mb-4 border-b border-gray-100 pb-2">{{ isRtl ? 'ملاحظات' : 'Notes' }}</h2>
                        <textarea
                            v-model="notes"
                            rows="3"
                            :placeholder="isRtl ? 'ملاحظات اختيارية للحجز...' : 'Optional booking notes...'"
                            class="w-full px-4 py-2.5 bg-white border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-teal-500/30 focus:border-teal-500"
                        ></textarea>
                    </div>

                    <!-- Promo Code -->
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100/80 p-4 sm:p-6">
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
                            <span class="text-sm font-semibold text-red-700">{{ isRtl ? 'يرجى تصحيح الأخطاء التالية:' : 'Please fix the following errors:' }}</span>
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
                        <svg class="w-4 h-4 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                        </svg>
                        {{ isRtl ? 'السابق' : 'Previous' }}
                    </button>
                </div>
                <div>
                    <button
                        v-if="currentStep < totalSteps"
                        type="button"
                        @click="nextStep"
                        :disabled="!canProceed(currentStep)"
                        class="inline-flex items-center gap-2 px-6 py-2.5 bg-gradient-to-r from-teal-500 to-[#1B365D] text-white rounded-xl text-sm font-semibold hover:from-teal-600 hover:to-[#1B365D] transition-all shadow-lg shadow-teal-500/20 disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        {{ isRtl ? 'التالي' : 'Next' }}
                        <svg class="w-4 h-4 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </button>
                    <button
                        v-if="currentStep === totalSteps"
                        type="button"
                        @click="submit"
                        :disabled="processing"
                        class="inline-flex items-center gap-2 px-6 py-2.5 bg-gradient-to-r from-teal-500 to-[#1B365D] text-white rounded-xl text-sm font-semibold hover:from-teal-600 hover:to-[#1B365D] transition-all shadow-lg shadow-teal-500/20 disabled:opacity-50"
                    >
                        <svg v-if="processing" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        {{ processing ? (isRtl ? 'جاري الإنشاء...' : 'Creating...') : (isRtl ? 'تأكيد الحجز' : 'Create Booking') }}
                    </button>
                </div>
            </div>

        </div>

        <QuickAddPatientModal
            :show="showNewPatientModal"
            theme="teal"
            :prefill="{}"
            submit-url="/secretary/patients/quick-create"
            @close="showNewPatientModal = false"
            @created="onPatientCreated"
        />
    </div>
</template>

<style scoped>
.booking-card {
  animation: bookingCardIn 0.45s cubic-bezier(0.16, 1, 0.3, 1) both;
}

@keyframes bookingCardIn {
  from {
    opacity: 0;
    transform: translateY(16px) scale(0.97);
  }
  to {
    opacity: 1;
    transform: translateY(0) scale(1);
  }
}

.icon-pulse {
  animation: iconPulse 2s ease-in-out infinite;
}

@keyframes iconPulse {
  0%, 100% { transform: scale(1); }
  50% { transform: scale(1.1); }
}

.active-dot {
  animation: dotPulse 1.5s ease-in-out infinite;
}

@keyframes dotPulse {
  0%, 100% { opacity: 1; transform: scale(1); }
  50% { opacity: 0.5; transform: scale(0.75); }
}
</style>
