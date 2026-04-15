<script setup>
import FrontendLayout from '@/Layouts/FrontendLayout.vue';
import { useForm } from '@inertiajs/vue3';
import { useLocale } from '@/Composables/useLocale';
import { useSettings } from '@/Composables/useSettings';
import { toEnglishNumbers } from '@/Composables/useArabicNumbers';
import { usePage, Link } from '@inertiajs/vue3';
import { computed, ref, watch, onMounted, onUnmounted } from 'vue';
import SeoHead from '@/Components/Frontend/SeoHead.vue';
import PageHero from '@/Components/Frontend/PageHero.vue';
import DoctorDatePicker from '@/Components/DoctorDatePicker.vue';
import PromoCodeInput from '@/Components/PromoCodeInput.vue';
import axios from 'axios';

const { t, localized, locale, isRtl, localizedRoute } = useLocale();
const { whatsappLink, phone1, phone2, email, whatsapp } = useSettings();
const page = usePage();

const seoTitle = computed(() => localized(props.seo, 'title'));
const seoDescription = computed(() => localized(props.seo, 'description'));

const props = defineProps({
    serviceCategories: Array,
    services: Array,
    doctors: Array,
    doctorSchedules: Array,
    seo: Object,
    modules: Object,
});

const flash = computed(() => page.props.flash || {});

// Active modules list
const activeModules = computed(() => {
    if (!props.modules) return [];
    return Object.values(props.modules).filter(m => m.enabled);
});

const hasMultipleModules = computed(() => activeModules.value.length > 1);

// Selected department/module
const selectedModule = ref('');

const countryCodes = [
    { code: '+20', flag: '🇪🇬', name: 'Egypt', nameAr: 'مصر' },
    { code: '+966', flag: '🇸🇦', name: 'Saudi Arabia', nameAr: 'السعودية' },
    { code: '+971', flag: '🇦🇪', name: 'UAE', nameAr: 'الإمارات' },
    { code: '+965', flag: '🇰🇼', name: 'Kuwait', nameAr: 'الكويت' },
    { code: '+974', flag: '🇶🇦', name: 'Qatar', nameAr: 'قطر' },
    { code: '+973', flag: '🇧🇭', name: 'Bahrain', nameAr: 'البحرين' },
    { code: '+968', flag: '🇴🇲', name: 'Oman', nameAr: 'عُمان' },
    { code: '+962', flag: '🇯🇴', name: 'Jordan', nameAr: 'الأردن' },
    { code: '+961', flag: '🇱🇧', name: 'Lebanon', nameAr: 'لبنان' },
    { code: '+964', flag: '🇮🇶', name: 'Iraq', nameAr: 'العراق' },
    { code: '+218', flag: '🇱🇾', name: 'Libya', nameAr: 'ليبيا' },
    { code: '+216', flag: '🇹🇳', name: 'Tunisia', nameAr: 'تونس' },
    { code: '+213', flag: '🇩🇿', name: 'Algeria', nameAr: 'الجزائر' },
    { code: '+212', flag: '🇲🇦', name: 'Morocco', nameAr: 'المغرب' },
    { code: '+249', flag: '🇸🇩', name: 'Sudan', nameAr: 'السودان' },
    { code: '+967', flag: '🇾🇪', name: 'Yemen', nameAr: 'اليمن' },
    { code: '+1', flag: '🇺🇸', name: 'USA', nameAr: 'أمريكا' },
    { code: '+44', flag: '🇬🇧', name: 'UK', nameAr: 'بريطانيا' },
    { code: '+49', flag: '🇩🇪', name: 'Germany', nameAr: 'ألمانيا' },
    { code: '+33', flag: '🇫🇷', name: 'France', nameAr: 'فرنسا' },
    { code: '+39', flag: '🇮🇹', name: 'Italy', nameAr: 'إيطاليا' },
    { code: '+90', flag: '🇹🇷', name: 'Turkey', nameAr: 'تركيا' },
];

const selectedCountryCode = ref('+20');
const phoneNumber = ref('');
const showCountryDropdown = ref(false);
const countrySearch = ref('');

const filteredCountries = computed(() => {
    if (!countrySearch.value) return countryCodes;
    const q = countrySearch.value.toLowerCase();
    return countryCodes.filter(c =>
        c.name.toLowerCase().includes(q) || c.nameAr.includes(q) || c.code.includes(q)
    );
});

const selectedCountry = computed(() => countryCodes.find(c => c.code === selectedCountryCode.value) || countryCodes[0]);

function selectCountry(c) {
    selectedCountryCode.value = c.code;
    showCountryDropdown.value = false;
    countrySearch.value = '';
    syncPhone();
}

function syncPhone() {
    phoneNumber.value = toEnglishNumbers(phoneNumber.value).replace(/[^\d]/g, '');
    form.phone = selectedCountryCode.value + phoneNumber.value;
}

// Close country dropdown on outside click
function handleCountryClickOutside(e) {
    if (!e.target.closest('.loc-country-dd')) {
        showCountryDropdown.value = false;
        countrySearch.value = '';
    }
}

const form = useForm({
    full_name: '',
    phone: '',
    email: '',
    module: '',
    booking_type: '',
    service_id: '',
    doctor_id: '',
    preferred_date: '',
    preferred_time: '',
    notes: '',
    promo_code: '',
    privacy_consent: false,
    _honeypot: '',
});

// Selected category filter for service dropdown
const selectedCategoryId = ref('');

// Filter services/doctors/categories by selected module
const filteredServiceCategories = computed(() => {
    if (!selectedModule.value) return props.serviceCategories || [];
    return (props.serviceCategories || []).filter(c => c.module === selectedModule.value)
        .map(c => ({
            ...c,
            services: (c.services || []).filter(s => s.module === selectedModule.value),
        }))
        .filter(c => c.services.length > 0);
});

const moduleServices = computed(() => {
    if (!selectedModule.value) return props.services || [];
    return (props.services || []).filter(s => s.module === selectedModule.value);
});

const moduleDoctors = computed(() => {
    if (!selectedModule.value) return props.doctors || [];
    return (props.doctors || []).filter(d => d.module === selectedModule.value);
});

