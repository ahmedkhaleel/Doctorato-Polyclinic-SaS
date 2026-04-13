<script setup>
import { ref, computed, watch, reactive } from 'vue';
import { Link, router , usePage } from '@inertiajs/vue3';
import SecretaryLayout from '@/Layouts/SecretaryLayout.vue';
import DoctorDatePicker from '@/Components/DoctorDatePicker.vue';
import QuickAddPatientModal from '@/Components/QuickAddPatientModal.vue';
import PromoCodeInput from '@/Components/PromoCodeInput.vue';
import { useCurrency } from '@/Composables/useCurrency.js';

defineOptions({ layout: SecretaryLayout });

const page = usePage();
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const props = defineProps({
    bundles: Array,
    patients: Array,
    doctors: Array,
    doctorSchedules: Array,
});

/* -- State ------------------------------------------------- */
const currentStep = ref(1);
const totalSteps = 4;
const processing = ref(false);
const errors = ref({});

// Step 1 - Patient & Bundle Selection
const patientSearch = ref('');
const showPatientDropdown = ref(false);
const selectedPatient = ref(null);
const selectedBundleId = ref(null);

// Step 2 - Doctor Assignment per Service
const serviceAssignments = reactive([]);

// Step 3 - Appointment Scheduling
const appointmentData = reactive({});

// Step 4 - Notes & Promo
const notes = ref('');
const promoCode = ref('');

const { formatCurrency, currencyCode } = useCurrency();

/* -- Helpers ----------------------------------------------- */

// JS dayOfWeek -> System dayOfWeek (0=Saturday)
function jsToSystemDay(jsDay) {
    const map = { 0: 1, 1: 2, 2: 3, 3: 4, 4: 5, 5: 6, 6: 0 };
    return map[jsDay];
}

const dayNames = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

function getDoctorScheduleForDay(doctorId, systemDay) {
    return props.doctorSchedules.find(
        s => Number(s.doctor_id) === Number(doctorId) && Number(s.day_of_week) === systemDay
    );
}

function getServiceDuration(serviceIndex) {
    const sa = serviceAssignments[serviceIndex];
    if (!sa) return 30;
    const bundle = props.bundles.find(b => b.id === selectedBundleId.value);
    const bs = bundle?.services?.find(s => s.id === sa.package_bundle_service_id);
    return bs?.service?.session_duration_minutes || 30;
}

/* -- Computed --------------------------------------------- */
const filteredPatients = computed(() => {
    if (!patientSearch.value) return props.patients.slice(0, 20);
    const q = patientSearch.value.toLowerCase();
    return props.patients.filter(p =>
        p.full_name?.toLowerCase().includes(q) ||
        p.phone?.includes(q) ||
        p.file_number?.toLowerCase().includes(q)
    ).slice(0, 20);
});

const selectedBundle = computed(() => {
    if (!selectedBundleId.value) return null;
    return props.bundles.find(b => b.id === selectedBundleId.value);
});

const bundleServices = computed(() => {
    return selectedBundle.value?.services || [];
});

const stepLabels = computed(() => [
    'Patient & Bundle',
    'Assign Doctors',
    'Schedule Appointments',
    'Review & Confirm',
]);

/* -- Patient Selection ------------------------------------ */
function selectPatient(patient) {
    selectedPatient.value = patient;
    patientSearch.value = patient.full_name;
    showPatientDropdown.value = false;
}

function clearPatient() {
    selectedPatient.value = null;
    patientSearch.value = '';
}

/* Quick Add Patient Modal */
const showNewPatientModal = ref(false);

function onPatientCreated(patient) {
    selectPatient(patient);
    if (props.patients && !props.patients.find(p => p.id === patient.id)) {
        props.patients.unshift(patient);
    }
}

/* -- Bundle Selection ------------------------------------- */
watch(selectedBundleId, (newId) => {
    serviceAssignments.length = 0;
    // Clear appointment data
    Object.keys(appointmentData).forEach(k => delete appointmentData[k]);

    if (!newId) return;

    const bundle = props.bundles.find(b => b.id === newId);
    if (bundle?.services) {
        bundle.services.forEach((bs, index) => {
            serviceAssignments.push({
                package_bundle_service_id: bs.id,
                service_name: bs.service?.name_en || 'Service',
                sessions_count: bs.sessions_count,
                bundle_price: bs.bundle_price,
                discount_percentage: bs.discount_percentage,
                doctor_id: null,
            });
        });
    }
});

