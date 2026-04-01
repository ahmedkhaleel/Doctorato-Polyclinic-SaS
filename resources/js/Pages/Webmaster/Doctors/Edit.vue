<script setup>
import { ref, computed } from 'vue';
import { Link, useForm, router , usePage } from '@inertiajs/vue3';
import WebmasterLayout from '@/Layouts/WebmasterLayout.vue';
import RichTextEditor from '@/Components/Admin/RichTextEditor.vue';
import SearchableSelect from '@/Components/Admin/SearchableSelect.vue';
import { useCurrency } from '@/Composables/useCurrency.js';

const { formatCurrency, currencyCode } = useCurrency();

const props = defineProps({
    doctor: Object,
    users: Array,
    services: Array,
});

const __page = usePage();
const locale = computed(() => __page.props.locale || 'ar');
const isRtl = computed(() => (__page.props.dir || 'rtl') === 'rtl');


const activeSection = ref('basic');

const sections = [
    { id: 'basic', label: 'Basic Info', icon: 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z' },
    { id: 'clinic', label: 'Clinic Settings', icon: 'M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z' },
    { id: 'schedule', label: 'Schedule', icon: 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z' },
    { id: 'vacations', label: 'Vacations', icon: 'M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z' },
    { id: 'rates', label: 'Service Rates', icon: 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z' },
];

const dayNames = ['Saturday', 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];

// Build initial schedules from doctor.schedules (fill missing days)
function buildSchedules() {
    const existing = props.doctor?.schedules || [];
    return dayNames.map((_, i) => {
        const found = existing.find(s => Number(s.day_of_week) === i);
        if (found) {
            const st = (found.start_time || '09:00').substring(0, 5);
            const et = (found.end_time || '17:00').substring(0, 5);
            return { day_of_week: i, start_time: st, end_time: et, is_active: !!found.is_active };
        }
        return { day_of_week: i, start_time: '09:00', end_time: '17:00', is_active: false };
    });
}

// Build initial vacations
function buildVacations() {
    return (props.doctor?.vacations || []).map(v => ({
        id: v.id,
        start_date: v.start_date ? v.start_date.split('T')[0] : '',
        end_date: v.end_date ? v.end_date.split('T')[0] : '',
        reason: v.reason || '',
    }));
}

// Build initial service rates
function buildServiceRates() {
    return (props.doctor?.service_rates || []).map(r => ({
        service_id: r.service_id,
        commission_percentage: r.commission_percentage,
    }));
}

const form = useForm({
    // Basic Info
    name_ar: props.doctor.name_ar || '',
    name_en: props.doctor.name_en || '',
    photo: null,
    specialization_ar: props.doctor.specialization_ar || '',
    specialization_en: props.doctor.specialization_en || '',
    bio_ar: props.doctor.bio_ar || '',
    bio_en: props.doctor.bio_en || '',
    qualifications_ar: props.doctor.qualifications_ar || '',
    qualifications_en: props.doctor.qualifications_en || '',
    display_order: props.doctor.display_order || 0,
    status: props.doctor.status || 'active',
    // Clinic Settings
    user_id: props.doctor.user_id || '',
    phone: props.doctor.phone || '',
    email: props.doctor.email || '',
    consultation_fee: props.doctor.consultation_fee || '',
    default_commission_percentage: props.doctor.default_commission_percentage || '',
    clinic_notes: props.doctor.clinic_notes || '',
    // Nested
    schedules: buildSchedules(),
    vacations: buildVacations(),
    service_rates: buildServiceRates(),
});

function submit() {
    // Helper: convert is_active booleans to 1/0 integers for safe serialization
    const safeSchedules = (schedules) => schedules.map(s => ({
        ...s,
        is_active: s.is_active ? 1 : 0,
    }));

    if (form.photo instanceof File) {
        // Photo upload — use PUT with forceFormData (Inertia auto-adds _method=PUT)
        form.transform((data) => ({
            ...data,
            schedules: safeSchedules(data.schedules),
        })).put(`/webmaster/doctors/${props.doctor.id}`, {
            forceFormData: true,
            preserveScroll: true,
        });
    } else {
        // No photo — actual PUT request with JSON body (preserves types correctly)
        form.transform((data) => {
            const { photo, ...rest } = data;
            return {
                ...rest,
                schedules: safeSchedules(rest.schedules),
            };
        }).put(`/webmaster/doctors/${props.doctor.id}`, {
            preserveScroll: true,
        });
    }
}

// Vacations management
function addVacation() {
    form.vacations.push({ id: null, start_date: '', end_date: '', reason: '' });
}

function removeVacation(index) {
    form.vacations.splice(index, 1);
}

// Service rates management
function addServiceRate() {
    form.service_rates.push({ service_id: '', commission_percentage: '' });
}

function removeServiceRate(index) {
    form.service_rates.splice(index, 1);
}

// Filter out services already assigned
const availableServices = computed(() => {
    const usedIds = form.service_rates.map(r => Number(r.service_id));
    return (props.services || []).filter(s => !usedIds.includes(s.id));
});

function getServiceName(serviceId) {
    const svc = (props.services || []).find(s => s.id === Number(serviceId));
    return svc ? svc.name_en : '-';
}

const userOptions = computed(() => (props.users || []).map(u => ({ value: u.id, label: u.name + ' (' + u.email + ')' })));

function serviceRateOptions(currentIndex) {
    return (props.services || []).map(svc => ({
        value: svc.id,
        label: svc.name_en,
    })).filter(opt => !form.service_rates.some((r, ri) => ri !== currentIndex && Number(r.service_id) === opt.value));
}
</script>

<template>
    <WebmasterLayout title="Edit Doctor">
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <h1 class="text-2xl font-bold text-gray-800">{{ $t('a_edit_doctor') }}</h1>
                    <Link v-if="doctor.id" :href="`/webmaster/doctors/${doctor.id}`" class="text-sm font-medium hover:underline" style="color: #C4A265;">{{ $t('a_view_profile') }}</Link>
                </div>
                <Link href="/webmaster/doctors" class="text-sm text-gray-500 hover:text-gray-700">{{ $t('a_back_to_doctors') }}</Link>
            </div>

            <form @submit.prevent="submit">
                <!-- Section Navigation -->
                <div class="bg-white rounded-lg shadow-sm mb-6">
                    <nav class="flex border-b border-gray-200 overflow-x-auto">
                        <button
                            v-for="section in sections"
                            :key="section.id"
                            type="button"
                            @click="activeSection = section.id"
                            class="flex items-center px-5 py-3 text-sm font-medium border-b-2 transition whitespace-nowrap"
                            :class="activeSection === section.id
                                ? 'border-current text-[#C4A265]'
                                : 'border-transparent text-gray-500 hover:text-gray-700'"
                            :style="activeSection === section.id ? 'color: #C4A265;' : ''"
                        >
                            <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="section.icon" />
                            </svg>
                            {{ section.label }}
                        </button>
                    </nav>
                </div>

                <!-- ==================== BASIC INFO ==================== -->
                <div v-show="activeSection === 'basic'" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <div class="lg:col-span-2 space-y-6">
                        <div class="bg-white rounded-lg shadow-sm p-6 space-y-5">
                            <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wider">{{ $t('a_names_specialization') }}</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_name_en') }} <span class="text-red-500">*</span></label>
                                    <input v-model="form.name_en" type="text" class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-yellow-200 focus:border-transparent" />
                                    <p v-if="form.errors.name_en" class="mt-1 text-sm text-red-600">{{ form.errors.name_en }}</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_name_ar') }} <span class="text-red-500">*</span></label>
                                    <input v-model="form.name_ar" type="text" dir="rtl" class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-yellow-200 focus:border-transparent" />
                                    <p v-if="form.errors.name_ar" class="mt-1 text-sm text-red-600">{{ form.errors.name_ar }}</p>
                                </div>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Specialization (English) <span class="text-red-500">*</span></label>
                                    <input v-model="form.specialization_en" type="text" class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-yellow-200 focus:border-transparent" />
                                    <p v-if="form.errors.specialization_en" class="mt-1 text-sm text-red-600">{{ form.errors.specialization_en }}</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Specialization (Arabic) <span class="text-red-500">*</span></label>
                                    <input v-model="form.specialization_ar" type="text" dir="rtl" class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-yellow-200 focus:border-transparent" />
                                    <p v-if="form.errors.specialization_ar" class="mt-1 text-sm text-red-600">{{ form.errors.specialization_ar }}</p>
                                </div>
                            </div>
                        </div>

                        <div class="bg-white rounded-lg shadow-sm p-6 space-y-5">
                            <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wider">{{ $t('a_bio_qualifications') }}</h3>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Bio (English)</label>
                                <RichTextEditor v-model="form.bio_en" dir="ltr" placeholder="Write bio..." />
                                <p v-if="form.errors.bio_en" class="mt-1 text-sm text-red-600">{{ form.errors.bio_en }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Bio (Arabic)</label>
                                <RichTextEditor v-model="form.bio_ar" dir="rtl" placeholder="اكتب السيرة الذاتية..." />
                                <p v-if="form.errors.bio_ar" class="mt-1 text-sm text-red-600">{{ form.errors.bio_ar }}</p>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Qualifications (English)</label>
                                    <textarea v-model="form.qualifications_en" rows="4" placeholder="One qualification per line" class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-yellow-200 focus:border-transparent"></textarea>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Qualifications (Arabic)</label>
                                    <textarea v-model="form.qualifications_ar" rows="4" dir="rtl" class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-yellow-200 focus:border-transparent"></textarea>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Sidebar -->
                    <div class="space-y-6">
                        <div class="bg-white rounded-lg shadow-sm p-6 space-y-5">
                            <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wider">{{ $t('a_status_order') }}</h3>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_status') }}</label>
                                <select v-model="form.status" class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-yellow-200 focus:border-transparent">
                                    <option value="active">{{ $t('a_active') }}</option>
                                    <option value="inactive">{{ $t('a_inactive') }}</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_display_order') }}</label>
                                <input v-model="form.display_order" type="number" min="0" class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-yellow-200 focus:border-transparent" />
                            </div>
                        </div>

                        <div class="bg-white rounded-lg shadow-sm p-6 space-y-5">
                            <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wider">{{ $t('a_photo') }}</h3>
                            <div v-if="doctor.photo" class="mb-3">
                                <img :src="doctor.photo.startsWith('http') ? doctor.photo : `/storage/${doctor.photo}`" alt="Doctor photo" class="w-24 h-24 object-cover rounded-lg" />
                            </div>
                            <input
                                type="file"
                                accept="image/*"
                                @input="form.photo = $event.target.files[0]"
                                class="w-full text-sm text-gray-500 ltr:file:mr-4 rtl:file:ml-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-gray-100 file:text-gray-700 hover:file:bg-gray-200"
                            />
                            <p class="text-xs text-gray-400">Leave empty to keep current photo</p>
                            <p v-if="form.errors.photo" class="mt-1 text-sm text-red-600">{{ form.errors.photo }}</p>
                        </div>
                    </div>
                </div>

                <!-- ==================== CLINIC SETTINGS ==================== -->
                <div v-show="activeSection === 'clinic'" class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <div class="bg-white rounded-lg shadow-sm p-6 space-y-5">
                        <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wider">{{ $t('a_contact_account') }}</h3>

                        <!-- Linked User Info (no create account for webmaster) -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Link to User Account</label>
                            <SearchableSelect v-model="form.user_id" :options="userOptions" placeholder="-- No user linked --" searchPlaceholder="Search users..." />
                            <p class="text-xs text-gray-400 mt-1">Link doctor to a system user for login & leave management</p>
                            <p v-if="form.errors.user_id" class="mt-1 text-sm text-red-600">{{ form.errors.user_id }}</p>
                            <div v-if="doctor.user_id" class="mt-2 p-3 bg-green-50 border border-green-200 rounded-lg">
                                <div class="flex items-center gap-2">
                                    <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                    <span class="text-sm font-medium text-green-700">User account linked — Doctor can login at <code class="bg-green-100 px-1 rounded">/doctor/login</code></span>
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Phone</label>
                                <input v-model="form.phone" type="text" placeholder="01xxxxxxxxx" class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-yellow-200 focus:border-transparent" />
                                <p v-if="form.errors.phone" class="mt-1 text-sm text-red-600">{{ form.errors.phone }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                                <input v-model="form.email" type="email" placeholder="doctor@clinic.com" class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-yellow-200 focus:border-transparent" />
                                <p v-if="form.errors.email" class="mt-1 text-sm text-red-600">{{ form.errors.email }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-6">
                        <div class="bg-white rounded-lg shadow-sm p-6 space-y-5">
                            <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wider">{{ $t('a_financial_settings') }}</h3>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Consultation Fee ({{ currencyCode }})</label>
                                <input v-model="form.consultation_fee" type="number" step="0.01" min="0" placeholder="0.00" class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-yellow-200 focus:border-transparent" />
                                <p v-if="form.errors.consultation_fee" class="mt-1 text-sm text-red-600">{{ form.errors.consultation_fee }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Default Commission Rate (%)</label>
                                <input v-model="form.default_commission_percentage" type="number" step="0.01" min="0" max="100" placeholder="0.00" class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-yellow-200 focus:border-transparent" />
                                <p class="text-xs text-gray-400 mt-1">Fallback rate when no per-service rate is set</p>
                                <p v-if="form.errors.default_commission_percentage" class="mt-1 text-sm text-red-600">{{ form.errors.default_commission_percentage }}</p>
                            </div>
                        </div>

                        <div class="bg-white rounded-lg shadow-sm p-6 space-y-5">
                            <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wider">{{ $t('a_clinic_notes') }}</h3>
                            <textarea v-model="form.clinic_notes" rows="5" placeholder="Internal notes about this doctor..." class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-yellow-200 focus:border-transparent"></textarea>
                            <p v-if="form.errors.clinic_notes" class="mt-1 text-sm text-red-600">{{ form.errors.clinic_notes }}</p>
                        </div>
                    </div>
                </div>

                <!-- ==================== SCHEDULE ==================== -->
                <div v-show="activeSection === 'schedule'">
                    <div class="bg-white rounded-lg shadow-sm p-6">
                        <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wider mb-4">{{ $t('a_weekly_schedule') }}</h3>
                        <p class="text-xs text-gray-400 mb-4">Toggle days on/off and set working hours for each day.</p>
                        <div class="space-y-3">
                            <div
                                v-for="(schedule, i) in form.schedules"
                                :key="i"
                                class="flex items-center gap-4 p-3 rounded-lg border transition"
                                :class="schedule.is_active ? 'border-green-200 bg-green-50' : 'border-gray-200 bg-gray-50'"
                            >
                                <label class="flex items-center cursor-pointer min-w-[140px]">
                                    <input
                                        type="checkbox"
                                        v-model="schedule.is_active"
                                        class="w-4 h-4 rounded border-gray-300 text-green-600 focus:ring-green-200 mr-3"
                                    />
                                    <span class="text-sm font-medium" :class="schedule.is_active ? 'text-gray-900' : 'text-gray-400'">
                                        {{ dayNames[i] }}
                                    </span>
                                </label>
                                <div class="flex items-center gap-2" :class="!schedule.is_active && 'opacity-40 pointer-events-none'">
                                    <input
                                        v-model="schedule.start_time"
                                        type="time"
                                        class="px-3 py-1.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-yellow-200 focus:border-transparent"
                                    />
                                    <span class="text-gray-400">to</span>
                                    <input
                                        v-model="schedule.end_time"
                                        type="time"
                                        class="px-3 py-1.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-yellow-200 focus:border-transparent"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ==================== VACATIONS ==================== -->
                <div v-show="activeSection === 'vacations'">
                    <div class="bg-white rounded-lg shadow-sm p-6">
                        <div class="flex items-center justify-between mb-4">
                            <div>
                                <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wider">{{ $t('a_vacations_time_off') }}</h3>
                                <p class="text-xs text-gray-400 mt-1">Periods when the doctor is unavailable.</p>
                            </div>
                            <button
                                type="button"
                                @click="addVacation"
                                class="inline-flex items-center px-3 py-1.5 rounded-lg text-white text-xs font-medium transition"
                                style="background-color: #C4A265;"
                            >
                                <svg class="w-3.5 h-3.5 ltr:mr-1 rtl:ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                </svg>
                                Add Vacation
                            </button>
                        </div>

                        <div v-if="form.vacations.length > 0" class="space-y-3">
                            <div
                                v-for="(vacation, i) in form.vacations"
                                :key="i"
                                class="flex items-start gap-4 p-4 rounded-lg border border-gray-200 bg-gray-50"
                            >
                                <div class="flex-1 grid grid-cols-1 sm:grid-cols-3 gap-4">
                                    <div>
                                        <label class="block text-xs font-medium text-gray-500 mb-1">Start Date</label>
                                        <input v-model="vacation.start_date" type="date" class="w-full px-3 py-1.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-yellow-200 focus:border-transparent" />
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-500 mb-1">End Date</label>
                                        <input v-model="vacation.end_date" type="date" class="w-full px-3 py-1.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-yellow-200 focus:border-transparent" />
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-500 mb-1">Reason</label>
                                        <input v-model="vacation.reason" type="text" placeholder="Optional" class="w-full px-3 py-1.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-yellow-200 focus:border-transparent" />
                                    </div>
                                </div>
                                <button
                                    type="button"
                                    @click="removeVacation(i)"
                                    class="mt-5 p-1.5 text-red-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition"
                                    title="Remove"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                        <p v-else class="text-sm text-gray-400 text-center py-8">No vacations configured. Click "Add Vacation" to create one.</p>
                    </div>
                </div>

                <!-- ==================== SERVICE RATES ==================== -->
                <div v-show="activeSection === 'rates'">
                    <div class="bg-white rounded-lg shadow-sm p-6">
                        <div class="flex items-center justify-between mb-4">
                            <div>
                                <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wider">{{ $t('a_per_service_commission_rates') }}</h3>
                                <p class="text-xs text-gray-400 mt-1">Override the default commission rate for specific services. If not set, the default rate ({{ form.default_commission_percentage || 0 }}%) applies.</p>
                            </div>
                            <button
                                type="button"
                                @click="addServiceRate"
                                :disabled="availableServices.length === 0"
                                class="inline-flex items-center px-3 py-1.5 rounded-lg text-white text-xs font-medium transition disabled:opacity-50"
                                style="background-color: #C4A265;"
                            >
                                <svg class="w-3.5 h-3.5 ltr:mr-1 rtl:ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                </svg>
                                Add Rate
                            </button>
                        </div>

                        <div v-if="form.service_rates.length > 0" class="space-y-3">
                            <div
                                v-for="(rate, i) in form.service_rates"
                                :key="i"
                                class="flex items-center gap-4 p-4 rounded-lg border border-gray-200 bg-gray-50"
                            >
                                <div class="flex-1 grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-xs font-medium text-gray-500 mb-1">Service</label>
                                        <SearchableSelect v-model="rate.service_id" :options="serviceRateOptions(i)" placeholder="-- Select Service --" searchPlaceholder="Search services..." />
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-500 mb-1">Commission Rate (%)</label>
                                        <input v-model="rate.commission_percentage" type="number" step="0.01" min="0" max="100" placeholder="0.00" class="w-full px-3 py-1.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-yellow-200 focus:border-transparent" />
                                    </div>
                                </div>
                                <button
                                    type="button"
                                    @click="removeServiceRate(i)"
                                    class="mt-5 p-1.5 text-red-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition"
                                    title="Remove"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                        <p v-else class="text-sm text-gray-400 text-center py-8">No custom service rates. The default commission rate will apply to all services.</p>
                    </div>
                </div>

                <!-- ==================== SAVE BUTTON (always visible) ==================== -->
                <div class="mt-6 flex items-center justify-between bg-white rounded-lg shadow-sm p-4">
                    <p v-if="form.recentlySuccessful" class="text-sm text-green-600 font-medium">{{ $t('a_saved_successfully') }}</p>
                    <p v-else-if="Object.keys(form.errors).length > 0" class="text-sm text-red-600 font-medium">
                        {{ $t('a_please_fix') }} {{ Object.keys(form.errors).length }} {{ $t('a_errors_before_saving') }}
                    </p>
                    <span v-else></span>
                    <div class="flex ltr:space-x-3 rtl:space-x-reverse rtl:space-x-3">
                        <Link href="/webmaster/doctors" class="px-4 py-2.5 rounded-lg bg-gray-200 text-gray-700 text-sm font-medium hover:bg-gray-300 transition">{{ $t('a_cancel') }}</Link>
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="px-6 py-2.5 rounded-lg text-white font-medium text-sm transition disabled:opacity-50"
                            style="background-color: #C4A265;"
                        >
                            {{ form.processing ? $t('a_saving') : $t('a_save_all_changes') }}
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </WebmasterLayout>
</template>