// Booking types per module
const bookingTypes = computed(() => {
    if (selectedModule.value === 'dental') {
        return [
            { key: 'dental_consultation', icon: 'M4.26 10.147a60.436 60.436 0 00-.491 6.347A48.627 48.627 0 0112 20.904a48.627 48.627 0 018.232-4.41 60.46 60.46 0 00-.491-6.347m-15.482 0a50.57 50.57 0 00-2.658-.813A59.905 59.905 0 0112 3.493a59.902 59.902 0 0110.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.697 50.697 0 0112 13.489a50.702 50.702 0 017.74-3.342', color: 'cyan' },
            { key: 'dental_service', icon: 'M11.42 15.17l-5.658-5.66A4.022 4.022 0 013 6.476V5a1 1 0 011-1h2.476c1.064 0 2.084.423 2.836 1.176l5.658 5.66M11.42 15.17l2.496-2.496M11.42 15.17l4.243 4.243a2 2 0 002.828 0l.586-.586a2 2 0 000-2.828L14.83 11.758M14.83 11.758L18 8.586a2 2 0 000-2.828l-.586-.586a2 2 0 00-2.828 0L11.42 8.414', color: 'teal' },
        ];
    }
    if (selectedModule.value === 'pediatric') {
        return [
            { key: 'pediatric_consultation', icon: 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z', color: 'green' },
            { key: 'pediatric_service', icon: 'M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z', color: 'lime' },
        ];
    }
    // Default: derma
    return [
        { key: 'dermatology_consultation', icon: 'M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z', color: 'blue' },
        { key: 'cosmetic_consultation', icon: 'M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z', color: 'pink' },
        { key: 'service', icon: 'M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z', color: 'emerald' },
    ];
});

function selectDepartment(slug) {
    selectedModule.value = slug;
    form.module = slug;
    // Reset form fields when switching department
    form.booking_type = '';
    form.service_id = '';
    form.doctor_id = '';
    form.preferred_date = '';
    form.preferred_time = '';
    selectedCategoryId.value = '';
    availableSlots.value = [];
    slotsError.value = '';
}

const today = computed(() => {
    const d = new Date();
    return d.toISOString().split('T')[0];
});

// Booking type helpers
const isConsultation = computed(() =>
    ['dermatology_consultation', 'cosmetic_consultation', 'dental_consultation', 'pediatric_consultation'].includes(form.booking_type)
);

const isService = computed(() => ['service', 'dental_service', 'pediatric_service'].includes(form.booking_type));

// Filtered services based on selected category + module
const filteredServices = computed(() => {
    const base = moduleServices.value;
    if (!selectedCategoryId.value) {
        return base;
    }
    return base.filter(s => s.category_id == selectedCategoryId.value);
});

// Dynamic time slots from API
const availableSlots = ref([]);
const loadingSlots = ref(false);
const slotsError = ref('');

// Convert JS day (0=Sunday) to system day (0=Saturday)
function jsToSystemDay(jsDay) {
    const map = { 0: 1, 1: 2, 2: 3, 3: 4, 4: 5, 5: 6, 6: 0 };
    return map[jsDay];
}

// Check if doctor works on a specific date
function isDoctorAvailable(doctorId, dateStr) {
    if (!doctorId || !dateStr || !props.doctorSchedules) return true;
    const date = new Date(dateStr);
    const systemDay = jsToSystemDay(date.getDay());
    return props.doctorSchedules.some(s => s.doctor_id == doctorId && s.day_of_week == systemDay);
}

// Get selected service's duration
const selectedServiceDuration = computed(() => {
    if (!form.service_id) return 30;
    const service = moduleServices.value.find(s => s.id == form.service_id);
    return service?.session_duration_minutes || 30;
});

// Clear service when switching booking type
watch(() => form.booking_type, (newType) => {
    if (!['service', 'dental_service'].includes(newType)) {
        form.service_id = '';
        selectedCategoryId.value = '';
    }
    form.doctor_id = '';
    form.preferred_date = '';
    form.preferred_time = '';
    availableSlots.value = [];
    slotsError.value = '';
});

// Fetch available slots when doctor + date change
watch([() => form.doctor_id, () => form.preferred_date], async ([doctorId, date]) => {
    form.preferred_time = '';
    availableSlots.value = [];
    slotsError.value = '';

    if (!doctorId || !date) return;

    if (!isDoctorAvailable(doctorId, date)) {
        slotsError.value = t('doctor_not_available_on_date');
        return;
    }

    loadingSlots.value = true;
    try {
        const res = await axios.get('/api/time-slots', {
            params: { doctor_id: doctorId, date: date, duration: selectedServiceDuration.value },
        });
        availableSlots.value = res.data.slots || [];
        if (availableSlots.value.length === 0) {
            slotsError.value = t('no_slots_available');
        }
    } catch (e) {
        slotsError.value = t('error_loading_slots');
    } finally {
        loadingSlots.value = false;
    }
});

function selectTimeSlot(slot) {
    form.preferred_time = slot.start;
}

function formatTime12h(time24) {
    if (!time24) return '';
    const [h, m] = time24.split(':');
    const hour = parseInt(h);
    const period = hour >= 12 ? 'PM' : 'AM';
    const hour12 = hour === 0 ? 12 : hour > 12 ? hour - 12 : hour;
    return `${hour12}:${m} ${period}`;
}

const emailError = ref('');

// Service searchable dropdown
const showServiceDropdown = ref(false);
const serviceSearch = ref('');
const selectedServiceName = computed(() => {
    if (!form.service_id) return '';
    const s = moduleServices.value.find(s => s.id == form.service_id);
    return s ? localized(s, 'name') : '';
});
const searchedServices = computed(() => {
    const base = filteredServices.value;
    if (!serviceSearch.value) return base;
    const q = serviceSearch.value.toLowerCase();
    return base.filter(s =>
        (s.name_ar && s.name_ar.includes(serviceSearch.value)) ||
        (s.name_en && s.name_en.toLowerCase().includes(q))
    );
});
function selectService(s) {
    form.service_id = s.id;
    showServiceDropdown.value = false;
    serviceSearch.value = '';
}
function handleServiceClickOutside(e) {
    if (!e.target.closest('.loc-service-dd')) {
        showServiceDropdown.value = false;
        serviceSearch.value = '';
    }
}

// Doctor searchable dropdown
const showDoctorDropdown = ref(false);
const doctorSearch = ref('');
const selectedDoctorName = computed(() => {
    if (!form.doctor_id) return '';
    const d = moduleDoctors.value.find(d => d.id == form.doctor_id);
    return d ? localized(d, 'name') : '';
});
const searchedDoctors = computed(() => {
    const base = moduleDoctors.value;
    if (!doctorSearch.value) return base;
    const q = doctorSearch.value.toLowerCase();
    return base.filter(d =>
        (d.name_ar && d.name_ar.includes(doctorSearch.value)) ||
        (d.name_en && d.name_en.toLowerCase().includes(q))
    );
});
function selectDoctor(d) {
    form.doctor_id = d.id;
    showDoctorDropdown.value = false;
    doctorSearch.value = '';
}
function handleDoctorClickOutside(e) {
    if (!e.target.closest('.loc-doctor-dd')) {
        showDoctorDropdown.value = false;
        doctorSearch.value = '';
    }
}

function validateEmail() {
    if (!form.email) {
        emailError.value = '';
        return;
    }
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    emailError.value = emailRegex.test(form.email)
        ? ''
        : (locale.value === 'ar' ? 'يرجى إدخال بريد إلكتروني صحيح' : 'Please enter a valid email address');
}

function normalizePhone() {
    syncPhone();
}

function selectBookingType(type) {
    form.booking_type = type;
}

onMounted(() => {
    document.addEventListener('click', handleCountryClickOutside);
    document.addEventListener('click', handleServiceClickOutside);
    document.addEventListener('click', handleDoctorClickOutside);
});
onUnmounted(() => {
    document.removeEventListener('click', handleCountryClickOutside);
    document.removeEventListener('click', handleServiceClickOutside);
    document.removeEventListener('click', handleDoctorClickOutside);
});

// Auto-select module if only one is active
if (activeModules.value.length === 1) {
    selectDepartment(activeModules.value[0].slug);
}

function submit() {
    validateEmail();
    if (emailError.value) return;

    form.post(localizedRoute('/booking'), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            availableSlots.value = [];
            selectedCategoryId.value = '';
            // Re-select if single module
            if (activeModules.value.length === 1) {
                selectDepartment(activeModules.value[0].slug);
            } else {
                selectedModule.value = '';
            }
        },
    });
}
</script>