/* -- Sync appointment slots when moving to step 3 --------- */
function initAppointmentData() {
    serviceAssignments.forEach((sa, sIndex) => {
        if (!appointmentData[sIndex]) {
            appointmentData[sIndex] = [];
        }
        // Grow or shrink to match sessions count
        while (appointmentData[sIndex].length < sa.sessions_count) {
            appointmentData[sIndex].push({
                doctor_id: sa.doctor_id,
                date: '',
                start_time: '',
                end_time: '',
                availableSlots: [],
                loadingSlots: false,
                scheduleWarning: '',
            });
        }
        while (appointmentData[sIndex].length > sa.sessions_count) {
            appointmentData[sIndex].pop();
        }
        // Update doctor_id from service assignments
        appointmentData[sIndex].forEach(apt => {
            if (!apt.doctor_id) apt.doctor_id = sa.doctor_id;
        });
    });
}

/* -- Time Slot Fetching ----------------------------------- */
async function fetchTimeSlots(serviceIndex, aptIndex) {
    const apt = appointmentData[serviceIndex]?.[aptIndex];
    if (!apt || !apt.doctor_id || !apt.date) return;

    const duration = getServiceDuration(serviceIndex);
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
        }
    } catch (err) {
        apt.availableSlots = [];
    } finally {
        apt.loadingSlots = false;
    }
}

function onAppointmentDateChange(serviceIndex, aptIndex) {
    const apt = appointmentData[serviceIndex]?.[aptIndex];
    if (!apt || !apt.date) return;

    // Check doctor availability for this day
    const dateObj = new Date(apt.date + 'T00:00:00');
    const jsDay = dateObj.getDay();
    const systemDay = jsToSystemDay(jsDay);
    const schedule = getDoctorScheduleForDay(apt.doctor_id, systemDay);

    if (!schedule) {
        apt.scheduleWarning = `Doctor does not work on ${dayNames[jsDay]}s`;
        apt.availableSlots = [];
        apt.start_time = '';
        apt.end_time = '';
        return;
    }

    apt.scheduleWarning = '';
    fetchTimeSlots(serviceIndex, aptIndex);
}

function onAppointmentDoctorChange(serviceIndex, aptIndex) {
    const apt = appointmentData[serviceIndex]?.[aptIndex];
    if (!apt) return;
    apt.date = '';
    apt.start_time = '';
    apt.end_time = '';
    apt.availableSlots = [];
    apt.scheduleWarning = '';
}

function selectSlot(serviceIndex, aptIndex, slot) {
    const apt = appointmentData[serviceIndex]?.[aptIndex];
    if (!apt) return;
    apt.start_time = slot.start;
    apt.end_time = slot.end;
}

/* -- Navigation ------------------------------------------- */
function canGoToStep(step) {
    if (step === 2) return selectedPatient.value && selectedBundleId.value;
    if (step === 3) return canGoToStep(2) && serviceAssignments.every(sa => sa.doctor_id);
    if (step === 4) {
        if (!canGoToStep(3)) return false;
        // All appointments must have date, start_time, end_time
        for (let sIndex = 0; sIndex < serviceAssignments.length; sIndex++) {
            const apts = appointmentData[sIndex] || [];
            for (let aIndex = 0; aIndex < apts.length; aIndex++) {
                if (!apts[aIndex].date || !apts[aIndex].start_time || !apts[aIndex].end_time) {
                    return false;
                }
            }
        }
        return true;
    }
    return true;
}

function nextStep() {
    if (currentStep.value < totalSteps && canGoToStep(currentStep.value + 1)) {
        if (currentStep.value === 2) {
            initAppointmentData();
        }
        currentStep.value++;
    }
}

function prevStep() {
    if (currentStep.value > 1) currentStep.value--;
}

/* -- Submit ----------------------------------------------- */
function submit() {
    if (!canGoToStep(4)) return;

    processing.value = true;
    errors.value = {};

    const payload = {
        package_bundle_id: selectedBundleId.value,
        patient_id: selectedPatient.value.id,
        notes: notes.value || null,
        promo_code: promoCode.value || null,
        services: serviceAssignments.map((sa, sIndex) => ({
            package_bundle_service_id: sa.package_bundle_service_id,
            doctor_id: sa.doctor_id,
            appointments: (appointmentData[sIndex] || []).map(apt => ({
                date: apt.date,
                start_time: apt.start_time,
                end_time: apt.end_time,
                doctor_id: apt.doctor_id,
            })),
        })),
    };

    router.post('/secretary/bundle-bookings', payload, {
        onError: (errs) => {
            errors.value = errs;
            processing.value = false;
        },
        onFinish: () => {
            processing.value = false;
        },
    });
}
</script>