<template>
    <FrontendLayout :title="t('book_appointment')">
        <SeoHead
            :title="seoTitle"
            :description="seoDescription"
            :keywords="seo?.keywords"
            :image="seo?.image"
        />

        <PageHero :title="isRtl ? 'احجز موعدك' : 'Book Appointment'" :subtitle="isRtl ? 'احجز موعدك الآن واستمتع برعاية صحية متميزة' : 'Book your appointment and enjoy outstanding healthcare'" :breadcrumb="isRtl ? 'الحجز' : 'Booking'" />

        <!-- Booking Form Section -->
        <section class="relative py-16 lg:py-24 bg-[#FDF8F0] overflow-hidden">
            <!-- Subtle dot pattern -->
            <div class="absolute inset-0 pointer-events-none texture-dots-lg"></div>
            <!-- Floating decorative elements -->
            <div class="absolute top-16 start-6 w-24 h-24 rounded-full bg-gold-primary/5 animate-float"></div>
            <div class="absolute bottom-24 end-8 w-18 h-18 rounded-full bg-gold-primary/8 animate-float-slow"></div>
            <div class="absolute top-1/2 start-1/3 w-12 h-12 rounded-full bg-gold-light/5 animate-float-delay"></div>
            <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

                <!-- Success Flash Message -->
                <Transition
                    enter-active-class="transition-all duration-500 ease-out"
                    enter-from-class="opacity-0 -translate-y-4"
                    enter-to-class="opacity-100 translate-y-0"
                    leave-active-class="transition-all duration-300 ease-in"
                    leave-from-class="opacity-100 translate-y-0"
                    leave-to-class="opacity-0 -translate-y-4"
                >
                    <div
                        v-if="flash.success"
                        class="mb-8 p-4 bg-green-50 border border-green-200 rounded-xl flex items-center gap-3"
                    >
                        <div class="flex-shrink-0 w-10 h-10 bg-green-100 rounded-full flex items-center justify-center">
                            <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                        </div>
                        <p class="text-green-800 font-medium">{{ flash.success }}</p>
                    </div>
                </Transition>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 lg:gap-12">

                    <!-- Booking Form (Left 2/3) -->
                    <div class="lg:col-span-2" v-scroll-reveal="{ type: 'fade-up' }">
                        <div class="bg-white rounded-2xl shadow-lg p-6 sm:p-8 lg:p-10 border border-gray-100 card-hover-lift">
                            <h2 class="text-2xl font-bold text-[#3A3A3A] mb-2">
                                {{ t('fill_booking_form') }}
                            </h2>
                            <p class="text-gray-500 mb-8">
                                {{ t('booking_form_description') }}
                            </p>

                            <form @submit.prevent="submit" class="space-y-6">

                                <!-- Honeypot (hidden spam prevention) -->
                                <div class="absolute opacity-0 -z-10 h-0 w-0 overflow-hidden" aria-hidden="true" tabindex="-1">
                                    <label for="website_url">Website</label>
                                    <input
                                        id="website_url"
                                        type="text"
                                        name="website_url"
                                        v-model="form._honeypot"
                                        autocomplete="off"
                                        tabindex="-1"
                                    />
                                </div>

                                <!-- Department Selector (only when multiple modules) -->
                                <div v-if="hasMultipleModules">
                                    <label class="block text-sm font-semibold text-[#3A3A3A] mb-3">
                                        {{ t('select_department') }} <span class="text-red-500">*</span>
                                    </label>
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                        <button
                                            v-for="mod in activeModules"
                                            :key="mod.slug"
                                            type="button"
                                            @click="selectDepartment(mod.slug)"
                                            class="relative p-5 rounded-xl border-2 transition-all duration-300 text-start"
                                            :class="selectedModule === mod.slug
                                                ? 'shadow-lg'
                                                : 'border-gray-200 bg-white hover:shadow-sm'"
                                            :style="selectedModule === mod.slug
                                                ? { borderColor: mod.color, backgroundColor: mod.color + '08', boxShadow: '0 4px 14px ' + mod.color + '20' }
                                                : {}"
                                        >
                                            <div class="flex items-center gap-4">
                                                <div
                                                    class="w-14 h-14 rounded-xl flex items-center justify-center flex-shrink-0 text-2xl transition-all duration-300"
                                                    :style="selectedModule === mod.slug
                                                        ? { backgroundColor: mod.color, color: '#fff' }
                                                        : { backgroundColor: mod.color + '15', color: mod.color }"
                                                >
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="mod.icon" /></svg>
                                                </div>
                                                <div>
                                                    <p class="font-bold text-[#3A3A3A] text-base">{{ locale === 'ar' ? mod.name_ar : mod.name_en }}</p>
                                                    <p class="text-xs text-gray-500 mt-0.5">{{ t('department_' + mod.slug + '_desc') }}</p>
                                                </div>
                                            </div>
                                            <div v-if="selectedModule === mod.slug" class="absolute top-3 end-3">
                                                <svg class="w-6 h-6" :style="{ color: mod.color }" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                                </svg>
                                            </div>
                                        </button>
                                    </div>
                                </div>

                                <!-- Booking Type Selector (shows after department or auto for single module) -->
                                <Transition
                                    enter-active-class="transition-all duration-400 ease-out"
                                    enter-from-class="opacity-0 translate-y-4"
                                    enter-to-class="opacity-100 translate-y-0"
                                >
                                <div v-if="selectedModule">
                                    <label class="block text-sm font-semibold text-[#3A3A3A] mb-3">
                                        {{ t('booking_type') }} <span class="text-red-500">*</span>
                                    </label>
                                    <div class="grid gap-3" :class="bookingTypes.length <= 2 ? 'grid-cols-1 sm:grid-cols-2' : 'grid-cols-1 sm:grid-cols-3'">
                                        <button
                                            v-for="bt in bookingTypes"
                                            :key="bt.key"
                                            type="button"
                                            @click="selectBookingType(bt.key)"
                                            class="relative p-4 rounded-xl border-2 transition-all duration-300 text-start"
                                            :class="form.booking_type === bt.key
                                                ? 'border-[var(--brand-primary)] bg-[var(--brand-primary)]/5 shadow-md shadow-[var(--brand-primary)]/10'
                                                : 'border-gray-200 bg-white hover:border-[var(--brand-primary)]/50 hover:shadow-sm'"
                                        >
                                            <div class="flex items-start gap-3">
                                                <div
                                                    class="w-10 h-10 rounded-lg flex items-center justify-center flex-shrink-0 text-lg transition-colors"
                                                    :class="form.booking_type === bt.key ? 'bg-[var(--brand-primary)] text-white' : 'bg-gray-50 text-gray-500'"
                                                >
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="bt.icon" /></svg>
                                                </div>
                                                <div>
                                                    <p class="font-semibold text-[#3A3A3A] text-sm">{{ t(bt.key) }}</p>
                                                    <p class="text-xs text-gray-500 mt-0.5">{{ t(bt.key + '_desc') }}</p>
                                                </div>
                                            </div>
                                            <div v-if="form.booking_type === bt.key" class="absolute top-2 end-2">
                                                <svg class="w-5 h-5 text-[var(--brand-primary)]" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                                </svg>
                                            </div>
                                        </button>
                                    </div>
                                    <p v-if="form.errors.booking_type" class="mt-1.5 text-sm text-red-500">
                                        {{ form.errors.booking_type }}
                                    </p>
                                </div>
                                </Transition>

                                <!-- Rest of form only shows after booking type selection -->
                                <Transition
                                    enter-active-class="transition-all duration-400 ease-out"
                                    enter-from-class="opacity-0 translate-y-4"
                                    enter-to-class="opacity-100 translate-y-0"
                                >
                                <div v-if="form.booking_type" class="space-y-6">

                                    <!-- Row: Full Name + Phone -->
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                                        <!-- Full Name -->
                                        <div>
                                            <label for="full_name" class="block text-sm font-semibold text-[#3A3A3A] mb-2">
                                                {{ t('full_name') }} <span class="text-red-500">*</span>
                                            </label>
                                            <input
                                                id="full_name"
                                                v-model="form.full_name"
                                                type="text"
                                                :placeholder="t('full_name_placeholder')"
                                                class="w-full px-4 py-3 border border-gray-300 rounded-lg bg-white text-[#3A3A3A] placeholder-gray-400 transition-all duration-200 focus:ring-2 focus:ring-[var(--brand-primary)]/30 focus:border-[var(--brand-primary)] outline-none form-input-animated"
                                                :class="{ 'border-red-400 focus:ring-red-300 focus:border-red-400': form.errors.full_name }"
                                                required
                                            />
                                            <p v-if="form.errors.full_name" class="mt-1.5 text-sm text-red-500">
                                                {{ form.errors.full_name }}
                                            </p>
                                        </div>

                                        <!-- Phone with Country Code -->
                                        <div>
                                            <label for="phone" class="block text-sm font-semibold text-[#3A3A3A] mb-2">
                                                {{ t('phone_number') }} <span class="text-red-500">*</span>
                                            </label>
                                            <div class="relative flex loc-country-dd" dir="ltr">
                                                <!-- Country Code Selector -->
                                                <button
                                                    type="button"
                                                    @click="showCountryDropdown = !showCountryDropdown"
                                                    class="flex items-center gap-1.5 px-3 py-3 border border-gray-300 rounded-s-lg bg-gray-50 hover:bg-gray-100 transition-colors flex-shrink-0"
                                                    :class="{ 'border-red-400': form.errors.phone, 'border-e-0': true }"
                                                >
                                                    <span class="text-lg leading-none">{{ selectedCountry.flag }}</span>
                                                    <span class="text-sm text-gray-700 font-medium">{{ selectedCountry.code }}</span>
                                                    <svg class="w-3.5 h-3.5 text-gray-400 transition-transform" :class="{ 'rotate-180': showCountryDropdown }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                                    </svg>
                                                </button>

                                                <!-- Phone Input -->
                                                <input
                                                    id="phone"
                                                    v-model="phoneNumber"
                                                    @input="normalizePhone"
                                                    type="tel"
                                                    :placeholder="t('phone_placeholder')"
                                                    class="w-full px-4 py-3 border border-gray-300 rounded-e-lg bg-white text-[#3A3A3A] placeholder-gray-400 transition-all duration-200 focus:ring-2 focus:ring-[var(--brand-primary)]/30 focus:border-[var(--brand-primary)] outline-none form-input-animated"
                                                    :class="{ 'border-red-400 focus:ring-red-300 focus:border-red-400': form.errors.phone }"
                                                    required
                                                />

                                                <!-- Country Dropdown -->
                                                <Transition
                                                    enter-active-class="transition duration-150 ease-out"
                                                    enter-from-class="opacity-0 -translate-y-1"
                                                    enter-to-class="opacity-100 translate-y-0"
                                                    leave-active-class="transition duration-100 ease-in"
                                                    leave-from-class="opacity-100 translate-y-0"
                                                    leave-to-class="opacity-0 -translate-y-1"
                                                >
                                                    <div v-if="showCountryDropdown" class="absolute top-full start-0 mt-1 w-72 bg-white border border-gray-200 rounded-xl shadow-xl z-50 overflow-hidden">
                                                        <!-- Search -->
                                                        <div class="p-2 border-b border-gray-100">
                                                            <input
                                                                v-model="countrySearch"
                                                                type="text"
                                                                :placeholder="locale === 'ar' ? 'ابحث عن دولة...' : 'Search country...'"
                                                                class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg bg-gray-50 outline-none focus:border-[var(--brand-primary)] focus:ring-1 focus:ring-[var(--brand-primary)]/30"
                                                            />
                                                        </div>
                                                        <!-- List -->
                                                        <div class="max-h-52 overflow-y-auto">
                                                            <button
                                                                v-for="c in filteredCountries"
                                                                :key="c.code"
                                                                type="button"
                                                                @click="selectCountry(c)"
                                                                class="w-full flex items-center gap-3 px-3 py-2.5 text-sm hover:bg-gray-50 transition-colors"
                                                                :class="{ 'bg-[var(--brand-primary)]/5 font-medium': c.code === selectedCountryCode }"
                                                            >
                                                                <span class="text-lg leading-none">{{ c.flag }}</span>
                                                                <span class="text-gray-800">{{ locale === 'ar' ? c.nameAr : c.name }}</span>
                                                                <span class="ms-auto text-gray-400 text-xs font-mono">{{ c.code }}</span>
                                                            </button>
                                                        </div>
                                                    </div>
                                                </Transition>
                                            </div>
                                            <p v-if="form.errors.phone" class="mt-1.5 text-sm text-red-500">
                                                {{ form.errors.phone }}
                                            </p>
                                        </div>
                                    </div>

                                    <!-- Email -->
                                    <div>
                                        <label for="email" class="block text-sm font-semibold text-[#3A3A3A] mb-2">
                                            {{ t('email') }}
                                            <span class="text-gray-400 font-normal">({{ t('optional') }})</span>
                                        </label>
                                        <input
                                            id="email"
                                            v-model="form.email"
                                            @blur="validateEmail"
                                            type="email"
                                            :placeholder="t('email_placeholder')"
                                            class="w-full px-4 py-3 border border-gray-300 rounded-lg bg-white text-[#3A3A3A] placeholder-gray-400 transition-all duration-200 focus:ring-2 focus:ring-[var(--brand-primary)]/30 focus:border-[var(--brand-primary)] outline-none form-input-animated"
                                            :class="{ 'border-red-400 focus:ring-red-300 focus:border-red-400': form.errors.email || emailError }"
                                        />
                                        <p v-if="emailError" class="mt-1.5 text-sm text-red-500">
                                            {{ emailError }}
                                        </p>
                                        <p v-else-if="form.errors.email" class="mt-1.5 text-sm text-red-500">
                                            {{ form.errors.email }}
                                        </p>
                                    </div>

                                    <!-- Service Selection (only for service booking type) -->
                                    <div v-if="isService" class="space-y-4">
                                        <!-- Category Filter Tabs -->
                                        <div v-if="filteredServiceCategories && filteredServiceCategories.length > 0">
                                            <label class="block text-sm font-semibold text-[#3A3A3A] mb-3">
                                                {{ t('select_service_category') }}
                                            </label>
                                            <div class="flex flex-wrap gap-2">
                                                <button
                                                    type="button"
                                                    @click="selectedCategoryId = ''"
                                                    class="px-4 py-2 rounded-lg text-sm font-medium border transition-all duration-200"
                                                    :class="!selectedCategoryId
                                                        ? 'bg-[var(--brand-primary)] text-white border-[var(--brand-primary)]'
                                                        : 'bg-white text-gray-600 border-gray-200 hover:border-[var(--brand-primary)] hover:text-[var(--brand-primary)]'"
                                                >
                                                    {{ t('all_services') }}
                                                </button>
                                                <button
                                                    v-for="cat in filteredServiceCategories"
                                                    :key="cat.id"
                                                    type="button"
                                                    @click="selectedCategoryId = cat.id"
                                                    class="px-4 py-2 rounded-lg text-sm font-medium border transition-all duration-200"
                                                    :class="selectedCategoryId === cat.id
                                                        ? 'bg-[var(--brand-primary)] text-white border-[var(--brand-primary)]'
                                                        : 'bg-white text-gray-600 border-gray-200 hover:border-[var(--brand-primary)] hover:text-[var(--brand-primary)]'"
                                                >
                                                    {{ localized(cat, 'name') }}
                                                </button>
                                            </div>
                                        </div>

                                        <!-- Service Searchable Dropdown -->
                                        <div class="relative loc-service-dd">
                                            <label class="block text-sm font-semibold text-[#3A3A3A] mb-2">
                                                {{ t('service') }} <span class="text-red-500">*</span>
                                            </label>
                                            <button
                                                type="button"
                                                @click="showServiceDropdown = !showServiceDropdown"
                                                class="w-full flex items-center justify-between px-4 py-3 border border-gray-300 rounded-lg bg-white text-start transition-all duration-200 focus:ring-2 focus:ring-[var(--brand-primary)]/30 focus:border-[var(--brand-primary)] outline-none"
                                                :class="{ 'border-red-400': form.errors.service_id, 'border-[var(--brand-primary)] ring-2 ring-[var(--brand-primary)]/30': showServiceDropdown }"
                                            >
                                                <span :class="selectedServiceName ? 'text-[#3A3A3A]' : 'text-gray-400'">
                                                    {{ selectedServiceName || t('select_service') }}
                                                </span>
                                                <svg class="w-4 h-4 text-gray-400 transition-transform flex-shrink-0" :class="{ 'rotate-180': showServiceDropdown }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                                </svg>
                                            </button>

                                            <Transition
                                                enter-active-class="transition duration-150 ease-out"
                                                enter-from-class="opacity-0 -translate-y-1"
                                                enter-to-class="opacity-100 translate-y-0"
                                                leave-active-class="transition duration-100 ease-in"
                                                leave-from-class="opacity-100 translate-y-0"
                                                leave-to-class="opacity-0 -translate-y-1"
                                            >
                                                <div v-if="showServiceDropdown" class="absolute top-full start-0 end-0 mt-1 bg-white border border-gray-200 rounded-xl shadow-xl z-50 overflow-hidden">
                                                    <!-- Search -->
                                                    <div class="p-2.5 border-b border-gray-100">
                                                        <div class="relative">
                                                            <svg class="absolute start-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                                            </svg>
                                                            <input
                                                                v-model="serviceSearch"
                                                                type="text"
                                                                :placeholder="locale === 'ar' ? 'ابحث عن خدمة...' : 'Search service...'"
                                                                class="w-full ps-9 pe-3 py-2.5 text-sm border border-gray-200 rounded-lg bg-gray-50 outline-none focus:border-[var(--brand-primary)] focus:ring-1 focus:ring-[var(--brand-primary)]/30"
                                                            />
                                                        </div>
                                                    </div>
                                                    <!-- List -->
                                                    <div class="max-h-60 overflow-y-auto">
                                                        <!-- Grouped by category when no category filter -->
                                                        <template v-if="!selectedCategoryId && !serviceSearch">
                                                            <template v-for="cat in filteredServiceCategories" :key="cat.id">
                                                                <div v-if="cat.services && cat.services.length > 0">
                                                                    <div class="px-3 py-1.5 text-xs font-bold text-gray-400 uppercase tracking-wider bg-gray-50 sticky top-0">
                                                                        {{ localized(cat, 'name') }}
                                                                    </div>
                                                                    <button
                                                                        v-for="service in cat.services"
                                                                        :key="service.id"
                                                                        type="button"
                                                                        @click="selectService(service)"
                                                                        class="w-full flex items-center gap-3 px-4 py-2.5 text-sm hover:bg-[var(--brand-primary)]/5 transition-colors"
                                                                        :class="{ 'bg-[var(--brand-primary)]/10 text-[var(--brand-primary)] font-medium': form.service_id == service.id }"
                                                                    >
                                                                        <svg v-if="form.service_id == service.id" class="w-4 h-4 text-[var(--brand-primary)] flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                                                        </svg>
                                                                        <span>{{ localized(service, 'name') }}</span>
                                                                                                                                            </button>
                                                                </div>
                                                            </template>
                                                        </template>
                                                        <!-- Flat list when searching or category selected -->
                                                        <template v-else>
                                                            <button
                                                                v-for="service in searchedServices"
                                                                :key="service.id"
                                                                type="button"
                                                                @click="selectService(service)"
                                                                class="w-full flex items-center gap-3 px-4 py-2.5 text-sm hover:bg-[var(--brand-primary)]/5 transition-colors"
                                                                :class="{ 'bg-[var(--brand-primary)]/10 text-[var(--brand-primary)] font-medium': form.service_id == service.id }"
                                                            >
                                                                <svg v-if="form.service_id == service.id" class="w-4 h-4 text-[var(--brand-primary)] flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                                                </svg>
                                                                <span>{{ localized(service, 'name') }}</span>
                                                                                                                            </button>
                                                        </template>
                                                        <!-- Empty state -->
                                                        <div v-if="searchedServices.length === 0 && serviceSearch" class="px-4 py-6 text-center text-sm text-gray-400">
                                                            {{ locale === 'ar' ? 'لا توجد نتائج' : 'No results found' }}
                                                        </div>
                                                    </div>
                                                </div>
                                            </Transition>
                                            <p v-if="form.errors.service_id" class="mt-1.5 text-sm text-red-500">
                                                {{ form.errors.service_id }}
                                            </p>
                                        </div>
                                    </div>

                                    <!-- Doctor Searchable Dropdown -->
                                    <div class="relative loc-doctor-dd">
                                        <label class="block text-sm font-semibold text-[#3A3A3A] mb-2">
                                            {{ t('doctor') }}
                                        </label>
                                        <button
                                            type="button"
                                            @click="showDoctorDropdown = !showDoctorDropdown"
                                            class="w-full flex items-center justify-between px-4 py-3 border border-gray-300 rounded-lg bg-white text-start transition-all duration-200 focus:ring-2 focus:ring-[var(--brand-primary)]/30 focus:border-[var(--brand-primary)] outline-none"
                                            :class="{ 'border-red-400': form.errors.doctor_id, 'border-[var(--brand-primary)] ring-2 ring-[var(--brand-primary)]/30': showDoctorDropdown }"
                                        >
                                            <span :class="selectedDoctorName ? 'text-[#3A3A3A]' : 'text-gray-400'">
                                                {{ selectedDoctorName || t('select_doctor') }}
                                            </span>
                                            <svg class="w-4 h-4 text-gray-400 transition-transform flex-shrink-0" :class="{ 'rotate-180': showDoctorDropdown }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                            </svg>
                                        </button>

                                        <Transition
                                            enter-active-class="transition duration-150 ease-out"
                                            enter-from-class="opacity-0 -translate-y-1"
                                            enter-to-class="opacity-100 translate-y-0"
                                            leave-active-class="transition duration-100 ease-in"
                                            leave-from-class="opacity-100 translate-y-0"
                                            leave-to-class="opacity-0 -translate-y-1"
                                        >
                                            <div v-if="showDoctorDropdown" class="absolute top-full start-0 end-0 mt-1 bg-white border border-gray-200 rounded-xl shadow-xl z-50 overflow-hidden">
                                                <!-- Search -->
                                                <div class="p-2.5 border-b border-gray-100">
                                                    <div class="relative">
                                                        <svg class="absolute start-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                                        </svg>
                                                        <input
                                                            v-model="doctorSearch"
                                                            type="text"
                                                            :placeholder="locale === 'ar' ? 'ابحث عن طبيب...' : 'Search doctor...'"
                                                            class="w-full ps-9 pe-3 py-2.5 text-sm border border-gray-200 rounded-lg bg-gray-50 outline-none focus:border-[var(--brand-primary)] focus:ring-1 focus:ring-[var(--brand-primary)]/30"
                                                        />
                                                    </div>
                                                </div>
                                                <!-- List -->
                                                <div class="max-h-60 overflow-y-auto">
                                                    <button
                                                        v-for="doctor in searchedDoctors"
                                                        :key="doctor.id"
                                                        type="button"
                                                        @click="selectDoctor(doctor)"
                                                        class="w-full flex items-center gap-3 px-4 py-3 text-sm hover:bg-[var(--brand-primary)]/5 transition-colors"
                                                        :class="{ 'bg-[var(--brand-primary)]/10 text-[var(--brand-primary)] font-medium': form.doctor_id == doctor.id }"
                                                    >
                                                        <!-- Doctor avatar -->
                                                        <div class="w-9 h-9 rounded-full bg-gray-100 flex-shrink-0 overflow-hidden flex items-center justify-center">
                                                            <img v-if="doctor.photo_url" :src="doctor.photo_url" :alt="localized(doctor, 'name')" class="w-full h-full object-cover" />
                                                            <svg v-else class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
                                                            </svg>
                                                        </div>
                                                        <div>
                                                            <p class="font-medium text-start">{{ localized(doctor, 'name') }}</p>
                                                            <p v-if="doctor.specialization_ar || doctor.specialization_en" class="text-xs text-gray-400 text-start">{{ localized(doctor, 'specialization') }}</p>
                                                        </div>
                                                        <svg v-if="form.doctor_id == doctor.id" class="w-4 h-4 text-[var(--brand-primary)] ms-auto flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                                        </svg>
                                                    </button>
                                                    <!-- Empty state -->
                                                    <div v-if="searchedDoctors.length === 0 && doctorSearch" class="px-4 py-6 text-center text-sm text-gray-400">
                                                        {{ locale === 'ar' ? 'لا توجد نتائج' : 'No results found' }}
                                                    </div>
                                                </div>
                                            </div>
                                        </Transition>
                                        <p v-if="form.errors.doctor_id" class="mt-1.5 text-sm text-red-500">
                                            {{ form.errors.doctor_id }}
                                        </p>
                                    </div>

                                    <!-- Date (Doctor Availability Calendar) -->
                                    <div>
                                        <label class="block text-sm font-semibold text-[#3A3A3A] mb-2">
                                            {{ t('preferred_date') }}
                                        </label>
                                        <DoctorDatePicker
                                            v-model="form.preferred_date"
                                            :doctor-id="form.doctor_id"
                                            :doctor-schedules="doctorSchedules"
                                            :min-date="today"
                                            :accent-color="'var(--brand-primary)'"
                                        />
                                        <p v-if="form.errors.preferred_date" class="mt-1.5 text-sm text-red-500">
                                            {{ form.errors.preferred_date }}
                                        </p>
                                    </div>

                                    <!-- Time Slots (dynamic) -->
                                    <div>
                                        <label class="block text-sm font-semibold text-[#3A3A3A] mb-2">
                                            {{ t('preferred_time') }}
                                        </label>

                                        <!-- Loading state -->
                                        <div v-if="loadingSlots" class="flex items-center gap-2 py-3 text-sm text-gray-500">
                                            <svg class="animate-spin w-4 h-4 text-[var(--brand-primary)]" fill="none" viewBox="0 0 24 24">
                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                            </svg>
                                            {{ t('loading_slots') }}
                                        </div>

                                        <!-- Error/Info message -->
                                        <p v-else-if="slotsError" class="py-3 text-sm text-amber-600">
                                            {{ slotsError }}
                                        </p>

                                        <!-- Prompt to select doctor + date -->
                                        <p v-else-if="!form.doctor_id || !form.preferred_date" class="py-3 text-sm text-gray-400">
                                            {{ t('select_doctor_and_date_first') }}
                                        </p>

                                        <!-- Available time slots as clickable buttons -->
                                        <div v-else-if="availableSlots.length > 0" class="flex flex-wrap gap-2">
                                            <button
                                                v-for="slot in availableSlots"
                                                :key="slot.start"
                                                type="button"
                                                @click="selectTimeSlot(slot)"
                                                class="px-4 py-2.5 rounded-lg text-sm font-medium border transition-all duration-200"
                                                :class="form.preferred_time === slot.start
                                                    ? 'bg-[var(--brand-primary)] text-white border-[var(--brand-primary)] shadow-md shadow-[var(--brand-primary)]/20'
                                                    : 'bg-white text-[#3A3A3A] border-gray-200 hover:border-[var(--brand-primary)] hover:text-[var(--brand-primary)]'"
                                            >
                                                {{ slot.start_12h || formatTime12h(slot.start) }}
                                            </button>
                                        </div>

                                        <!-- No slots available -->
                                        <p v-else class="py-3 text-sm text-gray-400">
                                            {{ t('no_slots_available') }}
                                        </p>

                                        <p v-if="form.errors.preferred_time" class="mt-1.5 text-sm text-red-500">
                                            {{ form.errors.preferred_time }}
                                        </p>
                                    </div>

                                    <!-- Notes -->
                                    <div>
                                        <label for="notes" class="block text-sm font-semibold text-[#3A3A3A] mb-2">
                                            {{ t('notes') }}
                                            <span class="text-gray-400 font-normal">({{ t('optional') }})</span>
                                        </label>
                                        <textarea
                                            id="notes"
                                            v-model="form.notes"
                                            rows="4"
                                            :placeholder="t('notes_placeholder')"
                                            class="w-full px-4 py-3 border border-gray-300 rounded-lg bg-white text-[#3A3A3A] placeholder-gray-400 transition-all duration-200 focus:ring-2 focus:ring-[var(--brand-primary)]/30 focus:border-[var(--brand-primary)] outline-none resize-none form-input-animated"
                                            :class="{ 'border-red-400 focus:ring-red-300 focus:border-red-400': form.errors.notes }"
                                        ></textarea>
                                        <p v-if="form.errors.notes" class="mt-1.5 text-sm text-red-500">
                                            {{ form.errors.notes }}
                                        </p>
                                    </div>

                                    <!-- Promo Code -->
                                    <PromoCodeInput
                                        v-model="form.promo_code"
                                        :booking-type="form.booking_type"
                                        :service-id="form.service_id ? Number(form.service_id) : null"
                                    />

                                    <!-- Privacy Consent -->
                                    <div>
                                        <label class="flex items-start gap-3 cursor-pointer group">
                                            <input
                                                type="checkbox"
                                                v-model="form.privacy_consent"
                                                class="mt-1 w-5 h-5 rounded border-gray-300 text-[var(--brand-primary)] focus:ring-[var(--brand-primary)]/30 transition-colors cursor-pointer"
                                                :class="{ 'border-red-400': form.errors.privacy_consent }"
                                            />
                                            <span class="text-sm text-gray-600 group-hover:text-gray-800 transition-colors">
                                                {{ t('privacy_consent_text') }}
                                                <Link
                                                    :href="localizedRoute('/page/privacy-policy')"
                                                    class="text-[var(--brand-primary)] hover:text-[var(--brand-primary-hover)] underline underline-offset-2 font-medium"
                                                    target="_blank"
                                                >
                                                    {{ t('privacy_policy') }}
                                                </Link>
                                            </span>
                                        </label>
                                        <p v-if="form.errors.privacy_consent" class="mt-1.5 text-sm text-red-500">
                                            {{ form.errors.privacy_consent }}
                                        </p>
                                    </div>

                                    <!-- Submit Button -->
                                    <button
                                        type="submit"
                                        :disabled="form.processing"
                                        class="w-full py-4 bg-[var(--brand-primary)] text-white font-bold text-lg rounded-xl hover:bg-[var(--brand-primary-hover)] focus:ring-4 focus:ring-[var(--brand-primary)]/30 transition-all duration-300 shadow-lg hover:shadow-xl hover:shadow-[var(--brand-primary)]/20 disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-3 btn-shimmer"
                                    >
                                        <svg v-if="form.processing" class="animate-spin w-5 h-5" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                        </svg>
                                        <span v-if="!form.processing">{{ t('submit_booking') }}</span>
                                        <span v-else>{{ t('submitting') }}</span>
                                    </button>
                                </div>
                                </Transition>
                            </form>
                        </div>
                    </div>

                    <!-- Info Sidebar (Right 1/3) -->
                    <div class="space-y-6" v-scroll-reveal="{ type: 'fade-left', delay: 150 }">

                        <!-- Clinic Info Card -->
                        <div class="bg-white rounded-2xl shadow-lg p-6 border border-gray-100 card-hover-lift">
                            <h3 class="text-lg font-bold text-[#3A3A3A] mb-5 flex items-center gap-2">
                                <span class="w-8 h-8 bg-[var(--brand-primary)]/10 rounded-lg flex items-center justify-center">
                                    <svg class="w-4 h-4 text-[var(--brand-primary)]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </span>
                                {{ t('clinic_info') }}
                            </h3>

                            <div class="space-y-4">
                                <!-- Phone Numbers -->
                                <div class="flex items-start gap-3">
                                    <div class="w-10 h-10 bg-[var(--brand-primary)]/10 rounded-xl flex items-center justify-center flex-shrink-0">
                                        <svg class="w-5 h-5 text-[var(--brand-primary)]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-500 mb-1">{{ t('phone') }}</p>
                                        <a :href="`tel:${phone1}`" class="block text-[#3A3A3A] font-medium hover:text-[var(--brand-primary)] transition-colors" dir="ltr">
                                            {{ phone1 }}
                                        </a>
                                        <a :href="`tel:${phone2}`" class="block text-[#3A3A3A] font-medium hover:text-[var(--brand-primary)] transition-colors mt-0.5" dir="ltr">
                                            {{ phone2 }}
                                        </a>
                                    </div>
                                </div>

                                <!-- Email -->
                                <div class="flex items-start gap-3">
                                    <div class="w-10 h-10 bg-[var(--brand-primary)]/10 rounded-xl flex items-center justify-center flex-shrink-0">
                                        <svg class="w-5 h-5 text-[var(--brand-primary)]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-500 mb-1">{{ t('email') }}</p>
                                        <a :href="`mailto:${email}`" class="text-[#3A3A3A] font-medium hover:text-[var(--brand-primary)] transition-colors break-all">
                                            {{ email }}
                                        </a>
                                    </div>
                                </div>

                                <!-- Working Hours -->
                                <div class="flex items-start gap-3">
                                    <div class="w-10 h-10 bg-[var(--brand-primary)]/10 rounded-xl flex items-center justify-center flex-shrink-0">
                                        <svg class="w-5 h-5 text-[var(--brand-primary)]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-500 mb-1">{{ t('working_hours') }}</p>
                                        <p class="text-[#3A3A3A] font-medium">{{ t('working_hours_weekdays') }}</p>
                                        <p class="text-gray-500 text-sm mt-0.5">{{ t('working_hours_friday') }}</p>
                                    </div>
                                </div>

                                <!-- Address -->
                                <div class="flex items-start gap-3">
                                    <div class="w-10 h-10 bg-[var(--brand-primary)]/10 rounded-xl flex items-center justify-center flex-shrink-0">
                                        <svg class="w-5 h-5 text-[var(--brand-primary)]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-500 mb-1">{{ t('address') }}</p>
                                        <p class="text-[#3A3A3A] font-medium text-sm leading-relaxed">
                                            {{ t('clinic_address') }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- WhatsApp CTA Card -->
                        <div class="bg-gradient-to-br from-green-50 to-green-100 rounded-2xl shadow-lg p-6 border border-green-200 card-hover-lift">
                            <div class="text-center">
                                <div class="w-16 h-16 bg-green-500 rounded-2xl flex items-center justify-center mx-auto mb-4 shadow-lg shadow-green-500/30">
                                    <svg class="w-8 h-8 text-white" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z" />
                                    </svg>
                                </div>
                                <h3 class="text-lg font-bold text-gray-800 mb-2">
                                    {{ t('or_contact_whatsapp') }}
                                </h3>
                                <p class="text-sm text-gray-600 mb-4">
                                    {{ t('whatsapp_booking_description') }}
                                </p>
                                <a
                                    :href="whatsappLink"
                                    target="_blank"
                                    rel="noopener"
                                    class="inline-flex items-center gap-2 w-full justify-center px-6 py-3 bg-green-500 text-white font-semibold rounded-xl hover:bg-green-600 transition-all duration-300 shadow-md hover:shadow-lg btn-shimmer"
                                >
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z" />
                                    </svg>
                                    {{ t('chat_on_whatsapp') }}
                                </a>
                            </div>
                        </div>

                        <!-- Quick Note Card -->
                        <div class="bg-[#F5EDE0] rounded-2xl p-6 border border-[var(--brand-primary)]/10 card-hover-lift">
                            <div class="flex items-start gap-3">
                                <div class="w-10 h-10 bg-[var(--brand-primary)]/10 rounded-xl flex items-center justify-center flex-shrink-0">
                                    <svg class="w-5 h-5 text-[var(--brand-primary)]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                                    </svg>
                                </div>
                                <div>
                                    <h4 class="font-semibold text-[#3A3A3A] mb-1">{{ t('good_to_know') }}</h4>
                                    <p class="text-sm text-gray-600 leading-relaxed">
                                        {{ t('booking_note_text') }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

    </FrontendLayout>
</template>