<template>
    <div class="space-y-6">
        <!-- Header -->
        <div class="flex items-center gap-3">
            <Link href="/secretary/bundle-bookings"
                  class="p-2 rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-100 transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
            </Link>
            <h1 class="text-2xl font-bold text-gray-800">{{ isRtl ? 'حجز باقة جديد' : 'New Bundle Booking' }}</h1>
        </div>

        <!-- Step Indicator -->
        <div class="bg-white rounded-lg shadow-sm p-4">
            <div class="flex items-center justify-center">
                <template v-for="step in totalSteps" :key="step">
                    <div class="flex items-center">
                        <div class="flex items-center justify-center w-8 h-8 rounded-full text-sm font-semibold transition-all duration-300"
                             :class="currentStep >= step ? 'text-white' : 'bg-gray-200 text-gray-500'"
                             :style="currentStep >= step ? 'background-color: #0d9488;' : ''">
                            <svg v-if="currentStep > step" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                            <span v-else>{{ step }}</span>
                        </div>
                        <span class="ltr:ml-2 rtl:mr-2 text-sm font-medium hidden sm:inline"
                              :class="currentStep >= step ? 'text-gray-800' : 'text-gray-400'">
                            {{ stepLabels[step - 1] }}
                        </span>
                    </div>
                    <div v-if="step < totalSteps" class="w-8 sm:w-12 mx-2 sm:mx-3 h-px"
                         :class="currentStep > step ? 'bg-teal-500' : 'bg-gray-200'"></div>
                </template>
            </div>
        </div>

        <!-- Step 1: Patient & Bundle Selection -->
        <div v-show="currentStep === 1" class="space-y-6">
            <!-- Patient Search -->
            <div class="bg-white rounded-lg shadow-sm p-4 sm:p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">{{ isRtl ? 'اختر المريض' : 'Select Patient' }}</h3>

                <div v-if="selectedPatient" class="flex items-center justify-between p-4 border-2 rounded-lg" style="border-color: #0d9488; background-color: rgba(13,148,136,0.05);">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full flex items-center justify-center text-white text-sm font-semibold" style="background-color: #0d9488;">
                            {{ selectedPatient.full_name?.charAt(0) }}
                        </div>
                        <div>
                            <div class="font-medium text-gray-900">{{ selectedPatient.full_name }}</div>
                            <div class="text-xs text-gray-500">{{ selectedPatient.phone }} <span v-if="selectedPatient.file_number" class="ltr:ml-2 rtl:mr-2 font-mono" style="color: #0d9488;">#{{ selectedPatient.file_number }}</span></div>
                        </div>
                    </div>
                    <button @click="clearPatient" class="text-gray-400 hover:text-red-500 transition p-1">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                <div v-else class="relative">
                    <input
                        v-model="patientSearch"
                        @focus="showPatientDropdown = true"
                        @blur="setTimeout(() => showPatientDropdown = false, 200)"
                        type="text"
                        :placeholder="isRtl ? 'بحث عن مريض بالاسم، الهاتف، أو رقم الملف...' : 'Search patient by name, phone, or file number...'"
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-teal-200 focus:border-transparent"
                    />
                    <div v-if="showPatientDropdown && filteredPatients.length > 0"
                         class="absolute z-20 w-full mt-1 bg-white border border-gray-200 rounded-lg shadow-lg max-h-60 overflow-y-auto">
                        <button v-for="p in filteredPatients" :key="p.id"
                                @mousedown.prevent="selectPatient(p)"
                                class="w-full flex items-center gap-3 px-4 py-3 ltr:text-left rtl:ltr:text-right rtl:text-left hover:bg-gray-50 transition">
                            <div class="w-8 h-8 rounded-full bg-gray-200 flex items-center justify-center text-xs font-semibold text-gray-600">
                                {{ p.full_name?.charAt(0) }}
                            </div>
                            <div>
                                <div class="text-sm font-medium text-gray-900">{{ p.full_name }}</div>
                                <div class="text-xs text-gray-400">{{ p.phone }} <span v-if="p.file_number" class="font-mono ltr:ml-1 rtl:mr-1">#{{ p.file_number }}</span></div>
                            </div>
                        </button>
                    </div>
                    <div v-if="showPatientDropdown && filteredPatients.length === 0 && patientSearch"
                         class="absolute z-20 w-full mt-1 bg-white border border-gray-200 rounded-lg shadow-lg p-4 text-center">
                        <p class="text-sm text-gray-400">{{ isRtl ? 'لم يتم العثور على مرضى' : 'No patients found' }}</p>
                        <button
                            type="button"
                            @mousedown.prevent="showNewPatientModal = true"
                            class="mt-2 inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold transition-all border border-teal-300 text-teal-600 hover:bg-teal-50"
                        >
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                            Add New Patient
                        </button>
                    </div>
                </div>

                <!-- Quick Add Patient Button (always visible) -->
                <div v-if="!selectedPatient" class="mt-3">
                    <button
                        type="button"
                        @click="showNewPatientModal = true"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold transition-all border border-teal-300 text-teal-600 hover:bg-teal-50"
                    >
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" /></svg>
                        Add New Patient
                    </button>
                </div>

                <p v-if="errors.patient_id" class="text-red-500 text-xs mt-1">{{ errors.patient_id }}</p>
            </div>

            <!-- Bundle Selection -->
            <div class="bg-white rounded-lg shadow-sm p-4 sm:p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">{{ isRtl ? 'اختر الباقة' : 'Select Package Bundle' }}</h3>

                <div v-if="bundles.length === 0" class="text-center py-8 text-gray-500 text-sm">
                    No active bundles available. Please create a package bundle first.
                </div>

                <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    <button v-for="bundle in bundles" :key="bundle.id"
                            @click="selectedBundleId = bundle.id"
                            class="relative ltr:text-left rtl:ltr:text-right rtl:text-left p-5 rounded-xl border-2 transition-all duration-200 hover:shadow-md"
                            :class="selectedBundleId === bundle.id ? 'shadow-md' : 'border-gray-200 hover:border-gray-300'"
                            :style="selectedBundleId === bundle.id ? 'border-color: #0d9488; background-color: rgba(13,148,136,0.05);' : ''">
                        <div v-if="selectedBundleId === bundle.id"
                             class="absolute top-3 right-3 w-6 h-6 rounded-full flex items-center justify-center text-white"
                             style="background-color: #0d9488;">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                        </div>
                        <div v-if="bundle.image_url" class="mb-3">
                            <img :src="bundle.image_url" :alt="bundle.name_en" class="w-full h-24 object-cover rounded-lg" />
                        </div>
                        <h4 class="font-semibold text-gray-900 mb-1">{{ bundle.name_en }}</h4>
                        <p v-if="bundle.name_ar" class="text-xs text-gray-400 mb-2" dir="rtl">{{ bundle.name_ar }}</p>
                        <div class="flex items-center gap-2 mb-2">
                            <span class="text-lg font-bold" style="color: #0d9488;">{{ formatCurrency(bundle.total_price) }}</span>
                            <span v-if="Number(bundle.original_price) > Number(bundle.total_price)"
                                  class="text-sm text-gray-400 line-through">{{ formatCurrency(bundle.original_price) }}</span>
                        </div>
                        <div class="text-xs text-gray-500">
                            {{ bundle.services?.length || 0 }} services included
                        </div>
                    </button>
                </div>
                <p v-if="errors.package_bundle_id" class="text-red-500 text-xs mt-2">{{ errors.package_bundle_id }}</p>

                <div v-if="selectedBundle" class="mt-6 border-t border-gray-200 pt-4">
                    <h4 class="text-sm font-semibold text-gray-700 mb-3">{{ isRtl ? 'الخدمات المضمنة' : 'Included Services' }}</h4>
                    <div class="space-y-2">
                        <div v-for="bs in bundleServices" :key="bs.id"
                             class="flex items-center justify-between p-3 bg-gray-50 rounded-lg text-sm">
                            <div>
                                <span class="font-medium text-gray-800">{{ bs.service?.name_en }}</span>
                                <span class="text-gray-400 ltr:ml-2 rtl:mr-2">({{ bs.sessions_count }} {{ bs.sessions_count === 1 ? 'session' : 'sessions' }})</span>
                            </div>
                            <div class="ltr:text-right rtl:text-left">
                                <span class="font-semibold" style="color: #0d9488;">{{ formatCurrency(bs.bundle_price) }}</span>
                                <span v-if="Number(bs.discount_percentage) > 0" class="text-xs text-green-600 ltr:ml-2 rtl:mr-2">-{{ bs.discount_percentage }}%</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Step 2: Assign Doctors per Service -->
        <div v-show="currentStep === 2" class="space-y-6">
            <div class="bg-white rounded-lg shadow-sm p-4 sm:p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-2">{{ isRtl ? 'تعيين الأطباء' : 'Assign Doctors' }}</h3>
                <p class="text-sm text-gray-500 mb-6">{{ isRtl ? 'عيّن طبيباً لكل خدمة في الباقة.' : 'Assign a doctor for each service in the bundle.' }}</p>

                <div class="space-y-4">
                    <div v-for="(sa, index) in serviceAssignments" :key="sa.package_bundle_service_id"
                         class="flex flex-col sm:flex-row sm:items-center gap-4 p-4 border border-gray-200 rounded-xl hover:border-gray-300 transition">
                        <div class="flex-1 min-w-0">
                            <div class="font-medium text-gray-800">{{ sa.service_name }}</div>
                            <div class="flex items-center gap-3 mt-1 text-xs text-gray-500">
                                <span>{{ sa.sessions_count }} {{ sa.sessions_count === 1 ? 'session' : 'sessions' }}</span>
                                <span>{{ formatCurrency(sa.bundle_price) }}</span>
                                <span v-if="Number(sa.discount_percentage) > 0" class="text-green-600">-{{ sa.discount_percentage }}% off</span>
                            </div>
                        </div>
                        <div class="w-full sm:w-64">
                            <select v-model="sa.doctor_id"
                                    class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-teal-200 focus:border-transparent"
                                    :class="!sa.doctor_id ? 'text-gray-400' : 'text-gray-800'">
                                <option :value="null" disabled>{{ isRtl ? 'اختر الطبيب' : 'Select Doctor' }}</option>
                                <option v-for="doc in doctors" :key="doc.id" :value="doc.id">
                                    {{ doc.name_en }}
                                </option>
                            </select>
                            <p v-if="errors[`services.${index}.doctor_id`]" class="text-red-500 text-xs mt-1">
                                {{ errors[`services.${index}.doctor_id`] }}
                            </p>
                        </div>
                    </div>
                </div>
                <p v-if="errors.services" class="text-red-500 text-xs mt-2">{{ errors.services }}</p>
            </div>
        </div>

        <!-- Step 3: Schedule Appointments -->
        <div v-show="currentStep === 3" class="space-y-6">
            <div v-for="(sa, sIndex) in serviceAssignments" :key="sa.package_bundle_service_id"
                 class="bg-white rounded-lg shadow-sm p-4 sm:p-6">
                <div class="flex items-center gap-3 mb-4 pb-3 border-b border-gray-200">
                    <div class="w-8 h-8 rounded-lg flex items-center justify-center text-white text-sm font-bold" style="background-color: #0d9488;">
                        {{ sIndex + 1 }}
                    </div>
                    <div>
                        <h3 class="font-semibold text-gray-800">{{ sa.service_name }}</h3>
                        <p class="text-xs text-gray-500">
                            {{ sa.sessions_count }} {{ sa.sessions_count === 1 ? 'session' : 'sessions' }}
                            &middot; Dr. {{ doctors.find(d => d.id === sa.doctor_id)?.name_en }}
                        </p>
                    </div>
                </div>

                <div class="space-y-4">
                    <div v-for="(apt, aIndex) in (appointmentData[sIndex] || [])" :key="aIndex"
                         class="p-4 border border-gray-200 rounded-xl"
                         :class="apt.start_time ? 'border-teal-300/50 bg-teal-50/30' : ''">
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-sm font-semibold text-gray-700">{{ isRtl ? 'جلسة' : 'Session' }} {{ aIndex + 1 }}</span>
                            <span v-if="apt.start_time && apt.date" class="text-xs font-medium px-2.5 py-1 rounded-full text-white" style="background-color: #0d9488;">
                                {{ apt.date }} | {{ apt.start_time }} - {{ apt.end_time }}
                            </span>
                        </div>

                        <!-- Doctor override per session -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-3">
                            <div>
                                <label class="block text-xs font-medium text-gray-500 mb-1">{{ isRtl ? 'الطبيب' : 'Doctor' }}</label>
                                <select v-model="apt.doctor_id"
                                        @change="onAppointmentDoctorChange(sIndex, aIndex)"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-teal-200 focus:border-transparent">
                                    <option v-for="doc in doctors" :key="doc.id" :value="doc.id">{{ doc.name_en }}</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-500 mb-1">{{ isRtl ? 'التاريخ' : 'Date' }}</label>
                                <DoctorDatePicker
                                    v-model="apt.date"
                                    :doctor-id="apt.doctor_id"
                                    :doctor-schedules="doctorSchedules"
                                    :min-date="new Date().toISOString().split('T')[0]"
                                    accent-color="#0d9488"
                                    popover
                                    @update:model-value="onAppointmentDateChange(sIndex, aIndex)"
                                />
                            </div>
                        </div>

                        <!-- Schedule warning -->
                        <div v-if="apt.scheduleWarning" class="mb-3 p-2 bg-amber-50 border border-amber-200 rounded-lg text-xs text-amber-700">
                            {{ apt.scheduleWarning }}
                        </div>

                        <!-- Loading -->
                        <div v-if="apt.loadingSlots" class="flex items-center justify-center py-4 text-sm text-gray-500">
                            <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                            </svg>
                            Loading available slots...
                        </div>

                        <!-- Available time slots -->
                        <div v-else-if="apt.date && apt.availableSlots.length > 0" class="mt-2">
                            <label class="block text-xs font-medium text-gray-500 mb-2">{{ isRtl ? 'المواعيد المتاحة' : 'Available Time Slots' }}</label>
                            <div class="flex flex-wrap gap-2">
                                <button v-for="slot in apt.availableSlots" :key="slot.start"
                                        @click="selectSlot(sIndex, aIndex, slot)"
                                        type="button"
                                        class="px-3 py-1.5 text-xs rounded-lg border transition-all duration-150"
                                        :class="apt.start_time === slot.start
                                            ? 'text-white border-transparent shadow-sm'
                                            : 'border-gray-200 text-gray-600 hover:border-gray-300 hover:bg-gray-50'"
                                        :style="apt.start_time === slot.start ? 'background-color: #0d9488;' : ''">
                                    {{ slot.start_12h || slot.start }}
                                </button>
                            </div>
                        </div>

                        <!-- No slots message -->
                        <div v-else-if="apt.date && !apt.loadingSlots && !apt.scheduleWarning"
                             class="mt-2 text-center py-3 text-xs text-gray-400 bg-gray-50 rounded-lg">
                            No available slots for this date
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Step 4: Review & Confirm -->
        <div v-show="currentStep === 4" class="space-y-6">
            <div class="bg-white rounded-lg shadow-sm p-4 sm:p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-6">{{ isRtl ? 'مراجعة وتأكيد' : 'Review & Confirm' }}</h3>

                <!-- Patient Summary -->
                <div class="mb-6 p-4 bg-gray-50 rounded-xl">
                    <h4 class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">{{ isRtl ? 'المريض' : 'Patient' }}</h4>
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full flex items-center justify-center text-white text-sm font-semibold" style="background-color: #0d9488;">
                            {{ selectedPatient?.full_name?.charAt(0) }}
                        </div>
                        <div>
                            <div class="font-medium text-gray-900">{{ selectedPatient?.full_name }}</div>
                            <div class="text-xs text-gray-500">{{ selectedPatient?.phone }}</div>
                        </div>
                    </div>
                </div>

                <!-- Bundle Summary -->
                <div class="mb-6 p-4 bg-gray-50 rounded-xl">
                    <h4 class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">{{ isRtl ? 'الباقة' : 'Bundle' }}</h4>
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="font-semibold text-gray-900">{{ selectedBundle?.name_en }}</div>
                            <div class="text-xs text-gray-500 mt-1">{{ bundleServices.length }} services</div>
                        </div>
                        <div class="ltr:text-right rtl:text-left">
                            <div class="text-lg font-bold" style="color: #0d9488;">{{ formatCurrency(selectedBundle?.total_price) }}</div>
                            <div v-if="Number(selectedBundle?.savings) > 0" class="text-xs text-green-600">{{ isRtl ? 'توفر' : 'You save' }} {{ formatCurrency(selectedBundle?.savings) }}</div>
                        </div>
                    </div>
                </div>

                <!-- Services, Doctors & Appointments Summary -->
                <div class="mb-6">
                    <h4 class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-3">{{ isRtl ? 'جدول المواعيد' : 'Appointments Schedule' }}</h4>
                    <div class="space-y-4">
                        <div v-for="(sa, sIndex) in serviceAssignments" :key="sa.package_bundle_service_id"
                             class="border border-gray-200 rounded-xl overflow-hidden">
                            <div class="px-4 py-3 bg-gray-50 flex items-center justify-between">
                                <div>
                                    <span class="font-medium text-gray-800 text-sm">{{ sa.service_name }}</span>
                                    <span class="text-gray-400 text-xs ltr:ml-2 rtl:mr-2">({{ sa.sessions_count }} sessions)</span>
                                </div>
                                <span class="font-semibold text-sm" style="color: #0d9488;">{{ formatCurrency(sa.bundle_price) }}</span>
                            </div>
                            <div class="divide-y divide-gray-100">
                                <div v-for="(apt, aIndex) in (appointmentData[sIndex] || [])" :key="aIndex"
                                     class="px-4 py-2.5 flex items-center justify-between text-sm">
                                    <div class="flex items-center gap-3">
                                        <span class="text-xs font-medium text-gray-400 w-16">{{ isRtl ? 'جلسة' : 'Session' }} {{ aIndex + 1 }}</span>
                                        <span class="text-gray-800">{{ apt.date }}</span>
                                    </div>
                                    <div class="flex items-center gap-3">
                                        <span class="text-gray-600">{{ apt.start_time }} - {{ apt.end_time }}</span>
                                        <span class="text-xs text-gray-400">Dr. {{ doctors.find(d => d.id === apt.doctor_id)?.name_en }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Notes -->
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ isRtl ? 'ملاحظات (اختياري)' : 'Notes (optional)' }}</label>
                    <textarea v-model="notes" rows="3" :placeholder="isRtl ? 'ملاحظات إضافية...' : 'Any additional notes...'"
                              class="w-full px-4 py-3 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-teal-200 focus:border-transparent resize-none"></textarea>
                    <p v-if="errors.notes" class="text-red-500 text-xs mt-1">{{ errors.notes }}</p>
                </div>

                <!-- Promo Code -->
                <div class="mb-4">
                    <PromoCodeInput
                        v-model="promoCode"
                        booking-type="package"
                        :package-bundle-id="selectedBundleId"
                        :amount="Number(selectedBundle?.total_price) || 0"
                    />
                </div>

                <!-- General Errors -->
                <div v-if="Object.keys(errors).length > 0" class="bg-red-50 border border-red-200 rounded-lg p-4 text-sm text-red-700">
                    <p class="font-semibold mb-1">{{ isRtl ? 'يرجى إصلاح الأخطاء التالية:' : 'Please fix the following errors:' }}</p>
                    <ul class="list-disc list-inside space-y-1">
                        <li v-for="(err, key) in errors" :key="key">{{ err }}</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Navigation Buttons -->
        <div class="bg-white rounded-lg shadow-sm p-4 flex items-center justify-between">
            <button v-if="currentStep > 1"
                    @click="prevStep"
                    class="inline-flex items-center px-4 py-2.5 rounded-lg text-sm font-medium text-gray-600 bg-gray-100 hover:bg-gray-200 transition">
                <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
                Previous
            </button>
            <div v-else></div>

            <button v-if="currentStep < totalSteps"
                    @click="nextStep"
                    :disabled="!canGoToStep(currentStep + 1)"
                    class="inline-flex items-center px-6 py-2.5 rounded-lg text-sm font-medium text-white transition disabled:opacity-40 disabled:cursor-not-allowed"
                    style="background-color: #0d9488;">
                Next
                <svg class="w-4 h-4 ltr:ml-2 rtl:mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </button>

            <button v-else
                    @click="submit"
                    :disabled="processing || !canGoToStep(4)"
                    class="inline-flex items-center px-6 py-2.5 rounded-lg text-sm font-medium text-white transition disabled:opacity-40 disabled:cursor-not-allowed"
                    style="background-color: #0d9488;">
                <svg v-if="processing" class="w-4 h-4 ltr:mr-2 rtl:ml-2 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                </svg>
                <svg v-else class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                {{ processing ? 'Creating...' : 'Create Booking' }}
            </button>
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
